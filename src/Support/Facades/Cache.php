<?php
namespace Clicalmani\Core\Support\Facades;

class Cache extends Facade
{
    public static function getFacadeAccessor(): string
    {
        return 'cache.manager';
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