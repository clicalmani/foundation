<?php 
namespace Clicalmani\Foundation\Providers;

abstract class RouteService 
{
    /**
     * Current route
     * 
     * @var \Clicalmani\Routing\Route|false
     */
    protected \Clicalmani\Routing\Route|false $route;

    /**
     * Request object
     * 
     * @var \Clicalmani\Foundation\Http\Request
     */
    protected $request;

    public function __construct()
    {
        $this->request = \Clicalmani\Foundation\Http\Request::current();
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
