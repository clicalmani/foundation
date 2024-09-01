<?php
namespace Clicalmani\Foundation\Messenger\Handler;

use Clicalmani\Foundation\Messenger\Envelope\EnvelopeInterface;

class Handler implements HandlerInterface
{
    public function handle(EnvelopeInterface $envelope) : EnvelopeInterface
    {
        return $envelope;
    }
}
