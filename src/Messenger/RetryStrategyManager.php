<?php

namespace Clicalmani\Core\Messenger;

use Psr\Container\ContainerInterface;
use Override;

/**
 * Class RetryStrategyManager
 * 
 * @package Clicalmani\Core\Messenger
 * @author @clicalmani
 */
class RetryStrategyManager implements ContainerInterface
{
    protected array $strategies = [];

    public function __construct(array $config = [])
    {
        // Extract internal strategies while maintaining a safe structural array envelope fallback
        $this->strategies = (array) ($config['strategies'] ?? []);
    }

    public function has(string $id): bool
    {
        return true;
    }

    public function get(string $id): mixed
    {
        return RetryStrategyFactory::make($id, $this->strategies);
    }
}