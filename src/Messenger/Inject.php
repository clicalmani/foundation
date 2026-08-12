<?php
namespace Clicalmani\Foundation\Messenger;

use Clicalmani\Foundation\Http\Controllers\InjectionLocator;
use Symfony\Component\Messenger\MessageBusInterface;
use Override;

/**
 * Class Inject
 * 
 * Custom injection locator responsible for resolving and provisioning the active 
 * MessageBusInterface instance during dynamic application controller and service injection workflows.
 * 
 * @package Clicalmani\Foundation\Messenger
 * @author @clicalmani
 */
class Inject extends InjectionLocator
{
    /**
     * Inject constructor.
     * 
     * @param MessageBusInterface $bus The active central message dispatching bus service layer.
     */
    public function __construct(private MessageBusInterface $bus)
    {
        parent::__construct();
    }

    /**
     * Evaluates the requested type signature and returns the primary message bus instance if applicable.
     * 
     * @return object|null The resolved MessageBusInterface instance if supported, or null to fall back.
     */
    #[Override]
    public function handle(): ?object
    {
        // Provide the active bus instance if the context targets or extends the core interface
        if (is_subclass_of($this->class, MessageBusInterface::class) || $this->class === MessageBusInterface::class) {
            return $this->bus;
        }

        return null;
    }
}