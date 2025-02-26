<?php
namespace Clicalmani\Foundation\Events;

interface Observable
{
    /**
     * Attach an observer
     * 
     * @param Observer $observer
     * @return void
     */
    public function attach(Observer $observer) : void;

    /**
     * Detach an observer
     * 
     * @param Observer $observer
     * @return void
     */
    public function detach(Observer $observer) : void;

    /**
     * Dispatch the event
     * 
     * @return void
     */
    public function dispatch() : void;
}