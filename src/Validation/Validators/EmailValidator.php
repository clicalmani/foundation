<?php
namespace Clicalmani\Foundation\Validation\Validators;

use Clicalmani\Foundation\Validation\Validator;

class EmailValidator extends Validator
{
    protected string $argument = 'email';
    
    public function validate(mixed &$email, ?array $options = []) : bool
    {
        return !! filter_var($this->parseString($email), FILTER_VALIDATE_EMAIL);
    }
}
