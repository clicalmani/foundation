<?php
namespace Clicalmani\Foundation\Acme;

use Clicalmani\Foundation\Exceptions\ContainerDepencyException;
use Clicalmani\Foundation\Maker\Application;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServiceConfigurator;

class Container extends Application
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
        return \Clicalmani\Foundation\Providers\ContainerServiceProvider::get();
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
        $this->set($class)->share($share);
        return $this->get($class);
    }

    public function getInterfaceInstance(string $interface, bool $share = true)
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

    public function has(string $id)
    {
        return $this->builder()->has($id);
    }

    public function getServiceIds()
    {
        return $this->builder()->getServiceIds();
    }
}

function reference(string $class) : string
{
    return \Clicalmani\Foundation\Support\Facades\Str::slug(str_replace('\\', '.', $class));
}