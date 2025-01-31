<?php
namespace Clicalmani\Foundation\Resources;

use Clicalmani\Foundation\Exceptions\ResourceNotFoundException;
use Clicalmani\Psr7\NonBufferedBody;
use Clicalmani\Psr7\Response;

class View extends Response
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
    public function __construct(private string $template, private ?array $context = [])
    {
        $this->body = new NonBufferedBody;
        $this->twig = new \Twig\Environment(new \Clicalmani\Foundation\Resources\TemplateLoader, []);
        $this->twig->addExtension(new \Clicalmani\Foundation\Resources\TonkaTwigExtension);
    }

    /**
     * Render the view
     * 
     * @return string
     */
    public function render(): string
    {
        return $this->twig->render($this->template, $this->context);
    }

    public function __toString()
    {
        $this->body->write($this->render());
        EXIT;
    }
}
