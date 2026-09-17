<?php
namespace Clicalmani\Core\Providers\Config;

use Clicalmani\Core\Maker\Application;
use Clicalmani\Core\Maker\ServiceConfiguratorInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\DefaultsConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServiceConfigurator;

/**
 * Class EventDispatcherConfig
 * 
 * This class serves as a configuration provider for the event dispatcher service within the application.
 * 
 * @package Clicalmani\Core\Providers\Config
 * @author @clicalmani
 */
class EventDispatcherConfig implements ServiceConfiguratorInterface
{
    /**
     * Binds the necessary container service reference into the target configurator instance.
     *
     * @param ServiceConfigurator|DefaultsConfigurator $configurator The active Symfony DI builder.
     * @param \Clicalmani\Core\Maker\Application $app The central framework application instance.
     * @return void
     */
    public function __invoke(ServiceConfigurator|DefaultsConfigurator $configurator, Application $app) : void
    {
        // Bind the core event dispatcher service into the configurator for dependency injection
        $configurator->args([
            $app->dependency('service', \Clicalmani\Core\Events\CoreEventDispatcher::class)
        ]);
    }
}