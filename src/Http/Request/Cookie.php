<?php
namespace Clicalmani\Core\Http\Request;

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
    public function cookie(?string $name = null, ?string $value = null, ?int $expiry = 0, ?string $path = '/') : \Clicalmani\Core\Http\Cookie
    {
        return new \Clicalmani\Core\Http\Cookie($name, $value, $expiry, $path);
    }

    public function setCookie(?string $name = null, ?string $value = null, ?int $expiry = 0, ?string $path = '/') : void
    {
        $this->cookie($name, $value, $expiry, $path)->set();
    }
}