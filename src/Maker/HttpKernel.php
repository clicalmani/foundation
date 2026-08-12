<?php
namespace Clicalmani\Foundation\Maker;

use Clicalmani\Foundation\Http\Middlewares\Api;
use Clicalmani\Foundation\Http\Middlewares\Web;

/**
 * Class HttpKernel
 * 
 * Handles the HTTP execution lifecycle context, configuring web and API middleware stacks
 * alongside custom validation rules within the application core.
 * 
 * @package Clicalmani\Foundation\Maker
 * @author @clicalmani
 */
class HttpKernel extends Kernel
{
    /**
     * The application's global HTTP middleware stack groups.
     *
     * These middleware layers are executed dynamically based on the targeted route gateway.
     *
     * @var array
     */
    protected array $middleware = [

        /**
         * |-------------------------------------------------------------------
         * | Web Gateway
         * |-------------------------------------------------------------------
         * 
         * Web gateway middleware stack.
         * 
         * Register application-specific custom web middleware here.
         */
        'web' => [],

        /**
         * |-------------------------------------------------------------------
         * | API Gateway
         * |-------------------------------------------------------------------
         * 
         * API gateway middleware stack.
         * 
         * Register application-specific custom API middleware here.
         */
        'api' => []
    ];

    /**
     * The application's global HTTP validation rules stack.
     *
     * These custom rule validators can be invoked throughout the data validation process.
     *
     * @var array
     */
    protected array $custom_rules = [];

    /**
     * Boots the prerequisite HTTP components.
     * Merges internal core framework gateway interceptors with user-defined global middlewares.
     * 
     * @return void
     */
    public function boot(): void
    {
        $this->middleware = [
            /**
             * |------------------------------------------------------------------------------
             * | Register Web Middleware
             * |------------------------------------------------------------------------------
             * 
             * Configures web middleware stacks for global route interceptors.
             * The core Web middleware enforces state protection routines such as CSRF verification 
             * on non-safe HTTP methods (e.g., POST, PUT, DELETE).
             * 
             * @var class-string<\Clicalmani\Foundation\Http\Middlewares\Web>
             */
            'web' => array_merge(
                [
                    'web' => \Clicalmani\Foundation\Http\Middlewares\Web::class
                ], 
                array_merge($this->middleware['web'], Web::getGlobals())
            ), 

            /**
             * |------------------------------------------------------------------------------
             * | Register API Middleware
             * |------------------------------------------------------------------------------
             * 
             * Configures API middleware stacks for global stateless API route boundaries.
             * Requests hitting the automatic '/api' prefix route prefix groups are matched 
             * and processed through this specific layer.
             * 
             * @var class-string<\Clicalmani\Foundation\Http\Middlewares\Api>
             */
            'api' => array_merge(
                [
                    'api' => \Clicalmani\Foundation\Http\Middlewares\Api::class
                ], 
                array_merge($this->middleware['api'], Api::getGlobals())
            )
        ];
    }

    /**
     * Commits the compiled middleware pipelines and custom validation rules 
     * into the global application configuration storage.
     * 
     * @return void
     */
    public function register(): void
    {
        $http_config        = $this->app->config['http'];
        $http_config['web'] = $this->middleware['web'];
        $http_config['api'] = $this->middleware['api'];
        $http_config['custom_rules'] = $this->custom_rules;
        $this->app->config['http']   = $http_config;
    }
}