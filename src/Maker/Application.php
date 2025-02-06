<?php
namespace Clicalmani\Foundation\Maker;

use Clicalmani\Foundation\Http\Response;
use Clicalmani\Psr7\NonBufferedBody;
use Clicalmani\Psr7\StatusCodeInterface;
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
     * Database configuration
     * 
     * @var array
     */
    protected $db_config;

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

    /**
     * File system
     * 
     * @var \Clicalmani\Foundation\FileSystem\FileSystem
     */
    protected $filesystem;

    /**
     * Response holder
     * 
     * @var \Psr\Http\Message\ResponseInterface
     */
    protected $response;

    public function __construct(private ?string $rootPath = null)
    {
        $this->config = new \Clicalmani\Foundation\Maker\Logic\Config;
        $paths = $this->config['paths'];
        $paths['root'] = $this->rootPath;
        $this->config['paths'] = $paths;
        
        $this->response = new Response(
            StatusCodeInterface::STATUS_OK,
            200,
            new NonBufferedBody
        );
    }

    public static function getInstance(?string $rootPath = null)
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
        $this->db_config = require config_path( '/database.php' );
        $this->boot();
        return \Clicalmani\Foundation\Http\Requests\RequestController::render();
    }

    public function handleCommands()
    {
        $this->console->make();
        $this->boot();
        $this->console->run();
    }

    public function getContainer()
    {
        return $this->classLoader;
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

    public function addKernel(string $kernel)
    {
        $kernel = new $kernel($this);
        $kernel->boot();
        $kernel->register();
    }

    /**
     * Root path
     * 
     * @return string|null
     */
    public function rootPath() : string|null
    {
        return $this->rootPath;
    }

    /**
     * App path
     * 
     * @param ?string $path
     * @return string
     */
    public function appPath(string $path = ''): string
    {
        return $this->rootPath . DIRECTORY_SEPARATOR . 'app' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Public path
     * 
     * @param ?string $path
     * @return string
     */
    public function publicPath(string $path = ''): string
    {
        return $this->rootPath . DIRECTORY_SEPARATOR . 'public' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Config path
     * 
     * @param ?string $path
     * @return string
     */
    public function configPath(string $path = ''): string
    {
        return $this->rootPath . DIRECTORY_SEPARATOR . 'config' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Bootstrap path
     * 
     * @param ?string $path
     * @return string
     */
    public function bootstrapPath(string $path = ''): string
    {
        return $this->rootPath . DIRECTORY_SEPARATOR . 'bootstrap' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Routes path
     * 
     * @param ?string $path
     * @return string
     */
    public function routesPath(string $path = ''): string
    {
        return $this->rootPath . DIRECTORY_SEPARATOR . 'routes' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Storage path
     * 
     * @param ?string $path
     * @return string
     */
    public function storagePath(string $path = ''): string
    {
        return $this->rootPath . DIRECTORY_SEPARATOR . 'storage' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Database path
     * 
     * @param ?string $path
     * @return string
     */
    public function databasePath(string $path = ''): string
    {
        return $this->rootPath . DIRECTORY_SEPARATOR . 'database' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Resources path
     * 
     * @param ?string $path
     * @return string
     */
    public function resourcesPath(string $path = ''): string
    {
        return $this->rootPath . DIRECTORY_SEPARATOR . 'resources' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Get the debug mode status
     * 
     * @return bool
     */
    public function getDebug(): bool
    {
        return $this->config['app']['debug'] ?? false;
    }

    /**
     * Get the application URL
     * 
     * @param string $path
     * @return string
     */
    public function getUrl(string $path = ''): string
    {
        $url = $this->config['app']['url'] ?? 'http://localhost';
        return rtrim($url, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Get the application locale
     * 
     * @return string
     */
    public function getLocale(): string
    {
        return $this->config['app']['locale'] ?? 'en';
    }

    /**
     * Get the application timezone
     * 
     * @return string
     */
    public function getTimezone(): string
    {
        return $this->config['app']['timezone'] ?? 'UTC';
    }

    /**
     * Get the session instance
     * 
     * @return \Clicalmani\Foundation\Http\Session\SessionHandler
     */
    public function session(): \Clicalmani\Foundation\Http\Session\SessionHandler
    {
        return \App\Providers\SessionServiceProvider::getDriver()::getInstance();
    }

    /**
     * Boot the application
     * 
     * @return void
     */
    public function boot() : void
    {
        $this->addKernel(\App\Http\Kernel::class);
        \Clicalmani\Foundation\Providers\ServiceProvider::provideServices($this->config['app']['providers']);
        // File system
        $this->filesystem = new \Clicalmani\Foundation\FileSystem\FilesystemManager($this);
    }

    public function __get($name)
    {
        return match ($name) {
            'config' => $this->config,
            'console' => $this->console,
            'database' => $this->db_config,
            'filesystem' => $this->filesystem,
            'response' => $this->response,
            default => null
        };
    }

    public function __set($name, $value)
    {
        return match ($name) {
            'config' => $this->config = $value,
            'console' => $this->console = $value,
            'database' => $this->db_config = $value,
        };
    }
}
