<?php
namespace Clicalmani\Foundation\Maker;

use Clicalmani\Foundation\Http\Request;
use Clicalmani\Foundation\Http\Response;
use Clicalmani\Foundation\Support\Facades\Arr;
use Clicalmani\Psr\NonBufferedBody;
use Clicalmani\Psr\StatusCodeInterface;
use Composer\Autoload\ClassLoader;
use Symfony\Component\DependencyInjection\Loader\Configurator\DefaultsConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServiceConfigurator;

use function Clicalmani\Foundation\Acme\reference;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

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
     * @var \Clicalmani\Foundation\Filesystem\FileSystem
     */
    protected $filesystem;

    /**
     * Response holder
     * 
     * @var \Psr\Http\Message\ResponseInterface
     */
    protected $response;

    /**
     * View shared data
     * 
     * @var array|callable
     */
    protected $viewSharedData;

    /**
     * Console commands
     * 
     * @var array
     */
    private $commands = [];

    /**
     * Container instance
     * 
     * @var \Clicalmani\Foundation\Acme\Container
     */
    private $container;

    private $cumulative_time_listeners = [];

    /**
     * Container services
     * 
     * @var \Symfony\Component\DependencyInjection\Loader\Configurator\ServiceConfigurator|
     * \Symfony\Component\DependencyInjection\Loader\Configurator\DefaultsConfigurator|null
     */
    protected ServiceConfigurator|DefaultsConfigurator $services;

    public function __construct(private ?string $rootPath = null)
    {
        $this->config = new \Clicalmani\Foundation\Acme\Configure;
        $paths = $this->config['paths'];
        $paths['root'] = $this->rootPath;
        $this->config['paths'] = $paths;
        
        $this->response = (new Response(
            'Ok',
            StatusCodeInterface::STATUS_OK,
        ))->withBody(new NonBufferedBody);

        $this->commands = \Clicalmani\Console\Kernel::$kernel;
    }

    public static function getInstance(?string $rootPath = null)
    {
        if ( isset(static::$instance) ) return static::$instance;

        return static::$instance = new self($rootPath);
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
        $this->db_config = require_once config_path( '/database.php' );
        return \Clicalmani\Foundation\Support\Facades\RequestController::render();
    }

    public function handleCommands()
    {
        $this->db_config = require_once config_path( '/database.php' );
        $this->console->make();
        $this->boot();
        $this->console->run();
    }

    public function getContainer()
    {
        return \Clicalmani\Foundation\Providers\ContainerServiceProvider::get();
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
     * Get app mode
     * 
     * @return string
     */
    public function env(): string
    {
        return $this->config('app.env', 'production');
    }

    /**
     * Get the debug mode status
     * 
     * @return bool
     */
    public function getDebug(): bool
    {
        return $this->config('app.debug', 'false');
    }

    /**
     * Get the application URL
     * 
     * @param string $path
     * @return string
     */
    public function getUrl(string $path = ''): string
    {
        $url = $this->config('app.url', 'http://localhost');
        return rtrim($url, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Get the application locale
     * 
     * @return string
     */
    public function getLocale(): string
    {
        return $this->config('app.locale', 'en');
    }

    /**
     * Get the application timezone
     * 
     * @return string
     */
    public function getTimezone(): string
    {
        return $this->config('app.timezone', 'UTC');
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
        $this->filesystem = new \Clicalmani\Foundation\Filesystem\FilesystemManager($this);
    }

    /**
     * Get config
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function config(string $key, mixed $default = '') : mixed
    {
        return Arr::get($this->config, $key, $default);
    }

    /**
     * Set view shared data
     * 
     * @param array $data
     */
    public function viewSharedData(array|callable|null $data = null) : array
    {
        if (isset($data)) $this->viewSharedData = $data;
        else {
            if ( is_array($this->viewSharedData)) return $this->viewSharedData;
            elseif ( is_callable($this->viewSharedData) ) return call($this->viewSharedData, Request::getcurrent() ?? new Request);
        }

        return [];
    }

    /**
     * Get or add console commands
     * 
     * @param array $new_commands
     */
    public function commands(array $new_commands = [])
    {
        if ( !empty($new_commands) ) $this->commands += $new_commands;
        return $this->commands;
    }

    public function initServices(DefaultsConfigurator $services)
    {
        $this->services = $services;
        return $this;
    }

    /**
     * Add a service to the application
     * 
     * @param string $key
     * @param array $data The second element of the service data may be an anonymous function or an invokable class. 
     * The __invoke method receives the service configurator as its first argument and the application instance as its second argument.
     */
    public function addService(string $key, array $data) : void
    {
        $this->coreServices = [
            static function() use($key, $data) {
                return [
                    $key => array_map(static fn($item) => $item, $data)
                ];
            }, 
            ...$this->coreServices
        ];
    }

    public function getServices()
    {
        return $this->services;
    }

    public function setTimeTracker(string $event, $handler)
    {
        $this->cumulative_time_listeners[$event][] = $handler;
    }

    public function getTimeTracker()
    {
        return $this->cumulative_time_listeners;
    }

    public function __get($name)
    {
        return match ($name) {
            'config' => $this->config,
            'console' => $this->console,
            'database' => $this->db_config,
            'filesystem' => $this->filesystem,
            'response' => $this->response,
            'container' => $this->container,
            default => null
        };
    }

    public function __set($name, $value)
    {
        return match ($name) {
            'config' => $this->config = $value,
            'console' => $this->console = $value,
            'database' => $this->db_config = $value,
            'response' => $this->response = $value
        };
    }

    public function registerCoreContainerServices() : void
    {
        if ($this->services) {
            foreach ($this->coreServices as $key => $value) {
                
                if (is_callable($value)) {
                    $value = $value();
                    $key = key($value);
                    @[$class, $config] = $value[$key];
                    $value = ['class' => $class, 'config' => $config ?? null];
                }

                $this->services = $this->services->set($key, $value['class']);

                if ($config = @$value['config']) {
                    if ($config instanceof \Closure) {
                        $config($this->services, $this);
                    } else {
                        with (new $config)($this->services);
                    }
                }
            }
            
            $this->services->set('view', \Clicalmani\Foundation\Resources\View::class);
        }
    }

    public function dependency(string $func, string $arg)
    {
        return match ($func) {
            'service' => service($arg),
            'param' => param($arg),
            'reference' => reference($arg),
            default => throw new \InvalidArgumentException("Unknown dependency function: $func"),
        };
    }

    private array $coreServices = [
        'logger' => ['class' => \Clicalmani\Foundation\Acme\Logger::class],
        'str' => ['class' => \Clicalmani\Foundation\Acme\Stringable::class],
        'router' => ['class' => \Clicalmani\Foundation\Acme\Router::class],
        'array' => ['class' => \Clicalmani\Foundation\Acme\Arrayable::class],
        'env' => ['class' => \Clicalmani\Foundation\Acme\Environment::class],
        'config' => ['class' => \Clicalmani\Foundation\Acme\Configure::class],
        'console' => ['class' => \Clicalmani\Foundation\Acme\Console::class],
        'response' => ['class' => \Clicalmani\Foundation\Http\Response::class, 'config' => \Clicalmani\Foundation\Providers\Config\ResponseConfig::class],
        'storage' => ['class' => \Clicalmani\Foundation\Acme\StorageManager::class],
        'controller' => ['class' => \Clicalmani\Foundation\Acme\Controller::class],
        'func' => ['class' => \Clicalmani\Foundation\Acme\Invokable::class],
        'database' => ['class' => \Clicalmani\Foundation\Acme\Database::class],
        'view' => ['class' => \Clicalmani\Foundation\Resources\View::class],

        /**
         * |----------------------------------------------------------
         * | Namespace Services
         * |----------------------------------------------------------
         */
        '*.request' => ['class' => \Clicalmani\Foundation\Http\Controllers\InjectRequest::class],
        '*.resource' => ['class' => \Clicalmani\Foundation\Http\Controllers\InjectResource::class],
        '*.mailer' => ['class' => \Clicalmani\Foundation\Mail\InjectMailer::class]
    ];
}
