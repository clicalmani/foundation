<?php
namespace Clicalmani\Foundation\Exceptions;

class HttpRequestException extends \Exception {
	function __construct($message){
		parent::__construct($message);
	}
}
