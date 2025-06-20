<?php
namespace Clicalmani\Foundation\Resources;

use Clicalmani\Psr7\NonBufferedBody;
use Clicalmani\Psr7\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;

class View extends Response implements ViewInterface
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
        $this->runCreators();
        $sharedData = app()->viewSharedData();
        $sharedData = array_merge($sharedData, $context);
        $this->context = $sharedData;
        $this->body = new NonBufferedBody;
        $this->twig = new \Twig\Environment(new \Clicalmani\Foundation\Resources\TemplateLoader, []);
        $this->twig->addExtension(new \Clicalmani\Foundation\Resources\TonkaTwigExtension);
        $this->runComposers();
    }

    public function render(): string
    {
        return $this->twig->render($this->template, $this->context);
    }

    /**
     * Add data to the view context
     * 
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function with(string $key, mixed $value): self
    {
        $this->context[$key] = $value;
        return $this;
    }

    public static function share(string $key, mixed $value): void
    {
        $sharedData = app()->viewSharedData();
        $sharedData[$key] = $value;
        app()->viewSharedData($sharedData);
    }

    public static function composer(string|array $views, string|callable $composer): void
    {
        $views = (array) $views;
        foreach ($views as $view) {
            Kernel::$composers[$view] = $composer;
            if ( is_string($composer) ) {
                app()->getContainer()->register("{$view}_composer", $composer);
            }
        }
    }

    public static function create(string $view, string|callable $creator) : void
    {
        Kernel::$creators[$view] = $creator;
        if ( is_string($creator) ) {
            app()->getContainer()->register("{$view}_creator", $creator);
        }
    }

    public function __toString()
    {
        $this->body->write($this->render());
        EXIT;
    }

    /**
     * Get the composers for the view
     * 
     * @return array
     */
    private function getComposers(): array
    {
        return Kernel::$composers[$this->parseTemplateName($this->template)] ?? [];
    }

    /**
     * Get creators for the view
     * 
     * @return array
     */
    private function getCreators(): array
    {
        return Kernel::$creators[$this->parseTemplateName($this->template)] ?? [];
    }

    /**
     * Run the composers for the view
     * 
     * @return void
     */
    private function runComposers(): void
    {
        $this->attachSharedData($this->getComposers());
    }

    private function runCreators(): void
    {
        $this->attachSharedData($this->getCreators());
    }

    private function parseTemplateName(string $name): string
    {
        return preg_replace('#\.|/{2,}#', '/', str_replace('\\', '/', $name));
    }

    private function attachSharedData(array $data): void
    {
        foreach ($data as $view => $shared) {
            if ( $shared instanceof \Closure ) $shared($this);
            elseif ( is_string($shared) ) {
                (app()->getContainer()->get("{$view}_composer", ContainerInterface::IGNORE_ON_INVALID_REFERENCE) 
                            ?: app()->getContainer()->get("{$view}_creator", ContainerInterface::IGNORE_ON_INVALID_REFERENCE))
                    ->{'compose'}($this);
            }
        }
    }
}
