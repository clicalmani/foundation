<?php
namespace Clicalmani\Foundation\Http\Controllers;

interface MethodInterface
{
    /**
     * Get method parameters
     * 
     * @return array
     */
    public function getParameters() : array;

    public function getParameterType() : void;
}