<?php
namespace Clicalmani\Foundation\Http;

class Session
{
    /**
     * Session constructor
     * 
     * @param string $name
     * @param string $value
     * @param int $expires
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httpOnly
     */
    public function __construct(
        protected string $name,
        protected string $value,
        protected int $expires,
        protected string $path,
        protected string $domain,
        protected bool $secure,
        protected bool $httpOnly
    ) {
        $this->name = $name;
        $this->value = $value;
        $this->expires = $expires;
        $this->path = $path;
        $this->domain = $domain;
        $this->secure = $secure;
        $this->httpOnly = $httpOnly;
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
    public function set(): void
    {
        $_SESSION[$this->name] = $this->value;
    }

    /**
     * Get session
     * 
     * @return string
     */
    public function get(): string
    {
        return $_SESSION[$this->name];
    }

    /**
     * Check if session exists
     * 
     * @return bool
     */
    public function exists(): bool
    {
        return isset($_SESSION[$this->name]);
    }

    /**
     * Remove session
     * 
     * @return void
     */
    public function remove(): void
    {
        unset($_SESSION[$this->name]);
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
}