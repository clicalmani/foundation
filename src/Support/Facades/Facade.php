<?php
namespace Clicalmani\Foundation\Support\Facades;

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
        if ($accessor = get_called_class()::getFacadeAccessor()) {
            $service = app()->getContainer()->get($accessor);

            if ( method_exists($service, $method) ) {
                return $service->{$method}(...$args);
            }
            
            if ($service instanceof \Clicalmani\Foundation\Acme\Controller) {
                $service = new \Clicalmani\Foundation\Http\Controllers\RequestController;
                
                if ( method_exists($service, $method) ) {
                    return $service->{$method}(...$args);
                }
            }
        }

        return null;
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