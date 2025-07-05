<?php
namespace Clicalmani\Foundation\Support\Facades;

use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

abstract class Facade 
{
    /**
     * PHP magic __callStatic
     * 
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public static function __callStatic($method, $args) : mixed
    {
        try {
            if ($accessor = get_called_class()::getFacadeAccessor() AND $container = app()->getContainer() AND $container->hash($accessor)) {
                
                $service = $container->get($accessor);
                
                if ( method_exists($service, $method) ) {

                    if ($service instanceof \Clicalmani\Foundation\Resources\View) {
                        if ($method === 'render') {
                            return view(...$args);
                        }

                        return view('')->{$method}(...$args);
                    }
                    
                    return $service->{$method}(...$args);
                }
                
                if ($service instanceof \Clicalmani\Foundation\Acme\Controller) {
                    $service = new \Clicalmani\Foundation\Http\Controllers\RequestController;
                    
                    if ( method_exists($service, $method) ) {
                        return $service->{$method}(...$args);
                    }
                }

                if ($service instanceof \Clicalmani\Foundation\Http\Response) {
                    return response();
                }

                if ($service instanceof \Clicalmani\Foundation\Acme\Configure) {
                    return match($method) {
                        'string' => (string)$service->get(...$args),
                        'integer' => (int)$service->get(...$args),
                        'array' => (array)$service->get(...$args),
                        'float' => (float)$service->get(...$args),
                        'boolean' => (bool)$service->get(...$args)
                    };
                }
            }

            return null;
        } catch (ServiceNotFoundException $e) {
            return null;
        }
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() : string
    {
        return '';
    }
}