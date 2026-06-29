<?php

namespace Clicalmani\Foundation\Messenger;

use Symfony\Component\Messenger\Retry\MultiplierRetryStrategy;
use Symfony\Component\Messenger\Retry\RetryStrategyInterface;

class RetryStrategyFactory
{
    /**
     * Creates a retry strategy based on a transport's configuration
     */
    public static function make(string $transportName, array $config = []): RetryStrategyInterface
    {
        // Default configuration if the transport is not configured
        $defaults = $config[config('retry_strategy.default')] ?? [
            'max_retries' => 3,
            'delay'       => 1000,
            'max_delay'   => 0,
            'multiplier'  => 2,
            'jitter'      => 0.1
        ];

        $settings = array_merge($defaults, $config[$transportName] ?? []);

        return new MultiplierRetryStrategy(
            maxRetries: (int) $settings['max_retries'],
            delayMilliseconds: (int) $settings['delay'],
            multiplier: (float) $settings['multiplier'],
            maxDelayMilliseconds: (int) $settings['max_delay'],
            jitter: (float) $settings['jitter']
        );
    }
}