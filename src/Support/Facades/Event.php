<?php
namespace Clicalmani\Core\Support\Facades;

use Clicalmani\Core\Events\CoreEventDispatcher;

class Event extends Facade
{
    public static function getFacadeAccessor(): string
    {
        return 'event';
    }

    /**
     * @param string $event
     * @param \Closure $callback
     */
    public static function listen(string $event, \Closure $callback, ?int $priority = 0) : void
    {
        container()->get(CoreEventDispatcher::class)->addListener($event, $callback, $priority);
    }

    public static function addSubscribe(string $subscriber) : void
    {
        container()->get(CoreEventDispatcher::class)->addSubscriber($subscriber);
    }
}