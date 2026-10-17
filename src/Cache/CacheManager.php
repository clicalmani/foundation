<?php

namespace Clicalmani\Core\Cache;

use Clicalmani\Core\Acme\Container;

class CacheManager
{
    /** @var array<string, CacheStore> */
    protected array $stores = [];

    protected string $default;

    protected CacheAdapterContext $context;

    public function __construct()
    {
        $this->default = config('cache.default', 'file');
        $this->context = new CacheAdapterContext($this);

        // Enregistrement des adaptateurs natifs
        NativeCacheAdapters::register($this->context);
    }

    /**
     * Expose le contexte pour permettre l'extension depuis l'extérieur.
     */
    public function extend(string $driver, callable $factory): static
    {
        $this->context->extend($driver, $factory);
        return $this;
    }

    public function store(?string $name = null): CacheStore
    {
        $name ??= $this->default;

        return $this->stores[$name] ??= $this->resolve($name);
    }

    public function __call(string $method, array $arguments): mixed
    {
        $stores = config('cache.stores', []);

        if (array_key_exists($method, $stores)) {
            return $this->store($method);
        }

        return $this->store()->{$method}(...$arguments);
    }

    protected function resolve(string $name): CacheStore
    {
        $config = config("cache.stores.{$name}");

        if ($config === null) {
            throw new \InvalidArgumentException(
                "Cache store [{$name}] is not defined in config/cache.php."
            );
        }

        $driver = $config['driver'] ?? $name;
        $prefix = config('cache.prefix', 'tonka_cache_');

        // Résolution via le contexte (drivers natifs + custom)
        $adapter = $this->context->resolve($driver, $config, $prefix);

        return new CacheStore($adapter, $name);
    }

    public function purge(): void
    {
        foreach ($this->stores as $store) {
            $store->clear();
        }
    }

    public function getDefaultStore(): string
    {
        return $this->default;
    }

    public function setDefaultStore(string $name): static
    {
        $this->default = $name;
        return $this;
    }

    public function getContext(): CacheAdapterContext
    {
        return $this->context;
    }
}