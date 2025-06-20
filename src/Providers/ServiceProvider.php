<?php
namespace Clicalmani\Foundation\Providers;

use Clicalmani\Foundation\Support\Facades\Config;

/**
 * ServiceProvider class
 * 
 * @package Clicalmani\Foundation
 * @author @Clicalmani\Foundation
 */
abstract class ServiceProvider
{
    public function __construct()
    {
    }

    /**
     * (non-PHPDoc)
     * @override
     */
    public abstract function boot() : void;

    /**
     * (non-PHPDoc)
     * @override
     */
    public function register() : void { /** TODO: Override */}

    /**
     * Get a provided middleware
     * 
     * @param string $gateway
     * @param string $name Middleware name
     * @return mixed
     */
    public static function getProvidedMiddleware(string $gateway, $name) : mixed
    {
        return @ Config::http($gateway)[$name];
    }

    public static function provideServices(?array $providers = [])
    {
        foreach ($providers as $provider)
            self::provideService($provider);
    }
    
    private static function provideService(string $service_class)
    {
        if ( class_exists( $service_class ) ) {
            $service = new $service_class;
            
            if ( method_exists($service, 'register') ) $service->register();
            if ( method_exists($service, 'boot') ) $service->boot();
        }
    }
}
