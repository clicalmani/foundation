<?php
namespace Clicalmani\Foundation\Acme;

class Stringable implements \Stringable
{
    /**
     * @var \Doctrine\Inflector\Inflector
     */
    private $inflector;

    public function __construct() 
    {
        $this->inflector = \Doctrine\Inflector\InflectorFactory::create()->build();
    }

    /**
     * Create a slug
     * 
     * @param string $value
     * @param ?string $fallback_value
     * @return string
     */
    public function slug(mixed $value, ?string $fallback_value = null) : string 
    {
        if ( ! isset($value) ) return $fallback_value;

        return str_replace('_', '-', $this->inflector->tableize($value));
    }

    /**
     * Get the singular form of a word
     * 
     * @param string $word
     * @return string Singular form of the word
     */
    public function singularize(string $word): string
    {
        return $this->inflector->singularize($word);
    }

    public function pluralize(string $word): string
    {
        return $this->inflector->pluralize($word);
    }

    public function urlize(string $string): string
    {
        return $this->inflector->urlize($string);
    }

    public function tableize(string $word): string
    {
        return $this->inflector->tableize($word);
    }

    public function classify(string $word): string
    {
        return $this->inflector->classify($word);
    }

    public function camelize(string $word): string
    {
        return $this->inflector->camelize($word);
    }

    public function capitalize(string $word): string
    {
        return $this->inflector->capitalize($word);
    }

    public function __toString(): string
    {
        return '';
    }
}