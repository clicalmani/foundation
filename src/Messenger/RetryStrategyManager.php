<?php

namespace Clicalmani\Foundation\Messenger;

use Psr\Container\ContainerInterface;

class RetryStrategyManager implements ContainerInterface
{
    protected array $strategies = [];

    public function __construct(array $config = [])
    {
        // Retrieve all strategies defined in the global configuration
        $this->strategies = $config['strategies'] ?? [];
    }

    public function has(string $id): bool
    {
        // The manager can handle any requested transport
        return true;
    }

    public function get(string $id): mixed
    {
        // Dynamically create the strategy on the fly (Lazy loading)
        return RetryStrategyFactory::make($id, $this->strategies);
    }
}