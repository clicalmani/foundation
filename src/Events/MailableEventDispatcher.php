<?php
namespace Clicalmani\Foundation\Events;

use Clicalmani\Foundation\Mail\SystemMailableListener;
use Symfony\Component\EventDispatcher\EventDispatcher;

class MailableEventDispatcher implements EventDispatcherDelegate
{
    public function __construct(private SystemMailableListener $listener) {}

    public function handle(object $event, ?string $eventName, EventDispatcher $dispatcher): void
    {
        ($this->listener)($event, container()->get('mailer.raw'), $eventName, $dispatcher);
    }
}