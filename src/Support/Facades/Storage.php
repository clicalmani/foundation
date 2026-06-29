<?php
namespace Clicalmani\Foundation\Support\Facades;

/**
 * @method static string store(string $source, string $filename, ?string $disk = null)
 * @method static mixed link() Create a storage link
 */
abstract class Storage extends Facade
{
    protected static function getFacadeAccessor() : string
    {
        return 'storage';
    }
}