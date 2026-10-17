<?php
namespace Clicalmani\Core\Events;

use Clicalmani\Core\Events\Listeners\SystemQueryListener;
use Clicalmani\Database\Events\Query;
use Symfony\Component\EventDispatcher\EventDispatcher;

class QueryEventDispatcher implements EventDispatcherDelegate
{
    public function __construct(
        private SystemQueryListener $listener,
    ) {}

    public function handle(object $event, ?string $eventName, EventDispatcher $dispatcher): void
    {
        // On ne traite que les événements Query (et dérivés)
        if (! $event instanceof Query) {
            return;
        }

        ($this->listener)($event);
    }
}