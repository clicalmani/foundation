<?php
namespace Clicalmani\Foundation\Resources;

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
        return str_replace($this->tag, $this->render(), $content);
    }
}