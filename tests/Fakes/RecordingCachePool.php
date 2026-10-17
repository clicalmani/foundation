<?php

declare(strict_types=1);

namespace Clicalmani\Core\Tests\Fakes;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Pool PSR-6 en mémoire, déterministe : il journalise les appels reçus et ceux
 * faits aux items qu'il distribue, et permet de forcer l'expiration d'une
 * entrée sans attendre.
 */
final class RecordingCachePool implements CacheItemPoolInterface
{
    /** @var array<string, array{value: mixed, expiry: ?int}> */
    private array $store = [];

    /** @var array<string, list<array{0: string, 1: mixed}>> */
    private array $itemCalls = [];

    /** @var array<string, RecordingCacheItem> */
    private array $deferred = [];

    /** @var list<array{0: string, 1: mixed}> */
    public array $calls = [];

    public function getItem(string $key): CacheItemInterface
    {
        $this->calls[] = ['getItem', $key];

        $item = new RecordingCacheItem($key, $this);

        if (!array_key_exists($key, $this->store)) {
            return $item;
        }

        $entry = $this->store[$key];

        if ($entry['expiry'] !== null && $entry['expiry'] <= time()) {
            unset($this->store[$key]);

            return $item;
        }

        return $item->hydrate($entry['value'], true, $entry['expiry']);
    }

    public function getItems(array $keys = []): iterable
    {
        $items = [];

        foreach ($keys as $key) {
            $items[$key] = $this->getItem($key);
        }

        return $items;
    }

    public function hasItem(string $key): bool
    {
        $this->calls[] = ['hasItem', $key];

        return $this->getItem($key)->isHit();
    }

    public function clear(): bool
    {
        $this->calls[] = ['clear', null];

        $this->store    = [];
        $this->deferred = [];

        return true;
    }

    public function deleteItem(string $key): bool
    {
        $this->calls[] = ['deleteItem', $key];

        unset($this->store[$key], $this->deferred[$key]);

        return true;
    }

    public function deleteItems(array $keys): bool
    {
        $this->calls[] = ['deleteItems', $keys];

        foreach ($keys as $key) {
            $this->deleteItem($key);
        }

        return true;
    }

    public function save(CacheItemInterface $item): bool
    {
        $this->calls[] = ['save', $item->getKey()];

        return $this->persist($item);
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        $this->calls[] = ['saveDeferred', $item->getKey()];

        if (!$item instanceof RecordingCacheItem) {
            return false;
        }

        $this->deferred[$item->getKey()] = $item;

        return true;
    }

    public function commit(): bool
    {
        $this->calls[] = ['commit', null];

        foreach ($this->deferred as $item) {
            $this->persist($item);
        }

        $this->deferred = [];

        return true;
    }

    /* -----------------------------------------------------------------
     |  Prise directe pour les tests
     | -----------------------------------------------------------------
     */

    public function seed(string $key, mixed $value, ?int $expiry = null): void
    {
        $this->store[$key] = ['value' => $value, 'expiry' => $expiry];
    }

    /** @return array<string, array{value: mixed, expiry: ?int}> */
    public function entries(): array
    {
        return $this->store;
    }

    /**
     * Appels faits par CacheStore sur les items distribués pour cette clé.
     *
     * @return list<array{0: string, 1: mixed}>
     */
    public function itemCalls(string $key): array
    {
        return $this->itemCalls[$key] ?? [];
    }

    /**
     * @param array{0: string, 1: mixed} $call
     */
    public function recordItemCall(string $key, array $call): void
    {
        $this->itemCalls[$key][] = $call;
    }

    private function persist(CacheItemInterface $item): bool
    {
        if (!$item instanceof RecordingCacheItem) {
            return false;
        }

        $this->store[$item->getKey()] = [
            'value'  => $item->get(),
            'expiry' => $item->getExpiry(),
        ];

        return true;
    }
}
