<?php

declare(strict_types=1);

namespace Clicalmani\Core\Tests\Cache;

use Clicalmani\Core\Cache\CacheAdapterContext;
use Clicalmani\Core\Cache\CacheManager;
use Clicalmani\Core\Cache\CacheStore;
use Clicalmani\Core\Tests\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class CacheManagerTest extends TestCase
{
    private function manager(?array $stores = null, string $default = 'array', ?string $prefix = 'tonka_tests_'): CacheManager
    {
        $config = ['default' => $default, 'stores' => $stores ?? [
            'array' => ['driver' => 'array'],
            'file'  => ['driver' => 'file', 'path' => $this->tempDir()],
        ]];

        if ($prefix !== null) {
            $config['prefix'] = $prefix;
        }

        $this->setConfig('cache', $config);

        return new CacheManager;
    }

    /* -----------------------------------------------------------------
     |  Store par défaut
     | -----------------------------------------------------------------
     */

    public function test_the_default_store_comes_from_the_configuration(): void
    {
        $manager = $this->manager(default: 'file');

        $this->assertSame('file', $manager->getDefaultStore());
        $this->assertSame('file', $manager->store()->getName());
    }

    /**
     * FIXME : sans clé `cache.default`, le repli sur 'file' explose.
     *
     * get_data() renvoie la valeur par défaut via value(), qui invoque tout
     * callable — or 'file' est le nom d'une fonction native PHP (file()).
     * Le repli codé en dur dans CacheManager est donc inutilisable.
     */
    public function test_the_default_store_fallback_is_unusable(): void
    {
        $this->setConfig('cache', []);

        $this->expectException(\ArgumentCountError::class);
        $this->expectExceptionMessage('file() expects at least 1 argument, 0 given');

        new CacheManager;
    }

    public function test_set_default_store_is_fluent_and_redirects_resolution(): void
    {
        $manager = $this->manager();

        $this->assertSame($manager, $manager->setDefaultStore('file'));
        $this->assertSame('file', $manager->store()->getName());
    }

    /* -----------------------------------------------------------------
     |  Résolution & mémoïsation
     | -----------------------------------------------------------------
     */

    public function test_store_builds_a_named_store_with_its_adapter(): void
    {
        $store = $this->manager()->store('array');

        $this->assertInstanceOf(CacheStore::class, $store);
        $this->assertSame('array', $store->getName());
        $this->assertInstanceOf(ArrayAdapter::class, $store->getAdapter());
    }

    public function test_store_is_memoized_per_name(): void
    {
        $manager = $this->manager();

        $this->assertSame($manager->store('array'), $manager->store('array'));
        $this->assertSame($manager->store(), $manager->store('array'));
        $this->assertNotSame($manager->store('array'), $manager->store('file'));
    }

    public function test_an_undefined_store_is_refused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache store [nope] is not defined in config/cache.php.');

        $this->manager()->store('nope');
    }

    /**
     * Sans clé "driver", le nom du store sert de nom de driver.
     */
    public function test_the_store_name_is_used_as_driver_when_none_is_configured(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unsupported cache driver [memory].');

        $this->manager(stores: ['memory' => []])->store('memory');
    }

    /* -----------------------------------------------------------------
     |  Délégation magique
     | -----------------------------------------------------------------
     */

    public function test_call_resolves_a_store_named_after_the_method(): void
    {
        $manager = $this->manager();

        $this->assertInstanceOf(CacheStore::class, $manager->file());
        $this->assertSame($manager->store('file'), $manager->file());
    }

    public function test_call_forwards_any_other_method_to_the_default_store(): void
    {
        $manager = $this->manager();

        $this->assertTrue($manager->set('k', 'v', 60));
        $this->assertSame('v', $manager->get('k'));
        $this->assertTrue($manager->has('k'));

        $this->assertSame('v', $manager->store('array')->get('k'));
        $this->assertNull($manager->store('file')->get('k'));
    }

    /* -----------------------------------------------------------------
     |  Extension
     | -----------------------------------------------------------------
     */

    public function test_extend_registers_a_custom_driver(): void
    {
        $manager = $this->manager(stores: ['custom' => ['driver' => 'probe']]);

        $this->assertSame($manager, $manager->extend('probe', fn () => new ArrayAdapter));

        $this->assertInstanceOf(ArrayAdapter::class, $manager->store('custom')->getAdapter());
    }

    public function test_the_context_receives_the_configured_prefix(): void
    {
        $received = null;

        $manager = $this->manager(stores: ['custom' => ['driver' => 'probe']], prefix: 'mon_prefix_');
        $manager->extend('probe', function (array $config, string $prefix) use (&$received) {
            $received = $prefix;

            return new ArrayAdapter;
        });

        $manager->store('custom');

        $this->assertSame('mon_prefix_', $received);
    }

    public function test_the_prefix_defaults_to_tonka_cache(): void
    {
        $received = null;

        $manager = $this->manager(stores: ['custom' => ['driver' => 'probe']], prefix: null);
        $manager->extend('probe', function (array $config, string $prefix) use (&$received) {
            $received = $prefix;

            return new ArrayAdapter;
        });

        $manager->store('custom');

        $this->assertSame('tonka_cache_', $received);
    }

    public function test_get_context_exposes_the_adapter_context(): void
    {
        $manager = $this->manager();

        $this->assertInstanceOf(CacheAdapterContext::class, $manager->getContext());
        $this->assertTrue($manager->getContext()->has('array'));
    }

    /* -----------------------------------------------------------------
     |  Purge
     | -----------------------------------------------------------------
     */

    public function test_purge_clears_every_resolved_store(): void
    {
        $manager = $this->manager();

        $manager->store('array')->set('k', 'v');
        $manager->store('file')->set('k', 'v');

        $this->assertTrue($manager->store('array')->has('k'));
        $this->assertTrue($manager->store('file')->has('k'));

        $manager->purge();

        $this->assertFalse($manager->store('array')->has('k'));
        $this->assertFalse($manager->store('file')->has('k'));
    }

    public function test_the_manager_is_resolvable_from_the_container(): void
    {
        $this->assertInstanceOf(CacheManager::class, container()->get('cache'));
        $this->assertInstanceOf(CacheManager::class, container()->get('cache.manager'));
    }
}
