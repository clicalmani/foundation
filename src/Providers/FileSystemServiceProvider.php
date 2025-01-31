<?php
namespace Clicalmani\Foundation\Providers;

/**
 * FileSystemServiceProvider class
 * 
 * @package Clicalmani\Foundation 
 * @author @Clicalmani
 */
class FileSystemServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        /**
         * Error log
         */
        \Clicalmani\Foundation\Support\Facades\Log::init( root_path() );
    }
}