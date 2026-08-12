<?php
namespace Clicalmani\Foundation\Events;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Broadcaster\SystemBroadcastListener;
use Clicalmani\Foundation\Mail\SystemMailableListener;

class BroadcastAwareEventDispatcher extends EventDispatcher
{
    public function __construct(
        private SystemBroadcastListener $systemListener,
        private SystemMailableListener $mailableListener
    )
    {
        parent::__construct();
    }

    public function dispatch(object $event, ?string $eventName = null): object
    {
        ($this->systemListener)($event, $eventName, $this);
        ($this->mailableListener)($event, $eventName, $this);

        return parent::dispatch($event, $eventName);
    }
}