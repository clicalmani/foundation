<?php
namespace Clicalmani\Foundation\Exceptions;

class ModelNotFoundException extends \Exception 
{
	public function __construct(?string $message = '', ?int $code = 404, private ?string $status = 'NOT_FOUND', ?\Throwable $previous = null){
		parent::__construct($message, $code, $previous);
	}

	public function getStatus()
	{
		return $this->status;
	}
}
