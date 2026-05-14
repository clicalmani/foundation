<?php
namespace Clicalmani\Foundation\Maker;

use Symfony\Component\DependencyInjection\Loader\Configurator\DefaultsConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServiceConfigurator;

class ApplicationBuilder
{
    private $kernels = [
        \Clicalmani\Foundation\Maker\AppKernel::class,
        \Clicalmani\Foundation\Maker\BootstrapKernel::class,
        \Clicalmani\Foundation\Maker\HttpKernel::class,
        \Clicalmani\Foundation\Resources\Kernel::class,
    ];

    public function __construct(private Application $app)
    {
        \Clicalmani\Foundation\Support\Helper::include();
        $this->app->console = new \Clicalmani\Console\Application($this->app);

        // Storage service
        $this->app->register(
            new \Clicalmani\Foundation\Providers\StorageServiceProvider
        );
    }

    /**
     * Runs the application
     * 
     * @return Application
     */
    public function run()
    {
        return $this->app;
    }

    /**
     * Loads kernels
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
        
        $commands = array_merge($this->app->commands(), $this->app->config['bootstrap']['commands']);
        $this->app->commands($commands);
        
        return $this;
    }

    /**
     * Loads middlewares
     * 
     * @return static
     */
    public function withMiddleware(\Closure $callback) : static
    {
        \Closure::bind($callback, null);
        $callback(new \Clicalmani\Foundation\Http\Middlewares\Web);
        return $this;
    }

    /**
     * Adds a service to the application
     * 
     * @return static
     */
    public function withService(\Closure $callback) : static
    {
        \Closure::bind($callback, null);
        $callback($this->app);
        return $this;
    }

    /**
     * Adds mailer services to the application
     * 
     * @return static
     */
    public function withMailer()
    {
        $this->app->addService('smtp.mailer.transport', [\Clicalmani\Foundation\Mail\MailerTransport::class]);
            $this->app->addService(
                'smtp.mailer', 
                [
                    \Clicalmani\Foundation\Mail\Mailer::class,
                    fn(ServiceConfigurator|DefaultsConfigurator $config) => 
                        $config->args([
                            $this->app->dependency('service', 'smtp.mailer.transport')
                        ])
                ]);
        return $this;
    }

    /**
     * Adds inertia services to the application
     * 
     * @return static
     */
    public function withInertia()
    {
        $middleware = new \Clicalmani\Foundation\Http\Middlewares\Web;
        $this->app->addService('inertia', [\Inertia\Response::class]);
        $middleware->web(append: [\Inertia\Middleware::class]);
        return $this;
    }

    /**
     * Adds messenger services to the application
     * 
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
     * Adds cache services to the application
     * 
     * @return static
     */
    public function withCache(): static
    {
        $cacheService = new \Clicalmani\Foundation\Providers\CacheServiceProvider;
        $this->app->register($cacheService);
        return $this;
    }
}