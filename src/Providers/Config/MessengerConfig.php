<?php
namespace Clicalmani\Core\Providers\Config;

use Clicalmani\Core\Maker\Application;
use Clicalmani\Core\Maker\ServiceConfiguratorInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\DefaultsConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServiceConfigurator;

/**
 * Class MessengerConfig
 * 
 * Provides runtime dependency injection wiring for the framework's Messenger subsystem,
 * automatically passing the core messenger instance as a service argument.
 * 
 * @package Clicalmani\Core\Providers\Config
 * @author @clicalmani
 */
class MessengerConfig implements ServiceConfiguratorInterface
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
        // Inject the default compiled messenger service reference as a constructor argument
        $configurator->args([
            $app->dependency('service', 'messenger')
        ]);
    }
}