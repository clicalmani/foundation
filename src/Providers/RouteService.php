<?php 
namespace Clicalmani\Foundation\Providers;

/**
 * Class RouteService
 * 
 * Provisioning contract strategies for intercepting or redirecting browser traffic.
 * 
 * @package Clicalmani\Foundation\Providers
 * @author @clicalmani
 */
abstract class RouteService 
{
    /**
     * Active route model entity matching current application state.
     * 
     * @var \Clicalmani\Routing\Route|false
     */
    protected \Clicalmani\Routing\Route|false $route;

    /**
     * Active inbound HTTP request context instance pointer.
     * 
     * @var \Clicalmani\Foundation\Http\Request
     */
    protected ?\Clicalmani\Foundation\Http\Request $request;

    /**
     * RouteService constructor.
     * Captures and binds the active HTTP global request context state.
     */
    public function __construct()
    {
        $this->request = \Clicalmani\Foundation\Http\Request::current();
    }

    /**
     * Aborts the operational route matching context loop immediately.
     * 
     * @return void
     */
    public function abort(): void
    {
        $this->route = false;
    }

    /**
     * Triggers a specialized HTTP routing redirection execution sweep.
     * 
     * This method must be overriden by extending concrete subclasses to detail custom 
     * application traffic redirection behaviors.
     * 
     * @throws \Exception If invoked directly on a child implementation without a strict structural override.
     * @return void
     */
    public function redirect(): void
    {
        throw new \Exception(
            sprintf("%s::%s must be overriden. Thrown in %s at line %d", __CLASS__, __METHOD__, static::class, __LINE__)
        );
    }
}