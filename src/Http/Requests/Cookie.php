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
    public function cookie(?string $name = null, ?string $value = null, ?int $expiry = 0, ?string $path = '/') : \Clicalmani\Cookie\Cookie
    {
        return new \Clicalmani\Cookie\Cookie($name, $value, $expiry, $path);
    }
}