<?php
namespace Clicalmani\Core\Resources\Tags;

use Clicalmani\Core\Resources\TemplateTag;

class IfTag extends TemplateTag
{
    /**
     * Tag expression
     * 
     * @var string
     */
    protected string $tag = '@if \((.*)\)';

    /**
     * Render a tag
     * 
     * @return string
     */
    public function render(array $matches) : string
    {
        return "{% if {$matches[1]} %}";
    }
}