<?php
namespace Clicalmani\Foundation\Validation\Validators;

use Clicalmani\Foundation\Validation\Validator;

class FileValidator extends Validator
{
    protected string $argument = 'file';

    public function options() : array
    {
        return [
            'max' => [
                'required' => false,
                'type' => 'integer'
            ]
        ];
    }

    public function validate(mixed &$value, ?array $options = [] ) : bool
    {
        /** @var \Clicalmani\Foundation\Http\Request */
        $request = \Clicalmani\Foundation\Http\Request::currentRequest();

        if ($request->file($this->parameter)?->isValid()) return true;

        return false;
    }
}
