<?php 
namespace Clicalmani\Foundation\Providers;

abstract class RouteService 
{
    /**
     * Current route
     * 
     * @var \Clicalmani\Routing\Route
     */
    protected $route;

    /**
     * Request object
     * 
     * @var \Clicalmani\Foundation\Http\Request
     */
    protected $request;

    public function __construct()
    {
        $this->request = new \Clicalmani\Foundation\Http\Request;
    }

    /**
     * Abort request
     * 
     * @return void
     */
    public function abort() : void
    {
        $this->route = false;
    }

    /**
     * @override
     */
    public function redirect()
    {
        throw new \Exception(sprintf("%s::%s must be overriden. Thrown in %s at line %d", __CLASS__, __METHOD__, static::class, __LINE__));
    }
}
