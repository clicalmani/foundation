<?php
namespace Clicalmani\Foundation\Http;

interface RedirectInterface
{
    /**
     * Redirect back to the previous route.
     * 
     * @return \Clicalmani\Foundation\Http\RedirectInterface
     */
    public function back() : \Clicalmani\Foundation\Http\RedirectInterface;

    /**
     * Flash a success message to the session.
     * 
     * @param string $status
     * @param string $value
     * @return \Clicalmani\Foundation\Http\RedirectInterface
     */
    public function with(string $status, string $value): RedirectInterface;

    /**
     * Set the HTTP status code for the redirect.
     * 
     * @param int $code
     * @return \Clicalmani\Foundation\Http\RedirectInterface
     */
    public function status(int $code) : \Clicalmani\Foundation\Http\RedirectInterface;

    /**
     * Redirect to a specific route.
     * 
     * @param mixed ...$args
     * @return \Clicalmani\Foundation\Http\RedirectInterface
     */
    public function route(mixed ...$args) : \Clicalmani\Foundation\Http\RedirectInterface;

    /**
     * Redirect to a specific action.
     * 
     * @param string|array $action
     * @return \Clicalmani\Foundation\Http\RedirectInterface
     */
    public function action(string|array $action) : \Clicalmani\Foundation\Http\RedirectInterface;

    /**
     * Redirect to an external URL.
     * 
     * @param string $url
     * @return \Clicalmani\Foundation\Http\RedirectInterface
     */
    public function away(string $url) : \Clicalmani\Foundation\Http\RedirectInterface;
}