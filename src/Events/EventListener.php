<?php
namespace Clicalmani\Foundation\Events;

abstract class EventListener
{
    /**
     * Listen event
     * 
     * @param string $event Event name
     * @param mixed $handler Event handler
     * @return void
     */
    public abstract function listen(string $event, mixed $handler) : void;
}