<?php
namespace Clicalmani\Foundation\FileSystem;

use League\Flysystem\Config;
use League\Flysystem\UrlGeneration\PublicUrlGenerator as UrlGenerator;

class PublicUrlGenerator implements UrlGenerator
{
    public function publicUrl(string $path, Config $config) : string
    {
        if ($url = $config->get('url')) return $url.'/'.$path;

        $app_url = app()->getUrl($path);
        $protocol = '';

        if (preg_match('/^http/', $app_url) == false) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || @$_SERVER['SERVER_PORT'] === 443) ? 'https://': 'http://';
        }
        
        return $protocol.$app_url;
    }
}