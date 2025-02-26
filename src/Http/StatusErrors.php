<?php
namespace Clicalmani\Foundation\Http;

trait StatusErrors
{
    /**
     * 404 Not found redirect
     * 
     * @return static
     */
    public function notFound() : static
    {
        $this->status = 404;
        return $this;
    }

    /**
     * 401 Unauthorized redirect
     * 
     * @return static
     */
    public function unauthorized() : static
    {
        $this->status = 401;
        return $this;
    }

    /**
     * 403 Forbiden redirect
     * 
     * @return static
     */
    public function forbiden() : static
    {
        $this->status = 403;
        return $this;
    }

    /**
     * 500 Internal server error
     * 
     * @return static
     */
    public function internalServerError() : static
    {
        $this->status = 500;
        return $this;
    }
}