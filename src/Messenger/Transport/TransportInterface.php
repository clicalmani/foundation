<?php
namespace Clicalmani\Foundation\Messenger\Transport;

use Clicalmani\Foundation\Messenger\Message\MessageInterface;

interface TransportInterface
{
    /**
     * Check transport
     * 
     * @return bool
     */
    public function check() : bool;

    /**
     * Send message
     * 
     * @param \Clicalmani\Foundation\Messenger\Message\MessageInterface $message
     * @return void
     */
    public function send(MessageInterface $message) : void;
}
