<?php
namespace Clicalmani\Foundation\Http\Requests;

trait Redirect
{
    /**
     * Redirect route
     * 
     * @return \Clicalmani\Foundation\Http\RequestRedirect
     */
    public function redirect() : RequestRedirect
    {
        return new \Clicalmani\Foundation\Http\Requests\RequestRedirect;
    }
}