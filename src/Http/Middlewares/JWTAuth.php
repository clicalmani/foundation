<?php
namespace Clicalmani\Foundation\Http\Middlewares;

use Clicalmani\Foundation\Http\Requests\Request;
use Clicalmani\Foundation\Http\Response\Response;
use Clicalmani\Foundation\Container\SPL_Loader;
use Clicalmani\Foundation\Auth\AuthServiceProvider;

/**
 * Class JWTAuth
 * 
 * @package Clicalmani\Foundation
 * @author @Clicalmani\Foundation
 */
abstract class JWTAuth extends AuthServiceProvider
{
    /**
     * Service container
     * 
     * @var \Clicalmani\Foundation\Container\SPL_Loader
     */
    protected $container;

    public function __construct()
    {
        $this->container = new SPL_Loader;
        parent::__construct();
    }

    /**
     * Handler
     * 
     * @param \Clicalmani\Foundation\Http\Requests\Request $request Request object
     * @param \Clicalmani\Foundation\Http\Response\Response $response Response object
     * @param callable $next Next middleware function
     * @return int|false
     */
    protected abstract function handle(Request $request, Response $response, callable $next) : int|false;

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
