<?php
namespace Clicalmani\Core\Support\Facades;

/**
 * @method static string render()
 * @method static \Clicalmani\Core\Resources\ViewInterface with(string $key, mixed $value)
 * @method static void share(string $key, mixed $value)
 * @method static void composer(string|array $views, string|callable $composer)
 * @method static void create(string $view, string|callable $creator)
 */
abstract class View extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'view';
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