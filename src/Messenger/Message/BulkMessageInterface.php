<?php
namespace Clicalmani\Foundation\Messenger\Message;

interface BulkMessageInterface
{
    public function getToAddresses() : iterable;

    public function getFromAddress() : string;

    public function getBody() : string;
}
