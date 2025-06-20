<?php
namespace Clicalmani\Foundation\Support\Facades;

/**
 * @method bool isInternal(string $func_name)
 * @method bool isUserDefined(string $func_name)
 * @method bool isGenerator(string $func_name)
 * @method bool isVariadic(string $func_name)
 * @method ?\ReflectionClass getClosureThis(string $func_name)
 * @method ?\ReflectionClass getClosureScopeClass(string $func_name)
 * @method ?\ReflectionClass getClosureCalledClass(string $func_name)
 */
class Func extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'func';
    }
}