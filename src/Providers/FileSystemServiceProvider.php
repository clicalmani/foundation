<?php
namespace Clicalmani\Foundation\Providers;

/**
 * Class FileSystemServiceProvider
 * 
 * Configures the framework's physical file handling boundaries, including the 
 * dynamic runtime allocation and initialization of system application error logging.
 * 
 * @package Clicalmani\Foundation\Providers
 * @author @clicalmani
 */
class FileSystemServiceProvider extends ServiceProvider
{
    /**
     * Boots runtime file logging and persistent storage interfaces.
     * Hooks into the global workspace to establish absolute application error targets.
     * 
     * @return void
     */
    public function boot(): void
    {
        /**
         * Initialize the default framework storage error logging registry.
         */
        \Clicalmani\Foundation\Support\Facades\Log::init(root_path());
    }
}