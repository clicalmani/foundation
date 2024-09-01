<?php
namespace Clicalmani\Foundation\Messenger\Receiver;

use Clicalmani\Foundation\Messenger\Envelope\EnvelopeInterface;
use Clicalmani\Foundation\Messenger\Receiver\ReceiverInterface;
use Clicalmani\Foundation\Messenger\Receiver\Storage\Storage;
use Clicalmani\Foundation\Messenger\Receiver\Storage\StorageInterface;

class Receiver implements ReceiverInterface
{
    private StorageInterface $storage;

    public function __construct()
    {
        $this->storage = new Storage;
    }

    public function get(): iterable
    {
        $last_id = $this->storage->lastID();
        $count = 0;

        while ($count <= $last_id) {
            if (isset($this->storage[$count])) yield $this->storage->get($count);
            $count++;
        }
    }

    public function store(EnvelopeInterface $envelope) : void
    {
        $this->storage[] = $envelope->getMessage();
    }
}
