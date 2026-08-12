<?php

namespace Clicalmani\Foundation\Messenger\Stamp;

use Symfony\Component\Messenger\Stamp\StampInterface;

/**
 * Class FailedDurableStamp
 * 
 * Custom operational stamp metadata attached to Symfony Messenger Envelopes. 
 * Serves as an explicit lifecycle marker flag to signal to underlying persistent 
 * transports that an envelope has exhausted all configured retry processing thresholds 
 * and is being isolated into permanent quarantine containment.
 * 
 * @package Clicalmani\Foundation\Messenger\Stamp
 * @author @clicalmani
 */
class FailedDurableStamp implements StampInterface
{
    /**
     * FailedDurableStamp constructor.
     * 
     * @param string $message Contextual reason or message outlining the cause of the final routing failure.
     */
    public function __construct(
        protected string $message = 'Max retries exceeded'
    ) {
        // Param tracking handled via constructor property promotion
    }

    /**
     * Retrieves the structural reason or trace message explaining why the envelope was flagged as a permanent failure.
     * 
     * @return string The contextual exception summary or message trace.
     */
    public function getMessage(): string
    {
        return $this->message;
    }
}