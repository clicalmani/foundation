<?php
namespace Clicalmani\Foundation\Http;

use Clicalmani\Foundation\Http\Session\SessionInterface;
use Clicalmani\Foundation\Support\Facades\Arr;

class Session implements SessionInterface
{
    /**
     * Session constructor
     * 
     * @param string $name
     * @param mixed $value
     */
    public function __construct(
        protected ?string $name = null,
        protected mixed $value = null
    ) {
        $this->name = $name;
        $this->value = $value;
    }

    public function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function set(?string $name = null, mixed $value = null): void
    {
        $_SESSION = Arr::set($_SESSION, $this->name ?: $name, $this->value ?: $value);
    }

    public function get(?string $name = null): mixed
    {
        return Arr::get(isset($_SESSION) ? $_SESSION: [], $name ?: $this->name);
    }

    public function exists(?string $name = null): bool
    {
        return Arr::exists(isset($_SESSION) ? $_SESSION: [], $name ?: $this->name);
    }

    public function remove(?string $name = null): void
    {
        $_SESSION = Arr::forget(isset($_SESSION) ? $_SESSION: [], $name ?: $this->name);
    }

    public function destroy(): void
    {
        session_destroy();
    }

    public function all(): array
    {
        return $_SESSION;
    }

    public function allExcept(array $keys)
    {
        return Arr::except($this->all(), $keys);
    }

    public function allKeys(): array
    {
        return array_keys($_SESSION);
    }

    public function allValues(): array
    {
        return array_values($_SESSION);
    }

    public function storeBackTrace(string $back_trace) : void
    {
        $this->set(\Clicalmani\Foundation\Providers\SessionStorageServiceProvider::backTraceIndex(), $back_trace);
    }

    public function retrieveBackTrace() : ?string
    {
        return $this->get(\Clicalmani\Foundation\Providers\SessionStorageServiceProvider::backTraceIndex());
    }

    public function __get($name)
    {
        return match ($name) {
            $this->name => $this->value
        };
    }

    public function __set($name, $value)
    {
        match ($name) {
            $this->name => $this->value = $value
        };
    }
}