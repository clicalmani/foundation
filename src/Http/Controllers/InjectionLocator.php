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

    protected function createInstance()
    {
        if (interface_exists($this->class)) {
            $this->instance = $this->container->getImplementingClassInstance($this->class, false);
        } else $this->instance = $this->container->getClassIntance($this->class, false);
    }

    protected static function listTypes(string $class): array
    {
        return [$class => $class]
            + class_parents($class)
            + class_implements($class)
            + ['*' => '*'];
    }

    abstract public function handle() : ?object;
}