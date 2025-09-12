<?php
namespace Clicalmani\Foundation\Providers;

use Clicalmani\Foundation\Http\Request;
use Clicalmani\Foundation\Support\Facades\Route;
use Clicalmani\Foundation\Support\Facades\Config;
use Clicalmani\Routing\Memory;
use Clicalmani\Routing\Record;

/**
 * RouteServiceProvider class
 * 
 * @package Clicalmani\Foundation/flesco 
 * @author @Clicalmani\Foundation
 */
class RouteServiceProvider extends ServiceProvider
{
    /**
     * API prefix
     * 
     * @var string
     */
    protected $api_prefix = 'api';

    /**
     * Parameter prefix
     * 
     * @var string 
     */
    protected $parameter_prefix = ':';

    /**
     * Default api handler
     * 
     * @var string
     */
    protected $api_handler = 'routes/api.php';

    /**
     * Default web handler
     * 
     * @var string
     */
    protected $web_handler = 'routes/web.php';

    /**
     * Request response
     * 
     * @var mixed
     */
    private static $response_data;

    /**
     * CORS settings
     * 
     * @var array
     */
    private static $cors_settings;

    /**
     * Route settings
     * 
     * @var array
     */
    private static $route_settings;

    /**
     * Global route binging callback.
     * 
     * @var callable
     */
    private static $route_binding_callback;
    
    /**
     * Initialize route service
     * 
     * @param callable $callback
     */
    public function routes(callable $callback)
    {
        Record::start('api');
        
        if ( Route::isApi() ) {
            $this->setHeaders();
        } else $this->storeCSRFToken();
        
        $callback();

        Record::clear();
    }

    /**
     * Get api prefix
     * 
     * @return string
     */
    public function getApiPrefix()
    {
        return $this->api_prefix;
    }

    /**
     * Get parameter prefix
     * 
     * @return string
     */
    public function getParameterPrefix()
    {
        return $this->parameter_prefix;
    }

    /**
     * Get api handler
     * 
     * @return string
     */
    public function getApiHandler()
    {
        return $this->api_handler;
    }

    /**
     * Get web handler
     * 
     * @return string
     */
    public function getWebHandler()
    {
        return $this->web_handler;
    }

    /**
     * Set response headers
     * 
     * @return void
     */
    public function setHeaders()
    {
        if ( isset($_SERVER['HTTP_ORIGIN']) ) {
            header("Access-Control-Allow-Origin: " . static::$cors_settings['allowed_origin']);
            header('Access-Control-Allow-Credentials: ' . static::$cors_settings['allow_credentials']);
            header('Access-Control-Max-Age: ' . static::$cors_settings['max_age']);
        }
    
        /**
         * |-------------------------------------------------------------------
         * |                ***** Preflight Routes *****
         * |-------------------------------------------------------------------
         * 
         * API Request is composed of preflight request and request.
         * Prefilght request is meant to check wether the CORS protocol is understood
         */
        if (@ $_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
                header("Access-Control-Allow-Methods: " . join(',', static::$cors_settings['allowed_methods']));         
    
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
                header("Access-Control-Allow-Headers: " . static::$cors_settings['allowed_headers']);
                
                // Preflight
                response()->sendStatus(204);
                exit;
        }
    }

    /**
     * Store CSRF token
     * 
     * @return void
     */
    public function storeCSRFToken() : void
    {
        // Escape console mode
        if ( FALSE == isConsoleMode() ) {
            // Generate CSRF token and Store it in $_SESSION global variable
            if ( ! isset($_SESSION['csrf_token']) ) {
                $_SESSION['csrf_token'] = with ( new \Clicalmani\Foundation\Auth\CSRF )->getToken(); 
            }
        }
    }

    /**
     * Request response handler
     * 
     * @param callable $callback
     * @return void
     */
    public static function responseHandler(callable $callback) : void
    {
        static::$response_data = $callback( (new Request)->user() );
    }

    /**
     * Get response data
     * 
     * @return mixed
     */
    public static function getResponseData() : mixed
    {
        return static::$response_data;
    }

    /**
     * Get provided third party route services
     * 
     * @param string $service_type
     * @return array
     */
    public static function getProvidedTPS(int $service_level = 0) : array 
    {
        $tps = Config::bootstrap('tps')[$service_level] ?? [];

        if ($service_level === 0) {
            $tps[] = \Clicalmani\Foundation\Providers\Config\RedirectService::class;
        }

        return $tps;
    }

    /**
     * Fire third party services
     * 
     * @param mixed $response Request response
     * @return void
     */
    public static function fireTPS(mixed &$route_response, int $service_level = 0) : void
    {
        foreach (self::getProvidedTPS($service_level) as $tps) {
            with( new $tps($route_response ?? false) )->redirect();
        }
    }

    /**
     * Get default builder
     * 
     * @return mixed
     */
    public function getDefaultBuilder() : mixed
    {
        return @static::$route_settings['default_builder'];
    }

    /**
     * Get builders
     * 
     * @return mixed
     */
    public function getBuilders() : mixed 
    {
        return @static::$route_settings['builders'];
    }

    /**
     * Get or set route binding callback.
     * 
     * @return mixed
     */
    public static function routeBindingCallback(?\Closure $callback = null) : mixed
    {
        if (NULL === $callback) return static::$route_binding_callback;
        return static::$route_binding_callback = $callback;
    }

    public function boot(): void
    {
        static::$route_settings = require_once config_path('/routing.php');
        static::$cors_settings = require_once config_path('/cors.php');

        require_once dirname(__DIR__, 3) . '/routing/src/functions.php';
        
        Memory::setRoutes(
            [
                'get'     => [], 
                'post'    => [],
                'options' => [],
                'delete'  => [],
                'put'     => [],
                'patch'   => []
            ]
        );
    }
}
