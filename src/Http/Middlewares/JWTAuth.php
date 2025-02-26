<?php
namespace Clicalmani\Foundation\Http\Middlewares;

use Clicalmani\Foundation\Http\Request;
use Clicalmani\Foundation\Http\Response;
use Clicalmani\Foundation\Container\SPL_Loader;
use Clicalmani\Foundation\Auth\AuthServiceProvider;
use Clicalmani\Foundation\Http\RedirectInterface;

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
     * @param \Clicalmani\Foundation\Http\Request $request Current request object
     * @param \Clicalmani\Foundation\Http\Response $response Http response
     * @param \Closure $next 
     * @return \Clicalmani\Foundation\Http\Response|\Clicalmani\Foundation\Http\RedirectInterface
     */
    public abstract function handle(Request $request, Response $response, \Closure $next) : Response|RedirectInterface;

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
