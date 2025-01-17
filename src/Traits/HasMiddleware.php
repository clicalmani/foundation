<?php
namespace Clicalmani\Foundation\Traits;

trait HasMiddleware
{
    public function middleware(string $name) : static
    {
        $this->setMiddleware($name, '.*');
        return $this;
    }

    public function only(string $name) : void
    {
        $this->setMiddleware($this->pop(), "^(?:$name)$");
    }

    public function except(array $names) : void
    {
        $names = join('|', $names);
        $this->setMiddleware($this->pop(), "^((?!$names).)*$");
    }

    public function pop()
    {
        $names = array_keys($this->middleware);
        return array_pop($names);
    }
}