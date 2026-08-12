<?php
namespace Clicalmani\Foundation\Events;

use Broadcaster\SystemBroadcastListener as BroadcastListener;
use Symfony\Component\EventDispatcher\EventDispatcher;

class BroadcastEventDispatcher implements EventDispatcherDelegate
{
    public function __construct(private BroadcastListener $listener) {}

    public function handle(object $event, ?string $eventName, EventDispatcher $dispatcher): void
    {
        ($this->listener)($event, $eventName, $dispatcher);
    }
}