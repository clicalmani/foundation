<?php
namespace Clicalmani\Foundation\Mail;

use Symfony\Component\Mailer\MailerInterface;

class SystemMailableListener
{
    public function __invoke(object $event, MailerInterface $mailer)
    {
        if (is_subclass_of($event, Mailable::class)) {
            $listener = container()->get(MailableListener::class);
            ($listener)($event, $mailer);
        }
    }
}