<?php
namespace Clicalmani\Foundation\Providers\Config;

use Clicalmani\Foundation\Maker\Application;
use Clicalmani\Foundation\Maker\ServiceConfiguratorInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\DefaultsConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServiceConfigurator;

/**
 * Class MessengerConfig
 * 
 * Provides runtime dependency injection wiring for the framework's Messenger subsystem,
 * automatically passing the core messenger instance as a service argument.
 * 
 * @package Clicalmani\Foundation\Providers\Config
 * @author @clicalmani
 */
class MessengerConfig implements ServiceConfiguratorInterface
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
        // Inject the default compiled messenger service reference as a constructor argument
        $configurator->args([
            $app->dependency('service', 'messenger')
        ]);
    }
}