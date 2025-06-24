<?php
namespace Clicalmani\Foundation\Http\Middlewares;

use Clicalmani\Foundation\Auth\AuthServiceProvider;
use Clicalmani\Foundation\Http\RedirectInterface;
use Clicalmani\Foundation\Http\RequestInterface;
use Clicalmani\Foundation\Http\ResponseInterface;

/**
 * Class JWTAuth
 * 
 * @package Clicalmani\Foundation
 * @author @Clicalmani\Foundation
 */
abstract class JWTAuth extends AuthServiceProvider
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Handler
     * 
     * @param \Clicalmani\Foundation\Http\RequestInterface $request Request object
     * @param \Clicalmani\Foundation\Http\ResponseInterface $response Response object
     * @param \Closure $next Next middleware function
     * @return \Clicalmani\Foundation\Http\ResponseInterface|\Clicalmani\Foundation\Http\RedirectInterface
     */
    public abstract function handle(RequestInterface $request, ResponseInterface $response, \Closure $next) : ResponseInterface|RedirectInterface;

    /**
     * Bootstrap
     * 
     * @return void
     */
    public function boot() : void
    {
        throw new \Exception(sprintf("%s::%s must been override; in %s at line %d", static::class, __METHOD__, __CLASS__, __LINE__));
    }
}
