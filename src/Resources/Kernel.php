<?php
namespace Clicalmani\Foundation\Resources;

class Kernel
{
    public static $template_tags = [
        \Clicalmani\Foundation\Resources\Tags\CSRFTokenField::class,
        \Clicalmani\Foundation\Resources\Tags\IfTag::class,
        \Clicalmani\Foundation\Resources\Tags\EndIfTag::class,
    ];
}
