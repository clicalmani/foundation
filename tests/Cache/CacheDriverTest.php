<?php

declare(strict_types=1);

namespace Clicalmani\Core\Tests\Cache;

use Clicalmani\Core\Cache\CacheDriver;
use Clicalmani\Core\Tests\TestCase;

final class CacheDriverTest extends TestCase
{
    public function test_each_case_carries_its_driver_name(): void
    {
        $expected = [
            'array'      => CacheDriver::Array,
            'file'       => CacheDriver::File,
            'filesystem' => CacheDriver::Filesystem,
            'redis'      => CacheDriver::Redis,
            'memcached'  => CacheDriver::Memcached,
            'database'   => CacheDriver::Database,
            'chain'      => CacheDriver::Chain,
        ];

        foreach ($expected as $value => $case) {
            $this->assertSame($value, $case->value);
        }

        $this->assertCount(count($expected), CacheDriver::cases());
    }

    public function test_from_alias_maps_file_and_filesystem_to_the_same_driver(): void
    {
        $this->assertSame(CacheDriver::Filesystem, CacheDriver::fromAlias('file'));
        $this->assertSame(CacheDriver::Filesystem, CacheDriver::fromAlias('filesystem'));
    }

    public function test_from_alias_returns_the_canonical_case_for_known_drivers(): void
    {
        $this->assertSame(CacheDriver::Array, CacheDriver::fromAlias('array'));
        $this->assertSame(CacheDriver::Redis, CacheDriver::fromAlias('redis'));
        $this->assertSame(CacheDriver::Memcached, CacheDriver::fromAlias('memcached'));
        $this->assertSame(CacheDriver::Database, CacheDriver::fromAlias('database'));
        $this->assertSame(CacheDriver::Chain, CacheDriver::fromAlias('chain'));
    }

    /**
     * 'file' est intercepté par le match() avant tryFrom() : l'alias ne rend donc
     * jamais CacheDriver::File, qui reste accessible uniquement par tryFrom().
     */
    public function test_from_alias_never_returns_the_file_case(): void
    {
        $this->assertSame(CacheDriver::File, CacheDriver::tryFrom('file'));
        $this->assertNotSame(CacheDriver::File, CacheDriver::fromAlias('file'));
    }

    public function test_from_alias_returns_null_for_an_unknown_driver(): void
    {
        $this->assertNull(CacheDriver::fromAlias('dynamodb'));
        $this->assertNull(CacheDriver::fromAlias(''));
    }
}
