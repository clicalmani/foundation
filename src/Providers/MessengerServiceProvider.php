<?php

namespace Clicalmani\Foundation\Providers;

use Clicalmani\Foundation\Messenger\ElegantTransportFactory;
use Clicalmani\Foundation\Providers\ServiceProviderInterface;
use Clicalmani\Foundation\Filesystem\RecursiveFilter;
use Symfony\Component\DependencyInjection\Loader\Configurator\DefaultsConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServiceConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Override;

class MessengerServiceProvider implements ServiceProviderInterface
{
    protected ?string $handlersPath = "app/Handlers";
    protected ?string $namespace = "\\App\\Handlers\\";
    protected ?string $transport = null;

    #[Override]
    public function register(): void
    {
        // On charge la config directement dans le register pour éviter les effets de bord
        $this->config = require config_path('/messenger.php');

        $handlersMapping = $this->discoverHandlers();

        /**
         * Elegant Transport
         */
        app()->addService('messenger.transport_factory.elegant', [
            ElegantTransportFactory::class,
            fn($config) => $config->args([
                $this->config['default'],
                $this->config
            ])
        ]);

        app()->addService('messenger.transport.elegant', [
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
        ]);
        
        app()->addService('messenger.senders_locator', [
            \Symfony\Component\Messenger\Transport\Sender\SendersLocator::class,
            function (ServiceConfigurator|DefaultsConfigurator $config) {
                $config->args([
                    [
                        ...($this->config['routing'] ?? []),
                        '*' => [($this->transport === "elegant://default") ? 'messenger.transport.elegant': $this->transport]
                    ],
                    new Reference('service_container')
                ]);
            }
        ]);

        app()->addService('messenger.middleware.send_message', [
            \Symfony\Component\Messenger\Middleware\SendMessageMiddleware::class,
            static function (ServiceConfigurator|DefaultsConfigurator $config) {
                $config->args([
                    app()->dependency('service', 'messenger.senders_locator')
                ]);
            }
        ]);

        /**
         * Handlers
         */
        app()->addService('messenger.handlers_locator', [
            \Symfony\Component\Messenger\Handler\HandlersLocator::class,
            static fn(ServiceConfigurator|DefaultsConfigurator $config) => $config->args([
                $handlersMapping
            ])
        ]);
        
        app()->addService('messenger.middleware.handle_message', [
            \Symfony\Component\Messenger\Middleware\HandleMessageMiddleware::class,
            static fn(ServiceConfigurator|DefaultsConfigurator $config) => $config->args([
                app()->dependency('service', 'messenger.handlers_locator')
            ])
        ]);
        
        app()->addService('messenger', [
            \Symfony\Component\Messenger\MessageBus::class,
            static fn(ServiceConfigurator|DefaultsConfigurator $config) => $config->args([
                [
                    // CORRECTION : Send DOIT être avant Handle pour éviter le double traitement en async
                    app()->dependency('service', 'messenger.middleware.send_message'),
                    app()->dependency('service', 'messenger.middleware.handle_message'),
                ]
            ])
        ]);
    }

    #[Override]
    public function boot(): void
    {
        if ( is_file(config_path('/messenger.php')) ) {
            app()->config->set('messenger', require_once config_path('/messenger.php'));
        }
    }

    public function setTransport(string $transport): void
    {
        $this->transport = $transport;
    }

    public function setHandlersPath(string $handlersPath): void
    {
        $this->handlersPath = $handlersPath;
    }

    public function setNamespace(string $namespace): void
    {
        $this->namespace = $namespace;
    }

    protected function discoverHandlers(): array
    {
        $handlersMapping = [];
        $handlersPath = root_path($this->handlersPath);

        if (!is_dir($handlersPath)) {
            return []; // On ne crée pas le dossier à la volée
        }

        $filter = new RecursiveFilter(
            new \RecursiveDirectoryIterator($handlersPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        $filter->setPattern("\\.php$");

        foreach (new \RecursiveIteratorIterator($filter) as $file) {
            // CORRECTION : Supporte les sous-dossiers (PSR-4)
            $relativePath = str_replace(
                [root_path($this->handlersPath), '/', '.php'], 
                ['', '\\', ''], 
                $file->getPathname()
            );
            $class = $this->namespace . ltrim($relativePath, '\\');

            if (class_exists($class)) {
                $reflection = new \ReflectionClass($class);
                if ($reflection->hasMethod('__invoke') && $reflection->isInstantiable()) {
                    $method = $reflection->getMethod('__invoke');
                    $parameters = $method->getParameters();
                    
                    if (isset($parameters[0]) && $type = $parameters[0]->getType()) {
                        // CORRECTION : Sécurise contre les Union Types (PHP 8+) et les types scalaires
                        if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                            $messageClass = $type->getName();
                            app()->addService($class, [$class]);
                            $handlersMapping[$messageClass][] = new Reference($class);
                        }
                    }
                }
            }
        }

        return $handlersMapping;
    }
}