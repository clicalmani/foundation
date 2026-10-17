<?php
namespace Clicalmani\Core\Support\Facades;

/**
 * @method static mixed link(string $source, string $destination)
 */
abstract class Tonka extends Facade
{
    protected static function getFacadeAccessor() : string
    {
        return 'console';
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