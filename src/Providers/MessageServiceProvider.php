<?php
namespace Clicalmani\Foundation\Providers;

use Clicalmani\Foundation\Messenger\Handler\HandlersLocatorInterface;

abstract class MessageServiceProvider extends ServiceProvider
{
    /**
     * Registered handlers
     * 
     * @var \iterable
     */
    protected static $handlers = [];

    public function setHandlers(HandlersLocatorInterface $locator) : void
    {
        static::$handlers = $locator->getHandlers();
    }

    public function getHandlers(): iterable
    {
        return static::$handlers;
    }
}
