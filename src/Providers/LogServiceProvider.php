<?php
namespace Clicalmani\Foundation\Providers;

/**
 * LogServiceProvider class
 * 
 * @package Clicalmani\Foundation/flesco 
 * @author @Clicalmani\Foundation
 */
class LogServiceProvider extends ServiceProvider
{
    /**
     * Log file name
     * 
     * @var string
     */
    protected const ERROR_LOG = 'errors.log';

    protected static $is_debug_mode = false;

    public function boot(): void
    {
        static::$is_debug_mode = 0 === strcasecmp(env('APP_DEBUG'), 'false') ? false: true;
        
        ini_set('log_errors', 1);
        ini_set('error_log', storage_path('/errors/' . static::ERROR_LOG) );
    }
}