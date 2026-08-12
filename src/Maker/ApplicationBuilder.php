<?php
namespace Clicalmani\Foundation\Maker;

use Symfony\Component\DependencyInjection\Loader\Configurator\DefaultsConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServiceConfigurator;

/**
 * Class ApplicationBuilder
 * 
 * Implements a fluent interface (Builder Pattern) to configure, orchestrate,
 * and bootstrap framework core kernels and optional plugin service providers.
 * 
 * @package Clicalmani\Foundation\Maker
 * @author @clicalmani
 */
class ApplicationBuilder
{
    /**
     * Stack of core framework kernels to load sequentially during initialization.
     * 
     * @var array<class-string<\Clicalmani\Foundation\Maker\Kernel>>
     */
    private array $kernels = [
        \Clicalmani\Foundation\Maker\AppKernel::class,
        \Clicalmani\Foundation\Maker\BootstrapKernel::class,
        \Clicalmani\Foundation\Maker\HttpKernel::class,
        \Clicalmani\Foundation\Resources\Kernel::class,
    ];

    /**
     * ApplicationBuilder Constructor.
     * 
     * @param \Clicalmani\Foundation\Maker\Application $app The core application instance to configure.
     */
    public function __construct(private Application $app)
    {
        // Include core helpers and initialize the console ecosystem mapping
        \Clicalmani\Foundation\Support\Helper::include();
        $this->app->console = new \Clicalmani\Console\Application($this->app);

        // Immediately boot and register the core storage infrastructure service
        $this->app->register(
            new \Clicalmani\Foundation\Providers\StorageServiceProvider
        );
    }

    /**
     * Completes the builder sequence and returns the fully configured application container instance.
     * 
     * @return \Clicalmani\Foundation\Maker\Application
     */
    public function run()
    {
        return $this->app;
    }

    /**
     * Boots the environment service provider, registers core kernels, 
     * compiles console command catalogs, and sets up JSON Web Token security handlers.
     * 
     * @return static
     */
    public function withKernels() : static
    {
        \Clicalmani\Foundation\Providers\ServiceProvider::provideServices([
            \Clicalmani\Foundation\Providers\EnvServiceProvider::class
        ]);
        
        foreach ($this->kernels as $kernel) {
            $this->app->addKernel($kernel);
        }
        
        $this->app->commands(
            array_merge(
                $this->app->commands(), 
                $this->app->config['bootstrap']['commands']
            )
        );

        // Register the built-in JSON Web Token service layer
        $this->app->register(
            new \Clicalmani\Foundation\Providers\JwtServiceProvider
        );
        
        return $this;
    }

    /**
     * Configures the global HTTP middleware layer by providing a custom closure hook.
     * 
     * @param \Closure $callback Setup callback receiving the Web middleware gateway driver instance.
     * @return static
     */
    public function withMiddleware(\Closure $callback) : static
    {
        \Closure::bind($callback, null);
        $callback(new \Clicalmani\Foundation\Http\Middlewares\Web);
        return $this;
    }

    /**
     * Customizes container instance bindings directly by passing an entry callback hook.
     * 
     * @param \Closure $callback Custom closure receiving the application container wrapper instance.
     * @return static
     */
    public function withService(\Closure $callback) : static
    {
        \Closure::bind($callback, null);
        $callback($this->app);
        return $this;
    }

    /**
     * Integrates and provisions automated mailing services into the framework container.
     * 
     * @return static
     */
    public function withMailer()
    {
        $this->app->register(new \Clicalmani\Foundation\Providers\MailerServiceProvider);
        return $this;
    }

    /**
     * Integrates Inertia.js protocol support, mapping a standard response service
     * and injecting the corresponding asset interceptor middleware into the global web stack.
     * 
     * @return static
     */
    public function withInertia()
    {
        $middleware = new \Clicalmani\Foundation\Http\Middlewares\Web;
        $this->app->addService('inertia', \Inertia\Response::class);
        $middleware->web(append: [\Inertia\Middleware::class]);
        return $this;
    }

    /**
     * Appends an event-driven asynchronous Messenger dispatch service provider into the framework core.
     * 
     * @param string|null $transport DSN-formatted connection string credential.
     * @param string|null $handlersPath Relative filesystem path tracking incoming message consumer logic.
     * @param string|null $namespace Root PHP namespace structural token identifying handler definitions.
     * @return static
     */
    public function withMessenger(?string $transport = "elegant://default", ?string $handlersPath = "app/Handlers", ?string $namespace = "\\App\\Handlers\\")
    {
        $messengerService = new \Clicalmani\Foundation\Providers\MessengerServiceProvider;
        $messengerService->setTransport($transport);
        $messengerService->setHandlersPath($handlersPath);
        $messengerService->setNamespace($namespace);
        $this->app->register($messengerService);
        return $this;
    }

    /**
     * Enables automated cron-style task scheduling capabilities.
     * 
     * @param string|null $tasksPath Directory location of job classes.
     * @param string|null $namespace Base namespace mapped to the target job files.
     * @param bool|null $statefull Whether to track execution intervals persistently across loops.
     * @return static
     */
    public function withScheduler(?string $tasksPath = 'app/Tasks', ?string $namespace = 'App\\Tasks', ?bool $statefull = false)
    {
        $scheduleService = new \Clicalmani\Foundation\Providers\ScheduleServiceProvider;
        $scheduleService->setPaths($tasksPath);
        $scheduleService->setNamespaces($namespace);
        $scheduleService->setStatefull($statefull);
        $this->app->register($scheduleService);
        return $this;
    }

    /**
     * Boots cache infrastructure services to manage shared variable key memory pools.
     * 
     * @return static
     */
    public function withCache(): static
    {
        $cacheService = new \Clicalmani\Foundation\Providers\CacheServiceProvider;
        $this->app->register($cacheService);
        return $this;
    }

    /**
     * Registers dedicated pub/sub system event handlers across localized class listeners.
     * 
     * @param string $listenersPath Custom directory path mapping registered actions.
     * @param string $namespace Target PHP namespace tracking event logic.
     * @return static
     */
    public function withEvents(string $listenersPath = 'app/Listeners', string $namespace = '\\App\\Listeners'): static
    {
        $eventService = new \Clicalmani\Foundation\Providers\EventServiceProvider; 
        $eventService->setPath($listenersPath);
        $eventService->setNamespace($namespace);
        $this->app->register($eventService);
        return $this;
    }

    /**
     * Sets up server-side live event broadcasting channels over WebSockets or SSE streams.
     * 
     * @throws \RuntimeException If the 'broadcasting.php' configuration blueprint is missing, 
     *                           or if the underlying 'Broadcaster' extension package is not installed via Composer.
     * @return static
     */
    public function withEventBroadcasting(): static
    {
        if ( ! is_file(config_path('/broadcasting.php')) ) {
            throw new \RuntimeException('The broadcasting.php configuration file is missing. Please create the broadcasting.php configuration file in your config directory.');
        }

        if ( ! class_exists(\Broadcaster\BroadcastManager::class) ) {
            throw new \RuntimeException('The Broadcaster package is not installed. Please run composer to install the Broadcaster package to use event broadcasting features.');
        }

        $this->app->register(new \Broadcaster\BroadcastServiceProvider);
        return $this;
    }
}