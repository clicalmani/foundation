<?php
namespace Clicalmani\Foundation\Http\Requests;

use Clicalmani\Foundation\Support\Facades\Route;

/**
 * Class RequestRoute
 * 
 * @package Clicalmani\Foundation
 * @author @Clicalmani\Foundation
 */
class RequestRoute 
{
    /**
     * Get current route
     * 
     * @return string Current route
     */
    public function current() : string
    {
        return client_uri();
    }

    /**
     * Verify if route has been named name.
     * 
     * @param string $name
     * @return bool
     */
    public function named(string $name) : bool 
    {
        return !!Route::findByName($name); 
    }
}
