<?php
namespace Clicalmani\Foundation\Resources\Tags;

use Clicalmani\Foundation\Resources\TemplateTag;

class EndIfTag extends TemplateTag
{
    /**
     * Tag expression
     * 
     * @var string
     */
    protected string $tag = '@endif';

    /**
     * Render a tag
     * 
     * @return string
     */
    public function render() : string
    {
        return "{% endif %}";
    }
}