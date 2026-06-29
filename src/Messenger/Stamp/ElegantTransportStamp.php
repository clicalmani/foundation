<?php

namespace Clicalmani\Foundation\Messenger\Stamp;

use Symfony\Component\Messenger\Stamp\StampInterface;

/**
 * Custom stamp to preserve the database identifier
 * throughout the message lifecycle within the Worker.
 */
class ElegantTransportStamp implements StampInterface
{
    /**
     * @param int $id The unique identifier of the message in the database table
     */
    public function __construct(
        private readonly int $id
    ) {}

    /**
     * Retrieves the message identifier from the database.
     * * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
}