<?php
namespace Clicalmani\Core\Providers;

/**
 * Class HelpersServiceProvider
 * 
 * Boots and registers the framework's native global utility function wrappers,
 * establishing foundational procedural APIs across the active runtime lifecycle.
 * 
 * @package Clicalmani\Core\Providers
 * @author Clicalmani\Core
 */
class HelpersServiceProvider extends ServiceProvider
{
    /**
     * Boots prerequisite system helpers.
     * Imperatively requires the structural global function files into the execution environment.
     * 
     * @return void
     */
    public function boot(): void
    {
        /**
         * |---------------------------------------------------------------------------
         * |            ***** TONKA built-in helper functions *****
         * |---------------------------------------------------------------------------
         * 
         * Imports global helper functions into scope to ensure universal access 
         * to quick structural framework shortcuts (e.g., app(), config(), root_path()).
         */
        \Clicalmani\Core\Support\Helper::include();
    }
}