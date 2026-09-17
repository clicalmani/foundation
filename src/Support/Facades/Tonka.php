<?php
namespace Clicalmani\Core\Support\Facades;

/**
 * @method static mixed link(string $source, string $destination)
 */
abstract class Tonka extends Facade
{
    protected static function getFacadeAccessor() : string
    {
        return 'console';
    }
}