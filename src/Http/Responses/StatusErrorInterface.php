<?php
namespace Clicalmani\Foundation\Http\Responses;

interface StatusErrorInterface
{
    /**
     * 404 Not found redirect
     * 
     * @return \Clicalmani\Foundation\Http\ResponseInterface
     */
    public function notFound() : \Clicalmani\Foundation\Http\ResponseInterface;

    /**
     * 401 Unauthorized redirect
     * 
     * @return \Clicalmani\Foundation\Http\ResponseInterface
     */
    public function unauthorized() : \Clicalmani\Foundation\Http\ResponseInterface;

    /**
     * 403 Forbiden redirect
     * 
     * @return \Clicalmani\Foundation\Http\ResponseInterface
     */
    public function forbiden() : \Clicalmani\Foundation\Http\ResponseInterface;

    /**
     * 500 Internal server error
     * 
     * @return \Clicalmani\Foundation\Http\ResponseInterface
     */
    public function internalServerError() : \Clicalmani\Foundation\Http\ResponseInterface;
}