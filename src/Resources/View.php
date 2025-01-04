<?php
namespace Clicalmani\Foundation\Resources;

class View
{
    /**
     * Twig environment
     * 
     * @var \Twig\Environment
     */
    private \Twig\Environment $twig;
    
    /**
     * Constructor
     * 
     * @param string $template
     * @param ?array $vars
     */
    public function __construct(private string $template, private ?array $vars = [])
    {
        $this->twig = new \Twig\Environment(new \Clicalmani\Foundation\Resources\TemplateLoader, []);
        $this->twig->addExtension(new \Clicalmani\Foundation\Resources\TonkaTwigExtension);
    }

    /**
     * Render a view
     * 
     * @param string $filename
     * @param ?array $vars Variables
     * @return string
     * @throws LoaderError — When the template cannot be found
     * @throws SyntaxError — When an error occurred during compilation
     */
    public function render() : string
    {
        return $this->twig->render($this->template, $this->vars);
    }

    public function __toString()
    {
        return $this->render();
    }
}
