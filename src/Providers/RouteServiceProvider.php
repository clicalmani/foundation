<?php
namespace Clicalmani\Foundation\Providers;

use Clicalmani\Foundation\Http\Request;
use Clicalmani\Foundation\Support\Facades\Route;
use Clicalmani\Foundation\Support\Facades\Config;
use Clicalmani\Routing\Memory;
use Clicalmani\Routing\Record;
use Override;

/**
 * Class RouteServiceProvider
 * 
 * Provisions the application routing layer infrastructure, establishing CORS configuration headers, 
 * tracking route discovery sequences, executing CSRF defense states, and managing third-party 
 * navigation redirection handler middleware services.
 * 
 * @package Clicalmani\Foundation\Providers
 * @author @clicalmani
 */
class RouteServiceProvider extends ServiceProvider
{
    /**
     * URI prefix assigned to identify API entry endpoints.
     * 
     * @var string
     */
    protected string $api_prefix = 'api';

    /**
     * Leading separator character flag parsing parameterized dynamic route parameter.
     * 
     * @var string
     */
    protected string $parameter_prefix = ':';

    /**
     * Primary filesystem pathway location containing API gateway definitions.
     * 
     * @var string
     */
    protected string $api_handler = 'routes/api.php';

    /**
     * Primary filesystem pathway location containing public web page definitions.
     * 
     * @var string
     */
    protected string $web_handler = 'routes/web.php';

    /**
     * Internal runtime response cache storage parsed from active route callbacks.
     * 
     * @var mixed
     */
    private static mixed $response_data;

    /**
     * Map block capturing Cross-Origin Resource Sharing policy rules.
     * 
     * @var array|null
     */
    private static ?array $cors_settings = null;

    /**
     * Structure collection holding global routing driver properties.
     * 
     * @var array|null
     */
    private static ?array $route_settings = null;

    /**
     * Execution container callback handling custom parameter bindings.
     * 
     * @var callable|\Closure|null
     */
    private static mixed $route_binding_callback = null;
    
    /**
     * Envelopes route scanning phases, configuring context hooks and clearing temporary caches.
     * 
     * @param callable $callback Discovery script processing logic execution loop.
     * @return void
     */
    public function routes(callable $callback): void
    {
        Record::start('api');
        $this->setHeaders();

        if ( ! Route::isApi() ) {
            $this->storeCSRFToken();
        }
        
        $callback();

        Record::clear();
    }

    /**
     * Retrieves the operational API URI prefix.
     * 
     * @return string
     */
    public function getApiPrefix(): string
    {
        return $this->api_prefix;
    }

    /**
     * Retrieves the structural dynamic parameter identification prefix token.
     * 
     * @return string
     */
    public function getParameterPrefix(): string
    {
        return $this->parameter_prefix;
    }

    /**
     * Retrieves the relative pathway location targeting API route collections.
     * 
     * @return string
     */
    public function getApiHandler(): string
    {
        return $this->api_handler;
    }

    /**
     * Retrieves the relative pathway location targeting standard web route collections.
     * 
     * @return string
     */
    public function getWebHandler(): string
    {
        return $this->web_handler;
    }

    /**
     * Resolves and issues necessary CORS context response headers back to browser clients.
     * Intercepts HTTP preflight checks to guarantee proper protocol understanding matches config rules.
     * 
     * @return void
     */
    public function setHeaders(): void
    {
        if (isset(static::$cors_settings['allowed_origin'])) {
            header("Access-Control-Allow-Origin: " . static::$cors_settings['allowed_origin']);
        }
        if (isset(static::$cors_settings['allow_credentials'])) {
            header('Access-Control-Allow-Credentials: ' . static::$cors_settings['allow_credentials']);
        }
        if (isset(static::$cors_settings['max_age'])) {
            header('Access-Control-Max-Age: ' . static::$cors_settings['max_age']);
        }
    
        // Evaluate preflight requests containing specific query check instructions
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']) && isset(static::$cors_settings['allowed_methods'])) {
                header("Access-Control-Allow-Methods: " . join(',', (array) static::$cors_settings['allowed_methods']));         
            }
    
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']) && isset(static::$cors_settings['allowed_headers'])) {
                header("Access-Control-Allow-Headers: " . static::$cors_settings['allowed_headers']);
            }
                
            // Terminate preflight early with a clean status code indicating validation success
            response()->sendStatus(204);
            exit;
        }
    }

    /**
     * Compiles a cross-site request forgery defense token within active state session structures.
     * 
     * @return void
     */
    public function storeCSRFToken(): void
    {
        // Execute strictly inside real HTTP server lifecycles, bypassing console terminal runs
        if ( false === isConsoleMode() ) {
            if ( ! isset($_SESSION['csrf_token']) && class_exists('\Clicalmani\Foundation\Auth\CSRF') ) {
                $_SESSION['csrf_token'] = (new \Clicalmani\Foundation\Auth\CSRF())->getToken(); 
            }
        }
    }

    /**
     * Binds the request response parser context to resolve current authenticated user entities.
     * 
     * @param callable $callback Extraction mapping directive execution logic.
     * @return void
     */
    public static function responseHandler(callable $callback): void
    {
        static::$response_data = $callback( (new Request)->user() );
    }

    /**
     * Obtains the accumulated static response instance pointer data.
     * 
     * @return mixed
     */
    public static function getResponseData(): mixed
    {
        return static::$response_data;
    }

    /**
     * Assembles full file paths mapping authorized Third-Party Redirection Services (TPS).
     * 
     * @param int $service_level Target authorization depth verification scope index.
     * @return array Calculated class namespace path strings collection.
     */
    public static function getProvidedTPS(int $service_level = 0): array 
    {
        $tps = Config::bootstrap('tps')[$service_level] ?? [];

        if ($service_level === 0) {
            $tps[] = \Clicalmani\Foundation\Providers\Config\RedirectService::class;
        }

        return $tps;
    }

    /**
     * Iterates across active Third-Party Service hooks to execute secondary redirection routines.
     * 
     * @param mixed $route_response Internal context data generated by active controller routes.
     * @param int $service_level Target verification structural tier index level.
     * @return void
     */
    public static function fireTPS(mixed &$route_response, int $service_level = 0): void
    {
        foreach (self::getProvidedTPS($service_level) as $tps) {
            (new $tps($route_response ?? false))->redirect();
        }
    }

    /**
     * Obtains the designated core framework query mapping generator builder instance.
     * 
     * @return mixed
     */
    public function getDefaultBuilder(): mixed
    {
        return static::$route_settings['default_builder'] ?? null;
    }

    /**
     * Retrieves the list of architectural route compiler builders registered in the layout system.
     * 
     * @return mixed
     */
    public function getBuilders(): mixed 
    {
        return static::$route_settings['builders'] ?? null;
    }

    /**
     * Mutates or acquires current execution pointers targeting localized route context binders.
     * 
     * @param \Closure|null $callback Optional function to bind parameter model entities.
     * @return mixed Closure reference binding map if fetching, otherwise execution update statuses.
     */
    public static function routeBindingCallback(?\Closure $callback = null): mixed
    {
        if (null === $callback) {
            return static::$route_binding_callback;
        }
        return static::$route_binding_callback = $callback;
    }

    /**
     * Boots configurations, merging application routing setups with environment parameters 
     * while resetting global memory tables.
     * 
     * @return void
     */
    #[Override]
    public function boot(): void
    {
        static::$route_settings = require_once config_path('/routing.php');
        static::$cors_settings = require_once config_path('/cors.php');

        // Capture properties explicitly to respect downstream local overrides
        $provider = new \App\Providers\RouteServiceProvider();
        
        app()->config->set('route', array_merge(static::$route_settings ?? [], [
            'api_prefix'       => $provider->api_prefix,
            'parameter_prefix' => $provider->parameter_prefix,
            'api_handler'      => $provider->web_handler,
            'web_handler'      => $provider->web_handler,
            'default_builder'  => $provider->getDefaultBuilder(),
            'builders'         => $provider->getBuilders(),
            'cors'             => static::$cors_settings
        ]));

        // Include downstream helpers explicitly supporting global request evaluation methods
        require_once dirname(__DIR__, 3) . '/routing/src/functions.php';
        
        // Wipe and isolate initial static routing tracking containers
        Memory::setRoutes([
            'get'     => [], 
            'post'    => [],
            'options' => [],
            'delete'  => [],
            'put'     => [],
            'patch'   => []
        ]);
    }
}