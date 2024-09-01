<?php
namespace Clicalmani\Foundation\Messenger\Handler;

use Clicalmani\Foundation\Messenger\Envelope\EnvelopeInterface;

interface HandlerInterface
{
    public function handle(EnvelopeInterface $envelope) : EnvelopeInterface;
}
