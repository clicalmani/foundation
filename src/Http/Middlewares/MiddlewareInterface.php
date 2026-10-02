<?php
namespace Clicalmani\Core\Http\Middlewares;

use Clicalmani\Core\Http\RedirectInterface;
use Clicalmani\Core\Http\RequestInterface;
use Clicalmani\Core\Http\ResponseInterface;

interface MiddlewareInterface
{
    /**
     * Handler
     * 
     * @param RequestInterface $request Request object
     * @param ResponseInterface $response Response object
     * @param \Closure $next Next middleware function
     * @return ResponseInterface|RedirectInterface
     */
    public function handle(RequestInterface $request, ResponseInterface $response, \Closure $next) : ResponseInterface|RedirectInterface;
}