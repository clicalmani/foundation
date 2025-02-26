<?php
namespace Clicalmani\Foundation\Resources;

class TonkaTwigExtension extends \Twig\Extension\AbstractExtension implements \Twig\Extension\GlobalsInterface
{
    use Paths;

    public function addFunction(string $name, callable $callback, array $options = [])
    {
        Kernel::$functions[] = new \Twig\TwigFunction($name, $callback, $options);
    }

    public function addFilter(string $name, callable $callback, array $options = [])
    {
        Kernel::$filters[] = new \Twig\TwigFilter($name, $callback, $options);
    }

    public function getFunctions() : array
    {
        return Kernel::$functions;
    }

    public function getFilters() : array
    {
        return [
            new \Twig\TwigFilter('json', [$this, 'json']),
        ];
    }

    public function getTokenParsers()
    {
        return [];
    }

    public function getGlobals() : array
    {
        return array_merge(app()->viewSharedData(), [
            'app' => [
                'name' => app()->config('app.name'),
                'url' => app()->config('app.url'),
                'env' => app()->config('app.env'),
                'debug' => app()->config('app.debug'),
                'timezone' => app()->config('app.timezone'),
            ],
        ]);
    }

    public function getName() : string
    {
        return 'tonka_twig_extension';
    }
}