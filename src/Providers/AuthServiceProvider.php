<?php
namespace Clicalmani\Core\Providers;

/**
 * Class AuthServiceProvider
 * 
 * Provides an abstract foundation for security authentication services,
 * mapping structural authentication parameters from the application runtime configuration.
 * 
 * @package Clicalmani\Core\Providers
 * @author @clicalmani
 */
abstract class AuthServiceProvider extends ServiceProvider
{
    /**
     * Resolves the fully qualified class name or reference of the configured user authenticator model.
     * 
     * @return mixed The configured authenticator model string identifier or instance.
     */
    public static function userAuthenticator() : mixed
    {
        return config('bootstrap.auth.user');
    }
}