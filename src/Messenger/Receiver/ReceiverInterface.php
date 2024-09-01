<?php
namespace Clicalmani\Foundation\Messenger\Receiver;

use Clicalmani\Foundation\Messenger\Envelope\EnvelopeInterface;

interface ReceiverInterface
{
    /**
     * @return iterable<int, \Clicalmani\Foundation\Messenger\Message\MessageInterface>
     */
    public function get() : iterable;

    /**
     * Store new message
     * 
     * @param \Clicalmani\Foundation\Messenger\Envelope\EnvelopeInterface $envelope
     */
    public function store(EnvelopeInterface $envelope) : void;
}
