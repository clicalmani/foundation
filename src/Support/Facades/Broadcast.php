<?php
namespace Clicalmani\Core\Support\Facades;

use Override;

/**
 * @method \Broadcaster\Drivers\BroadcasterInterface driver(?string $name = null)
 * @method string subscribe(array $topics = [], int $ttl = 900, array $extraClaims = [])
 * @method string publish(array $topics = [], int $ttl = 900, array $extraClaims = [])
 */
class Broadcast extends Facade
{
    #[Override]
    public static function getFacadeAccessor(): string
    {
        return \Broadcaster\BroadcastManager::class;
    }
}