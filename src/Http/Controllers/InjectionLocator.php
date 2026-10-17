<?php
namespace Clicalmani\Core\Http\Controllers;

use Clicalmani\Core\Acme\Container;
use Clicalmani\Routing\Route;

abstract class InjectionLocator implements InjectionResolverInterface
{
    protected object $instance;
    protected Container $container;
    protected ReflectorInterface $reflector;
    protected Route $route;

    public function __construct(protected ?string $class = null)
    {
        $this->container = Container::getInstance();
    }

    public function setType(string $class): static
    {
        $this->class = $class;
        return $this;
    }

    public function setReflection(ReflectorInterface $reflector): static
    {
        $this->reflector = $reflector;
        return $this;
    }

    public function setRoute(Route $route): static
    {
        $this->route = $route;
        return $this;
    }

    public function inject(array &$args, mixed $value, int $pos) : void
    {
        $args[$pos] = $value;
    }

    /**
     * Point d'entrée unifié : vérifie d'abord si le type correspond,
     * puis délègue à handle().
     */
    final public function resolve(
        \ReflectionParameter $parameter,
        ReflectorInterface $reflector,
        Route $route,
        array &$args,
        int $position
    ): bool {
        $this->setReflection($reflector)->setRoute($route);
        
        foreach ($this->getSupportedTypes($parameter) as $type) {
            $this->setType($type);

            try {
                if ($instance = $this->handle()) {
                    $this->inject($args, $instance, $position);
                    return true;
                }
            } catch (\Throwable $e) {
                // Laisse le resolver suivant tenter sa chance
                continue;
            }
        }

        return false;
    }

    /**
     * Liste des types que ce resolver peut tenter de résoudre.
     */
    protected function getSupportedTypes(\ReflectionParameter $parameter): array
    {
        $type = $parameter->getType();
        if (!$type || $type->isBuiltin()) return [];

        if ($type instanceof \ReflectionNamedType) {
            return $this->listTypes($type->getName());
        }

        if ($type instanceof \ReflectionUnionType) {
            return array_merge(...array_map(
                fn($t) => $this->listTypes($t->getName()),
                $type->getTypes()
            ));
        }

        return [];
    }

    protected function createInstance(): void
    {
        $this->instance = interface_exists($this->class)
            ? $this->container->getImplementingClassInstance($this->class, false)
            : $this->container->getClassIntance($this->class, false);
    }

    protected static function listTypes(string $class): array
    {
        return array_merge(
            [$class => $class],
            class_parents($class) ?: [],
            class_implements($class) ?: [],
            ['*' => '*']
        );
    }

    abstract public function handle(): ?object;
}