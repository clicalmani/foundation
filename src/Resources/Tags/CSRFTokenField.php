<?php
namespace Clicalmani\Core\Resources\Tags;

use Clicalmani\Core\Resources\TemplateTag;

class CSRFTokenField extends TemplateTag
{
    /**
     * Tag expression
     * 
     * @var string
     */
    protected string $tag = '@csrf';

    /**
     * Render a tag
     * 
     * @return string
     */
    public function render(array $matches) : string
    {
        return "<input type='hidden' name='csrf_token' value='" . csrf_token() . "'>";
    }
}