<?php
namespace Clicalmani\Foundation\Messenger;

use Clicalmani\Foundation\Messenger\Envelope\EnvelopeInterface;

interface MessageBusInterface
{
    /**
     * Dispatches the given message.
     *
     * @param object|EnvelopeInterface  $message The message or the message pre-wrapped in an envelope
     */
    public function dispatch(object $message): EnvelopeInterface;
}
