<?php
namespace Clicalmani\Foundation\Http;

use Clicalmani\Foundation\Routing\Route;

trait StatusErrors
{
    /**
     * 404 Not found redirect
     * 
     * @return int
     */
    public function notFound() : int
    {
        $this->status = 404;
        $this->sendStatus();
        return http_response_code();
    }

    /**
     * 401 Unauthorized redirect
     * 
     * @return int
     */
    public function unauthorized() : int
    {
        $this->status = 401;
        $this->sendStatus();
        return http_response_code();
    }

    /**
     * 403 Forbiden redirect
     * 
     * @return int
     */
    public function forbiden() : int
    {
        $this->status = 403;
        $this->sendStatus();
        return http_response_code();
    }

    /**
     * 500 Internal server error
     * 
     * @return int
     */
    public function internalServerError() : int
    {
        $this->status = 500;
        $this->sendStatus();
        return http_response_code();
    }
}