<?php
namespace Clicalmani\Foundation\Http\Middlewares;

use Clicalmani\Foundation\Http\Request;
use Clicalmani\Foundation\Http\Response;

class Api extends Middleware
{
    /**
     * Handler
     * 
     * @param \Clicalmani\Foundation\Http\Request $request Request object
     * @param \Clicalmani\Foundation\Http\Response $response Response object
     * @param callable $next Next middleware function
     * @return int|false
     */
    public function handle(Request $request, Response $response, callable $next) : int|false
    {
        return $next($request, $response);
    }

    /**
     * Bootstrap
     * 
     * @return void
     */
    public function boot() : void
    {
        (new \Clicalmani\Foundation\Container\SPL_Loader)
            ->inject(fn() => root_path(\Clicalmani\Foundation\Support\Facades\Config::route('api_handler')));
    }
}