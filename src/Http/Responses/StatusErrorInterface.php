<?php
namespace Clicalmani\Foundation\Http\Responses;

interface StatusErrorInterface
{
    /**
     * 404 Not found redirect
     * 
     * @return never
     */
    public function notFound();

    /**
     * 401 Unauthorized redirect
     * 
     * @return never
     */
    public function unauthorized();

    /**
     * 403 Forbidden redirect
     * 
     * @return never
     */
    public function forbidden();

    /**
     * 500 Internal server error
     * 
     * @return never
     */
    public function internalServerError();
}