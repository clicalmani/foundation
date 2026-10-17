<?php

namespace Clicalmani\Core\Cache\Adapters;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Clicalmani\Core\Support\Facades\DB;

class DatabaseAdapter implements CacheItemPoolInterface
{
    /**
     * Items différés (à sauvegarder en fin de requête via commit()).
     *
     * @var array<string, DatabaseCacheItem>
     */
    protected array $deferred = [];

    /**
     * Probabilité (en %) de déclencher une purge des entrées expirées
     * à chaque lecture. 2 = ~2% des get().
     */
    protected int $pruneProbability = 2;

    public function __construct(
        protected string $table = 'cache',
        protected int $defaultLifetime = 0,
        protected bool $lock = true
    ) {}

    /* -----------------------------------------------------------------
     |  PSR-6 : lecture
     | -----------------------------------------------------------------
     */

    public function getItem(string $key): CacheItemInterface
    {
        if (isset($this->deferred[$key])) {
            return $this->deferred[$key];
        }

        $item = new DatabaseCacheItem($key);

        $row = DB::table($this->table)
            ->where('`key` = ?', [$key])
            ->first();

        if ($row === null) {
            $this->maybePrune();
            return $item->markMiss();
        }

        // Entrée expirée → on la supprime et on retourne un miss
        if ($row['expiration'] > 0 && (int) $row['expiration'] < time()) {
            $this->deleteItem($key);
            $this->maybePrune();
            return $item->markMiss();
        }

        $value = json_decode($row['value'], true);

        // Petite magie : si le JSON échoue, on retente en base64+serialize
        if (json_last_error() !== JSON_ERROR_NONE) {
            $value = @unserialize(base64_decode($row['value']));
        }

        $this->maybePrune();

        return $item->markHit($value, (int) $row['expiration'] ?: null);
    }

    /**
     * @param  array<int, string> $keys
     * @return iterable<string, CacheItemInterface>
     */
    public function getItems(array $keys = []): iterable
    {
        if (empty($keys)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $rows = DB::table($this->table)
            ->where("`key` IN ({$placeholders})", $keys)
            ->get();

        $found = [];
        foreach ($rows as $row) {
            $found[$row['key']] = $row;
        }

        $items = [];
        $now   = time();

        foreach ($keys as $key) {
            $item = new DatabaseCacheItem($key);

            if (!isset($found[$key])) {
                $items[$key] = $item->markMiss();
                continue;
            }

            $row = $found[$key];

            if ($row['expiration'] > 0 && (int) $row['expiration'] < $now) {
                $this->deleteItem($key);
                $items[$key] = $item->markMiss();
                continue;
            }

            $value = json_decode($row['value'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $value = @unserialize(base64_decode($row['value']));
            }

            $items[$key] = $item->markHit($value, (int) $row['expiration'] ?: null);
        }

        return $items;
    }

    public function hasItem(string $key): bool
    {
        if (isset($this->deferred[$key])) {
            return $this->deferred[$key]->isHit();
        }

        $row = DB::table($this->table)
            ->where('`key` = ?', [$key])
            ->first();

        if ($row === null) {
            return false;
        }

        if ($row['expiration'] > 0 && (int) $row['expiration'] < time()) {
            $this->deleteItem($key);
            return false;
        }

        return true;
    }

    /* -----------------------------------------------------------------
     |  PSR-6 : écriture
     | -----------------------------------------------------------------
     */

    public function save(CacheItemInterface $item): bool
    {
        if (!$item instanceof DatabaseCacheItem) {
            throw new \InvalidArgumentException(
                'DatabaseAdapter only accepts DatabaseCacheItem instances.'
            );
        }

        $key        = $item->getKey();
        $value      = $item->get();
        $expiration = $item->getExpiration() ?? $this->defaultExpiration();

        $payload = json_encode($value);

        if ($payload === false) {
            // Fallback : sérialisation binaire encodée en base64
            $payload = base64_encode(serialize($value));
        }

        $now = time();

        // UPSERT : on tente UPDATE, si aucune ligne affectée → INSERT
        $updated = DB::table($this->table)
            ->where('`key` = ?', [$key])
            ->update([
                'value'      => $payload,
                'expiration' => $expiration,
            ]);

        if (!$updated) {
            // Course possible : un autre process a inséré entre-temps.
            // On retente un UPDATE une fois avant d'INSERT.
            $exists = $this->hasItem($key);

            if ($exists) {
                DB::table($this->table)
                    ->where('`key` = ?', [$key])
                    ->update([
                        'value'      => $payload,
                        'expiration' => $expiration,
                    ]);
            } else {
                DB::table($this->table)->insert([
                    'key'        => $key,
                    'value'      => $payload,
                    'expiration' => $expiration,
                ]);
            }
        }

        unset($this->deferred[$key]);

        return true;
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        if (!$item instanceof DatabaseCacheItem) {
            return false;
        }

        $this->deferred[$item->getKey()] = $item;

        return true;
    }

    public function commit(): bool
    {
        if (empty($this->deferred)) {
            return true;
        }

        // Transaction isolée pour garantir l'atomicité du flush.
        $ok = true;

        DB::transaction(function () use (&$ok) {
            foreach ($this->deferred as $item) {
                if (!$this->save($item)) {
                    $ok = false;
                }
            }
        });

        $this->deferred = [];

        return $ok;
    }

    /* -----------------------------------------------------------------
     |  PSR-6 : suppression
     | -----------------------------------------------------------------
     */

    public function deleteItem(string $key): bool
    {
        unset($this->deferred[$key]);

        DB::table($this->table)
            ->where('`key` = ?', [$key])
            ->delete();

        return true;
    }

    public function deleteItems(array $keys): bool
    {
        if (empty($keys)) {
            return true;
        }

        foreach ($keys as $key) {
            unset($this->deferred[$key]);
        }

        $placeholders = implode(',', array_fill(0, count($keys), '?'));

        DB::table($this->table)
            ->where("`key` IN ({$placeholders})", $keys)
            ->delete();

        return true;
    }

    public function clear(): bool
    {
        $this->deferred = [];

        DB::table($this->table)->truncate();

        return true;
    }

    /* -----------------------------------------------------------------
     |  Extensions propres à l'adapter DB
     | -----------------------------------------------------------------
     */

    /**
     * Incrémentation atomique via UPDATE ... SET value = value + step.
     * Retourne la nouvelle valeur, ou false en cas d'échec.
     */
    public function increment(string $key, int $step = 1): int|false
    {
        return DB::transaction(function () use ($key, $step) {
            // Lecture verrouillée
            $row = DB::table($this->table)
                ->where('`key` = ?', [$key])
                ->lockForUpdate()
                ->first();

            if ($row === null) {
                $this->save(
                    (new DatabaseCacheItem($key))->set($step)
                );
                return $step;
            }

            $current = (int) json_decode($row['value'], true);
            $new     = $current + $step;

            DB::table($this->table)
                ->where('`key` = ?', [$key])
                ->update(['value' => json_encode($new)]);

            return $new;
        });
    }

    /**
     * Supprime toutes les entrées expirées. À appeler via une commande
     * CLI (`cache:prune`) ou automatiquement via maybePrune().
     */
    public function prune(): int
    {
        return DB::table($this->table)
            ->where('expiration > 0 AND expiration < ?', [time()])
            ->delete();
    }

    /**
     * Déclenche prune() avec une probabilité de $pruneProbability %.
     */
    protected function maybePrune(): void
    {
        if (random_int(1, 100) <= $this->pruneProbability) {
            $this->prune();
        }
    }

    /**
     * Calcule le timestamp d'expiration par défaut.
     */
    protected function defaultExpiration(): int
    {
        return $this->defaultLifetime > 0
            ? time() + $this->defaultLifetime
            : 0;
    }

    /* -----------------------------------------------------------------
     |  Configuration à la volée
     | -----------------------------------------------------------------
     */

    public function setPruneProbability(int $percent): static
    {
        $this->pruneProbability = max(0, min(100, $percent));
        return $this;
    }
}