<?php
namespace Clicalmani\Foundation\Providers;

use Symfony\Component\DependencyInjection\Loader\Configurator\DefaultsConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServiceConfigurator;
use Override;

/**
 * EventServiceProvider class
 * 
 * @package Clicalmani\Foundation/flesco 
 * @author @Clicalmani\Foundation
 */
class EventServiceProvider implements ServiceProviderInterface
{
    protected string $path = 'app/Listeners';
    protected string $namespace = '\\App\\Listeners';

    #[Override]
    public function register(): void
    {
        app()->addService('events', [
            \Symfony\Component\EventDispatcher\EventDispatcher::class,
            static function(ServiceConfigurator|DefaultsConfigurator $config) {
                //
            }
        ]);

        app()->addService('events.discovery', [
            \Clicalmani\Foundation\Events\ListenerDiscovery::class,
            function(ServiceConfigurator|DefaultsConfigurator $config) {
                $config->args([
                    $this->path,
                    $this->namespace,
                    app()->dependency('service', 'events')
                ])->call('discover');
            }
        ]);
    }

    #[Override]
    public function boot(): void
    {
        // ...
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function setNamespace(string $namespace): void
    {
        $this->namespace = $namespace;
    }
}
