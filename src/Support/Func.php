<?php
namespace Clicalmani\Foundation\Support;

/**
 * @method bool isInternal(string $func_name)
 * @method bool isUserDefined(string $func_name)
 * @method bool isGenerator(string $func_name)
 * @method bool isVariadic(string $func_name)
 * @method ?\ReflectionClass getClosureThis(string $func_name)
 * @method ?\ReflectionClass getClosureScopeClass(string $func_name)
 * @method ?\ReflectionClass getClosureCalledClass(string $func_name)
 */
class Func extends Mock
{
    /**
     * @var \ReflectionFunction
     */
    private $reflection;

    public function __construct(private \Closure|string $name)
    {
        $this->reflection = new \ReflectionFunction($name);
    }

    public function _isInternal()
    {
        return $this->reflection->isInternal();
    }

    public function _isUserDefined()
    {
        return $this->reflection->isUserDefined();
    }

    public function _isGenerator()
    {
        return $this->reflection->isGenerator();
    }

    public function _isVariadic()
    {
        return $this->reflection->isVariadic();
    }

    public function _getClosureThis()
    {
        return $this->reflection->getClosureThis();
    }

    public function _getClosureScopeClass()
    {
        return $this->reflection->getClosureScopeClass();
    }

    public function _getClosureCalledClass()
    {
        return $this->reflection->getClosureCalledClass();
    }
}