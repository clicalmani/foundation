<?php
namespace Clicalmani\Core\Providers\Config;

use Clicalmani\Core\Maker\Application;
use Clicalmani\Core\Maker\ServiceConfiguratorInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServiceConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\DefaultsConfigurator;
use Override;

final class ResponseResolverRegistryConfig implements ServiceConfiguratorInterface
{
    #[Override]
    public function __invoke(ServiceConfigurator|DefaultsConfigurator $services, Application $app): void
    {
        $services->args([])->call('init', [
            [
                \Clicalmani\Core\Http\Response\Resolvers\HtmlResolver::class,
                \Clicalmani\Core\Http\Response\Resolvers\ViewResolver::class,
                \Clicalmani\Core\Http\Response\Resolvers\JsonResolver::class,
                \Clicalmani\Core\Http\Response\Resolvers\InertiaResolver::class,
            ]
        ]);
    }
}