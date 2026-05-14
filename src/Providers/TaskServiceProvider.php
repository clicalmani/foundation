<?php
namespace Clicalmani\Foundation\Providers;

use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\RecurringMessage;
use App\Messages\ClearCacheMessage;
use Override;

class TaskServiceProvider implements ServiceProviderInterface
{
    public static function getSchedule(): Schedule
    {
        return (new Schedule())->add(
            // Envoie ce message au Bus toutes les heures
            RecurringMessage::every('1 hour', new ClearCacheMessage())
        );
    }

    #[Override]
    public function register(): void
    {
        throw new \Exception('Not implemented');
    }

    #[Override]
    public function boot(): void
    {
        throw new \Exception('Not implemented');
    }
}