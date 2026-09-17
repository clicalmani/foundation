<?php
namespace Clicalmani\Core\Http\Middlewares;

use Clicalmani\Core\Auth\AuthServiceProvider;
use Clicalmani\Core\Http\RedirectInterface;
use Clicalmani\Core\Http\RequestInterface;
use Clicalmani\Core\Http\ResponseInterface;

/**
 * Class JWTAuth
 * 
 * @package Clicalmani\Core
 * @author @Clicalmani\Core
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
     * @param \Clicalmani\Core\Http\RequestInterface $request Request object
     * @param \Clicalmani\Core\Http\ResponseInterface $response Response object
     * @param \Closure $next Next middleware function
     * @return \Clicalmani\Core\Http\ResponseInterface|\Clicalmani\Core\Http\RedirectInterface
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
