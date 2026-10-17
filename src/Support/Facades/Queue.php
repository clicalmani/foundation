<?php
namespace Clicalmani\Core\Support\Facades;

use Override;

class Queue extends Facade
{
    #[Override]
    public static function getFacadeAccessor(): string
    {
        return 'queue';
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