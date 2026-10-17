<?php

declare(strict_types=1);

namespace Clicalmani\Core\Tests\Cache;

use Clicalmani\Core\Cache\CacheAdapterContext;
use Clicalmani\Core\Cache\CacheManager;
use Clicalmani\Core\Tests\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class CacheAdapterContextTest extends TestCase
{
    private CacheManager $manager;

    private CacheAdapterContext $context;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setConfig('cache', [
            'default' => 'array',
            'prefix'  => 'tonka_tests_',
            'stores'  => [
                'array' => ['driver' => 'array'],
            ],
        ]);

        $this->manager = new CacheManager;
        $this->context = new CacheAdapterContext($this->manager);
    }

    public function test_a_fresh_context_knows_no_driver(): void
    {
        $this->assertSame([], $this->context->drivers());
        $this->assertFalse($this->context->has('array'));
    }

    public function test_extend_registers_a_factory_and_is_fluent(): void
    {
        $returned = $this->context->extend('memory', fn () => new ArrayAdapter);

        $this->assertSame($this->context, $returned);
        $this->assertTrue($this->context->has('memory'));
        $this->assertSame(['memory'], $this->context->drivers());
    }

    public function test_extend_many_registers_every_factory(): void
    {
        $this->context->extendMany([
            'one' => fn () => new ArrayAdapter,
            'two' => fn () => new ArrayAdapter,
        ]);

        $this->assertSame(['one', 'two'], $this->context->drivers());
    }

    public function test_extend_overrides_a_previously_registered_driver(): void
    {
        $this->context->extend('array', fn () => new ArrayAdapter(0, false));
        $this->context->extend('array', fn () => new ArrayAdapter);

        $this->assertSame(['array'], $this->context->drivers());
    }

    public function test_resolve_builds_the_adapter_and_forwards_the_whole_signature(): void
    {
        $received = [];

        $this->context->extend('probe', function (array $config, string $prefix, CacheManager $manager) use (&$received) {
            $received = ['config' => $config, 'prefix' => $prefix, 'manager' => $manager];

            return new ArrayAdapter;
        });

        $adapter = $this->context->resolve('probe', ['table' => 'cache'], 'pfx_');

        $this->assertInstanceOf(CacheItemPoolInterface::class, $adapter);
        $this->assertSame(['table' => 'cache'], $received['config']);
        $this->assertSame('pfx_', $received['prefix']);
        $this->assertSame($this->manager, $received['manager']);
    }

    public function test_resolve_calls_the_factory_again_on_every_call(): void
    {
        $this->context->extend('array', fn () => new ArrayAdapter);

        $this->assertNotSame(
            $this->context->resolve('array', [], 'pfx_'),
            $this->context->resolve('array', [], 'pfx_')
        );
    }

    public function test_resolve_refuses_an_unregistered_driver(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Unsupported cache driver [dynamodb]. Register it via CacheAdapterContext::extend().'
        );

        $this->context->resolve('dynamodb', [], 'pfx_');
    }

    public function test_native_adapters_are_registered_by_the_manager(): void
    {
        $native = $this->manager->getContext()->drivers();

        sort($native);

        $this->assertSame(
            ['array', 'chain', 'database', 'file', 'filesystem', 'memcached', 'redis'],
            $native
        );
    }
}
