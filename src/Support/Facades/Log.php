<?php 
namespace Clicalmani\Core\Support\Facades;

use Clicalmani\Core\Support\Facades\Facade;

/**
 * Log Class
 * 
 * @package Clicalmani\Core/flesco 
 * @author @Clicalmani\Core
 * 
 * @method static void init()
 * @method static void error(string $error_message, ?int $error_level = E_ERROR, ?string $file = 'Unknow', ?int $line = null)
 * @method static void warning(string $warning_message, ?string $file = 'Unknow', ?int $line = null)
 * @method static void notice(string $notice_message, ?string $file = 'Unknow', ?int $line = null)
 * @method static void debug(string $debug_message, ?string $file = 'Unknow', ?int $line = null)
 */
abstract class Log extends Facade
{
    protected static function getFacadeAccessor() : string
    {
        return 'logger';
    }
}
