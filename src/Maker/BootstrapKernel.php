<?php
namespace Clicalmani\Foundation\Maker;

/**
 * Class BootstrapKernel
 * 
 * Handles the loading and registration of the core application bootstrapping configuration
 * from the environment initialization scripts.
 * 
 * @package Clicalmani\Foundation\Maker
 * @author @clicalmani
 */
class BootstrapKernel extends Kernel
{
    /**
     * The loaded bootstrap configuration array registry.
     * 
     * @var array
     */
    private $bootstrap;

    /**
     * Boots the prerequisite runtime configurations.
     * Locates and requires the explicit bootstrap kernel mapping script.
     * 
     * @return void
     */
    public function boot() : void
    {
        $this->bootstrap = require_once $this->app->config['paths']['root'] . '/bootstrap/kernel.php';
    }

    /**
     * Registers the loaded bootstrap settings into the global application configuration manager.
     * 
     * @return void
     */
    public function register(): void
    {
        $this->app->config['bootstrap'] = $this->bootstrap;
    }
}