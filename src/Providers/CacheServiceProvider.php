<?php
namespace Clicalmani\Foundation\Providers;

use Override;
use Symfony\Component\DependencyInjection\Loader\Configurator\DefaultsConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServiceConfigurator;

class CacheServiceProvider implements ServiceProviderInterface
{
    /**
     * @var class-string<>
     */
    protected string $adapter = \Symfony\Component\Cache\Adapter\FilesystemAdapter::class;
    protected string $path = 'storage/cache';

    #[Override]
    public function register(): void
    {
        /**
         * Service de Cache principal
         */
        app()->addService('cache.app', [
            $this->adapter,
            fn($config) => $this->config($config)
        ]);
    }

    #[Override]
    public function boot(): void
    {
        // ...
    }

    protected function config(ServiceConfigurator|DefaultsConfigurator $config): void
    {
        $cachePath = app()->rootPath() . '/' . $this->path;

        if ( !is_dir($cachePath) ) {
            mkdir($cachePath, 0775, true);
        }

        $config->args([
            'tonka',   // Namespace
            3600,      // Default TTL (in seconds 1h)
            $cachePath // Storage directory
        ]);
    }
}