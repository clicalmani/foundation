<?php

namespace Clicalmani\Core\Messenger;

use Symfony\Component\Messenger\Retry\MultiplierRetryStrategy;
use Symfony\Component\Messenger\Retry\RetryStrategyInterface;

/**
 * Class RetryStrategyFactory
 * 
 * @package Clicalmani\Core\Messenger
 * @author @clicalmani
 */
class RetryStrategyFactory
{
    public static function make(string $transportName, array $config = []): RetryStrategyInterface
    {
        $defaultKey = (string) config('messenger.retry_strategy.default', 'elegant');
        
        $defaults = $config[$defaultKey] ?? [
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