<?php
namespace Clicalmani\Foundation\Support\Facades;

/**
 * @method bool accessible(\ArrayAccess|array $array)
 * @method mixed get(\ArrayAccess|array $array, string|int|null $key, $default = null)
 * @method bool exists(\ArrayAccess|array $array, string|int|float $key)
 * @method void forget(\ArrayAccess|array $array, array|string|int|float $keys)
 * @method bool has(\ArrayAccess|array $array, string|array $keys)
 * @method bool hasAny(\ArrayAccess|array $array, string|array $keys)
 * @method bool isAssoc(\ArrayAccess|array $array)
 * @method bool isList(\ArrayAccess|array $array)
 * @method string join(\ArrayAccess|array $array, string $glue, string $finalGlue = '')
 * @method array mapWithKeys(\ArrayAccess|array $array, callable $callback)
 * @method array map(\ArrayAccess|array $array, callable $callback)
 * @method array explodePluckParameters(\ArrayAccess|array $array, string|array|null $key)
 * @method array pluck(\ArrayAccess|array $array, string|array|int|null $value, string|array|null $key = null)
 * @method array collapse(\ArrayAccess|array $array) Flatten a multi-dimensional array
 * @method array add(\ArrayAccess|array $array, string|int|float $key, mixed $value)
 * @method array set(\ArrayAccess|array $array, string|int|null $key, mixed $value)
 * @method array shuffle(\ArrayAccess|array $array)
 * @method array except($array, $keys) : array
 */
class Arr extends Facade
{
    protected static function getFacadeAccessor() : string
    {
        return 'array';
    }
}