<?php
namespace Clicalmani\Core\Http\Middlewares;

use Clicalmani\Core\Http\RequestInterface;
use Clicalmani\Core\Http\ResponseInterface;

class Api extends Middleware
{
    /**
     * Handler
     * 
     * @param \Clicalmani\Core\Http\RequestInterface $request Request object
     * @param \Clicalmani\Core\Http\ResponseInterface $response Response object
     * @param \Closure $next Next middleware function
     * @return \Clicalmani\Core\Http\ResponseInterface|\Clicalmani\Core\Http\RedirectInterface
     */
    public function handle(RequestInterface $request, ResponseInterface $response, \Closure $next) : \Clicalmani\Core\Http\ResponseInterface|\Clicalmani\Core\Http\RedirectInterface
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
        include_once root_path(\Clicalmani\Core\Support\Facades\Config::route('api_handler'));
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