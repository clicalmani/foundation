<?php

declare(strict_types=1);

namespace Clicalmani\Core\Tests;

use Clicalmani\Core\Acme\Configure;
use Clicalmani\Core\Acme\StorageManager;
use Clicalmani\Core\Cache\CacheManager;

/**
 * Vérifie le câblage de l'amorçage de tests (tests/bootstrap.php) : sans ces
 * services, la plupart des autres suites échouent de manière illisible.
 */
final class BootstrapTest extends TestCase
{
    public function test_application_root_points_to_the_fake_app(): void
    {
        $this->assertSame(TESTS_FIXTURE_ROOT, app()->rootPath());
        $this->assertDirectoryExists(TESTS_FIXTURE_ROOT . '/config');
    }

    public function test_core_services_are_resolvable(): void
    {
        $this->assertInstanceOf(Configure::class, container()->get('config'));
        $this->assertInstanceOf(StorageManager::class, container()->get('storage'));
        $this->assertInstanceOf(CacheManager::class, container()->get('cache'));
    }

    public function test_helpers_are_loaded(): void
    {
        foreach (['app', 'config', 'container', 'cache', 'storage', 'collection', 'logger', 'event'] as $helper) {
            $this->assertTrue(function_exists($helper), "Le helper {$helper}() devrait être chargé.");
        }
    }

    public function test_cache_configuration_is_loaded_from_the_fixture(): void
    {
        $this->assertSame('array', config('cache.default'));
        $this->assertSame('tonka_tests_', config('cache.prefix'));
        $this->assertIsArray(config('cache.stores'));
    }

    public function test_dot_notation_writes_into_the_configuration(): void
    {
        $this->setConfig('cache.stores.array.driver', 'array');

        $this->assertSame('array', config('cache.stores.array.driver'));
    }

    public function test_configuration_changes_are_rolled_back_between_tests(): void
    {
        // Aucun set ici : si l'isolation fonctionne, la valeur posée par le test
        // précédent a disparu et la configuration de l'amorçage est intacte.
        $this->assertSame('array', config('cache.default'));
        $this->assertCount(4, config('cache.stores'));
    }

    public function test_bound_service_replaces_the_container_entry(): void
    {
        $fake = new \stdClass;

        $this->bindService('config', $fake);

        $this->assertSame($fake, container()->get('config'));
    }

    public function test_bound_service_is_restored_on_the_next_test(): void
    {
        $this->assertInstanceOf(Configure::class, container()->get('config'));
        $this->assertSame('array', config('cache.default'));
    }
}
