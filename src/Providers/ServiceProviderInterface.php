<?php
namespace Clicalmani\Foundation\Providers;

/**
 * Interface ServiceProviderInterface
 * 
 * Enforces the primary contractual execution sequence for booting and registering 
 * independent service sub-systems within the framework dependency container.
 * 
 * @package Clicalmani\Foundation\Providers
 * @author @clicalmani
 */
interface ServiceProviderInterface
{
    /**
     * Bootstraps application configuration schemes, routes, or active event hooks 
     * post-dependency registration.
     * 
     * @return void
     */
    public function boot(): void;

    /**
     * Registers architectural core components, factory logic, and bound references 
     * into the global application service container.
     * 
     * @return void
     */
    public function register(): void;
}