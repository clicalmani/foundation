<?php

namespace Clicalmani\Core\Providers;

use Clicalmani\Core\Acme\Container;
use Clicalmani\Core\Cache\CacheManager;
use Symfony\Component\DependencyInjection\Loader\Configurator\DefaultsConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServiceConfigurator;

class CacheServiceProvider implements ServiceProviderInterface
{
    public function register(): void
    {
        app()->addService(
            'cache',
            CacheManager::class,
            function (ServiceConfigurator|DefaultsConfigurator $config) {
                $config->args([]);
            },
            ['cache.manager', 'cache']
        );
    }

    public function boot(?Container $container = null): void
    {
        if (file_exists(config_path('/cache.php'))) {
            app()->config->set('cache', require_once config_path('/cache.php'));
        }
    }
}