<?php

namespace Clicalmani\Core\Providers;

use Clicalmani\Core\Filesystem\DirectoryScanner;
use Clicalmani\Core\Messenger\ElegantTransportFactory;
use Clicalmani\Core\Providers\ServiceProviderInterface;
use Clicalmani\Core\Filesystem\RecursiveFilter;
use Symfony\Component\DependencyInjection\Loader\Configurator\DefaultsConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServiceConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Override;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;

/**
 * Class MessengerServiceProvider
 * 
 * Boots, provisions, and configures the framework's internal message bus system,
 * handles automatic runtime class discovery of custom invokable handlers, and configures
 * middleware stacks alongside specialized asynchronous transport layers.
 * 
 * @package Clicalmani\Core\Providers
 * @author @clicalmani
 */
class MessengerServiceProvider implements ServiceProviderInterface
{
    /**
     * Relative path to the application message handlers directory.
     * 
     * @var string|null
     */
    protected ?string $handlersPath = "app/Handlers";

    /**
     * Root PHP namespace prefix assigned to application message handlers.
     * 
     * @var string|null
     */
    protected ?string $namespace = "\\App\\Handlers\\";

    /**
     * Explicit transport driver target reference string.
     * 
     * @var string|null
     */
    protected ?string $transport = null;

    /**
     * Internal operational array storage holding raw configuration data values.
     * 
     * @var array
     */
    protected array $config = [];

    private array $handlersMapping = [];

    /**
     * Registers message bus infrastructure dependencies, discovery maps, and routing middlewares.
     * 
     * @return void
     */
    #[Override]
    public function register(): void
    {
        // Load the framework's primary messenger configuration blueprints directly into memory
        $this->config = require config_path('/messenger.php');

        // Execute reflection routines to automatically construct message-to-handler maps
        $this->discoverHandlers();
        
        // Register the framework's proprietary Elegant Transport Factory component
        app()->addService('messenger.transport_factory.elegant', 
            ElegantTransportFactory::class,
            fn($config) => $config->args([
                $this->config['default'],
                $this->config
            ])
        );

        // Provision the default Elegant asynchronous runtime transport engine
        app()->addService('messenger.transport.elegant', 
            \Symfony\Component\Messenger\Transport\TransportInterface::class,
            function (ServiceConfigurator|DefaultsConfigurator $config) {
                $config->factory([
                    app()->dependency('service', 'messenger.transport_factory.elegant'), 
                    'createTransport'
                ])->args([
                    $this->config['default'],
                    $this->config
                ]);
            }
        );

        // Provision a synchronous, immediate execution transport strategy fallback
        app()->addService('messenger.transport.sync', 
            \Symfony\Component\Messenger\Transport\Sync\SyncTransport::class,
            static function(ServiceConfigurator|DefaultsConfigurator $config) {
                $config->args([
                    app()->dependency('service', 'messenger')
                ]);
            }
        );

        // Provision a specialized error handling transport engine for dead-letter processing
        app()->addService('messenger.transport.failed', 
            \Symfony\Component\Messenger\Transport\TransportInterface::class,
            function(ServiceConfigurator|DefaultsConfigurator $config) {
                $config->factory([
                    app()->dependency('service', 'messenger.transport_factory.elegant'), 
                    'createTransport'
                ])->args([
                    'elegant://failed',
                    $this->config + ['table_name' => 'failed_messages'],
                ]);
            }
        );

        // Map which specific operational transports route unexpected failures into storage
        app()->addService('messenger.failure_transports', 
            \Clicalmani\Core\Messenger\FailureTransportLocator::class,
            function(ServiceConfigurator|DefaultsConfigurator $config) {
                $config->args([
                    $this->config['failure_transports'] ?? []
                ]);
            }
        );
        
        // Compile physical message-to-transport sender mappings across the application context
        app()->addService('messenger.senders_locator', 
            \Symfony\Component\Messenger\Transport\Sender\SendersLocator::class,
            function (ServiceConfigurator|DefaultsConfigurator $config) {
                $config->args([
                    [
                        ...($this->config['routing'] ?? []),
                        '*' => [($this->transport === "elegant://default") ? 'messenger.transport.elegant' : $this->transport]
                    ],
                    new Reference('service_container')
                ]);
            }
        );

        // Bind the fundamental message outward dispatching middleware engine
        app()->addService('messenger.middleware.send_message', 
            \Symfony\Component\Messenger\Middleware\SendMessageMiddleware::class,
            static function (ServiceConfigurator|DefaultsConfigurator $config) {
                $config->args([
                    app()->dependency('service', 'messenger.senders_locator')
                ]);
            }
        );

        // Register the handler locator storage containing resolved invokable maps
        app()->addService('messenger.handlers_locator', 
            \Symfony\Component\Messenger\Handler\HandlersLocator::class,
            fn(ServiceConfigurator|DefaultsConfigurator $config) => $config->args([
                $this->handlersMapping
            ])
        );
        
        // Bind the inbound message target handling execution middleware engine
        app()->addService('messenger.middleware.handle_message', 
            \Symfony\Component\Messenger\Middleware\HandleMessageMiddleware::class,
            static fn(ServiceConfigurator|DefaultsConfigurator $config) => $config->args([
                app()->dependency('service', 'messenger.handlers_locator')
            ])
        );

        // Mailable Middleware
        app()->addService('messenger.middleware.mailable', 
            \Clicalmani\Core\Mail\MailableMiddleware::class,
            static fn(ServiceConfigurator|DefaultsConfigurator $config) => $config->args([
                app()->dependency('service', \Symfony\Component\Mailer\Transport\TransportInterface::class)
            ])
        );
        
        // Core framework Message Bus orchestration service containing the sequenced middleware pipeline
        app()->addService('messenger', 
            \Symfony\Component\Messenger\MessageBus::class,
            static fn(ServiceConfigurator|DefaultsConfigurator $config) => $config->args([
                [
                    app()->dependency('service', 'messenger.middleware.send_message'),
                    app()->dependency('service', 'messenger.middleware.mailable'),
                    app()->dependency('service', 'messenger.middleware.handle_message'),
                ]
            ])
        );

        // Injector
        app()->addService('*.messenger', 
            \Clicalmani\Core\Messenger\Inject::class,
            static fn(ServiceConfigurator|DefaultsConfigurator $config) => $config->args([
                app()->dependency('service', 'messenger')
            ])
        );
    }

    /**
     * Synchronizes global configuration instances inside application boot cycles.
     * 
     * @return void
     */
    #[Override]
    public function boot(
        ?\Clicalmani\Core\Acme\Container $container = null
    ): void
    {
        if ( is_file(config_path('/messenger.php')) ) {
            app()->config->set('messenger', require config_path('/messenger.php'));
        }
    }

    /**
     * Mutates the target transport driver definition pointer string.
     * 
     * @param string $transport The targeted transport URI scheme string.
     * @return void
     */
    public function setTransport(string $transport): void
    {
        $this->transport = $transport;
    }

    /**
     * Mutates the filesystem directory track targeted for automatic handler scanning.
     * 
     * @param string $handlersPath Relative application file pathway.
     * @return void
     */
    public function setHandlersPath(string $handlersPath): void
    {
        $this->handlersPath = $handlersPath;
    }

    /**
     * Mutates the root PHP structural class namespace prefix for handlers.
     * 
     * @param string $namespace The targeted namespace structure string.
     * @return void
     */
    public function setNamespace(string $namespace): void
    {
        $this->namespace = $namespace;
    }

    /**
     * Inspects the target filesystem to dynamically build an execution map 
     * binding typed parameter objects to invokable class handlers.
     * 
     * @return void
     */
    protected function discoverHandlers(): void
    {
        if ($this->handlersMapping) return;
        
        $handlersPath = root_path($this->handlersPath);

        if (!is_dir($handlersPath)) {
            return;
        }

        $scanner = (new DirectoryScanner(
            rootPath: $handlersPath,
            baseNamespace: $this->namespace,
            ignore: ['.', '..']
        ));
        $classes = $scanner->classes();

        /** @var \SplFileInfo $file */
        foreach ($classes as $className) {

            $reflection = new \ReflectionClass($className);

            if ($reflection->hasMethod('__invoke') && $reflection->isInstantiable()) {
                $method = $reflection->getMethod('__invoke');
                $parameters = $method->getParameters();
                
                if (isset($parameters[0]) && $type = $parameters[0]->getType()) {
                    // Protects against Union Types (PHP 8+) and scalar types
                    if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                        $messageClass = $type->getName();
                        app()->addService($className, $className);
                        $this->handlersMapping[$messageClass][] = new Reference($className);
                    }
                }
            }
        }
    }
}