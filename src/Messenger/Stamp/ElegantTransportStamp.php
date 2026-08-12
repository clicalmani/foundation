<?php

namespace Clicalmani\Foundation\Messenger\Stamp;

use Symfony\Component\Messenger\Stamp\StampInterface;

/**
 * Class ElegantTransportStamp
 * 
 * Custom operational stamp metadata attached to Symfony Messenger Envelopes. 
 * Preserves the underlying persistent database row identifier throughout the 
 * consumer lifecycle inside active queue workers.
 * 
 * @package Clicalmani\Foundation\Messenger\Stamp
 * @author @clicalmani
 */
class ElegantTransportStamp implements StampInterface
{
    /**
     * ElegantTransportStamp constructor.
     * 
     * @param int $id The unique primary key identifier of the message row inside the database.
     */
    public function __construct(
        private readonly int $id
    ) {
        // Parameter tracking handled via native constructor property promotion
    }

    /**
     * Retrieves the tracking database primary key identifier stored within the envelope.
     * 
     * @return int The unique identity record number.
     */
    public function getId(): int
    {
        return $this->id;
    }
}