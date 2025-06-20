<?php
namespace Clicalmani\Foundation\Acme;

class Invokable
{
    /**
     * @var \ReflectionFunction
     */
    private $reflection;

    public function __construct(private \Closure|string $name)
    {
        $this->reflection = new \ReflectionFunction($name);
    }

    public function isInternal()
    {
        return $this->reflection->isInternal();
    }

    public function isUserDefined()
    {
        return $this->reflection->isUserDefined();
    }

    public function isGenerator()
    {
        return $this->reflection->isGenerator();
    }

    public function isVariadic()
    {
        return $this->reflection->isVariadic();
    }

    public function getClosureThis()
    {
        return $this->reflection->getClosureThis();
    }

    public function getClosureScopeClass()
    {
        return $this->reflection->getClosureScopeClass();
    }

    public function getClosureCalledClass()
    {
        return $this->reflection->getClosureCalledClass();
    }
}