<?php
namespace Clicalmani\Foundation\Providers;

use Clicalmani\Foundation\Support\Facades\Config;
use Override;

/**
 * Class ServiceProvider
 * 
 * Serves as the structural base class for all application and framework service providers.
 * Manages the sequential bootstrap runtime execution context, handles contract service bridging, 
 * and controls dynamic service registration across discrete application middleware layers.
 * 
 * @package Clicalmani\Foundation\Providers
 * @author @clicalmani
 */
abstract class ServiceProvider implements ServiceProviderInterface
{
    /**
     * ServiceProvider constructor.
     */
    public function __construct()
    {
        // Base initialization hook
    }

    /**
     * Bootstrap any application services post-registration.
     * 
     * @return void
     */
    #[Override]
    public abstract function boot(): void;

    /**
     * Register core application service dependencies into the container container.
     * 
     * @return void
     */
    #[Override]
    public function register(): void 
    { 
        // Intentionally left blank to be optionally overriden by extending subclasses
    }

    /**
     * Resolves a designated infrastructure middleware implementation mapped to a specific gateway.
     * 
     * @param string $gateway Target gateway stack context (e.g., 'web', 'api').
     * @param string $name Unique alias key identifying the target middleware class.
     * @return mixed Calculated configuration middleware class path reference string, or null if missing.
     */
    public static function getProvidedMiddleware(string $gateway, string $name): mixed
    {
        return config("http.{$gateway}.{$name}");
    }

    /**
     * Iterates across a collection of provider classes to sequentially initialize them.
     * 
     * @param array|null $providers Collection list containing service provider class paths.
     * @return void
     */
    public static function provideServices(?array $providers = []): void
    {
        foreach ($providers ?? [] as $provider) {
            self::provideService($provider);
        }
    }
    
    /**
     * Instantiates a single service provider and triggers its register and boot lifecycles.
     * 
     * @param string $service_class Qualified target class path string of the service provider.
     * @return void
     */
    private static function provideService(string $service_class): void
    {
        if ( class_exists($service_class) ) {
            $service = new $service_class();
            
            // Execute standard framework bootstrapping and dependency registration hooks
            if ( method_exists($service, 'register') ) {
                $service->register();
            }
            
            if ( method_exists($service, 'boot') ) {
                $service->boot();
            }
        }
    }
}