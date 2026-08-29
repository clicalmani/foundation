<?php
namespace Clicalmani\Foundation\Events;

use Clicalmani\Foundation\Events\Listeners\SystemNoticeableListener;
use Clicalmani\Foundation\Mail\SystemMailableListener;
use Symfony\Component\EventDispatcher\EventDispatcher;

class NoticeableEventDispatcher implements EventDispatcherDelegate
{
    public function __construct(private SystemNoticeableListener $listener) {}

    public function handle(object $event, ?string $eventName, EventDispatcher $dispatcher): void
    {
        ($this->listener)($event, $dispatcher, $eventName, );
    }
}