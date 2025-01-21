<?php
namespace Clicalmani\Foundation\Events;

interface EventHandler
{
    /**
     * Handle event
     * 
     * @param string $event
     * @param mixed $data
     * @return void
     */
    public function handle(string $event, mixed $data = null) : void;
}