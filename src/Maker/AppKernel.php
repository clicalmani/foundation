<?php
namespace Clicalmani\Foundation\Maker;

/**
 * Class AppKernel
 * 
 * Handles the loading and registration of the core application environment configurations 
 * and explicit mail service blueprints during the framework boot phase.
 * 
 * @package Clicalmani\Foundation\Maker
 * @author @clicalmani
 */
class AppKernel extends Kernel
{
    /**
     * Cached application core configuration array.
     * 
     * @var array|null
     */
    private $app_config;

    /**
     * Cached mailing component configuration array.
     * 
     * @var array|null
     */
    private $mail_config;

    /**
     * Boots the core framework runtime configurations.
     * Resolves absolute paths, verifies file existence, and imports targeted arrays into memory.
     * 
     * @return void
     */
    public function boot(): void
    {
        $app_config_file = $this->app->config['paths']['root'] . '/config/app.php';
        $mail_config_file = $this->app->config['paths']['root'] . '/config/mail.php';
        
        if ( file_exists($app_config_file) ) {
            $this->app_config = require_once $app_config_file;
        }
        
        if ( file_exists($mail_config_file) ) {
            $this->mail_config = require_once $mail_config_file;
        }
    }

    /**
     * Commits the loaded application and mailing configurations into the global framework settings container.
     * 
     * @return void
     */
    public function register(): void
    {
        $this->app->config['app']  = $this->app_config;
        $this->app->config['mail'] = $this->mail_config;
    }
}