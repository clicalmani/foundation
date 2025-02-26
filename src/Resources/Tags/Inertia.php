<?php
namespace Clicalmani\Foundation\Resources\Tags;

use Clicalmani\Foundation\Resources\TemplateTag;

class Inertia extends TemplateTag
{
    /**
     * Tag expression
     * 
     * @var string
     */
    protected string $tag = '@inertia';

    /**
     * Render a tag
     * 
     * @return string
     */
    public function render() : string
    {
        return <<<INERTIA
        <div id="app" data-page="{{ retrieveViewData() }}"></div>
        INERTIA;
    }
}