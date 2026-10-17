<?php

namespace Clicalmani\Core\Cache;

class CacheAdapterContext
{
    /**
     * Registry des factories : nom du driver => callable.
     *
     * @var array<string, callable(array $config, string $prefix, CacheManager $manager): \Psr\Cache\CacheItemPoolInterface>
     */
    protected array $factories = [];

    public function __construct(protected CacheManager $manager) {}

    /**
     * Enregistre une factory pour un driver.
     *
     * @param string   $driver    Nom du driver (ex: 'redis', 'dynamodb', 'my_custom')
     * @param callable $factory   fn(array $config, string $prefix): CacheItemPoolInterface
     */
    public function extend(string $driver, callable $factory): static
    {
        $this->factories[$driver] = $factory;
        return $this;
    }

    /**
     * Enregistre plusieurs factories d'un coup.
     *
     * @param array<string, callable> $factories
     */
    public function extendMany(array $factories): static
    {
        foreach ($factories as $driver => $factory) {
            $this->extend($driver, $factory);
        }
        return $this;
    }

    /**
     * Indique si un driver est enregistré.
     */
    public function has(string $driver): bool
    {
        return isset($this->factories[$driver]);
    }

    /**
     * Résout un driver en adaptateur PSR-6.
     */
    public function resolve(string $driver, array $config, string $prefix): \Psr\Cache\CacheItemPoolInterface
    {
        if (!isset($this->factories[$driver])) {
            throw new \RuntimeException(
                "Unsupported cache driver [{$driver}]. " .
                "Register it via CacheAdapterContext::extend()."
            );
        }

        return ($this->factories[$driver])($config, $prefix, $this->manager);
    }

    /**
     * Liste les drivers enregistrés.
     *
     * @return array<int, string>
     */
    public function drivers(): array
    {
        return array_keys($this->factories);
    }
}