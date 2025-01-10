<?php
namespace Clicalmani\Foundation\Maker;

class AppKernel extends Kernel
{
    private $app_config;

    public function boot(): void
    {
        $this->app_config = $this->require( $this->app->config['paths']['root'] . '/config/app.php' );
    }

    public function register(): void
    {
        $this->app->config['app'] = $this->app_config;
    }
}