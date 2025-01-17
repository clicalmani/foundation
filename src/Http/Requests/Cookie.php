<?php
namespace Clicalmani\Foundation\Http\Requests;

trait Cookie
{
    /**
     * Get or set cookie
     * 
     * @param string $name Cookie name
     * @param ?string $value Cookie value
     * @param ?int $expiry Default one year
     * @param ?string $path Default root path
     * @return mixed
     */
    public function cookie(string $name, ?string $value = null, ?int $expiry = 604800, ?string $path = '/') : mixed
    {
        if ( ! is_null($value) ) {
            return setcookie($name, $value, time() + $expiry, $path);
        }

        return $_COOKIE[$name];
    }
}