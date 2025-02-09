<?php
namespace Clicalmani\Foundation\Resources\Tags;

use Clicalmani\Foundation\Resources\TemplateTag;

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
    public function render() : string
    {
        return "<input type='hidden' name='csrf_token' value='" . csrf_token() . "'>";
    }
}