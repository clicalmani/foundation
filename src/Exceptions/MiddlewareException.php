<?php
namespace Clicalmani\Foundation\Exceptions;

class MiddlewareException extends \Exception {
	function __construct($message){
		parent::__construct($message);
	}
}
?>