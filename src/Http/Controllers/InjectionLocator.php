<?php
namespace Clicalmani\Foundation\Http\Controllers;

use Clicalmani\Foundation\Acme\Container;
use Clicalmani\Routing\Route;

abstract class InjectionLocator
{
    /**
     * @var object
     */
    protected $instance;

    /**
     * @var \Clicalmani\Foundation\Acme\Container
     */
    protected $container;

    /**
     * @var \Clicalmani\Foundation\Http\Controllers\ReflectorInterface
     */
    protected $reflector;

    /**
     * @var \Clicalmani\Routing\Route
     */
    protected $route;

    public function __construct(protected ?string $class = null)
    {
        $this->container = Container::getInstance();
        if (isset($this->class)) $this->setType($this->class);
    }

    public function setType(string $class) : void
    {
        $this->class = $class;
    }

    public function setReflection(ReflectorInterface $reflector)
    {
        $this->reflector = $reflector;
    }

    public function setRoute(Route $route)
    {
        $this->route = $route;
    }

    public function inject(array &$args, mixed $value, int $pos) : void
    {
        $args[$pos] = $value;
    }

    protected function listTypes()
    {
        return [$this->class => $this->class]
             + class_parents($this->class)
             + class_implements($this->class);
    }

    protected function canHandle()
    {
        return in_array($this->class, $this->listTypes(), true);
    }

    protected function createInstance()
    {
        if (interface_exists($this->class)) {
            $this->instance = $this->container->getImplementingClassInstance($this->class, false);
        } else $this->instance = $this->container->getClassIntance($this->class, false);
    }

    abstract public function handle() : ?object;
}