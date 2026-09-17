<?php
namespace Clicalmani\Core\Http\Requests;

trait Redirect
{
    /**
     * Redirect route
     * 
     * @return \Clicalmani\Core\Http\RequestRedirect
     */
    public function redirect() : RequestRedirect
    {
        return new \Clicalmani\Core\Http\Requests\RequestRedirect;
    }
}