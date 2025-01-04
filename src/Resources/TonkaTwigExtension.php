<?php
namespace Clicalmani\Foundation\Resources;

class TonkaTwigExtension extends \Twig\Extension\AbstractExtension implements \Twig\Extension\GlobalsInterface
{
    public function getFunctions() : array
    {
        return [
            new \Twig\TwigFunction('csrf_field', [$this, 'csrf_field']),
            new \Twig\TwigFunction('route', [$this, 'route']),
            new \Twig\TwigFunction('assets', [$this, 'assets']),
        ];
    }

    public function getFilters() : array
    {
        return [
            new \Twig\TwigFilter('json', [$this, 'json']),
        ];
    }

    public function csrf_field() : string
    {
        return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
    }

    public function route(...$args) : string
    {
        return route(...$args);
    }

    public function json(string $json) : \stdClass
    {
        return json_decode($json);
    }

    public function assets(?string $path = '/') : string
    {
        return assets($path);
    }

    public function getGlobals() : array
    {
        return [
            'app' => [
                'name' => env('APP_NAME'),
                'url' => env('APP_URL'),
                'env' => $_ENV,
                'debug' => env('APP_DEBUG'),
                'timezone' => env('APP_TIMEZONE'),
            ],
        ];
    }

    public function getName() : string
    {
        return 'tonka_twig_extension';
    }
}