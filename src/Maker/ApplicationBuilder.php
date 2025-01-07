<?php
namespace Clicalmani\Foundation\Maker;

class ApplicationBuilder
{
    public function __construct(private Application $app)
    {
        
    }

    public function run()
    {
        return $this->app;
    }
}
