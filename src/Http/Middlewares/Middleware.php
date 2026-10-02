<?php
namespace Clicalmani\Core\Http\Middlewares;

use Clicalmani\Core\Http\RedirectInterface;
use Clicalmani\Core\Http\RequestInterface;
use Clicalmani\Core\Http\ResponseInterface;
use Clicalmani\Routing\Group;

/**
 * Class Middleware
 * 
 * @package Clicalmani\Core
 * @author @Clicalmani\Core
 */
abstract class Middleware implements MiddlewareInterface
{
    /**
     * Global middlewares
     * 
     * @var array
     */
    protected static array $globals = [
        'api' => ['api'],
        'web' => ['web']
    ];
    
    /**
     * Bootstrap
     * 
     * @return void
     */
    public abstract function boot() : void;

    /**
     * Group routes
     * 
     * @return \Clicalmani\Routing\Group
     */
    public function group() : Group
    {
        return (new Group)->group(fn() => $this->boot());
    }

    /**
     * Inject middleware routes into the service container.
     * 
     * @param string $routes_file Without extension
     * @return void
     */
    protected function include(string $routes_file) : void
    {
        include_once routes_path("$routes_file.php");
    }

    /**
     * Append a middleware to the global middlewares list
     * 
     * @param string $middleware Middleware class name
     * @return void
     */
    public function append(string $middleware) : void
    {
        /**
         * TODO
         */
    }

    /**
     * Get global middlewares
     * 
     * @return array
     */
    public static function getGlobals() : array
    {
        /**
         * TODO
         */
        return [];
    }

    /**
     * Add middlewares to the web group
     * 
     * @param array $append Middlewares to append
     * @return void
     */
    public function web(array $append) : void
    {
        self::$globals['web'] = array_merge(self::$globals['web'], $append);
    }

    /**
     * Add middlewares to the api group
     * 
     * @param array $append Middlewares to append
     * @return void
     */
    public function api(array $append) : void
    {
        self::$globals['api'] = array_merge(self::$globals['api'], $append);
    }
}
