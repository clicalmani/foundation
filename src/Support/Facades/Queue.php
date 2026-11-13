<?php
namespace Clicalmani\Core\Support\Facades;

use Clicalmani\Queue\Contracts\JobInterface;
use Override;

/**
 * @method static string push(string $queue, string|JobInterface $payload, int $delay = 0)
 * @method static ?array pop(string $queue)
 * @method static void delete(string $id)
 * @method static void release(string $id, int $delay = 0)
 * @method static void fail(string $id, string $reason)
 * @method static int size(string $queue)
 */
class Queue extends Facade
{
    #[Override]
    public static function getFacadeAccessor(): string
    {
        return 'queue';
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