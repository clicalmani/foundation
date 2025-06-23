<?php 
namespace Clicalmani\Foundation\Support\Facades;

use Clicalmani\Foundation\Support\Facades\Facade;

/**
 * @method static mixed get(string $key, $default = null) Get the value of the environment variable.
 * @method static void set(string $key, $value) Set the value of the environment variable.
 */
class Env extends Facade
{
    protected static function getFacadeAccessor() : string
    {
        return 'env';
    }
}
