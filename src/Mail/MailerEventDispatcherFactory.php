<?php
namespace Clicalmani\Foundation\Mail;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\EventListener\EnvelopeListener;
use Symfony\Component\Mime\Address;

class MailerEventDispatcherFactory
{
    public function create(): EventDispatcherInterface
    {
        $dispatcher = new EventDispatcher();

        $fromAddress = config('mail.from.address');
        $fromName    = config('mail.from.name', config('app.name', 'Tonka'));

        if ($fromAddress) {
            $dispatcher->addSubscriber(
                new EnvelopeListener(new Address($fromAddress, $fromName))
            );
        }

        return $dispatcher;
    }
}