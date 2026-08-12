<?php
namespace Clicalmani\Foundation\Providers;

use Clicalmani\Foundation\Mail\MailerEventDispatcherFactory;
use Clicalmani\Foundation\Mail\MailerTransport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mailer\EventListener\EnvelopeListener;
use Symfony\Component\Mime\Address;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\DependencyInjection\Loader\Configurator\DefaultsConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServiceConfigurator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Override;

/**
 * Class MailerServiceProvider
 * 
 * Boots, provisions, and configures the framework's email distribution services,
 * establishing factories for mailing transports, structural event dispatchers, 
 * and asynchronous background message queue bus delegation.
 * 
 * @package Clicalmani\Foundation\Providers
 * @author @clicalmani
 */
class MailerServiceProvider implements ServiceProviderInterface
{
    /**
     * Registers mail infrastructure dependencies, transports, and dispatchers into the container.
     * 
     * @return void
     */
    #[Override]
    public function register(): void
    {
        // 1. Load the framework's primary mail configuration blueprints into runtime memory
        if (is_file(config_path('/mail.php'))) {
            app()->config->set('mail', require config_path('/mail.php'));
        }

        // 2. Register the custom framework mailer transport factory engine
        app()->addService(MailerTransport::class, 
            MailerTransport::class,
            static function(ServiceConfigurator|DefaultsConfigurator $config) {
                // Instantiated strictly for initial service visibility inside the container
            }
        );

        // 3. Dynamically resolve and bind the underlying transport driver implementation using the factory
        app()->addService(TransportInterface::class, 
            TransportInterface::class,
            static function(ServiceConfigurator|DefaultsConfigurator $config) {
                $config->factory([
                    app()->dependency('service', MailerTransport::class), 
                    'create'
                ]);
            }
        );

        // 4. Provision the specialized event dispatcher factory and its contract wrapper
        app()->addService(MailerEventDispatcherFactory::class, 
            MailerEventDispatcherFactory::class,
            static function(ServiceConfigurator|DefaultsConfigurator $config) {}
        );

        app()->addService(EventDispatcherInterface::class, 
            EventDispatcherInterface::class,
            static function(ServiceConfigurator|DefaultsConfigurator $config) {
                $config->factory([
                    app()->dependency('service', MailerEventDispatcherFactory::class),
                    'create'
                ]);
            }
        );

        // Provision a raw mailer instance bypassing asynchronous message busses to guard against infinite recursion loops
        app()->addService('mailer.raw', 
            Mailer::class,
            static function(ServiceConfigurator|DefaultsConfigurator $config) {
                $config->args([
                    app()->dependency('service', TransportInterface::class),
                    null, // Explicitly NULL message bus to force direct, synchronous processing
                    app()->dependency('service', EventDispatcherInterface::class)
                ]);
            }
        );

        // 5. Compile and map the primary application-wide Mailer interface service
        app()->addService(MailerInterface::class, 
            Mailer::class,
            function(ServiceConfigurator|DefaultsConfigurator $config) {
                $config->args([
                    app()->dependency('service', TransportInterface::class),
                    $this->resolveBusArgument(),
                    app()->dependency('service', EventDispatcherInterface::class)
                ]);
            },
            [MailerInterface::class, 'mailer'] // Shared runtime aliases for seamless DI injection or facade bindings
        );
    }

    /**
     * Boots prerequisite options right before service handling loops initiate.
     * 
     * @return void
     */
    #[Override]
    public function boot(): void
    {
        // Reserved for sub-dependency initialization routines
    }

    /**
     * Evaluates environment runtime states to conditionally inject the asynchronous 
     * Messenger bus dependency into the mail orchestration layer.
     * 
     * @return mixed A service dependency pointer reference if queueing is active, otherwise null.
     */
    protected function resolveBusArgument(): mixed
    {
        $queueEnabled = (bool) config('mail.queue.enabled', false);

        if (!$queueEnabled) {
            return null;
        }

        // Return a structural reference pointing directly to the messenger service container layer
        return app()->dependency('service', 'messenger');
    }
}