<?php

namespace Clicalmani\Foundation\Messenger;

use Psr\Container\ContainerInterface;
use Override;

/**
 * Class RetryStrategyManager
 * 
 * Implements a service locator interface specifically for managing message retry behaviors.
 * Employs lazy-loading strategies to dynamically resolve and initialize specific exponential 
 * backoff configurations from global runtime profiles on a per-transport basis.
 * 
 * @package Clicalmani\Foundation\Messenger
 * @author @clicalmani
 */
class RetryStrategyManager implements ContainerInterface
{
    /**
     * Map collection holding specific transport retry profiles.
     * 
     * @var array<string, array<string, mixed>>
     */
    protected array $strategies = [];

    /**
     * RetryStrategyManager constructor.
     * 
     * @param array<string, mixed> $config Central framework configuration block containing retry schemas.
     */
    public function __construct(array $config = [])
    {
        // Extract internal strategies while maintaining a safe structural array envelope fallback
        $this->strategies = (array) ($config['strategies'] ?? []);
    }

    /**
     * Indicates whether the locator container can resolve a dynamic backoff strategy for the specified target.
     * 
     * @param string $id The targeted transport queue name identifier.
     * @return bool Always returns true, as a fallback default strategy is dynamically guaranteed for any queue.
     */
    #[Override]
    public function has(string $id): bool
    {
        return true;
    }

    /**
     * Resolves and provides the concrete retry engine instance associated with the specified queue identifier.
     * Leverages dynamic factory generation to lazy-load the instance on-demand.
     * 
     * @param string $id The targeted transport queue name identifier being evaluated.
     * @return mixed A fully configured RetryStrategyInterface instance.
     */
    #[Override]
    public function get(string $id): mixed
    {
        return RetryStrategyFactory::make($id, $this->strategies);
    }
}