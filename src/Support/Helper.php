<?php
namespace Clicalmani\Core\Support;

/**
 * Class Helper
 * 
 * @package Clicalmani\Core
 * @author @Clicalmani\Core
 */
class Helper 
{
    /**
     * Include helper functions
     * 
     * @return void
     */
    public static function include()
    {
        include_once dirname( __DIR__ ) . '/helpers.php';
    }
}
