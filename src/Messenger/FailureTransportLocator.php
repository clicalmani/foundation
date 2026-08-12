<?php
namespace Clicalmani\Foundation\Messenger;

use Psr\Container\ContainerInterface;
use Override;

/**
 * Class FailureTransportLocator
 * 
 * Provides an isolated service locator wrapper for resolving dead-letter and poison 
 * message failure transports. Supports explicitly mapped transport configurations 
 * alongside global dynamic fallbacks within the framework application layer.
 * 
 * @package Clicalmani\Foundation\Messenger
 * @author @clicalmani
 */
class FailureTransportLocator implements ContainerInterface
{
    /**
     * FailureTransportLocator constructor.
     * 
     * @param array<string, string> $map Associative mapping array routing standard transport handles to failure service identifiers.
     */
    public function __construct(private array $map)
    {
        // Constructor assignments handled via constructor property promotion
    }

    /**
     * Checks whether the locator can resolve a dedicated failure transport service matching the provided identity key.
     * 
     * @param string $id The source transport identity keyword evaluated.
     * @return bool True if a designated failure path or global wildcard identifier is registered, false otherwise.
     */
    #[Override]
    public function has(string $id): bool
    {
        // Accepts the transport if it is mapped OR if a global fallback exists
        return isset($this->map[$id]) || isset($this->map['*']);
    }

    /**
     * Resolves and retrieves the corresponding failure transport service instance linked to the given identity identifier.
     * 
     * @param string $id The source transport identity keyword being tracked.
     * @return mixed The corresponding concrete transport service instance retrieved from the central dependency container.
     */
    #[Override]
    public function get(string $id): mixed
    {
        $serviceId = $this->map[$id]
            ?? $this->map['*']               // Global fallback if defined
            ?? 'messenger.transport.failed'; // Ultimate fallback

        return container()->get($serviceId);
    }
}