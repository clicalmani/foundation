<?php

declare(strict_types=1);

namespace Clicalmani\Core\Tests\Cache;

use Clicalmani\Core\Cache\Adapters\DatabaseAdapter;
use Clicalmani\Core\Cache\CacheManager;
use Clicalmani\Core\Tests\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\ChainAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

final class NativeCacheAdaptersTest extends TestCase
{
    private function manager(array $stores, string $default = 'array', string $prefix = 'tonka_tests_'): CacheManager
    {
        $this->setConfig('cache', [
            'default' => $default,
            'prefix'  => $prefix,
            'stores'  => $stores,
        ]);

        return new CacheManager;
    }

    private function fileCount(string $dir): int
    {
        if (!is_dir($dir)) {
            return 0;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        $count = 0;

        foreach ($files as $file) {
            if ($file->isFile()) {
                ++$count;
            }
        }

        return $count;
    }

    /* -----------------------------------------------------------------
     |  array
     | -----------------------------------------------------------------
     */

    public function test_the_array_driver_builds_a_symfony_array_adapter(): void
    {
        $store = $this->manager(['array' => ['driver' => 'array']])->store('array');

        $this->assertInstanceOf(ArrayAdapter::class, $store->getAdapter());

        $store->set('k', 'v');

        $this->assertSame('v', $store->get('k'));
    }

    /**
     * La factory « array » n'utilise ni $config ni $prefix : les clés restent
     * brutes, contrairement aux drivers persistants.
     */
    public function test_the_array_driver_ignores_the_configured_prefix(): void
    {
        $store = $this->manager(
            ['array' => ['driver' => 'array', 'path' => $this->tempDir()]],
            prefix: 'PFX_'
        )->store('array');

        $store->set('k', 'v');

        $this->assertSame(['k'], array_keys($store->getAdapter()->getValues()));
    }

    /* -----------------------------------------------------------------
     |  filesystem / file
     | -----------------------------------------------------------------
     */

    public function test_the_filesystem_driver_writes_into_the_configured_path(): void
    {
        $path = $this->tempDir();

        $store = $this->manager(['fs' => ['driver' => 'filesystem', 'path' => $path]], 'fs')->store('fs');

        $this->assertInstanceOf(FilesystemAdapter::class, $store->getAdapter());
        $this->assertSame(0, $this->fileCount($path));

        $store->set('k', 'v');

        $this->assertGreaterThan(0, $this->fileCount($path));
        $this->assertSame('v', $store->get('k'));

        $store->clear();

        $this->assertSame(0, $this->fileCount($path));
    }

    /**
     * Sans clé « path », l'adapter retombe sur storage_path('framework/cache/data').
     */
    public function test_the_filesystem_driver_falls_back_to_the_storage_path(): void
    {
        $path = storage_path('framework/cache/data');

        $store = $this->manager(['auto' => ['driver' => 'filesystem']], 'auto')->store('auto');

        $store->set('fallback_probe', 'v');

        $this->assertGreaterThan(0, $this->fileCount($path));
        $this->assertSame('v', $store->get('fallback_probe'));

        $store->clear();
    }

    /**
     * « file » est un alias : la factory ré-invoque resolve('filesystem'),
     * donc la configuration est identique.
     */
    public function test_the_file_alias_produces_the_same_adapter(): void
    {
        $path = $this->tempDir();

        $store = $this->manager(['f' => ['driver' => 'file', 'path' => $path]], 'f')->store('f');

        $this->assertInstanceOf(FilesystemAdapter::class, $store->getAdapter());

        $store->set('k', 'v');

        $this->assertGreaterThan(0, $this->fileCount($path));
        $this->assertSame('v', $store->get('k'));
    }

    /* -----------------------------------------------------------------
     |  chain
     | -----------------------------------------------------------------
     */

    public function test_the_chain_driver_reuses_the_sub_store_adapters(): void
    {
        $path = $this->tempDir();

        $manager = $this->manager([
            'array' => ['driver' => 'array'],
            'fs'    => ['driver' => 'file', 'path' => $path],
            'ch'    => ['driver' => 'chain', 'stores' => ['array', 'fs']],
        ], 'ch');

        $chain = $manager->store('ch')->getAdapter();

        $this->assertInstanceOf(ChainAdapter::class, $chain);

        $property = new \ReflectionProperty(ChainAdapter::class, 'adapters');
        $adapters = $property->getValue($chain);

        $this->assertSame($manager->store('array')->getAdapter(), $adapters[0]);
        $this->assertSame($manager->store('fs')->getAdapter(), $adapters[1]);
    }

    public function test_a_value_written_through_the_chain_reaches_every_sub_store(): void
    {
        $manager = $this->manager([
            'array' => ['driver' => 'array'],
            'fs'    => ['driver' => 'file', 'path' => $this->tempDir()],
            'ch'    => ['driver' => 'chain', 'stores' => ['array', 'fs']],
        ], 'ch');

        $manager->store('ch')->set('k', 'v');

        $this->assertSame('v', $manager->store('array')->get('k'));
        $this->assertSame('v', $manager->store('fs')->get('k'));

        $manager->store('ch')->clear();
    }

    public function test_a_chain_without_sub_stores_is_refused_by_symfony(): void
    {
        $this->expectException(\Symfony\Component\Cache\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one adapter must be specified.');

        $this->manager(['ch' => ['driver' => 'chain']], 'ch')->store('ch');
    }

    /* -----------------------------------------------------------------
     |  database
     | -----------------------------------------------------------------
     */

    public function test_the_database_driver_forwards_its_configuration(): void
    {
        $adapter = $this->manager(
            ['db' => ['driver' => 'database', 'table' => 'tonka_cache', 'lifetime' => 30, 'lock' => false]],
            'db'
        )->store('db')->getAdapter();

        $this->assertInstanceOf(DatabaseAdapter::class, $adapter);
        $this->assertSame('tonka_cache', $this->read($adapter, 'table'));
        $this->assertSame(30, $this->read($adapter, 'defaultLifetime'));
        $this->assertFalse($this->read($adapter, 'lock'));
    }

    public function test_the_database_driver_defaults_to_the_cache_table(): void
    {
        $adapter = $this->manager(['db' => ['driver' => 'database']], 'db')->store('db')->getAdapter();

        $this->assertSame('cache', $this->read($adapter, 'table'));
        $this->assertSame(0, $this->read($adapter, 'defaultLifetime'));
        $this->assertTrue($this->read($adapter, 'lock'));
    }

    /* -----------------------------------------------------------------
     |  redis / memcached
     | -----------------------------------------------------------------
     */

    public function test_the_redis_driver_requires_a_redis_client(): void
    {
        if (extension_loaded('redis') || class_exists(\Redis::class) || class_exists(\Predis\Client::class)) {
            $this->markTestSkipped('Un client Redis est disponible : le chemin d\'erreur ne peut pas être observé.');
        }

        $this->expectException(\Symfony\Component\Cache\Exception\CacheException::class);
        $this->expectExceptionMessage('Cannot find the "redis" extension');

        $this->manager(['r' => ['driver' => 'redis']], 'r')->store('r');
    }

    public function test_the_memcached_driver_requires_the_memcached_extension(): void
    {
        if (extension_loaded('memcached')) {
            $this->markTestSkipped('ext-memcached est chargée : le chemin d\'erreur ne peut pas être observé.');
        }

        $this->expectException(\Symfony\Component\Cache\Exception\CacheException::class);
        $this->expectExceptionMessage('Memcached > 3.1.5 is required.');

        $this->manager(['m' => ['driver' => 'memcached']], 'm')->store('m');
    }

    /* -----------------------------------------------------------------
     |  Helpers
     | -----------------------------------------------------------------
     */

    private function read(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object::class, $property);

        return $reflection->getValue($object);
    }
}
