<?php
namespace Clicalmani\Foundation\Maker;

class HttpKernel extends Kernel
{
    private $http;

    public function boot(): void
    {
        $this->http = $this->require( $this->app->config['paths']['root'] . '/app/Http/kernel.php' );
    }

    public function register(): void
    {
        $this->app->config['http'] = $this->http;
    }
}