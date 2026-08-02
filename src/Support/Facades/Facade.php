<?php

namespace Clicalmani\Foundation\Support\Facades;

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
    public static function __callStatic($method, $args) : mixed
    {
        try {
            $service = static::resolveFacadeService();

            if (null === $service) {
                return null;
            }

            return static::dispatch($service, $method, $args);
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
            if ($service instanceof \Clicalmani\Foundation\Resources\View) {
                return static::callView($method, $args);
            }

            if ($service instanceof \Clicalmani\Foundation\Acme\Model) {
                logger()->info('Elegant');
            }

            return $service->{$method}(...$args);
        }

        if ($service instanceof \Clicalmani\Foundation\Acme\Controller) {
            return static::callController($method, $args);
        }

        if ($service instanceof \Clicalmani\Foundation\Http\Response) {
            return response();
        }

        if ($service instanceof \Clicalmani\Foundation\Acme\Configure) {
            return static::callConfigure($service, $method, $args);
        }

        return null;
    }

    /**
     * Forward dynamic calls to the View instance.
     * 
     * @param string $method
     * @param array $args
     * @return mixed
     */
    protected static function callView(string $method, array $args) : mixed
    {
        if ($method === 'render') {
            return view(...$args);
        }

        return view('')->{$method}(...$args);
    }

    /**
     * Forward dynamic calls to the RequestController instance.
     * 
     * @param string $method
     * @param array $args
     * @return mixed
     */
    protected static function callController(string $method, array $args) : mixed
    {
        $controller = new \Clicalmani\Foundation\Http\Controllers\RequestController;

        if (method_exists($controller, $method)) {
            return $controller->{$method}(...$args);
        }

        return null;
    }

    /**
     * Cast configuration values retrieved from the Configure instance.
     * 
     * @param mixed $service
     * @param string $method
     * @param array $args
     * @return mixed
     */
    protected static function callConfigure(mixed $service, string $method, array $args) : mixed
    {
        return match($method) {
            'string'  => (string) $service->get(...$args),
            'integer' => (int) $service->get(...$args),
            'array'   => (array) $service->get(...$args),
            'float'   => (float) $service->get(...$args),
            'boolean' => (bool) $service->get(...$args),
            default   => null,
        };
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
}