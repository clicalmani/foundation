<?php
namespace Clicalmani\Core\Acme;

use Clicalmani\Core\Exceptions\ContainerDepencyException;
use Clicalmani\Core\Maker\Application;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServiceConfigurator;

class Container extends Application implements ContainerInterface
{
    /**
     * An array of the types that have been resolved.
     *
     * @var array<string, string>
     */
    protected $resolved = [];

    protected $app;

    private static $injected_interfaces = [];

    public function __construct()
    {
        $this->app = parent::getInstance();
    }

    public static function getInstance(?string $rootPath = null)
    {
        return new self;
    }

    public function set(string $id, ?string $class = null) : ?ServiceConfigurator
    {
        if (!isset($class) && !$this->resolved($id)) {
            $this->resolve($id);
            $class = $id;
            $id = $this->resolved[$id];
        }
        
        return $this->app->services->set($id, $class);
    }

    /**
     * @return (($id is class-string<C> ? ((B is 0|1 ? C|object : C|object|null)) : ((B is 0|1 ? object : object|null))))
     */
    public function get(string $id)
    {
        if ($this->resolved($id)) {
            $id = $this->resolved[$id];
        }
        
        return $this->builder()->get($id);
    }

    public function builder()
    {
        return \Clicalmani\Core\Providers\ContainerServiceProvider::get();
    }

    public function resolve(string $class) : void
    {
        $this->resolved[$class] = reference($class);
    }

    /**
     * Determine if the given class has been resolved.
     *
     * @param  string  $class
     * @return bool
     */
    public function resolved($class) : bool
    {
        return isset($this->resolved[$class]);
    }

    public function getClassIntance(string $class, bool $share = true)
    {
        if ($share && isset($this->resolved[$class])) {
            return $this->resolved[$class];
        }

        // Déjà connu du conteneur compilé (ex: service enregistré avant compile)
        if ($this->has($class)) {
            return $this->get($class);
        }

        $object = new $class(...$this->resolveArguments($class));

        return $share ? $this->resolved[$class] = $object : $object;
    }

    public function getImplementingClassInstance(string $interface, bool $share = true)
    {
        $class = isset(self::$injected_interfaces[$interface]) ? self::$injected_interfaces[$interface]: 
                    substr($interface, 0, strlen($interface) - 9);
        
        try {
            $this->set($class)->share($share);
            return $this->get($class);
        } catch (\Exception $e) {
            throw new ContainerDepencyException(sprintf("Interface %s is not instaciable; try resolving to class %s", $interface, $class));
        }
    }

    public static function injectInterfaces(array $interfaces) : void
    {
        self::$injected_interfaces = $interfaces;
    }

    public function has(string $id): bool
    {
        return $this->builder()->has($id);
    }

    public function getServiceIds()
    {
        return $this->builder()->getServiceIds();
    }

    private function resolveArguments(string $class): array
    {
        $constructor = (new \ReflectionClass($class))->getConstructor();
        if (!$constructor) return [];

        $args = [];

        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();

            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $dep = $type->getName();

                if ($this->has($dep)) {
                    $args[] = $this->get($dep);                       // FQCN ou alias
                } elseif ($this->has($slug = reference($dep))) {
                    $args[] = $this->get($slug);                      // id slugifié
                } elseif (interface_exists($dep)) {
                    $args[] = $this->getImplementingClassInstance($dep);
                } else {
                    $args[] = $this->getClassIntance($dep);           // classe concrète, récursif
                }
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } elseif ($type?->allowsNull()) {
                $args[] = null;
            } else {
                throw new ContainerDepencyException(
                    sprintf('Cannot resolve $%s of %s::__construct()', $param->getName(), $class)
                );
            }
        }

        return $args;
    }
}

function reference(string $class) : string
{
    return slugify(str_replace('\\', '.', $class));
}