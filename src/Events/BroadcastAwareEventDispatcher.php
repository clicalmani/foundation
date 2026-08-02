<?php
namespace Clicalmani\Foundation\Events;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Broadcaster\SystemBroadcastListener;

class BroadcastAwareEventDispatcher extends EventDispatcher
{
    public function __construct(private SystemBroadcastListener $systemListener)
    {
        parent::__construct();
    }

    public function dispatch(object $event, ?string $eventName = null): object
    {
        ($this->systemListener)($event, $eventName, $this);

        return parent::dispatch($event, $eventName);
    }
}