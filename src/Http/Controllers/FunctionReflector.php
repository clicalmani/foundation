<?php
namespace Clicalmani\Foundation\Http\Controllers;

class FunctionReflector extends Reflector implements ReflectorInterface
{
    public function __invoke(mixed ...$args) : mixed
    {
        return $this->reflect->invoke(...$args);
    }
}