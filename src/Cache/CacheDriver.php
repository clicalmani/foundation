<?php

namespace Clicalmani\Core\Cache;

enum CacheDriver: string
{
    case Array      = 'array';
    case File       = 'file';
    case Filesystem = 'filesystem';
    case Redis      = 'redis';
    case Memcached  = 'memcached';
    case Database   = 'database';
    case Chain      = 'chain';

    /**
     * Alias internes → driver canonique.
     */
    public static function fromAlias(string $driver): ?self
    {
        return match ($driver) {
            'file', 'filesystem' => self::Filesystem,
            default              => self::tryFrom($driver),
        };
    }
}