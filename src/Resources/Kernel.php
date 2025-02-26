<?php
namespace Clicalmani\Foundation\Resources;

use Clicalmani\Foundation\Maker\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    /**
     * @var array
     */
    public static array $composers = [];

    /**
     * @var array
     */
    public static array $creators = [];

    /**
     * @var \Clicalmani\Foundation\Resources\TonkaTwigExtension
     */
    private $extension;

    /**
     * |---------------------------------------------------------------
     * | Template Tags
     * |---------------------------------------------------------------
     * 
     */
    public static $template_tags = [
        \Clicalmani\Foundation\Resources\Tags\CSRFTokenField::class,
        \Clicalmani\Foundation\Resources\Tags\IfTag::class,
        \Clicalmani\Foundation\Resources\Tags\EndIfTag::class,
        \Clicalmani\Foundation\Resources\Tags\Vite::class,
        \Clicalmani\Foundation\Resources\Tags\InertiaHead::class,
        \Clicalmani\Foundation\Resources\Tags\Inertia::class,
    ];

    /**
     * |---------------------------------------------------------------
     * | Template Functions
     * |---------------------------------------------------------------
     * 
     * @var \Twig\TwigFunction[]
     */
    public static array $functions = [];

    /**
     * |---------------------------------------------------------------
     * | Template Filters
     * |---------------------------------------------------------------
     * 
     * @var \Twig\TwigFilter[]
     */
    public static array $filters = [];

    public function boot(): void
    {
        $this->extension = new TonkaTwigExtension;
    }

    public function register(): void
    {
        $reflactor = new \ReflectionClass(TemplateFunctions::class);
        $methods = $reflactor->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            $this->extension->addFunction($method->name, [TemplateFunctions::class, $method->name]);
            $this->extension->addFilter($method->name, [TemplateFunctions::class, $method->name]);
        }
    }
}
