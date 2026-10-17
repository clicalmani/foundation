<?php

namespace Clicalmani\Core\Support\Facades;

use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

abstract class Facade 
{
    /**
     * Handle dynamic static calls to the facade.
     * 
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public static function __callStatic(string $method, array $arguments): mixed
    {
        try {
            $service = static::resolveFacadeService();

            if (null === $service) {
                return null;
            }

            return static::dispatch($service, $method, $arguments);
        } catch (ServiceNotFoundException $e) {
            return null;
        }
    }

    /**
     * Resolve the facade root service instance from the container.
     * 
     * @return mixed|null
     */
    protected static function resolveFacadeService() : mixed
    {
        $accessor = static::getFacadeAccessor();
        $container = app()->getContainer();

        if (!$accessor || !$container || !$container->has($accessor)) {
            return null;
        }

        return $container->get($accessor);
    }

    /**
     * Dispatch the call to the appropriate handler based on the service type.
     * 
     * @param mixed $service
     * @param string $method
     * @param array $args
     * @return mixed
     */
    protected static function dispatch(mixed $service, string $method, array $args) : mixed
    {
        if (method_exists($service, $method)) {

            return $service->{$method}(...$args);
        }
        
        return null;
    }

    /**
     * Get the registered name of the component inside the container.
     *
     * @return string
     */
    protected static function getFacadeAccessor() : string
    {
        return '';
    }

    protected static function getFacadeRoot() : ?object
    {
        return self::resolveFacadeService();
    }
}