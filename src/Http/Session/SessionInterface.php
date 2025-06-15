<?php
namespace Clicalmani\Foundation\Http\Session;

interface SessionInterface
{
    /**
     * Start session
     * 
     * @return void
     */
    public function start(): void;

    /**
     * Set session
     * 
     * @return void
     */
    public function set(?string $name = null, mixed $value = null): void;

    /**
     * Get session
     * 
     * @return ?string
     */
    public function get(?string $name = null): ?string;

    /**
     * Check if session exists
     * 
     * @return bool
     */
    public function exists(?string $name = null): bool;

    /**
     * Remove session
     * 
     * @param ?string $name
     * @return void
     */
    public function remove(?string $name = null): void;

    /**
     * Destroy session
     * 
     * @return void
     */
    public function destroy(): void;

    /**
     * Get all session data
     * 
     * @return array
     */
    public function all(): array;

    /**
     * Get all session keys
     * 
     * @return array
     */
    public function allKeys(): array;

    /**
     * Get all session values
     * 
     * @return array
     */
    public function allValues(): array;
}