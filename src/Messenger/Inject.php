<?php
namespace Clicalmani\Foundation\Messenger;

use Clicalmani\Foundation\Http\Controllers\InjectionLocator;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;

class Inject extends InjectionLocator
{
    public function __construct(private MessageBusInterface $bus)
    {}

    public function handle(): ?object
    {
        if (is_subclass_of($this->class, MessageBusInterface::class) || $this->class === MessageBusInterface::class) {
            return $this->bus;
        }
    }
}