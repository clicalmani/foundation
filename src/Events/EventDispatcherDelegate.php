<?php
namespace Clicalmani\Foundation\Events;

use Symfony\Component\EventDispatcher\EventDispatcher;

interface EventDispatcherDelegate
{
    /**
     * Traite l'événement à sa manière (broadcast, envoi de mail, log, etc.)
     * sans interrompre la chaîne de dispatch.
     */
    public function handle(object $event, ?string $eventName, EventDispatcher $dispatcher): void;
}