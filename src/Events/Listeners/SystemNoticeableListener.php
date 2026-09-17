<?php
namespace Clicalmani\Core\Events\Listeners;

use Clicalmani\Core\Events\NoticeableInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\MailerInterface;

class SystemNoticeableListener
{
    public function __invoke(object $event, EventDispatcherInterface $dispatcher)
    {
        if (is_subclass_of($event, NoticeableInterface::class)) {
            $listener = container()->get(NoticeableListener::class);
            ($listener)($event, $dispatcher);
        }
    }
}