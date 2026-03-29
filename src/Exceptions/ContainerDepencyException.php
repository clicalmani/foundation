<?php
namespace Clicalmani\Foundation\Exceptions;

class ContainerDepencyException extends \Exception
{
    public function __construct(string $message = "", int $code = 0, \Throwable|null $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}