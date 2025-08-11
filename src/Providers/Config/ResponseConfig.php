<?php
namespace Clicalmani\Foundation\Providers\Config;

use Symfony\Component\DependencyInjection\Loader\Configurator\DefaultsConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServiceConfigurator;

class ResponseConfig
{
    public function __invoke(ServiceConfigurator|DefaultsConfigurator $configurator) : void
    {
        $configurator->args([
            \Clicalmani\Psr\StatusCodeInterface::STATUS_OK,
            200
        ]);
    }
}