<?php
namespace Clicalmani\Foundation\Messenger\Envelope;

use Clicalmani\Foundation\Messenger\Message\Message;

class Envelope implements EnvelopeInterface
{
    /**
     * Message
     * 
     * @var \Clicalmani\Foundation\Messenger\Message\Message
     */
    private $message;

    public function __construct($envelope)
    {
        if ($envelope instanceof self) $this->message = $envelope->getMessage();
        elseif ($envelope instanceof Message) $this->message = $envelope;
        else $this->message = new Message($envelope);
    }
    
    public function getMessage(): Message
    {
        return $this->message;
    }

    public function getStamps()
    {
        //
    }
}
