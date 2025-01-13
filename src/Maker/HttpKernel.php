<?php
namespace Clicalmani\Foundation\Maker;

class HttpKernel extends Kernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected array $middleware = [

        /**
         * |-------------------------------------------------------------------
         * |                          Web Gateway
         * |-------------------------------------------------------------------
         * 
         * Web gateway middleware stack
         * 
         * Register here your custom middlewares for web gateway.
         */
        'web' => [],

        /**
         * |-------------------------------------------------------------------
         * |                          API Gateway
         * |-------------------------------------------------------------------
         * 
         * API gateway middleware stack
         * 
         * Register here your custom middlewares for api gateway.
         */
        'api' => []
    ];

    /**
     * The application's global HTTP validator stack.
     *
     * These validators can be invoked anywhere in your application.
     *
     * @var array
     */
    protected array $validator = [];

    public function boot(): void
    {
        $this->middleware = [
            /**
             * |------------------------------------------------------------------------------
             * | Register web middleware
             * |------------------------------------------------------------------------------
             * 
             * Web middleware is registered here for global access and usage in the application
             * for web routes. Web middleware is used to check CSRF token for non GET and OPTIONS
             * requests.
             * 
             * @var \Clicalmani\Foundation\Http\Middlewares\Web
             */
            'web' => array_merge([
                'web' => \Clicalmani\Foundation\Http\Middlewares\Web::class], 
                $this->middleware['web']
            ), 

            /**
             * |------------------------------------------------------------------------------
             * | Register api middleware
             * |------------------------------------------------------------------------------
             * 
             * API middleware is registered here for global access and usage in the application
             * for API routes. Each route will have a /api prfix will be handled by this middleware.
             * However, /api prefix will be added to the route automatically.
             * 
             * @var \Clicalmani\Foundation\Http\Middlewares\Api
             */
            'api' => array_merge([
                'api' => \Clicalmani\Foundation\Http\Middlewares\Api::class], 
                $this->middleware['api']
            )
        ];
    }

    public function register(): void
    {
        $http_config = $this->app->config['http'];
        $http_config['web'] = $this->middleware['web'];
        $http_config['api'] = $this->middleware['api'];
        $http_config['validators'] = $this->validator;
        $this->app->config['http'] = $http_config;
    }
}