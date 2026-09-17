<?php
namespace Clicalmani\Core\Events;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class InjectDispatcher extends \Clicalmani\Core\Http\Controllers\InjectionLocator
{
    /**
     * InjectDispatcher constructor.
     *
     * @param object $dispatcher The active event dispatcher service layer.
     */
    public function __construct(private EventDispatcherInterface $dispatcher)
    {
        // Initialize the injection dispatcher with the provided service instance
    }

    public function handle(): ?object
    {
        // Provide the active dispatcher instance if the context targets or extends the core interface
        if (is_subclass_of($this->class, EventDispatcherInterface::class) || $this->class === EventDispatcherInterface::class) {
            return $this->dispatcher;
        }

        return null;
    }

    /**
     * Retrieves the active event dispatcher instance.
     *
     * @return object The resolved event dispatcher service instance.
     */
    public function getDispatcher(): object
    {
        return $this->dispatcher;
    }
}