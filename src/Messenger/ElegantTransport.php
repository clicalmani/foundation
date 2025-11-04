<?php
namespace Clicalmani\Foundation\Messenger;

/**
 * https://symfony.com/doc/current/messenger/custom-transport.html
 * https://symfony.com/doc/current/components/messenger.html#your-own-sender
 */

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class ElegantTransport implements TransportInterface
{
    private ?SerializerInterface $serializer = null;

    public function __construct(
        private \Clicalmani\Database\Interfaces\DBInterface $db,
        ?SerializerInterface $serializer = null
    )
    {
        $this->serializer = $serializer;
    }

    public function get(): iterable
    {
        //
    }

    public function ack(Envelope $envelope): void
    {
        //
    }

    public function reject(Envelope $envelope): void
    {
        //
    }

    public function send(Envelope $envelope): Envelope
    {
        //
    }
}