<?php
namespace Clicalmani\Core\Support\Facades;

/**
 * @method static never render()
 * @method static mixed invokeMethod(\Clicalmani\Core\Http\Controllers\ReflectorInterface $reflector)
 * @method static \Clicalmani\Core\Test\Controllers\TestController test(string $action)
 * @method static object getInstance(string $class)
 */
abstract class RequestController extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'controller';
    }
}