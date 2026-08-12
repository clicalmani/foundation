<?php
namespace Clicalmani\Foundation\Maker;

use Clicalmani\Foundation\Acme\Container;
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
 * Class Application
 * 
 * Central Core (Kernel/App Container) for the Tonka framework.
 * Manages the application lifecycle (HTTP requests, CLI commands), global configuration,
 * physical path resolution, and dependency injection via Symfony DI.
 * 
 * @package Clicalmani\Foundation
 * @author @clicalmani
 */
class Application
{
    /**
     * Unique application Singleton instance.
     * 
     * @var static|null
     */
    protected static $instance = null;

    /**
     * Raw database configuration array.
     * 
     * @var array
     */
    protected array $db_config = [];

    /**
     * Application configuration manager.
     * 
     * @var \Clicalmani\Foundation\Acme\Configure
     */
    protected $config;

    /**
     * Console application instance for CLI command execution.
     * 
     * @var \Clicalmani\Console\Application
     */
    protected $console;

    /**
     * Filesystem management component.
     * 
     * @var \Clicalmani\Foundation\Filesystem\FileSystem
     */
    protected $filesystem;

    /**
     * Default HTTP response structure (PSR-7).
     * 
     * @var \Psr\Http\Message\ResponseInterface
     */
    protected $response;

    /**
     * Globally shared data for all rendered views.
     * 
     * @var array|callable|null
     */
    protected $viewSharedData;

    /**
     * State indicator to prevent executing the `viewSharedData` callable multiple times.
     * 
     * @var bool
     */
    protected bool $viewDataShared = false;

    /**
     * List of registered Console commands.
     * 
     * @var array
     */
    private array $commands = [];

    /**
     * Core and custom service definitions map.
     * 
     * @var array<string, ServiceDefinition>
     */
    private array $coreServices = [];

    /**
     * Application dependency injection container instance.
     * 
     * @var \Clicalmani\Foundation\Acme\Container|null
     */
    private $container;

    /**
     * Time-tracking event listeners registered for performance profiling.
     * 
     * @var array<string, array<callable>>
     */
    private $cumulative_time_listeners = [];

    /**
     * Symfony service configurator used during container compilation.
     * 
     * @var ServiceConfigurator|DefaultsConfigurator|null
     */
    protected ServiceConfigurator|DefaultsConfigurator|null $services = null;

    /**
     * Initializes a new application instance and pre-configures the core environment.
     * The constructor is protected to ensure Singleton pattern enforcement.
     * 
     * @param string|null $rootPath Absolute application root path.
     */
    protected function __construct(private ?string $rootPath = null)
    {
        // Initialize configuration and register fundamental paths
        $this->config  = new \Clicalmani\Foundation\Acme\Configure;
        $paths         = $this->config['paths'];
        $paths['root'] = $this->rootPath;
        $this->config['paths'] = $paths;
        
        // Prepare a blank, default non-buffered (streaming/PSR) HTTP response
        $this->response = (new Response(
            'Ok',
            StatusCodeInterface::STATUS_OK,
        ))->withBody(new NonBufferedBody);
        
        // Load the console command registry and core framework service definitions
        $this->commands     = \Clicalmani\Console\Kernel::$kernel;
        $this->coreServices = $this->defaultServiceDefinitions();
    }

    /**
     * Retrieves the single application instance (Singleton Pattern).
     * 
     * @param string|null $rootPath
     * @return static
     */
    public static function getInstance(?string $rootPath = null)
    {
        if ( isset(static::$instance) ) return static::$instance;

        return static::$instance = new self($rootPath);
    }

    /**
     * Fluid entry point (Fluent API) to configure and initialize the application.
     * Automatically infers the root path and boots required Kernels.
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

    /**
     * Handles the full lifecycle of an incoming HTTP request.
     * Loads the database configuration, boots service providers, and delegates to the router.
     * 
     * @return mixed Render output from the matching controller or route (HTML/JSON/Response).
     */
    public function handleRequest()
    {
        $this->config->set('database', require_once config_path( '/database.php' ));
        $this->boot();
        return \Clicalmani\Foundation\Support\Facades\RequestController::render();
    }

    /**
     * Handles the application execution lifecycle in CLI mode.
     * Initializes the console application, boots service providers, and runs the command.
     * 
     * @return void
     */
    public function handleCommands()
    {
        $this->config->set('database', require_once config_path( '/database.php' ));
        $this->console->make();
        $this->boot();
        $this->console->run();
    }

    /**
     * Retrieves the active service container instance with strict PSR-11 typing.
     * 
     * @return \Psr\Container\ContainerInterface
     */
    public function getContainer()
    {
        return \Clicalmani\Foundation\Providers\ContainerServiceProvider::get();
    }

    /**
     * Tries to guess the absolute project root path.
     * Looks up environment variables or checks Composer's ClassLoader mapping location.
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

    /**
     * Instantiates, boots, and registers a specific Kernel (HTTP or Console) into the application.
     * 
     * @param class-string $kernel Fully qualified class name (FQCN) of the Kernel to load.
     * @return void
     */
    public function addKernel(string $kernel)
    {
        $kernel = new $kernel($this);
        $kernel->boot();
        $kernel->register();
    }

    /**
     * Returns the absolute application root path.
     * 
     * @return string|null
     */
    public function rootPath() : string|null
    {
        return $this->rootPath;
    }

    /**
     * Generates an absolute path to the 'app/' directory.
     * 
     * @param string $path Optional sub-path (e.g., 'Http/Controllers')
     * @return string
     */
    public function appPath(string $path = ''): string
    {
        return $this->rootPath . DIRECTORY_SEPARATOR . 'app' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Generates an absolute path to the public assets directory ('public/').
     * 
     * @param string $path
     * @return string
     */
    public function publicPath(string $path = ''): string
    {
        return $this->rootPath . DIRECTORY_SEPARATOR . 'public' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Generates an absolute path to the configuration files ('config/').
     * 
     * @param string $path
     * @return string
     */
    public function configPath(string $path = ''): string
    {
        return $this->rootPath . DIRECTORY_SEPARATOR . 'config' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Generates an absolute path to the bootstrap initialization scripts ('bootstrap/').
     * 
     * @param string $path
     * @return string
     */
    public function bootstrapPath(string $path = ''): string
    {
        return $this->rootPath . DIRECTORY_SEPARATOR . 'bootstrap' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Generates an absolute path to the route definitions directory ('routes/').
     * 
     * @param string $path
     * @return string
     */
    public function routesPath(string $path = ''): string
    {
        return $this->rootPath . DIRECTORY_SEPARATOR . 'routes' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Generates an absolute path to local persistent/temporary storage ('storage/').
     * 
     * @param string $path
     * @return string
     */
    public function storagePath(string $path = ''): string
    {
        return $this->rootPath . DIRECTORY_SEPARATOR . 'storage' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Generates an absolute path to database-related assets (migrations, seeders, SQLite files).
     * 
     * @param string $path
     * @return string
     */
    public function databasePath(string $path = ''): string
    {
        return $this->rootPath . DIRECTORY_SEPARATOR . 'database' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Generates an absolute path to raw resources (views, raw templates, uncompiled assets).
     * 
     * @param string $path
     * @return string
     */
    public function resourcesPath(string $path = ''): string
    {
        return $this->rootPath . DIRECTORY_SEPARATOR . 'resources' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Retrieves the current execution environment (e.g., 'production', 'local', 'staging').
     * 
     * @return string
     */
    public function env(): string
    {
        return $this->config('app.env', 'production');
    }

    /**
     * Determines if the application is running in the specified environment.
     * 
     * @param string $env Environment name to check (e.g., 'local').
     * @return bool
     */
    public function environment(string $env): bool
    {
        return $this->config('app.env') === $env;
    }

    /**
     * Checks whether debug mode is currently enabled.
     * 
     * @return bool
     */
    public function getDebug(): bool
    {
        return $this->config('app.debug', 'false');
    }

    /**
     * Constructs a normalized, fully qualified URL toward a route or asset.
     * 
     * @param string $path Relative path to append to the base URL.
     * @return string
     */
    public function getUrl(string $path = ''): string
    {
        $url = $this->config('app.url', 'http://localhost');
        return rtrim($url, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Retrieves the default configured application language/locale (e.g., 'en', 'fr').
     * 
     * @return string
     */
    public function getLocale(): string
    {
        return $this->config('app.locale', 'en');
    }

    /**
     * Retrieves the default configured application timezone (e.g., 'UTC', 'Europe/Paris').
     * 
     * @return string
     */
    public function getTimezone(): string
    {
        return $this->config('app.timezone', 'UTC');
    }

    /**
     * Retrieves the active HTTP session handler instance.
     * 
     * @return \Clicalmani\Foundation\Http\Session\SessionHandler
     */
    public function session(): \Clicalmani\Foundation\Http\Session\SessionHandler
    {
        return \App\Providers\SessionServiceProvider::getDriver()::getInstance();
    }

    /**
     * Bootstraps and initializes the core application layers.
     * Sets up the default HTTP kernel and fires all registered Service Providers.
     * 
     * @return void
     */
    public function boot() : void
    {
        $this->addKernel(\App\Http\Kernel::class);
        \Clicalmani\Foundation\Providers\ServiceProvider::provideServices($this->config['app']['providers']);
    }

    /**
     * Checks if the core container and provider bootstrap sequence has completed.
     * 
     * @return bool
     */
    public function booted(): bool
    {
        return !!$this->container;
    }

    /**
     * Retrieves a configuration setting using dot notation mapping.
     * 
     * @param string $key Configuration key path (e.g., 'app.name').
     * @param mixed $default Fallback value if the key does not exist.
     * @return mixed
     */
    public function config(string $key, mixed $default = '') : mixed
    {
        return Arr::get($this->config, $key, $default);
    }

    /**
     * Sets or extracts globally shared view data variables.
     * Overwrites variables if a payload parameter is provided; resolves the payload otherwise.
     * 
     * @param array|callable|null $data
     * @return array Resolved payload data ready to be injected into rendered views.
     */
    public function viewSharedData(array|callable|null $data = null) : array
    {
        if (isset($data)) $this->viewSharedData = $data;
        else {
            if ( is_array($this->viewSharedData)) return $this->viewSharedData;
            elseif ( is_callable($this->viewSharedData) && !$this->viewDataShared ) {
                $response = call($this->viewSharedData, Request::getcurrent() ?? new Request);
                $this->viewDataShared = true;
                return $response;
            }
        }

        return [];
    }

    /**
     * Merges a batch of custom console commands or retrieves the full command catalog.
     * 
     * @param array $new_commands Optional command class names to add.
     * @return array Full command list.
     */
    public function commands(array $new_commands = [])
    {
        if ( !empty($new_commands) ) $this->commands += $new_commands;
        return $this->commands;
    }

    /**
     * Appends a unique console command class to the application catalog.
     * 
     * @param string $new_command Fully qualified class name of the command.
     * @return void
     */
    public function addCommand(string $new_command): void
    {
        $this->commands[] = $new_command;
    }

    /**
     * Binds the Symfony DI service configurator to the current app instance.
     * Called during the initialization and compilation phase of the container.
     * 
     * @param DefaultsConfigurator $services
     * @return $this
     */
    public function initServices(DefaultsConfigurator $services)
    {
        $this->services = $services;
        return $this;
    }

    /**
     * Dynamically registers a custom service definition to be compiled into the DI container later.
     *
     * @param string $id Unique service identifier alias.
     * @param string $class Concretely mapped FQCN to instantiate.
     * @param \Closure|string|null $config Optional closure handler to configure service arguments.
     * @param array{0: string, 1: string}|null $alias Optional alias credentials tracking [AliasId, TargetId].
     * @return void
     */
    public function addService(string $id, string $class, \Closure|string|null $config = null, ?array $alias = null): void
    {
        $this->coreServices[$id] = new ServiceDefinition($id, $class, $config, $alias, ServiceType::Custom);
    }

    /**
     * Immediately boots and registers a specific third-party ServiceProvider instance.
     * 
     * @param \Clicalmani\Foundation\Providers\ServiceProviderInterface $service
     * @return void
     */
    public function register(\Clicalmani\Foundation\Providers\ServiceProviderInterface $service)
    {
        $service->boot();
        $service->register();
    }

    /**
     * Exposes the raw active Symfony DI service configurator linked to the instance.
     * 
     * @return ServiceConfigurator|DefaultsConfigurator|null
     */
    public function getServices()
    {
        return $this->services;
    }

    /**
     * Attaches a profiling callback associated with a specific temporal metric or hook.
     * Used for measuring execution intervals across micro-benchmarks.
     * 
     * @param string $event Event name to observe.
     * @param callable $handler Callback executed when the event triggers.
     * @return void
     */
    public function setTimeTracker(string $event, $handler)
    {
        $this->cumulative_time_listeners[$event][] = $handler;
    }

    /**
     * Retrieves the absolute list of all registered time-tracking profiler hooks.
     * 
     * @return array
     */
    public function getTimeTracker()
    {
        return $this->cumulative_time_listeners;
    }

    /**
     * Magic getter supplying direct shortcut access to core architectural layers.
     * Non-matching strings fall back to looking up records inside the configuration loader.
     * 
     * @param string $name Requested property identifier.
     * @return mixed
     */
    public function __get($name)
    {
        return match ($name) {
            'config' => $this->config,
            'console' => $this->console,
            'filesystem' => $this->filesystem,
            'response' => $this->response,
            'container' => $this->container,
            default => $this->config->get($name)
        };
    }

    /**
     * Secured magic setter restricting external override mutations.
     * Direct overrides are only allowed on fundamental container properties.
     * 
     * @param string $name Property variable to update.
     * @param mixed $value
     * @throws \RuntimeException If the dev attempts to dynamically inject arbitrary properties on the container core.
     * @return void
     */
    public function __set($name, $value)
    {
        return match ($name) {
            'config' => $this->config = $value,
            'console' => $this->console = $value,
            'response' => $this->response = $value,
            default => $this->config->set($name, $value)
        };
    }

    /**
     * Iterates through and processes the catalog of core/custom definitions inside Symfony Dependency Injection.
     * 
     * @return void
     */
    public function registerCoreContainerServices(): void
    {
        if (!$this->services) return;

        foreach ($this->coreServices as $definition) {
            $this->services = $this->services->set($definition->id, $definition->class);

            $definition->config?->__invoke($this->services, $this);

            if ($definition->alias) {
                [$aliasId, $referenceId] = $definition->alias;
                $this->services->alias($aliasId, $referenceId);
            }
        }

        $this->services->set('view', \Clicalmani\Foundation\Resources\View::class);
    }

    /**
     * Syntactic sugar method to declaring Symfony service configuration bindings fluids.
     * Maps short syntax keywords into functional native calls (`service()`, `param()`, etc.).
     * 
     * @param string $func Binding type descriptor ('service', 'param', 'reference', 'alias').
     * @param mixed $arg Target payload parameter (e.g., target FQCN string).
     * @return mixed Matching Symfony Service configuration primitives.
     * @throws \InvalidArgumentException If the requested mapping function does not exist.
     */
    public function dependency(string $func, mixed $arg)
    {
        return match ($func) {
            'service' => service($arg),
            'param' => param($arg),
            'reference' => reference($arg),
            default => throw new \InvalidArgumentException("Unknown dependency function: $func"),
        };
    }

    /**
     * Evaluates and returns the initial hardcoded catalog of native framework components.
     * Contains global manager registries alongside generic namespace patterns (`*.request`).
     * 
     * @return array<string, ServiceDefinition>
     */
    private function defaultServiceDefinitions(): array
    {
        return [
            'logger'     => new ServiceDefinition('logger', \Clicalmani\Foundation\Acme\Logger::class, type: ServiceType::Core),
            'str'        => new ServiceDefinition('str', \Clicalmani\Foundation\Acme\Stringable::class, type: ServiceType::Core),
            'router'     => new ServiceDefinition('router', \Clicalmani\Foundation\Acme\Router::class, type: ServiceType::Core),
            'array'      => new ServiceDefinition('array', \Clicalmani\Foundation\Acme\Arrayable::class, type: ServiceType::Core),
            'env'        => new ServiceDefinition('env', \Clicalmani\Foundation\Acme\Environment::class, type: ServiceType::Core),
            'config'     => new ServiceDefinition('config', \Clicalmani\Foundation\Acme\Configure::class, type: ServiceType::Core),
            'console'    => new ServiceDefinition('console', \Clicalmani\Foundation\Acme\Console::class, type: ServiceType::Core),
            'response'   => new ServiceDefinition('response', \Clicalmani\Foundation\Http\Response::class, \Clicalmani\Foundation\Providers\Config\ResponseConfig::class, type: ServiceType::Core),
            'storage'    => new ServiceDefinition('storage', \Clicalmani\Foundation\Acme\StorageManager::class, type: ServiceType::Core),
            'controller' => new ServiceDefinition('controller', \Clicalmani\Foundation\Acme\Controller::class, type: ServiceType::Core),
            'func'       => new ServiceDefinition('func', \Clicalmani\Foundation\Acme\Invokable::class, type: ServiceType::Core),
            'database'   => new ServiceDefinition('database', \Clicalmani\Foundation\Acme\Database::class, type: ServiceType::Core),
            'view'       => new ServiceDefinition('view', \Clicalmani\Foundation\Resources\View::class, type: ServiceType::Core),

            // Namespace-based fallback structural shortcuts: Resolved dynamically depending on the suffix of the dependency.
            '*.request'   => new ServiceDefinition('*.request', \Clicalmani\Foundation\Http\Controllers\InjectRequest::class, type: ServiceType::Namespace),
            '*.resource'  => new ServiceDefinition('*.resource', \Clicalmani\Foundation\Http\Controllers\InjectResource::class, type: ServiceType::Namespace),
            '*.mailer'    => new ServiceDefinition('*.mailer', \Clicalmani\Foundation\Mail\InjectMailer::class, type: ServiceType::Namespace),
            '*.messenger' => new ServiceDefinition('*.messenger', \Clicalmani\Foundation\Messenger\Inject::class, \Clicalmani\Foundation\Providers\Config\MessengerConfig::class, type: ServiceType::Namespace),
        ];
    }
}