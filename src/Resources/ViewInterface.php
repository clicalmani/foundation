<?php
namespace Clicalmani\Foundation\Resources;

interface ViewInterface
{
    /**
     * Render the view
     * 
     * @return string
     */
    public function render(): string;

    /**
     * Add data to the view context
     * 
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function with(string $key, mixed $value): self;

    /**
     * Share data across all views
     * 
     * @param string $key
     * @param mixed $value
     */
    public static function share(string $key, mixed $value): void;

    /**
     * Add a view composer
     * 
     * @param string|array $views
     * @param string|callable $composer
     * @return void
     */
    public static function composer(string|array $views, string|callable $composer): void;

    /**
     * Add a view creator
     * 
     * @param string $view
     * @param string|callable $creator
     * @return void
     */
    public static function create(string $view, string|callable $creator) : void;
}