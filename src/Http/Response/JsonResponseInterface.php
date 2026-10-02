<?php
namespace Clicalmani\Core\Http\Response;

interface JsonResponseInterface
{
    /**
     * Send a json response
     * 
     * @param mixed $data
     * @return self
     */
    public function json(mixed $data) : self;

    /**
     * Send a success status
     * 
     * @param mixed $message
     * @return self
     */
    public function success(mixed $message = null) : self;

    /**
     * Send an error status
     * 
     * @param mixed $message
     * @return self
     */
    public function error(mixed $message = null) : self;
}