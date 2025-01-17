<?php
namespace Clicalmani\Foundation\Maker;

class BootstrapKernel extends Kernel
{
    private $bootstrap;

    public function boot() : void
    {
        $this->bootstrap = $this->require( $this->app->config['paths']['root'] . '/bootstrap/kernel.php' );
    }

    public function register(): void
    {
        $this->app->config['bootstrap'] = $this->bootstrap;
    }
}