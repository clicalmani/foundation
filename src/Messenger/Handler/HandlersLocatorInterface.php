<?php
namespace Clicalmani\Foundation\Messenger\Handler;

use Clicalmani\Foundation\Messenger\Envelope;

interface HandlersLocatorInterface
{
    /**
     * Returns the registered handlers.
     *
     * @return iterable<int, HandlerDescriptor>
     */
    public function getHandlers(): iterable;
}
