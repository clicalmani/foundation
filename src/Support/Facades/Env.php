<?php 
namespace Clicalmani\Core\Support\Facades;

use Clicalmani\Core\Support\Facades\Facade;

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

    /**
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic(string $method, array $arguments): mixed
    {
        return static::getFacadeRoot()?->$method(...$arguments);
    }
}
