<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    app()->initServices(
        $container->services()
            ->defaults()
            ->autowire()
            ->autoconfigure()
    )->registerCoreContainerServices();
};