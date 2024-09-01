<?php
namespace Clicalmani\Foundation\Support;

/**
 * Class Helper
 * 
 * @package Clicalmani\Foundation
 * @author @Clicalmani\Foundation
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
