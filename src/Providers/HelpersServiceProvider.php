<?php
namespace Clicalmani\Foundation\Providers;

/**
 * HelpersServiceProvider class
 * 
 * @package Clicalmani\Foundation/flesco 
 * @author @Clicalmani\Foundation
 */
class HelpersServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        /**
         * |---------------------------------------------------------------------------
         * |              ***** TONKA built-in helper functions *****
         * |---------------------------------------------------------------------------
         * 
         * Built-in helper functions
         * 
         * 
         */

        \Clicalmani\Foundation\Support\Helper::include();
    }
}