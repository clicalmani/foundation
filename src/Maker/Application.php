<?php
namespace Clicalmani\Foundation\Maker;

use Composer\Autoload\ClassLoader;

/**
 * Make an application
 * 
 * @package Clicalmani\Foundation
 * @author Clicalmani\Foundation
 */
class Application
{
    /**
     * Instance
     * 
     * @var static
     */
    protected static $instance;

    /**
     * Application configuration
     * 
     * @var \Clicalmani\Foundation\Maker\Logic\Config
     */
    protected $config;

    /**
     * Application console
     * 
     * @var \Clicalmani\Console\Application
     */
    protected $console;

    public function __construct(private ?string $rootPath = null)
    {
        $this->config = new \Clicalmani\Foundation\Maker\Logic\Config;
        $paths = $this->config['paths'];
        $paths['root'] = $this->rootPath;
        $this->config['paths'] = $paths;
    }

    public static function getInstance(?string $rootPath)
    {
        if ( isset(static::$instance) ) return static::$instance;

        static::$instance = new self($rootPath);
        return static::$instance;
    }

    /**
     * Configuring a new Tonka application instance.
     *
     * @param  string|null  $rootPath
     * @return \Clicalmani\Foundation\Maker\ApplicationBuilder
     */
    public static function setup(?string $rootPath = null) : ApplicationBuilder
    {
        $rootPath = match (true) {
            is_string($rootPath) => $rootPath,
            default => static::inferRootPath(),
        };

        return (new ApplicationBuilder(static::getInstance($rootPath)))
                    ->withKernels();
    }

    public function handleRequest()
    {
        $this->boot();
        return \Clicalmani\Foundation\Http\Requests\RequestController::render();
    }

    public function handleCommands()
    {
        $this->console->make();
        $this->boot();
        $this->console->run();
    }

    /**
     * Infer the application's root directory from the environment.
     *
     * @return string
     */
    public static function inferRootPath()
    {
        return match (true) {
            isset($_ENV['APP_ROOT_PATH']) => $_ENV['APP_ROOT_PATH'],
            default => dirname(array_keys(ClassLoader::getRegisteredLoaders())[0]),
        };
    }

    public function provideMiddlewares()
    {
        $http = $this->config['http'];

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
        $http['web']['web'] = \Clicalmani\Foundation\Http\Middlewares\Web::class;

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
        $http['api']['api'] = \Clicalmani\Foundation\Http\Middlewares\Api::class;

        $this->config['http'] = $http;
    }

    public function addKernel(string $kernel)
    {
        $kernel = new $kernel($this);
        $kernel->boot();
        $kernel->register();
    }

    public function rootPath()
    {
        return $this->rootPath;
    }

    public function provideServices()
    {
        \Clicalmani\Foundation\Providers\ServiceProvider::provideServices($this->config['app']['providers']);
    }

    /**
     * Boot the application
     * 
     * @return void
     */
    public function boot() : void
    {
        $this->provideMiddlewares();
        $this->provideServices();
    }

    public function __get($name)
    {
        return match ($name) {
            'config' => $this->config,
            'console' => $this->console,
            default => null
        };
    }

    public function __set($name, $value)
    {
        return match ($name) {
            'config' => $this->config = $value,
            'console' => $this->console = $value
        };
    }
}
