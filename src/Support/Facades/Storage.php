<?php
namespace Clicalmani\Foundation\Support\Facades;

/**
 * @method static mixed link() Create a storage link
 */
class Storage extends Facade
{
    protected static function getFacadeAccessor() : string
    {
        return 'storage';
    }
}