<?php
namespace Clicalmani\Foundation\Maker;

class BootstrapKernel extends Kernel
{
    private $bootstrap;

    public function boot() : void
    {
        $this->bootstrap = $this->require( $this->app->config['paths']['root'] . '/bootstrap/kernel.php' );
        $this->bootstrap['tps'][0][] = \Clicalmani\Foundation\Messenger\Transport\Service::class;
    }

    public function register(): void
    {
        $this->app->config['bootstrap'] = $this->bootstrap;
    }
}