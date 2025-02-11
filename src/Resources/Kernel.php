<?php
namespace Clicalmani\Foundation\Resources;

class Kernel
{
    /**
     * @var array
     */
    public static array $sharedData = [];

    /**
     * @var array
     */
    public static array $composers = [];

    /**
     * @var array
     */
    public static array $creators = [];

    /**
     * |---------------------------------------------------------------
     * | Template Tags
     * |---------------------------------------------------------------
     * 
     */
    public static $template_tags = [
        \Clicalmani\Foundation\Resources\Tags\CSRFTokenField::class,
        \Clicalmani\Foundation\Resources\Tags\IfTag::class,
        \Clicalmani\Foundation\Resources\Tags\EndIfTag::class,
    ];
}
