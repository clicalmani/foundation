<?php

namespace Clicalmani\Core\Support\Facades;

/**
 * @method static \Clicalmani\Metrics\MetricsManager increment(string $key, int $value = 1, array $tags = [])
 * @method static \Clicalmani\Metrics\MetricsManager decrement(string $key, int $value = 1, array $tags = [])
 * @method static \Clicalmani\Metrics\MetricsManager gauge(string $key, float|int $value, array $tags = [])
 * @method static \Clicalmani\Metrics\MetricsManager timing(string $key, float $ms, array $tags = [])
 * @method static mixed measure(string $key, callable $callback, array $tags = [])
 * @method static mixed withTags(array $tags, callable $callback)
 * @method static \Clicalmani\Metrics\Contracts\MetricsDriver driver()
 * @method static \Clicalmani\Metrics\MetricsManager setDriver(\Clicalmani\Metrics\Contracts\MetricsDriver $driver)
 * @method static \Clicalmani\Metrics\MetricsManager use(string $driverClass, mixed ...$args)
 * @method static array all()
 * @method static \Clicalmani\Metrics\MetricsManager flush()
 * @method static \Clicalmani\Metrics\MetricsManager defaultTags(array $tags)
 *
 * @see \Clicalmani\Metrics\MetricsManager
 */
final class Metrics extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'metrics';
    }

    /**
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic(string $method, array $arguments): mixed
    {
        return static::getFacadeRoot()?->$method(...$arguments);
    }
}