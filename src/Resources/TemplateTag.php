<?php
namespace Clicalmani\Foundation\Resources;

use Inertia\Inertia;

abstract class TemplateTag
{
    /**
     * Tag expression
     * 
     * @var string
     */
    protected string $tag;

    /**
     * Render a template
     * 
     * @return string
     */
    abstract public function render() : string;

    public function bind(string $content) : string
    {
        return preg_replace("/$this->tag/", $this->render(), $content);
    }

    /**
     * Get the attributes to be added to the root div
      * 
      * @return string
     */
    public function getAttributes(): string
    {
        return join(' ', array_map(fn(string $name, string $value) => "data-$name='$value'", array_keys(Inertia::$rootDataAttributes), Inertia::$rootDataAttributes));
    }
}