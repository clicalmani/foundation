<?php
namespace Clicalmani\Foundation\Exceptions;

class ResourceViewException extends \Exception
{
    function __construct($message){
		parent::__construct($message);
	}
}