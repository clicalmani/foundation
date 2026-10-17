<?php

namespace Clicalmani\Core\Cache;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CacheStore
{
    public function __construct(
        protected CacheItemPoolInterface $pool,
        protected string $name
    ) {}

    /* -----------------------------------------------------------------
     |  Stockage
     | -----------------------------------------------------------------
     */

    /**
     * Stocke une valeur dans le cache.
     *
     * @param  string       $key
     * @param  mixed        $value
     * @param  int|null     $ttl     Durée de vie en secondes (null = permanent)
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $item = $this->pool->getItem($this->normalizeKey($key));
        $item->set($value);

        if ($ttl !== null && $ttl > 0) {
            $item->expiresAfter($ttl);
        }

        return $this->pool->save($item);
    }

    /**
     * Alias de set() pour coller à la sémantique Laravel.
     */
    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        return $this->set($key, $value, $ttl);
    }

    /**
     * Stocke uniquement si la clé n'existe pas encore.
     */
    public function add(string $key, mixed $value, ?int $ttl = null): bool
    {
        if ($this->has($key)) {
            return false;
        }

        return $this->set($key, $value, $ttl);
    }

    /**
     * Stocke uniquement si la clé existe déjà.
     */
    public function replace(string $key, mixed $value, ?int $ttl = null): bool
    {
        if (!$this->has($key)) {
            return false;
        }

        return $this->set($key, $value, $ttl);
    }

    /**
     * Stocke définitivement (sans TTL).
     */
    public function forever(string $key, mixed $value): bool
    {
        return $this->set($key, $value, null);
    }

    /* -----------------------------------------------------------------
     |  Récupération
     | -----------------------------------------------------------------
     */

    /**
     * Récupère une valeur depuis le cache.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $item = $this->pool->getItem($this->normalizeKey($key));

        return $item->isHit() ? $item->get() : $default;
    }

    /**
     * Récupère une valeur ou exécute le callback pour la calculer.
     *
     * @param  callable $callback  function(): mixed
     */
    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $item = $this->pool->getItem($this->normalizeKey($key));

        if ($item->isHit()) {
            return $item->get();
        }

        $value = $callback();

        $item->set($value);
        $item->expiresAfter($ttl);
        $this->pool->save($item);

        return $value;
    }

    /**
     * Variante permanente de remember().
     */
    public function rememberForever(string $key, callable $callback): mixed
    {
        $item = $this->pool->getItem($this->normalizeKey($key));

        if ($item->isHit()) {
            return $item->get();
        }

        $value = $callback();

        $item->set($value);
        $this->pool->save($item);

        return $value;
    }

    /**
     * Récupère puis supprime la clé (pop atomique).
     */
    public function pull(string $key, mixed $default = null): mixed
    {
        $value = $this->get($key, $default);
        $this->delete($key);

        return $value;
    }

    /* -----------------------------------------------------------------
     |  Vérification & Suppression
     | -----------------------------------------------------------------
     */

    public function has(string $key): bool
    {
        return $this->pool->hasItem($this->normalizeKey($key));
    }

    public function missing(string $key): bool
    {
        return !$this->has($key);
    }

    public function delete(string $key): bool
    {
        return $this->pool->deleteItem($this->normalizeKey($key));
    }

    /**
     * Supprime plusieurs clés d'un coup.
     *
     * @param  array<int, string> $keys
     */
    public function deleteMany(array $keys): bool
    {
        $normalized = array_map(fn($k) => $this->normalizeKey($k), $keys);
        return $this->pool->deleteItems($normalized);
    }

    /* -----------------------------------------------------------------
     |  Compteurs
     | -----------------------------------------------------------------
     */

    public function increment(string $key, int $step = 1): int|false
    {
        $item = $this->pool->getItem($this->normalizeKey($key));

        $current = $item->isHit() ? (int) $item->get() : 0;
        $new     = $current + $step;

        $item->set($new);
        $this->pool->save($item);

        return $new;
    }

    public function decrement(string $key, int $step = 1): int|false
    {
        return $this->increment($key, -$step);
    }

    /* -----------------------------------------------------------------
     |  Vidage
     | -----------------------------------------------------------------
     */

    public function clear(): bool
    {
        return $this->pool->clear();
    }

    /* -----------------------------------------------------------------
     |  Accès bas niveau
     | -----------------------------------------------------------------
     */

    /**
     * Expose l'adaptateur PSR-6 sous-jacent pour les cas avancés.
     */
    public function getAdapter(): CacheItemPoolInterface
    {
        return $this->pool;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /* -----------------------------------------------------------------
     |  Helpers internes
     | -----------------------------------------------------------------
     */

    /**
     * PSR-6 interdit certains caractères dans les clés
     * (`{}()/\@:` et espaces). On normalise à la volée.
     */
    protected function normalizeKey(string $key): string
    {
        return preg_replace('/[{}()\/\\\\@:]/', '_', $key);
    }
}