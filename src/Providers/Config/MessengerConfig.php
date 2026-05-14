<?php
namespace Clicalmani\Foundation\Providers\Config;

use Symfony\Component\DependencyInjection\Loader\Configurator\DefaultsConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServiceConfigurator;

class MessengerConfig
{
    public function __invoke(ServiceConfigurator|DefaultsConfigurator $configurator) : void
    {
        $configurator->args([
            app()->dependency('service', 'messenger')
        ]);
    }
}