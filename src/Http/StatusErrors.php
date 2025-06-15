<?php
namespace Clicalmani\Foundation\Http;

trait StatusErrors
{
    public function notFound() : \Clicalmani\Foundation\Http\ResponseInterface
    {
        $this->status = 404;
        return $this;
    }

    public function unauthorized() : \Clicalmani\Foundation\Http\ResponseInterface
    {
        $this->status = 401;
        return $this;
    }

    public function forbiden() : \Clicalmani\Foundation\Http\ResponseInterface
    {
        $this->status = 403;
        return $this;
    }

    public function internalServerError() : \Clicalmani\Foundation\Http\ResponseInterface
    {
        $this->status = 500;
        return $this;
    }
}