<?php
namespace Clicalmani\Foundation\Http\Requests;

use Clicalmani\Foundation\Support\Facades\Route;

/**
 * Class RequestRedirect
 * 
 * @package Clicalmani\Foundation
 * @author @Clicalmani\Foundation
 */
class RequestRedirect 
{
    /**
     * Redirect URI
     * 
     * @param mixed $uri
     * @return never
     */
    public function route(mixed ...$uri) : never
    {
        header('Location: ' . Route::resolve(...$uri));
        exit;
    }

    /**
     * Redirect back
     * 
     * @return never
     */
    public function back() : never
    {
        $this->route($_SERVER['PHP_SELF']);
    }

    /**
     * Redirect home
     * 
     * @return never
     */
    public function home() : never
    {
        $this->route('/');
    }

    /**
     * Redirect error
     * 
     * @param ?string $error_message
     * @return never
     */
    public function error(?string $error_message = '') : never
    {
        $this->route($_SERVER['HTTP_REFERER'] . '?error=' . $error_message);
    }

    /**
     * Redirect success
     * 
     * @param ?string $success_message
     * @return never
     */
    public function success(?string $success_message = '') : never
    {
        $this->route($_SERVER['HTTP_REFERER'] . '?success=' . $success_message);
    }

    /**
     * Redirect with
     * 
     * @param mixed ...$args
     * @return never
     */
    public function with(... $args) : mixed
    {
        foreach ($args as $key => $value) {
            $_SESSION[$key] = $value;
        }

        return $this->route(...$args);
    }
}
