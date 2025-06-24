<?php
namespace Clicalmani\Foundation\Http\Middlewares;

use Clicalmani\Foundation\Http\RequestInterface;
use Clicalmani\Foundation\Http\ResponseInterface;

class Web extends Middleware
{
    /**
     * Handler
     * 
     * @param \Clicalmani\Foundation\Http\RequestInterface $request Request object
     * @param \Clicalmani\Foundation\Http\ResponseInterface $response Response object
     * @param \Closure $next Next middleware function
     * @return \Clicalmani\Foundation\Http\ResponseInterface|\Clicalmani\Foundation\Http\RedirectInterface
     */
    public function handle(RequestInterface $request, ResponseInterface $response, \Closure $next) : \Clicalmani\Foundation\Http\ResponseInterface|\Clicalmani\Foundation\Http\RedirectInterface
    {
        if (!in_array($request->getMethod(), ['get', 'options']) AND FALSE === $request->checkCSRFToken()) {
            return $response->forbiden();
        }

        return $next($request, $response);
    }

    /**
     * Bootstrap
     * 
     * @return void
     */
    public function boot() : void
    {
        include_once root_path(\Clicalmani\Foundation\Support\Facades\Config::route('web_handler'));
    }

    public function append(string $middleware): void
    {
        self::$globals['web'][] = $middleware;
    }

    public static function getGlobals(): array
    {
        return self::$globals['web'];
    }
}