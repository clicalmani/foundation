<?php
namespace Clicalmani\Foundation\Acme;

use Clicalmani\Foundation\Support\Facades\Arr;
use Clicalmani\Foundation\Support\Facades\Env;

class Configure implements \ArrayAccess, \JsonSerializable
{
    /**
     * App config
     * 
     * @var array
     */
    protected static $app_config;

    /**
     * Service kernel
     * 
     * @var array
     */
    protected static $kernel;

    /**
     * Http middlewares
     * 
     * @var array
     */
    protected static $http_kernel;

    protected static $paths = [];

    protected static $mail_config;

    protected static $sotorage = [];

    public function app(?string $key = null)
    {
        return $key ? Arr::get(static::$sotorage['app'], $key): static::$sotorage['app'];
    }

    public function mail(?string $key = null)
    {
        return $key ? Arr::get(@ static::$sotorage['mail'] ?? [], $key): static::$sotorage['mail'];
    }

    public function http(?string $key = null)
    {
        return $key ? Arr::get(@ static::$sotorage['http'] ?? [], $key): static::$sotorage['http'];
    }

    public function bootstrap(?string $key = null)
    {
        return $key ? Arr::get(@ static::$sotorage['bootstrap'] ?? [], $key): static::$sotorage['bootstrap'];
    }

    public function route(?string $key = null)
    {
        return $key ? Arr::get(@ static::$sotorage['route'] ?? [], $key): static::$sotorage['route'];
    }

    public function database(?string $key = null)
    {
        return $key ? Arr::get(@ static::$sotorage['database'] ?? [], $key): static::$sotorage['database'];
    }

    public function env(?string $key = null, ?string $default = null)
    {
        return $key ? Env::get($key, $default) : $_ENV;
    }

    public function offsetExists(mixed $offset): bool
    {
        return !!in_array( $offset, static::$sotorage);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return @ static::$sotorage[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        static::$sotorage[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset(static::$sotorage[$offset]);
    }

    public function jsonserialize(): array
    {
        return static::$sotorage;
    }

    public function set(string $key, mixed $value): void
    {
        Arr::set(static::$sotorage, $key, $value);
    }

    public function get(?string $key = null, $default = null)
    {
        if (NULL === $key) return static::$sotorage;
        return Arr::get(static::$sotorage, $key, $default);
    }

    public static function register(string $key, array $value)
    {
        static::$sotorage[$key] = $value;
    }
}