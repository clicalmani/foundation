<?php
namespace Clicalmani\Core\Providers\Config;

use Clicalmani\Core\Providers\RouteService;

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
    public function redirect() : void
    {
        if ($this->route) {
            if ($this->route->isDirty()) {
                $this->route->redirect = $this->route->redirect ?? self::traceBack();
            }
            
            if (!\Clicalmani\Core\Support\Facades\Route::isApi() && $this->route->isGettable()) {
                session()->storeBackTrace(client_url());
            }
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
