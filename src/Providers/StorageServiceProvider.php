<?php
namespace Clicalmani\Foundation\Providers;

use Override;

class StorageServiceProvider implements ServiceProviderInterface
{
    #[Override]
    public function register(): void
    {
        $app_config = require config_path('/storage.php');
        
        /**
         * Service de manipulation de fichiers
         */
        app()->addService('storage.manager', [
            \Clicalmani\Foundation\Filesystem\StorageManager::class,
            static fn($config) => $config->args([
                [
                    'default' => $app_config['default'],
                    'disks' => $app_config['disks']
                ]
            ])
        ]);
    }

    #[Override]
    public function boot(): void
    {
        if ( is_file(config_path('/storage.php')) ) {
            app()->config->set('storage', require config_path('/storage.php'));
        }
    }
}