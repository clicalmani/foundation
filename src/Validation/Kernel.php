<?php
namespace Clicalmani\Foundation\Validation;

class Kernel {

    /**
     * Return input validators
     * 
     * @return string[]
     */
    public function validators() : array
    {
        return [
            \Clicalmani\Foundation\Validation\Validators\BoolValidator::class,
            \Clicalmani\Foundation\Validation\Validators\BooleanValidator::class,
            \Clicalmani\Foundation\Validation\Validators\DateTimeValidator::class,
            \Clicalmani\Foundation\Validation\Validators\DateValidator::class,
            \Clicalmani\Foundation\Validation\Validators\EmailValidator::class,
            \Clicalmani\Foundation\Validation\Validators\EnumValidator::class,
            \Clicalmani\Foundation\Validation\Validators\FloatValidator::class,
            \Clicalmani\Foundation\Validation\Validators\IDValidator::class,
            \Clicalmani\Foundation\Validation\Validators\IntValidator::class,
            \Clicalmani\Foundation\Validation\Validators\IntegerValidator::class,
            \Clicalmani\Foundation\Validation\Validators\NumberValidator::class,
            \Clicalmani\Foundation\Validation\Validators\NumbersValidator::class,
            \Clicalmani\Foundation\Validation\Validators\NumericValidator::class,
            \Clicalmani\Foundation\Validation\Validators\NumericsValidator::class,
            \Clicalmani\Foundation\Validation\Validators\ObjectValidator::class,
            \Clicalmani\Foundation\Validation\Validators\ObjectsValidator::class,
            \Clicalmani\Foundation\Validation\Validators\RegExpValidator::class,
            \Clicalmani\Foundation\Validation\Validators\StringValidator::class,
            \Clicalmani\Foundation\Validation\Validators\StringsValidator::class,
            \Clicalmani\Foundation\Validation\Validators\UriValidator::class,
            \Clicalmani\Foundation\Validation\Validators\NavigationGuardValidator::class,
            \Clicalmani\Foundation\Validation\Validators\PasswordValidator::class,
        ];
    }
};
