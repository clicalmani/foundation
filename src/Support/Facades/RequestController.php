<?php
namespace Clicalmani\Foundation\Support\Facades;

/**
 * @method static never render()
 * @method static mixed invokeMethod(\Clicalmani\Foundation\Http\Controllers\ReflectorInterface $reflector)
 * @method static \Clicalmani\Foundation\Test\Controllers\TestController test(string $action)
 * @method static object getInstance(string $class)
 */
abstract class RequestController extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'controller';
    }
}