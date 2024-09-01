<?php
namespace Clicalmani\Foundation\Resources\Views;

use Clicalmani\Foundation\Resources\TemplateLoader;

class View
{
    /**
     * Render a view
     * 
     * @param string $template
     * @param ?array $vars Variables
     * @return mixed
     */
    public static function render(string $template, ?array $vars = []) : mixed
    {
        return ( new \Twig\Environment(new TemplateLoader, []) )->render($template, $vars);
    }
}
