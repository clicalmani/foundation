<?php
namespace Clicalmani\Foundation\Maker;

class AppKernel extends Kernel
{
    private $app_config;
    private $mail_config;

    public function boot(): void
    {
        $app_config_file = $this->app->config['paths']['root'] . '/config/app.php';
        $mail_config_file = $this->app->config['paths']['root'] . '/config/mail.php';

        if ( file_exists($app_config_file) ) {
            $this->app_config = require_once $this->app->config['paths']['root'] . '/config/app.php';
        }

        if ( file_exists($mail_config_file) ) {
            $this->mail_config = require_once $this->app->config['paths']['root'] . '/config/mail.php';
        }
    }

    public function register(): void
    {
        $this->app->config['app'] = $this->app_config;
        $this->app->config['mail'] = $this->mail_config;
    }
}