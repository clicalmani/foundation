<?php
namespace Clicalmani\Core\Support\Facades;

/**
 * @method \Broadcaster\Drivers\BroadcasterInterface driver(?string $name = null)
 * @method string subscribe(array $topics = [], int $ttl = 900, array $extraClaims = [])
 * @method string publish(array $topics = [], int $ttl = 900, array $extraClaims = [])
 */
class Broadcast extends Facade
{
    public static function getFacadeAccessor(): string
    {
        return \Broadcaster\BroadcastManager::class;
    }

    /**
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic(string $method, array $arguments): mixed
    {
        return static::getFacadeRoot()->$method(...$arguments);
    }
}