<?php

namespace Clicalmani\Core\Mail\Transport;

use Clicalmani\Core\Mail\Mailable;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;

class BuildingTransport implements TransportInterface
{
    public function __construct(private TransportInterface $inner) {}

    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
    {
        if ($message instanceof Mailable) {
            $message->ensureBuilt();
        }

        return $this->inner->send($message, $envelope);
    }

    public function __toString(): string
    {
        return (string) $this->inner;
    }
}