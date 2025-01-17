<?php
namespace Clicalmani\Foundation\Http\Requests;

trait Session
{
    /**
     * Get or set session
     * 
     * @return object
     */
    public function session() : object
    {
        return new \Clicalmani\Foundation\Http\Session\Session;
    }
}