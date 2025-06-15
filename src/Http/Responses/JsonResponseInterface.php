<?php
namespace Clicalmani\Foundation\Http\Responses;

interface JsonResponseInterface
{
    /**
     * Send a json response
     * 
     * @param mixed $data
     * @return \Clicalmani\Foundation\Http\ResponseInterface
     */
    public function json(mixed $data) : \Clicalmani\Foundation\Http\ResponseInterface;

    /**
     * Send a success status
     * 
     * @param mixed $message
     * @return \Clicalmani\Foundation\Http\ResponseInterface
     */
    public function success(mixed $message = null) : \Clicalmani\Foundation\Http\ResponseInterface;

    /**
     * Send an error status
     * 
     * @param mixed $message
     * @return \Clicalmani\Foundation\Http\ResponseInterface
     */
    public function error(mixed $message = null) : \Clicalmani\Foundation\Http\ResponseInterface;
}