<?php
namespace Clicalmani\Core\Providers;

use Override;

class NotificationServiceProvider extends ServiceProvider implements ServiceProviderInterface
{
    #[Override]
    public function register(): void
    {
        \Clicalmani\Notification\ChannelRegistry::registerMany(
            app()->config['bootstrap']['notifications'] ?? []
        );
    }

    public function boot(
        ?\Clicalmani\Core\Acme\Container $container = null
    ): void
    {}
}