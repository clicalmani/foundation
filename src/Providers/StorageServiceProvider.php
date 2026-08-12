<?php
namespace Clicalmani\Foundation\Providers;

use Symfony\Component\DependencyInjection\Loader\Configurator\DefaultsConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServiceConfigurator;
use Override;

/**
 * Class StorageServiceProvider
 * 
 * Boots and provisions the framework's centralized filesystem abstraction layer.
 * Registers the global storage manager factory and injects configuration profiles 
 * governing physical disks, cloud objects, and local volume streams.
 * 
 * @package Clicalmani\Foundation\Providers
 * @author @clicalmani
 */
class StorageServiceProvider implements ServiceProviderInterface
{
    /**
     * Registers filesystem infrastructure dependencies, storage managers, and driver disks.
     * 
     * @return void
     */
    #[Override]
    public function register(): void
    {
        // Load the framework's filesystem structure rules directly into memory context
        $app_config = require config_path('/storage.php');
        
        // Primary management service engine coordinating custom filesystem disk actions
        app()->addService('storage.manager', 
            \Clicalmani\Foundation\Filesystem\StorageManager::class,
            fn(ServiceConfigurator|DefaultsConfigurator $config) => $config->args([
                [
                    'default' => $app_config['default'] ?? 'local',
                    'disks'   => $app_config['disks'] ?? []
                ]
            ])
        );
    }

    /**
     * Synchronizes global framework configuration maps inside storage boot cycles.
     * 
     * @return void
     */
    #[Override]
    public function boot(): void
    {
        if ( is_file(config_path('/storage.php')) ) {
            app()->config->set('storage', require config_path('/storage.php'));
        }
    }
}