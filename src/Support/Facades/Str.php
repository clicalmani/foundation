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
 * @method static string singularize(string $word)
 * @method static string pluralize(string $word)
 * @method static string urlize(string $word)
 * @method static string tableize(string $word)
 * @method static string classify(string $word)
 * @method static string camelize(string $word)
 * @method static string capitalize(string $word)
 */
abstract class Str extends Facade
{
    protected static function getFacadeAccessor() : string
    {
        return 'str';
    }
}
