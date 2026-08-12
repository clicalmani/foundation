<?php
namespace Clicalmani\Foundation\Providers\Config;

use Clicalmani\Foundation\Maker\Application;
use Clicalmani\Foundation\Maker\ServiceConfiguratorInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\DefaultsConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServiceConfigurator;

/**
 * Class ResponseConfig
 * 
 * Provides runtime dependency injection wiring for the framework's HTTP Response service,
 * establishing default initialization values for the HTTP status codes.
 * 
 * @package Clicalmani\Foundation\Providers\Config
 * @author @clicalmani
 */
class ResponseConfig implements ServiceConfiguratorInterface
{
    /**
     * Binds default response parameters into the target configurator instance.
     *
     * @param ServiceConfigurator|DefaultsConfigurator $configurator The active Symfony DI builder.
     * @param \Clicalmani\Foundation\Maker\Application $app The central framework application instance.
     * @return void
     */
    public function __invoke(ServiceConfigurator|DefaultsConfigurator $configurator, Application $app) : void
    {
        // Inject the PSR interface default status string and corresponding integer value as constructor arguments
        $configurator->args([
            \Clicalmani\Psr\StatusCodeInterface::STATUS_OK,
            200
        ]);
    }
}