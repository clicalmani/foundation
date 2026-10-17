<?php
namespace Clicalmani\Core\Messenger;

use Clicalmani\Core\Http\Controllers\InjectionLocator;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Class Inject
 * 
 * @package Clicalmani\Core\Messenger
 * @author @clicalmani
 */
class Inject extends InjectionLocator
{
    public function __construct(private MessageBusInterface $bus)
    {
        parent::__construct();
    }

    public function handle(): ?object
    {
        if (!is_subclass_of($this->class, MessageBusInterface::class) 
            && $this->class !== MessageBusInterface::class) {
            return null;
        }

        return $this->bus;
    }
}