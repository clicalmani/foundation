<?php
namespace Clicalmani\Foundation\Resources\Tags;

use Clicalmani\Foundation\Http\Request;
use Clicalmani\Foundation\Resources\TemplateTag;

class Vite extends TemplateTag
{
    /**
     * Tag expression
     * 
     * @var string
     */
    protected string $tag = '@vite\(([0-9a-zA-Z\'"-_\/\.]+)\)';

    /**
     * Render a tag
     * 
     * @return string
     */
    public function render() : string
    {
        return '<script type="module" src="' . (app()->env() !== 'production' ? app()->config('app.asset_url', ''): '@') . '/{{ strip_quotes($1) }}"></script>';
    }
}