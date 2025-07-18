<?php
namespace Clicalmani\Foundation\Support\Facades;

use Clicalmani\Foundation\Support\Facades\Facade;

/**
 * Class Str
 * 
 * @package Clicalmani\Foundation
 * @author @Clicalmani\Foundation
 * 
 * @method static string slug(mixed $value, ?string $fallback_value = '' )
 * @method static string removeAccents(string $string, ?string $locale = '' )
 * @method static bool seemsUtf8(string $str)
 * @method static void mbstringBinarySafeEncoding(?bool $reset = false)
 * @method static void resetMbstringEncoding()
 * @method static string escape(string $str, ?array $exclude = [' '])
 * @method static string unescape(string $escaped)
 */
abstract class Str extends Facade
{
    protected static function getFacadeAccessor() : string
    {
        return 'str';
    }
}
