<?php
namespace Clicalmani\Foundation\Providers;

/**
 * ContainerServiceProvider class
 * 
 * @package Clicalmani\Foundation/flesco 
 * @author @Clicalmani\Foundation
 */
class ContainerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        /**
         * |----------------------------------------------------------------
         * |            ***** Container AutoLoader *****
         * |----------------------------------------------------------------
         * 
         * Classes defined in the app directory will be automatically injected.
         */
        new \Clicalmani\Foundation\Container\SPL_Loader( root_path() );
    }
}