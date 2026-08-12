<?php
namespace Clicalmani\Foundation\Providers;

use Broadcaster\BroadcastManager;
use Broadcaster\Event\ShouldBroadcastInterface;
use Broadcaster\SystemBroadcastListener;
use Clicalmani\Foundation\Events\BroadcastEventDispatcher;
use Clicalmani\Foundation\Events\CoreEventDispatcher;
use Clicalmani\Foundation\Events\MailableEventDispatcher;
use Clicalmani\Foundation\Mail\MailableListener;
use Clicalmani\Foundation\Mail\SystemMailableListener;
use Symfony\Component\DependencyInjection\Loader\Configurator\DefaultsConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServiceConfigurator;
use Override;

/**
 * Class EventServiceProvider
 * 
 * Provisions and bootstraps the application event infrastructure, handling listener 
 * discovery, internal core dispatchers, and external mailable/broadcasting event hooks.
 * 
 * @package Clicalmani\Foundation\Providers
 * @author @clicalmani
 */
class EventServiceProvider implements ServiceProviderInterface
{
    /**
     * The relative path from the application root where application event listeners are located.
     * 
     * @var string
     */
    protected string $path = 'app/Listeners';

    /**
     * The root PHP namespace mapped to the target event listener classes.
     * 
     * @var string
     */
    protected string $namespace = '\\App\\Listeners';

    /**
     * Registers all core event infrastructure and dispatching layers into the container ecosystem.
     * 
     * @return void
     */
    #[Override]
    public function register(): void
    {
        // Register core event handlers and structural bridge listeners
        app()->addService(
            SystemBroadcastListener::class,
            SystemBroadcastListener::class,
            static function(ServiceConfigurator|DefaultsConfigurator $config) {
                // Instantiated strictly for initial service visibility inside the container
            }
        );

        app()->addService(SystemMailableListener::class, 
            SystemMailableListener::class,
            static function(ServiceConfigurator|DefaultsConfigurator $config) {}
        );

        app()->addService(MailableListener::class, 
            MailableListener::class,
            static function(ServiceConfigurator|DefaultsConfigurator $config) {}
        );

        // Wire the specialized event dispatchers with their corresponding system listeners
        app()->addService(BroadcastEventDispatcher::class, 
            BroadcastEventDispatcher::class,
            static function(ServiceConfigurator|DefaultsConfigurator $config) {
                $config->args([
                    app()->dependency('service', SystemBroadcastListener::class)
                ]);
            }
        );

        app()->addService(MailableEventDispatcher::class, 
            MailableEventDispatcher::class,
            static function(ServiceConfigurator|DefaultsConfigurator $config) {
                $config->args([
                    app()->dependency('service', SystemMailableListener::class)
                ]);
            }
        );

        // Consolidate specialized dispatchers into the primary core event dispatcher pipeline
        app()->addService(CoreEventDispatcher::class, 
            CoreEventDispatcher::class,
            static function(ServiceConfigurator|DefaultsConfigurator $config) {
                $config->args([
                    [
                        app()->dependency('service', BroadcastEventDispatcher::class),
                        app()->dependency('service', MailableEventDispatcher::class),
                    ]
                ]);
            }
        );

        // Provision the automated scanning mechanics to dynamically resolve app listeners
        app()->addService('events.discovery', 
            \Clicalmani\Foundation\Events\ListenerDiscovery::class,
            function(ServiceConfigurator|DefaultsConfigurator $config) {
                $config->args([
                    app()->rootPath() . '/' . $this->path,
                    $this->namespace,
                    app()->dependency('service', CoreEventDispatcher::class)
                ])->call('discover');
            }
        );
    }

    /**
     * Boots prerequisite actions right before service runtime execution loop triggers.
     * 
     * @return void
     */
    #[Override]
    public function boot(): void
    {
        // Reserved for sub-dependency initialization routines
    }

    /**
     * Overrides the default file directory location mapping registered listeners.
     * 
     * @param string $path Relative filesystem directory path.
     * @return void
     */
    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    /**
     * Overrides the default structural PHP namespace token identifying handler classes.
     * 
     * @param string $namespace Root tracking class path.
     * @return void
     */
    public function setNamespace(string $namespace): void
    {
        $this->namespace = $namespace;
    }
}