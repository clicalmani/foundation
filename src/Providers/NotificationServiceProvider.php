<?php
namespace Clicalmani\Core\Providers;

use Override;
use Symfony\Component\DependencyInjection\Loader\Configurator\DefaultsConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServiceConfigurator;

class NotificationServiceProvider extends ServiceProvider implements ServiceProviderInterface
{
    #[Override]
    public function register(): void
    {
        \Clicalmani\Notification\ChannelRegistry::registerMany(
            app()->config['bootstrap']['notifications'] ?? []
        );

        // Manager
        app()->addService(
            'notification', 
            \Clicalmani\Notification\NotificationManager::class,
            function(ServiceConfigurator|DefaultsConfigurator $config) {
                $config->args([]);
            },
        );
    }

    public function boot(
        ?\Clicalmani\Core\Acme\Container $container = null
    ): void
    {}
}