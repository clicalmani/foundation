<?php
namespace Clicalmani\Foundation\Maker;

class AppKernel extends Kernel
{
    private $app_config;
    private $mail_config;
    private $messenger_config;

    public function boot(): void
    {
        $app_config_file = $this->app->config['paths']['root'] . '/config/app.php';
        $mail_config_file = $this->app->config['paths']['root'] . '/config/mail.php';
        $messenger_config_file = $this->app->config['paths']['root'] . '/config/messenger.php';
        
        if ( file_exists($app_config_file) ) {
            $this->app_config = require_once $app_config_file;
        }
        
        if ( file_exists($mail_config_file) ) {
            $this->mail_config = require_once $mail_config_file;
        }

        if ( file_exists($messenger_config_file) ) {
            $this->messenger_config = require_once $messenger_config_file;
        }
    }

    public function register(): void
    {
        $this->app->config['app'] = $this->app_config;
        $this->app->config['mail'] = $this->mail_config;
        $this->app->config['messenger'] = $this->messenger_config;
    }
}