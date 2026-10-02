<?php
namespace Clicalmani\Core\Traits;

/**
 * Trait HasMiddleware
 *
 * Provides fluent routing and controller middleware registration methods,
 * allowing conditional execution filtering based on route or method names.
 *
 * @package Clicalmani\Core\Traits
 * @author clicalmani
 */
trait HasMiddleware
{
    /**
     * Registers a middleware directive to apply globally across all actions.
     *
     * @param string $name Middleware directive name or class name.
     * @return static
     */
    public function middleware(string $name) : static
    {
        $this->setMiddleware($name, '.*');
        return $this;
    }

    /**
     * Scopes the most recently registered middleware to execute ONLY on the specified action or route name.
     *
     * @param string $name Target action or route name to restrict middleware execution to.
     * @return void
     */
    public function only(string $name) : void
    {
        $this->setMiddleware($this->pop(), "^(?:$name)$");
    }

    /**
     * Scopes the most recently registered middleware to execute on ALL actions EXCEPT the specified ones.
     *
     * @param array<int, string> $names List of action or route names to exclude from middleware execution.
     * @return void
     */
    public function except(array $names) : void
    {
        $names = join('|', $names);
        $this->setMiddleware($this->pop(), "^((?!$names).)*$");
    }

    /**
     * Pops and returns the key of the last registered middleware from the internal storage array.
     *
     * @return string|int|null The key of the last registered middleware, or null if the stack is empty.
     */
    public function pop()
    {
        $names = array_keys($this->middleware);
        return array_pop($names);
    }
}