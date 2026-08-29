<?php
namespace Clicalmani\Foundation\Providers\Config;

use Clicalmani\Foundation\Maker\Application;
use Clicalmani\Foundation\Maker\ServiceConfiguratorInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\DefaultsConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServiceConfigurator;

/**
 * Class EventDispatcherConfig
 * 
 * This class serves as a configuration provider for the event dispatcher service within the application.
 * 
 * @package Clicalmani\Foundation\Providers\Config
 * @author @clicalmani
 */
class EventDispatcherConfig implements ServiceConfiguratorInterface
{
    /**
     * Binds the necessary container service reference into the target configurator instance.
     *
     * @param ServiceConfigurator|DefaultsConfigurator $configurator The active Symfony DI builder.
     * @param \Clicalmani\Foundation\Maker\Application $app The central framework application instance.
     * @return void
     */
    public function __invoke(ServiceConfigurator|DefaultsConfigurator $configurator, Application $app) : void
    {
        // Bind the core event dispatcher service into the configurator for dependency injection
        $configurator->args([
            $app->dependency('service', \Clicalmani\Foundation\Events\CoreEventDispatcher::class)
        ]);
    }
}