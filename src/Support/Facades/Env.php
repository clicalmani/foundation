<?php 
namespace Clicalmani\Foundation\Support\Facades;

use Clicalmani\Foundation\Support\Facades\Facade;

class Env extends Facade
{
    protected static function getFacadeAccessor() : string
    {
        return 'env';
    }
}
