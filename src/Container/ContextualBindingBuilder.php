<?php
namespace Clicalmani\Foundation\Container;

class ContextualBindingBuilder
{
    protected string $concrete;

    protected string $abstract;

    protected string $context;

    public function __construct(Container $container, string $abstract, string $context)
    {
        $this->concrete = $concrete;
        $this->abstract = $abstract;
        $this->context = $context;
    }

    public function needs(string $abstract): static
    {
        return $this;
    }

    public function give($implementation): void
    {
    }
}