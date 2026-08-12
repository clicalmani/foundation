<?php
namespace Clicalmani\Foundation\Providers;

use Override;
use Symfony\Component\DependencyInjection\Loader\Configurator\DefaultsConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServiceConfigurator;

/**
 * Class CacheServiceProvider
 * 
 * Provisions and configures the application cache components, managing pool setup 
 * and persistent storage allocation within the dependency injection container.
 * 
 * @package Clicalmani\Foundation\Providers
 * @author @clicalmani
 */
class CacheServiceProvider implements ServiceProviderInterface
{
    /**
     * The fully qualified class name (FQCN) of the default cache adapter implementation.
     * 
     * @var class-string<\Symfony\Component\Cache\Adapter\AdapterInterface>
     */
    protected string $adapter = \Symfony\Component\Cache\Adapter\FilesystemAdapter::class;

    /**
     * The relative path from the application root where cache files will be stored.
     * 
     * @var string
     */
    protected string $path = 'storage/cache';

    /**
     * Registers the application cache service components into the container.
     * 
     * @return void
     */
    #[Override]
    public function register(): void
    {
        /**
         * Main Cache Service
         * Binds the dedicated application caching gateway layer with custom injection arguments.
         */
        app()->addService('cache.app', $this->adapter, fn($config) => $this->config($config));
    }

    /**
     * Boots prerequisite options or environments right before service runtime execution.
     * 
     * @return void
     */
    #[Override]
    public function boot(): void
    {
        // Reserved for sub-dependency initialization routines
    }

    /**
     * Binds custom construction arguments and creates necessary persistent storage directories 
     * for the Symfony Cache adapter instance.
     * 
     * @param \Symfony\Component\DependencyInjection\Loader\Configurator\ServiceConfigurator|
     *        \Symfony\Component\DependencyInjection\Loader\Configurator\DefaultsConfigurator $config The active Symfony DI builder instance.
     * @return void
     */
    protected function config(ServiceConfigurator|DefaultsConfigurator $config): void
    {
        $cachePath = app()->rootPath() . '/' . $this->path;

        // Ensure the absolute target cache directory exists on the filesystem
        if ( !is_dir($cachePath) ) {
            mkdir($cachePath, 0775, true);
        }

        $config->args([
            'tonka',    // Cache namespace prefix identifier
            3600,       // Default lifetime (TTL) in seconds (1 hour)
            $cachePath  // Absolute target directory for filesystem persistence
        ]);
    }
}