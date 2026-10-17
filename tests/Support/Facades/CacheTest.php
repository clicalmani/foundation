<?php

declare(strict_types=1);

namespace Clicalmani\Core\Tests\Support\Facades;

use Clicalmani\Core\Cache\CacheManager;
use Clicalmani\Core\Cache\CacheStore;
use Clicalmani\Core\Support\Facades\Cache;
use Clicalmani\Core\Tests\TestCase;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

final class CacheTest extends TestCase
{
    /* -----------------------------------------------------------------
     |  Résolution
     | -----------------------------------------------------------------
     */

    public function test_the_facade_points_at_the_cache_manager_service(): void
    {
        $this->assertSame('cache.manager', Cache::getFacadeAccessor());
    }

    public function test_the_facade_root_is_the_manager_shared_by_the_container(): void
    {
        $this->assertInstanceOf(CacheManager::class, container()->get('cache.manager'));
        $this->assertSame(container()->get('cache'), container()->get('cache.manager'));
        $this->assertSame(container()->get('cache')->getContext(), Cache::getContext());
    }

    /**
     * Le CacheManager lit sa configuration dans son constructeur : retoucher
     * config/cache.php ensuite ne change rien au gestionnaire partagé.
     */
    public function test_changing_the_configuration_does_not_affect_an_already_built_manager(): void
    {
        $manager = container()->get('cache');

        $this->assertInstanceOf(CacheManager::class, $manager);

        $this->setConfig('cache.default', 'file');

        $this->assertSame('array', $manager->getDefaultStore());
        $this->assertSame($manager->getDefaultStore(), Cache::getDefaultStore());
        $this->assertSame('array', $manager->store()->getName());
    }

    /* -----------------------------------------------------------------
     |  Délégation
     | -----------------------------------------------------------------
     */

    public function test_the_facade_forwards_the_store_api_to_the_default_store(): void
    {
        $this->assertTrue(Cache::set('facade.forward', 'valeur', 60));
        $this->assertTrue(Cache::has('facade.forward'));
        $this->assertSame('valeur', Cache::get('facade.forward'));
        $this->assertSame('valeur', Cache::pull('facade.forward'));
        $this->assertFalse(Cache::has('facade.forward'));
    }

    public function test_the_default_store_is_the_array_store_of_the_fixtures(): void
    {
        $store = Cache::store();

        $this->assertInstanceOf(CacheStore::class, $store);
        $this->assertSame('array', $store->getName());
        $this->assertSame(container()->get('cache')->store('array'), $store);
    }

    public function test_the_facade_resolves_a_named_store(): void
    {
        $store = Cache::store('file');

        $this->assertSame('file', $store->getName());
        $this->assertInstanceOf(FilesystemAdapter::class, $store->getAdapter());

        $store->clear();
    }

    /**
     * CacheManager::__call() traite d'abord le nom comme un store : toute
     * méthode portant le nom d'un store configuré renvoie ce store.
     */
    public function test_a_method_named_after_a_store_returns_that_store(): void
    {
        $this->assertSame(Cache::store('file'), Cache::file());

        Cache::store('file')->clear();
    }

    /**
     * Toute autre méthode est poussée vers le store par défaut, qui n'a pas de
     * __call : une méthode inconnue remonte donc en Error.
     */
    public function test_an_unknown_method_is_forwarded_to_the_default_store(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Call to undefined method Clicalmani\Core\Cache\CacheStore::methodeInconnue()');

        Cache::methodeInconnue();
    }

    public function test_an_unknown_store_is_refused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache store [absent] is not defined in config/cache.php.');

        Cache::store('absent');
    }
}
