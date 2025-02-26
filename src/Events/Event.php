<?php
namespace Clicalmani\Foundation\Events;

abstract class Event implements Observable
{
    private static $instance;

    /**
     * Observers
     * 
     * @var \Clicalmani\Foundation\Events\Observer[]
     */
    protected static $observers = [];

    /**
     * Get the instance
     * 
     * @return self
     */
    public static function getInstance() : self
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    public function attach(Observer $observer) : void
    {
        $this->observers[] = $observer;
    }

    public function detach(Observer $observer) : void
    {
        unset(self::$observers[array_search($observer, self::$observers)]);
    }

    public function dispatch(): void
    {
        foreach (self::$observers as $observer) {
            $observer->notify($this);
        }
    }
}