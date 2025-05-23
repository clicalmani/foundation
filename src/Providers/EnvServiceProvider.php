<?php
namespace Clicalmani\Foundation\Providers;

use Clicalmani\Foundation\Support\Facades\Env;

/**
 * EnvServiceProvider class
 * 
 * @package Clicalmani\Foundation/flesco 
 * @author @Clicalmani\Foundation
 */
class EnvServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Env::enablePutenv();

        /**
         * Load environment variables
         */
        \Dotenv\Dotenv::create(
            Env::getRepository(), 
            dirname( __DIR__, 5)
        )->safeLoad();
        
        if ( isConsoleMode() ) {
            app()->database = require_once config_path('/database.php');
        }
    }
}