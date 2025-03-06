<?php
namespace Clicalmani\Foundation\Maker;

class AppKernel extends Kernel
{
    private $app_config;
    private $mail_config;

    public function boot(): void
    {
        $this->app_config = $this->require( $this->app->config['paths']['root'] . '/config/app.php' );
        $this->mail_config = $this->require( $this->app->config['paths']['root'] . '/config/mail.php' );
    }

    public function register(): void
    {
        $this->app->config['app'] = $this->app_config;
        $this->app->config['mail'] = $this->mail_config;
    }
}