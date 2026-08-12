<?php

namespace Clicalmani\Foundation\Messenger;

use Symfony\Component\Messenger\Retry\MultiplierRetryStrategy;
use Symfony\Component\Messenger\Retry\RetryStrategyInterface;

/**
 * Class RetryStrategyFactory
 * 
 * Factory utility responsible for parsing queue execution parameters and assembling concrete 
 * RetryStrategyInterface instances. Provisions failure fallback rules, linear or exponential 
 * delay backoffs, and processing jitters dynamically per specific transport configurations.
 * 
 * @package Clicalmani\Foundation\Messenger
 * @author @clicalmani
 */
class RetryStrategyFactory
{
    /**
     * Creates and provisions a specialized retry strategy based on runtime transport configurations.
     * 
     * @param string $transportName The unique identifier key representing the active targeted transport queue.
     * @param array<string, array<string, mixed>> $config The top-level collection containing structural retry profiles.
     * @return RetryStrategyInterface A fully provisioned exponential multiplier backoff retry manager strategy.
     */
    public static function make(string $transportName, array $config = []): RetryStrategyInterface
    {
        // Define operational default profiles used when the target queue lacks custom parameters
        $defaultKey = (string) config('retry_strategy.default');
        
        $defaults = $config[$defaultKey] ?? [
            'max_retries' => 3,
            'delay'       => 1000,
            'max_delay'   => 0,
            'multiplier'  => 2,
            'jitter'      => 0.1
        ];

        // Safely overlay specific transport-level parameters over base architectural configurations
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