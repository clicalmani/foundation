<?php
namespace Clicalmani\Foundation\Container;

use Clicalmani\Foundation\Container\ContextualBindingBuilder;

class Container implements ContainerInterface
{
    protected array $bindings = [];
    protected array $instances = [];

    /**
     * Get the instance of the given type
     * 
     * @param string $abstract
     * @return mixed
     */
    public function get(string $abstract): mixed
    {
        return $this->make($abstract);
    }

    /**
     * Make a new instance of the given type
     * 
     * @param string $abstract
     * @param array $parameters
     * @return mixed
     */
    public function make(string $abstract, array $parameters = []): mixed
    {
        return $this->resolve($abstract, $parameters);
    }

    /**
     * Resolve the given type from the container
     * 
     * @param string $abstract
     * @param array $parameters
     * @return mixed
     */
    public function resolve(string $abstract, array $parameters = []): mixed
    {
        $concrete = $this->getConcrete($abstract);

        if ($this->isBuildable($concrete, $abstract)) {
            $object = $this->build($concrete);
        } else {
            $object = $this->make($concrete, $parameters);
        }

        return $object;
    }

    /**
     * Get the concrete type for the given abstract
     * 
     * @param string $abstract
     * @return mixed
     */
    public function getConcrete(string $abstract): mixed
    {
        if (!isset($this->bindings[$abstract])) {
            return $abstract;
        }

        return $this->bindings[$abstract]['concrete'];
    }

    /**
     * Check if the given abstract is shared
     * 
     * @param string $abstract
     * @return bool
     */
    public function isShared(string $abstract): bool
    {
        if (!isset($this->bindings[$abstract])) {
            return false;
        }

        return $this->bindings[$abstract]['shared'];
    }

    /**
     * Bind the given abstract to the container
     * 
     * @param string $abstract
     * @param mixed $concrete
     * @param bool $shared
     */
    public function bind(string $abstract, $concrete = null, bool $shared = false): void
    {
        $this->bindings[$abstract] = compact('concrete', 'shared');
    }

    /**
     * Bind the given abstract as a singleton
     * 
     * @param string $abstract
     * @param mixed $concrete
     */
    public function singleton(string $abstract, $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Check if the given abstract is bound
     * 
     * @param string $abstract
     * @return bool
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]);
    }

    /**
     * Bind the given instance to the container
     * 
     * @param string $abstract
     * @param mixed $instance
     */
    public function instance(string $abstract, $instance): void
    {
        $this->bindings[$abstract] = ['concrete' => $instance, 'shared' => true];
    }

    /**
     * Call the given callback with the given parameters
     * 
     * @param mixed $callback
     * @param array $parameters
     * @return mixed
     */
    public function call($callback, array $parameters = []): mixed
    {
        if (is_string($callback)) {
            $callback = $this->resolve($callback);
        }

        return $callback(...$parameters);
    }

    /**
     * Extend the given abstract with the given closure
     * 
     * @param string $abstract
     * @param mixed $closure
     */
    public function extend(string $abstract, $closure): void
    {
        if (!isset($this->bindings[$abstract])) {
            return;
        }

        if (!isset($this->bindings[$abstract]['extenders'])) {
            $this->bindings[$abstract]['extenders'] = [];
        }

        $this->bindings[$abstract]['extenders'][] = $closure;
    }

    /**
     * Forget the given abstract from the container
     * 
     * @param string $abstract
     */
    public function forget(string $abstract): void
    {
        unset($this->bindings[$abstract]);
    }

    /**
     * Get all the bindings
     * 
     * @return array
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * Get all the instances
     * 
     * @return array
     */
    public function getInstances(): array
    {
        return $this->instances;
    }

    /**
     * Get the alias for the given abstract
     * 
     * @param string $abstract
     * @return string
     */
    public function getAlias(string $abstract): string
    {
        if (!isset($this->bindings[$abstract])) {
            return $abstract;
        }

        if ($this->bindings[$abstract]['concrete'] === $abstract) {
            return $abstract;
        }

        return $this->getAlias($this->bindings[$abstract]['concrete']);
    }

    /**
     * Get the extenders for the given abstract
     * 
     * @param string $abstract
     * @return array
     */
    public function getExtenders(string $abstract): array
    {
        if (!isset($this->bindings[$abstract]['extenders'])) {
            return [];
        }

        return $this->bindings[$abstract]['extenders'];
    }

    /**
     * Extend the given abstract after resolving
     * 
     * @param string $abstract
     * @param mixed $closure
     */
    public function afterResolving(string $abstract, $closure): void
    {
        if (!isset($this->bindings[$abstract])) {
            return;
        }

        if (!isset($this->bindings[$abstract]['after_resolving'])) {
            $this->bindings[$abstract]['after_resolving'] = [];
        }

        $this->bindings[$abstract]['after_resolving'][] = $closure;
    }

    /**
     * Get the rebound callbacks for the given abstract
     * 
     * @param string $abstract
     * @return array
     */
    public function getReboundCallbacks(string $abstract): array
    {
        if (!isset($this->bindings[$abstract]['after_resolving'])) {
            return [];
        }

        return $this->bindings[$abstract]['after_resolving'];
    }

    /**
     * Rebound the given abstract
     * 
     * @param string $abstract
     */
    public function rebound(string $abstract): void
    {
        $instance = $this->make($abstract);

        foreach ($this->getReboundCallbacks($abstract) as $callback) {
            $callback($instance);
        }
    }

    /**
     * Wrap the given callback with the given parameters
     * 
     * @param mixed $callback
     * @param array $parameters
     * @return mixed
     */
    public function wrap($callback, array $parameters = []): mixed
    {
        return function () use ($callback, $parameters) {
            return $this->call($callback, $parameters);
        };
    }

    /**
     * Tag the given abstract with the given tags
     * 
     * @param string $abstracts
     * @param array|string $tags
     */
    public function tag(string $abstracts, array|string $tags): void
    {
        $tags = is_array($tags) ? $tags : [$tags];

        foreach ($tags as $tag) {
            if (!isset($this->bindings[$tag])) {
                $this->bindings[$tag] = [];
            }

            $this->bindings[$tag]['tags'][] = $abstracts;
        }
    }

    /**
     * Get the tagged abstracts for the given tag
     * 
     * @param string $tag
     * @return array
     */
    public function tagged(string $tag): array
    {
        if (!isset($this->bindings[$tag]['tags'])) {
            return [];
        }

        return $this->bindings[$tag]['tags'];
    }

    /**
     * Bind the given abstract if it is not already bound
     * 
     * @param string $abstract
     * @param mixed $concrete
     * @param bool $shared
     */
    public function bindIf(string $abstract, $concrete = null, bool $shared = false): void
    {
        if ($this->has($abstract)) {
            return;
        }

        $this->bind($abstract, $concrete, $shared);
    }

    /**
     * Get the contextual binding builder for the given concrete
     * 
     * @param mixed $concrete
     * @return mixed
     */
    public function when($concrete): mixed
    {
        return new ContextualBindingBuilder($this, $concrete);
    }

    /**
     * Get the factory closure for the given abstract
     * 
     * @param string $abstract
     * @return mixed
     */
    public function factory(string $abstract): mixed
    {
        return function () use ($abstract) {
            return $this->make($abstract);
        };
    }

    /**
     * Flush the given instance from the container
     * 
     * @param string $abstract
     */
    public function flushInstance(string $abstract): void
    {
        unset($this->instances[$abstract]);
    }

    /**
     * Flush the given resolved from the container
     * 
     * @param string $abstract
     */
    public function flushResolved(string $abstract): void
    {
        unset($this->resolved[$abstract]);
    }

    /**
     * Flush the given shared from the container
     * 
     * @param string $abstract
     */
    public function flushShared(string $abstract): void
    {
        unset($this->shared[$abstract]);
    }

    /**
     * Flush the given tagged from the container
     * 
     * @param string $tag
     */
    public function flushTagged(string $tag): void
    {
        unset($this->tagged[$tag]);
    }

    /**
     * Flush the bindings from the container
     * 
     * @return void
     */
    public function flushBindings(): void
    {
        $this->bindings = [];
    }

    /**
     * Flush the instances from the container
     * 
     * @return void
     */
    public function flushInstances(): void
    {
        $this->instances = [];
    }

    /**
     * Flush the extenders from the container
     * 
     * @return void
     */
    public function flushExtenders(): void
    {
        $this->extenders = [];
    }

    /**
     * Flush the rebound callbacks from the container
     * 
     * @return void
     */
    public function flushReboundCallbacks(): void
    {
        $this->reboundCallbacks = [];
    }

    /**
     * Flush the after resolving callbacks from the container
     * 
     * @return void
     */
    public function flushAfterResolvingCallbacks(): void
    {
        $this->afterResolvingCallbacks = [];
    }

    /**
     * Flush the tags from the container
     * 
     * @return void
     */
    public function flushTags(): void
    {
        $this->tags = [];
    }

    /**
     * Flush the when from the container
     * 
     * @return void
     */
    public function flushWhen(): void
    {
        $this->when = [];
    }

    /**
     * Flush the container
     * 
     * @return void
     */
    public function flush(): void
    {
        $this->flushBindings();
        $this->flushInstances();
        $this->flushExtenders();
        $this->flushReboundCallbacks();
        $this->flushAfterResolvingCallbacks();
        $this->flushTags();
        $this->flushWhen();
    }

    /**
     * Check if the given concrete is buildable
     * 
     * @param mixed $concrete
     * @param string $abstract
     * @return bool
     */
    protected function isBuildable(mixed $concrete, string $abstract): bool
    {
        return $concrete === $abstract || $concrete instanceof \Closure;
    }

    /**
     * Build the given concrete
     * 
     * @param mixed $concrete
     * @return mixed
     */
    protected function build(mixed $concrete): mixed
    {
        if ($concrete instanceof \Closure) {
            return $concrete($this);
        }

        $reflection = new \ReflectionClass($concrete);

        if (!$reflection->isInstantiable()) {
            throw new \Exception("{$concrete} is not instantiable");
        }

        $constructor = $reflection->getConstructor();

        if (is_null($constructor)) {
            return new $concrete;
        }

        $dependencies = $constructor->getParameters();
        $instances = $this->getDependencies($dependencies);

        return $reflection->newInstanceArgs($instances);
    }

    /**
     * Get the dependencies for the given parameters
     * 
     * @param array $parameters
     * @return array
     */
    protected function getDependencies(array $parameters): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $dependency = $parameter->getClass();

            if (is_null($dependency)) {
                $dependencies[] = $this->resolveNonClass($parameter);
            } else {
                $dependencies[] = $this->resolve($dependency->name);
            }
        }

        return $dependencies;
    }

    /**
     * Resolve the given non-class parameter
     * 
     * @param \ReflectionParameter $parameter
     * @return mixed
     */
    protected function resolveNonClass(\ReflectionParameter $parameter): mixed
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new \Exception("Unresolvable dependency resolving [{$parameter}] in class");
    }
}