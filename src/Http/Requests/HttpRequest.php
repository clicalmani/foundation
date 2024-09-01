<?php
namespace Clicalmani\Foundation\Http\Requests;

/**
 * HttpRequest class
 * 
 * @package Clicalmani\Foundation/flesco 
 * @author @Clicalmani\Foundation
 */
abstract class HttpRequest 
{
    /**
     * (non-PHPDoc)
     * @override
     */
    abstract public function render() : never;
}
