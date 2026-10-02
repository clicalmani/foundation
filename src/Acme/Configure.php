<?php
namespace Clicalmani\Core\Acme;

use Clicalmani\Core\Support\Facades\Arr;
use Clicalmani\Core\Support\Facades\Env;

class Configure implements \ArrayAccess, \JsonSerializable
{
    protected static $storage = [];

    public function app(?string $key = null, mixed $default = null)
    {
        return $key ? Arr::get(static::$storage['app'] ?? [], $key, $default): static::$storage['app'];
    }

    public function mail(?string $key = null, mixed $default = null)
    {
        return $key ? Arr::get(static::$storage['mail'] ?? [], $key, $default): static::$storage['mail'];
    }

    public function http(?string $key = null, mixed $default = null)
    {
        return $key ? Arr::get(static::$storage['http'] ?? [], $key, $default): static::$storage['http'];
    }

    public function bootstrap(?string $key = null, mixed $default = null)
    {
        return $key ? Arr::get(static::$storage['bootstrap'] ?? [], $key, $default): static::$storage['bootstrap'];
    }

    public function route(?string $key = null, mixed $default = null)
    {
        return $key ? Arr::get(static::$storage['route'] ?? [], $key, $default): static::$storage['route'];
    }

    public function database(?string $key = null, mixed $default = null)
    {
        return $key ? Arr::get(static::$storage['database'] ?? [], $key, $default): static::$storage['database'];
    }

    public function broadcasting(?string $key = null, mixed $default = null)
    {
        return $key ? Arr::get(static::$storage['broadcasting'] ?? [], $key, $default): static::$storage['broadcasting'];
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
        return static::$storage[$offset] ?? null;
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