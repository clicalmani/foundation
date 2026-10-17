<?php

namespace Clicalmani\Core\Cache;

use Symfony\Component\Cache\Adapter;

class NativeCacheAdapters
{
    /**
     * Enregistre tous les adaptateurs natifs dans le contexte.
     */
    public static function register(CacheAdapterContext $context): void
    {
        $context->extendMany([

            CacheDriver::Array->value => fn(array $config) =>
                new Adapter\ArrayAdapter(),

            CacheDriver::Filesystem->value => fn(array $config, string $prefix) =>
                new Adapter\FilesystemAdapter(
                    $prefix,
                    0,
                    $config['path'] ?? storage_path('framework/cache/data')
                ),

            CacheDriver::Redis->value => fn(array $config, string $prefix) =>
                new Adapter\RedisAdapter(
                    Adapter\RedisAdapter::createConnection(
                        $config['dsn'] ?? 'redis://127.0.0.1:6379'
                    ),
                    $prefix
                ),

            CacheDriver::Memcached->value => fn(array $config, string $prefix) =>
                new Adapter\MemcachedAdapter(
                    Adapter\MemcachedAdapter::createConnection(
                        $config['dsn'] ?? 'memcached://127.0.0.1:11211'
                    ),
                    $prefix
                ),

            CacheDriver::Database->value => fn(array $config) =>
                new Adapters\DatabaseAdapter(
                    $config['table'] ?? 'cache',
                    $config['lifetime'] ?? 0,
                    $config['lock'] ?? true
                ),

            CacheDriver::Chain->value => function (array $config, string $prefix, CacheManager $manager) use ($context) {
                $adapters = [];
                foreach ($config['stores'] ?? [] as $subStore) {
                    $adapters[] = $manager->store($subStore)->getAdapter();
                }
                return new Adapter\ChainAdapter(
                    $adapters,
                    $config['default_lifetime'] ?? 0
                );
            },
        ]);

        // Alias : 'file' route vers 'filesystem'
        $context->extend(
            'file',
            fn(array $config, string $prefix, CacheManager $manager) =>
                $context->resolve(CacheDriver::Filesystem->value, $config, $prefix)
        );
    }
}