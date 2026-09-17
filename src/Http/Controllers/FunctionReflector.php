<?php
namespace Clicalmani\Core\Http\Controllers;

class FunctionReflector extends Reflector implements ReflectorInterface
{
    public function __invoke(mixed ...$args) : mixed
    {
        return $this->reflect->invoke(...$args);
    }
}