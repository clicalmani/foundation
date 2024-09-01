<?php
namespace Clicalmani\Foundation\Messenger\Sender;

use Clicalmani\Foundation\Messenger\Envelope\EnvelopeInterface;

interface SenderInterface
{
    public function dispatch(EnvelopeInterface $envelope) : void;
}
