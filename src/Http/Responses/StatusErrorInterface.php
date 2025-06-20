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
     * 403 Forbiden redirect
     * 
     * @return never
     */
    public function forbiden();

    /**
     * 500 Internal server error
     * 
     * @return never
     */
    public function internalServerError();
}