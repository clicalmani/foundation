<?php
namespace Clicalmani\Foundation\Support\Facades;

class Storage extends Facade
{
    protected static function getFacadeAccessor() : string
    {
        return 'storage';
    }
}