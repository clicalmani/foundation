<?php
namespace Clicalmani\Foundation\Maker;

use Symfony\Component\DependencyInjection\Loader\Configurator\DefaultsConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServiceConfigurator;

/**
 * Interface ServiceConfiguratorInterface
 * 
 * Outlines the contract for object-oriented container configuration hook handlers.
 * Allows isolation of advanced service wiring logic from the service definition models.
 * 
 * @package Clicalmani\Foundation\Maker
 * @author @clicalmani
 */
interface ServiceConfiguratorInterface
{
    /**
     * Executes the custom service configuration and wiring routines.
     *
     * @param ServiceConfigurator|DefaultsConfigurator $services The active Symfony DI service builder instance.
     * @param \Clicalmani\Foundation\Maker\Application $app The central framework application instance.
     * @return void
     */
    public function __invoke(ServiceConfigurator|DefaultsConfigurator $services, Application $app): void;
}