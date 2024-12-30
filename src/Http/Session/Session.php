<?php
namespace Clicalmani\Foundation\Http\Session;

class Session
{
    /**
     * Get CSRF token
     * 
     * @return string
     */
    public function token()
    {
        return csrf_token();
    }

    /**
     * Get session value
     * 
     * @param string $name
     * @return mixed
     */
    public function get(string $name)
    {
        return @$_SESSION[$name] ?? null;
    }

    /**
     * Set session value
     * 
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function set(string $name, mixed $value)
    {
        $_SESSION[$name] = $value;
    }
}
