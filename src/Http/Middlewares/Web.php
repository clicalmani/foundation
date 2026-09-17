<?php
namespace Clicalmani\Core\Http\Middlewares;

use Clicalmani\Core\Http\RequestInterface;
use Clicalmani\Core\Http\ResponseInterface;

class Web extends Middleware
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
        if (!in_array($request->getMethod(), ['get', 'options']) AND FALSE === $request->checkCSRFToken()) {
            return $response->forbidden();
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
        include_once root_path(\Clicalmani\Core\Support\Facades\Config::route('web_handler'));
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