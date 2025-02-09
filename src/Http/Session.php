<?php
namespace Clicalmani\Foundation\Http;

class Session
{
    /**
     * Session constructor
     * 
     * @param string $name
     * @param string $value
     */
    public function __construct(
        protected ?string $name = null,
        protected ?string $value = null
    ) {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * Start session
     * 
     * @return void
     */
    public function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Set session
     * 
     * @return void
     */
    public function set(?string $name = null, ?string $value = null): void
    {
        $_SESSION[$this->name ?: $name] = $this->value ?: $value;
    }

    /**
     * Get session
     * 
     * @return ?string
     */
    public function get(?string $name = null): ?string
    {
        return @$_SESSION[$name ?: $this->name];
    }

    /**
     * Check if session exists
     * 
     * @return bool
     */
    public function exists(?string $name = null): bool
    {
        return isset($_SESSION[$name ?: $this->name]);
    }

    /**
     * Remove session
     * 
     * @param ?string $name
     * @return void
     */
    public function remove(?string $name = null): void
    {
        unset($_SESSION[$name ?: $this->name]);
    }

    /**
     * Destroy session
     * 
     * @return void
     */
    public function destroy(): void
    {
        session_destroy();
    }

    /**
     * Get all session data
     * 
     * @return array
     */
    public function all(): array
    {
        return $_SESSION;
    }

    /**
     * Get all session keys
     * 
     * @return array
     */
    public function allKeys(): array
    {
        return array_keys($_SESSION);
    }

    /**
     * Get all session values
     * 
     * @return array
     */
    public function allValues(): array
    {
        return array_values($_SESSION);
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