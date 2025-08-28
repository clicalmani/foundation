<?php
namespace Clicalmani\Foundation\Providers\Config;

use Clicalmani\Foundation\Providers\RouteService;

final class RedirectService extends RouteService
{
    /**
     * Constructor
     * 
     * @param \Clicalmani\Routing\Route|false $route
     */
    public function __construct(protected \Clicalmani\Routing\Route|false $route)
    {
        parent::__construct();
    }

    /**
     * Issue a redirect
     * 
     * @return void
     */
    public function redirect()
    {
        if ($this->route->isDirty()) {
            $this->route->redirect = 302;
        }
        
        if (!\Clicalmani\Foundation\Support\Facades\Route::isApi() && $this->route->isGettable()) {
            session()->storeBackTrace($this->route->uri());
        }
    }

    /**
     * Trace back
     * 
     * @return ?string
     */
    public static function traceBack() : ?string
    {
        return session()->retrieveBackTrace();
    }
}