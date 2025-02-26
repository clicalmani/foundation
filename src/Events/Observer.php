<?php
namespace Clicalmani\Foundation\Events;

interface Observer
{
    /**
     * Notify the observer
     * 
     * @return void
     */
    public function notify($event) : void;
}