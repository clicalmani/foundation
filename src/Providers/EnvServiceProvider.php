<?php
namespace Clicalmani\Foundation\Providers;

use Clicalmani\Foundation\Acme\Environment;
use Clicalmani\Foundation\Support\Facades\Env;

/**
 * EnvServiceProvider class
 * 
 * @package Clicalmani\Foundation/flesco 
 * @author @Clicalmani\Foundation
 */
class EnvServiceProvider extends ServiceProvider
{
    private $environment;

    public function __construct()
    {
        $this->environment = new Environment;
    }

    public function boot(): void
    {
        $this->environment->enablePutenv();

        /**
         * Load environment variables
         */
        \Dotenv\Dotenv::create(
            $this->environment->getRepository(), 
            dirname( __DIR__, 5)
        )->safeLoad();
        
        if ( isConsoleMode() ) {
            app()->database = require_once config_path('/database.php');
        }
    }
}