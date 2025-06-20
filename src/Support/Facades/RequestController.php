<?php
namespace Clicalmani\Foundation\Support\Facades;

class RequestController extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'controller';
    }
}