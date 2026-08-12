<?php
namespace Clicalmani\Foundation\Maker;

use Symfony\Component\DependencyInjection\Loader\Configurator\DefaultsConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServiceConfigurator;

/**
 * Class CallableServiceConfigurator
 * 
 * An adapter wrapper that converts a standard PHP Closure callback into a valid
 * object-oriented ServiceConfiguratorInterface contract.
 * 
 * @package Clicalmani\Foundation\Maker
 * @author @clicalmani
 */
final class CallableServiceConfigurator implements ServiceConfiguratorInterface
{
    /**
     * CallableServiceConfigurator Constructor.
     * 
     * @param \Closure $callback The anonymous configuration function to wrap.
     */
    public function __construct(private \Closure $callback) {}

    /**
     * Executes the internal configuration wrapper callback when invoked by the container builder.
     *
     * @param ServiceConfigurator|DefaultsConfigurator $services The active Symfony DI service builder instance.
     * @param \Clicalmani\Foundation\Maker\Application $app The central framework application instance.
     * @return void
     */
    public function __invoke(ServiceConfigurator|DefaultsConfigurator $services, Application $app): void
    {
        with($this->callback)($services, $app);
    }
}