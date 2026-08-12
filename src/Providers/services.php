<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

/**
 * Dependency Injection Service Registry Manifest.
 * 
 * Configures the primary container defaults (autowiring and autoconfiguration) 
 * and binds core framework system components into the active execution space.
 * 
 * @param \Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $container The container configurator wrapper.
 * @return void
 */
return static function (ContainerConfigurator $container): void {
    
    // Initialize defaults, enable intelligent autowire resolution, and register core framework subsystems
    app()->initServices(
        $container->services()
            ->defaults()
            ->autowire()
            ->autoconfigure()
    )->registerCoreContainerServices();
};