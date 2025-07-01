<?php
namespace Clicalmani\Foundation\Support\Facades;

abstract class View extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'view';
    }
}