<?php
namespace Clicalmani\Foundation\Messenger\Transport;

use App\Providers\MessageServiceProvider;
use Clicalmani\Foundation\Messenger\Envelope\Envelope;
use Clicalmani\Foundation\Messenger\Message\MessageInterface;

abstract class Transport implements TransportInterface
{
    public function __construct(private string $dns)
    {
        //
    }

    public function send(MessageInterface $message) : void
    {
        $handlers = (new MessageServiceProvider)->getHandlers();

        if (NULL === $handler = @$handlers[$message::class]) throw new \Exception("No handler");

        (new $handler)->handle(new Envelope($message));
    }
}
