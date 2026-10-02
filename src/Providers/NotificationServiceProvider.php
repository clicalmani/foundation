<?php
namespace Clicalmani\Core\Providers;

use Clicalmani\Core\Support\Facades\Config;
use Clicalmani\Notification\Middleware\LogNotificationMiddleware;
use Clicalmani\Notification\Middleware\RetryMiddleware;
use Override;

class NotificationServiceProvider extends ServiceProvider implements ServiceProviderInterface
{
    #[Override]
    public function boot(): void
    {
        \Clicalmani\Notification\ChannelRegistry::registerMany(
            app()->config['bootstrap']['notifications'] ?? []
        );
    }
}