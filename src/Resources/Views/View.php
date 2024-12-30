<?php
namespace Clicalmani\Foundation\Resources\Views;

use Clicalmani\Foundation\Resources\TemplateLoader;
use Clicalmani\Foundation\Resources\TonkaTwigExtension;

class View
{
    /**
     * Render a view
     * 
     * @param string $filename
     * @param ?array $vars Variables
     * @return mixed
     */
    public static function render(string $filename, ?array $vars = []) : mixed
    {
        $template = new \Twig\Environment(new TemplateLoader, []);
        $template->addExtension(new TonkaTwigExtension);

        return $template->render($filename, $vars);
    }
}
