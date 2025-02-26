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
     * @param \Closure $next Next middleware function
     * @return \Clicalmani\Foundation\Http\Response|\Clicalmani\Foundation\Http\RedirectInterface
     */
    public function handle(Request $request, Response $response, \Closure $next) : \Clicalmani\Foundation\Http\Response|\Clicalmani\Foundation\Http\RedirectInterface
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

    public function append(string $middleware): void
    {
        self::$globals['api'][] = $middleware;
    }

    public static function getGlobals(): array
    {
        return self::$globals['api'];
    }
}