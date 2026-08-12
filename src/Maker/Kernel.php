<?php
namespace Clicalmani\Foundation\Maker;

/**
 * Class Kernel
 * 
 * Provides a foundational blueprint for handling the initialization, configuration,
 * and component registration layers of specific application execution cycles (such as HTTP or CLI).
 * 
 * @package Clicalmani\Foundation\Maker
 * @author @clicalmani
 */
abstract class Kernel
{
    /**
     * Kernel constructor.
     * 
     * @param \Clicalmani\Foundation\Maker\Application $app The central framework application instance.
     */
    public function __construct(protected \Clicalmani\Foundation\Maker\Application $app)
    {
        //
    }

    /**
     * Bootstraps prerequisite components, global options, and environment variables 
     * required before the kernel starts handling incoming interactions.
     * 
     * @return void
     */
    public abstract function boot() : void;

    /**
     * Registers specific routing rules, console commands, structural middleware, 
     * or dedicated services linked to this execution context into the application container.
     * 
     * @return void
     */
    public abstract function register() : void;
}