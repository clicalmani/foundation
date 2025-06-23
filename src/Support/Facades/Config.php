<?php
namespace Clicalmani\Foundation\Support\Facades;

/**
 * Log Class
 * 
 * @package Clicalmani\Foundation/flesco 
 * @author @Clicalmani\Foundation
 * 
 * @method static mixed app(?string $key = null)
 * @method static mixed route(?string $key = null)
 * @method static mixed http(?string $key = null)
 * @method static mixed bootstrap(?string $key = null)
 * @method static mixed database(?string $key = null)
 * @method static mixed env(?string $key = null)
 * @method static string string(?string $key = null, string $default = '')
 * @method static int integer(?string $key = null, int $default = 0)
 * @method static float float(?string $key = null, float $default = 0)
 * @method static array array(?string $key = null, array $default = [])
 * @method static bool boolean(?string $key = null, bool $default = false)
 */
class Config extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() : string
    {
        return 'config';
    }
}