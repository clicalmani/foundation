<?php
namespace Clicalmani\Foundation\Exceptions;

class MethodNotFoundException extends \Exception {
	function __construct($method = ''){
		parent::__construct("Call to undefined method $method");
	}
}
