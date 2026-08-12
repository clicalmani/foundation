<?php
namespace Clicalmani\Foundation\Providers;

use Clicalmani\Foundation\Acme\Environment;
use Clicalmani\Foundation\Support\Facades\Env;

/**
 * Class EnvServiceProvider
 * 
 * Boots the environment infrastructure layer, processing runtime parameters 
 * and initializing configuration fallbacks for terminal runtime interfaces.
 * 
 * @package Clicalmani\Foundation\Providers
 * @author @clicalmani
 */
class EnvServiceProvider extends ServiceProvider
{
    /**
     * The environment controller backend engine instance.
     * 
     * @var \Clicalmani\Foundation\Acme\Environment
     */
    private Environment $environment;

    /**
     * EnvServiceProvider Constructor.
     */
    public function __construct()
    {
        $this->environment = new Environment;
    }

    /**
     * Boots runtime global attributes and environment settings.
     * Instructs immutable variable adapters and injects structural configs during console routines.
     * 
     * @return void
     */
    public function boot(): void
    {
        $this->environment->enablePutenv();

        /**
         * Load runtime environment configurations safely from the root context.
         */
        \Dotenv\Dotenv::create(
            $this->environment->getRepository(), 
            dirname(__DIR__, 5)
        )->safeLoad();
        
        // Console scopes lack request cycles; inject database configurations immediately if needed
        if ( isConsoleMode() ) {
            app()->config->set('database', require_once config_path('/database.php'));
        }
    }
}