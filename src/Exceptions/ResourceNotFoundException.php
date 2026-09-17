<?php
namespace Clicalmani\Core\Exceptions;

class ResourceNotFoundException extends \Exception
{
    function __construct($message){
		parent::__construct($message);
	}
}