<?php
namespace Clicalmani\Foundation\Container;

/**
 * Class SPL_Loader
 * 
 * @package Clicalmani\Foundation\Container
 * @author @clicalmani
 */
class SPL_Loader extends Manager
{
    /**
     * Lazy load
     * 
     * @param string $classname
     * @return void
     */
    public function lazyLoad(string $classname) : void
    {
        $this->cache($classname);
    }

    /**
     * Load
     * 
     * @param string $classname
     * @return never
     */
    public function load(string $classname) : never
    {
        $this->current_class = $classname;
        $this->require( $this->find() );
    }

    /**
     * Inject into service container
     * 
     * @param string|callable $class_or_file
     * @return void
     */
    public function inject(string|callable $class_or_file) : void
    {
        if (is_callable($class_or_file) AND $file = $class_or_file() AND file_exists($file)) include_once $file;
        elseif ( is_string($class_or_file) ) $this->lazyLoad($class_or_file);
    }
}
