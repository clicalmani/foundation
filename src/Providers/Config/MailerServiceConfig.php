<?php
namespace Clicalmani\Core\Providers\Config;

use Clicalmani\Core\Maker\Application;
use Clicalmani\Core\Maker\ServiceConfiguratorInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServiceConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\DefaultsConfigurator;
use Override;

final class MailerServiceConfig implements ServiceConfiguratorInterface
{
    #[Override]
    public function __invoke(ServiceConfigurator|DefaultsConfigurator $services, Application $app): void
    {
        $services->args([
            $app->dependency('service', 'mailer'),
            $app->config['mail']['default_layout'] ?? ''
        ]);
    }
}