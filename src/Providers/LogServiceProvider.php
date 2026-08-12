<?php
namespace Clicalmani\Foundation\Providers;

/**
 * Class LogServiceProvider
 * 
 * Boots and provisions the framework's native PHP error logging infrastructure, 
 * evaluating environment debug states and initializing persistent file storage targets.
 * 
 * @package Clicalmani\Foundation\Providers
 * @author @clicalmani
 */
class LogServiceProvider extends ServiceProvider
{
    /**
     * Default file name for targeted application error logs.
     * 
     * @var string
     */
    protected const ERROR_LOG = 'errors.log';

    /**
     * Internal operational flag tracking application verbose debug mode states.
     * 
     * @var bool
     */
    protected static bool $is_debug_mode = false;

    /**
     * Boots runtime application logging systems.
     * Assesses environment debug flags and binds native PHP directives to persistent target folders.
     * 
     * @return void
     */
    public function boot(): void
    {
        // Parse the dynamic environment configuration variable safely matching alternative value models
        if (is_string(env('APP_DEBUG'))) {
            static::$is_debug_mode = 0 === strcasecmp(env('APP_DEBUG'), 'false') ? false : true;
        } else {
            static::$is_debug_mode = (bool) env('APP_DEBUG', false);
        }

        // Ensure the absolute target logging subsystem directory exists on the filesystem
        if ( !file_exists(storage_path('/errors')) ) {
            mkdir(storage_path('/errors'), 0775, true);
        }
        
        // Bind framework processing indicators to native PHP runtime configuration spaces
        ini_set('log_errors', 1);
        ini_set('error_log', storage_path('/errors/' . static::ERROR_LOG));
    }
}