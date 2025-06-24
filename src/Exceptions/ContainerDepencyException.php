<?php
namespace Clicalmani\Foundation\Exceptions;

class ContainerDepencyException extends \Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}