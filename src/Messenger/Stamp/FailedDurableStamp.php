<?php

namespace Clicalmani\Foundation\Messenger\Stamp;

use Symfony\Component\Messenger\Stamp\StampInterface;

/**
 * This Stamp serves as a flag to notify ElegantTransport::reject() 
 * that the message has exhausted all its retries and is being sent to quarantine.
 */
class FailedDurableStamp implements StampInterface
{
    public function __construct(
        protected string $message = 'Max retries exceeded'
    ) {}

    /**
     * Retrieves the message or reason for marking as a permanent failure
     */
    public function getMessage(): string
    {
        return $this->message;
    }
}