<?php

namespace Clicalmani\Core\Messenger\Stamp;

use Symfony\Component\Messenger\Stamp\StampInterface;

/**
 * Class RetryingStamp
 * 
 * Custom operational stamp metadata attached to Symfony Messenger Envelopes.
 * Tracks and increments transient processing attempt counts in real time across
 * individual worker execution cycles.
 * 
 * @package Clicalmani\Core\Messenger\Stamp
 * @author @clicalmani
 */
class RetryingStamp implements StampInterface
{
    /**
     * RetryingStamp constructor.
     * 
     * @param int $retryCount The current total number of execution retries performed on the message.
     */
    public function __construct(
        public readonly int $retryCount
    ) {
        // Parameter tracking handled via native constructor property promotion
    }
}