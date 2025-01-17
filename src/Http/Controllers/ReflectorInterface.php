<?php
namespace Clicalmani\Foundation\Http\Controllers;

interface ReflectorInterface
{
    /**
     * Get the function parameters.
     * 
     * @return array
     */
    public function getParameters() : array;

    /**
     * Get the resource class name if the function has a resource parameter.
     * 
     * @return array|null
     */
    public function getResource() : array|null;

    /**
     * Get the nested resource class name if the function has a nested resource parameter.
     * 
     * @return array|null
     */
    public function getNestedResource() : array|null;

    /**
     * Get the request class name if the function has a request parameter.
     * 
     * @return array|null
     */
    public function getRequest() : array|null;

    public function __invoke(object $object, mixed ...$args) : mixed;
}