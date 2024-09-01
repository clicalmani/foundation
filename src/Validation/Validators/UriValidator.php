<?php
namespace Clicalmani\Foundation\Validation\Validators;

use Clicalmani\Foundation\Validation\InputValidator;

class UriValidator extends InputValidator
{
    protected string $argument = 'uri';

    public function validate(mixed &$value, ?array $options = []) : bool
    {
        return !! filter_var($value, FILTER_VALIDATE_URL);
    }
}
