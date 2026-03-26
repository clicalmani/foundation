<?php
namespace Clicalmani\Foundation\Acme;

use Clicalmani\Foundation\Support\Facades\Arr;
use Clicalmani\Foundation\Support\Facades\Env;

class Configure implements \ArrayAccess, \JsonSerializable
{
    protected static $storage = [];

    public function app(?string $key = null)
    {
        return $key ? Arr::get(@ static::$storage['app'] ?? [], $key): static::$storage['app'];
    }

    public function mail(?string $key = null)
    {
        return $key ? Arr::get(@ static::$storage['mail'] ?? [], $key): static::$storage['mail'];
    }

    public function http(?string $key = null)
    {
        return $key ? Arr::get(@ static::$storage['http'] ?? [], $key): static::$storage['http'];
    }

    public function bootstrap(?string $key = null)
    {
        return $key ? Arr::get(@ static::$storage['bootstrap'] ?? [], $key): static::$storage['bootstrap'];
    }

    public function route(?string $key = null)
    {
        return $key ? Arr::get(@ static::$storage['route'] ?? [], $key): static::$storage['route'];
    }

    public function database(?string $key = null)
    {
        return $key ? Arr::get(@ static::$storage['database'] ?? [], $key): static::$storage['database'];
    }

    public function env(?string $key = null, ?string $default = null)
    {
        return $key ? Env::get($key, $default) : $_ENV;
    }

    public function set(string $key, mixed $value): void
    {
        if ( ! str_contains($key, '.') && ! $this->get($key) ) {
            static::$storage[$key] = $value;
        } else static::$storage = Arr::set(static::$storage, $key, $value);
    }

    public function get(?string $key = null, $default = null)
    {
        if (NULL === $key) return static::$storage;
        return get_data(static::$storage, $key, $default);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset(static::$storage[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return @ static::$storage[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        static::$storage[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset(static::$storage[$offset]);
    }

    public function jsonserialize(): array
    {
        return static::$storage;
    }
}