<?php
namespace Clicalmani\Foundation\Exceptions;

class MailException extends \Exception {
	function __construct($message = ''){
		parent::__construct($message);
	}
}
