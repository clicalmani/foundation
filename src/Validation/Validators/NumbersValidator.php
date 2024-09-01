<?php
namespace Clicalmani\Foundation\Validation\Validators;

use Clicalmani\Foundation\Validation\InputValidator;

class NumbersValidator extends InputValidator
{
    protected string $argument = 'number[]';

    public function validate(mixed &$value, ?array $options = []) : bool
    {console_log(json_encode($value));
        $value = $this->parseArray( $value );

        foreach ($value as $entry) {
            if ( ! is_numeric($entry) ) return false;
        }

        return true;
    }
}
