<?php
namespace Clicalmani\Foundation\Messenger\Envelope;

use Clicalmani\Foundation\Messenger\Message\Message;

interface EnvelopeInterface
{
    public function getMessage() : Message;

    public function getStamps();
}
