<?php 
namespace Clicalmani\Foundation\Sandbox;

use App\Models\Client;
use Carbon\Carbon;
use Clicalmani\Foundation\Providers\RouteService;

/**
 * @package clicalmani/flesco 
 * @author @clicalmani
 */
class Service extends RouteService
{
    private Client $client;

    /**
     * Constructor
     * 
     * @param \Clicalmani\Routing\Route|false $route
     */
    public function __construct(protected \Clicalmani\Routing\Route|false $route)
    {
        parent::__construct();
        $this->client = Client::where()->first();
    }

    /**
     * Issue a redirect
     * 
     * @return void
     */
    public function redirect()
    {
        if (NULL !== $this->client) {

            $access = (int)$this->client->auth_access;

            if ($access > 0) {
                $access -= (int) Carbon::parse($this->client->auth_date)->diff(now())->days;
            }
            
            if ($access > 0) {
                $this->client->auth_access = $access;
                $this->client->auth_date = now();
                $this->client->save();
            } elseif ($this->route) $this->route->redirect = 303;
        } elseif ($this->route) $this->route->redirect = 303;
    }
}
