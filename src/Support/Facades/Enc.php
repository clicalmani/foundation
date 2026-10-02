<?php
namespace Clicalmani\Core\Support\Facades;

/**
 * @method string hash(string $data) Generate a data hash.
 * @method string createParametersHash(array $params) Creates parameters hash.
 * @method bool verifyParameters() Verify parameters.
 * @method string encrypt(string $value) Encrypt a value.
 * @method string decrypt(string $encrypted) Decrypt an encrypted value.
 * @method string password(string $str) PHP built-in password_hash wrapper.
 * @method string|null hashParameter() Returns hash parameter name.
 */
class Enc extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'encryption';
    }
}