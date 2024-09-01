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
    public function boot(): void
    {
        /**
         * Error log
         */
        \Clicalmani\Foundation\Support\Facades\Log::init( root_path() );
    }
}