<?php
namespace Clicalmani\Foundation\Maker;

abstract class Kernel
{
    public function __construct(protected \Clicalmani\Foundation\Maker\Application $app)
    {
        //
    }

    public abstract function boot() : void;

    public abstract function register() : void;
}