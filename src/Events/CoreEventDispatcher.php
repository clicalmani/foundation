<?php
namespace Clicalmani\Foundation\Events;

use Symfony\Component\EventDispatcher\EventDispatcher;

class CoreEventDispatcher extends EventDispatcher
{
    /** @var EventDispatcherDelegate[] */
    private array $delegates = [];

    /**
     * @param iterable<EventDispatcherDelegate> $delegates
     */
    public function __construct(iterable $delegates = [])
    {
        parent::__construct();

        foreach ($delegates as $delegate) {
            $this->addDelegate($delegate);
        }
    }

    public function addDelegate(EventDispatcherDelegate $delegate): static
    {
        $this->delegates[] = $delegate;
        return $this;
    }

    public function dispatch(object $event, ?string $eventName = null): object
    {
        foreach ($this->delegates as $delegate) {
            try {
                $delegate->handle($event, $eventName, $this);
            } catch (\Throwable $e) {
                throw $e;
            }
        }

        return parent::dispatch($event, $eventName);
    }
}