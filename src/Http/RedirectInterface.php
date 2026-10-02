<?php
namespace Clicalmani\Core\Http;

interface RedirectInterface
{
    /**
     * Redirect back to the previous route.
     * 
     * @return RedirectInterface
     */
    public function back() : RedirectInterface;

    /**
     * Flash a status message to the session.
     * 
     * @param string $status
     * @param string $value
     * @return RedirectInterface
     */
    public function with(string $status, string $value): RedirectInterface;

    /**
     * Flash an error message to the session.
     * 
     * @param string $message
     * @return RedirectInterface
     */
    public function withError(string $message = ''): RedirectInterface;

    /**
     * Flash a success message to the session.
     * 
     * @param string $message
     * @return RedirectInterface
     */
    public function withSuccess(string $message = ''): RedirectInterface;

    /**
     * Flash input data to the session for the next request.
     * 
     * @param array $input
     * @return RedirectInterface
     */
    public function withInput(array $input): RedirectInterface;

    /**
     * Set the HTTP status code for the redirect.
     * 
     * @param int $code
     * @return RedirectInterface
     */
    public function status(int $code) : RedirectInterface;

    /**
     * Returns the status code.
     * 
     * @return int Status code
     */
    public function getStatusCode() : int;

    /**
     * Redirect to a specific route.
     * 
     * @param mixed ...$args
     * @return RedirectInterface
     */
    public function route(mixed ...$args) : RedirectInterface;

    public function to(string $url) : RedirectInterface;

    /**
     * Redirect to a specific action.
     * 
     * @param string|array $action
     * @return RedirectInterface
     */
    public function action(string|array $action) : RedirectInterface;

    /**
     * Redirect to an external URL.
     * 
     * @param string $url
     * @return RedirectInterface
     */
    public function away(string $url) : RedirectInterface;
}