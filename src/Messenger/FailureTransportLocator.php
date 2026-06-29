<?php
namespace Clicalmani\Foundation\Messenger;

use Psr\Container\ContainerInterface;

class FailureTransportLocator implements ContainerInterface
{
    public function __construct(private array $map)
    {}

    public function has(string $id): bool
    {
        // Accepts the transport if it is mapped OR if a global fallback exists
        return isset($this->map[$id]) || isset($this->map['*']);
    }

    public function get(string $id): mixed
    {
        $serviceId = $this->map[$id]
            ?? $this->map['*']               // Global fallback if defined
            ?? 'messenger.transport.failed'; // Ultimate fallback

        return container()->get($serviceId);
    }
}