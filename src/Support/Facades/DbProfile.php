<?php

namespace Clicalmani\Core\Support\Facades;

/**
 * @method static \Clicalmani\Database\Profiling\QueryProfiler enable()
 * @method static \Clicalmani\Database\Profiling\QueryProfiler disable()
 * @method static bool isEnabled()
 * @method static array profile(callable $callback, string $connection = 'default')
 * @method static array profileQuery(string $sql, array $bindings = [], string $connection = 'default')
 * @method static array captureLast(string $connection = 'default')
 * @method static array summary(array $profile)
 *
 * @see \Clicalmani\Database\Profiling\QueryProfiler
 */
final class DbProfile extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'query_profiler';
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