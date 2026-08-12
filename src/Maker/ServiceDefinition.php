<?php

namespace Clicalmani\Foundation\Maker;

/**
 * Class ServiceDefinition
 * 
 * Represents a normalized service registration metadata wrapper within the DI container framework.
 * Encapsulates service identity, target FQCN, initialization hooks/configurators, aliases, and service types.
 * 
 * @package Clicalmani\Foundation
 * @author @clicalmani
 */
final class ServiceDefinition
{
    /**
     * Normalized service configurator instance.
     * 
     * @var ServiceConfiguratorInterface|null
     */
    public readonly ?ServiceConfiguratorInterface $config;

    /**
     * Initializes a new service definition and normalizes its configuration pipeline.
     * 
     * @param string $id Unique service identifier or alias within the container.
     * @param string $class Fully qualified class name (FQCN) to be instantiated.
     * @param \Closure|class-string<ServiceConfiguratorInterface>|null $config Optional inline closure or configurator class name.
     * @param array{0: string, 1: string}|null $alias Optional alias mapping represented as [aliasId, targetReferenceId].
     * @param ServiceType $type Categorization descriptor (Core, Custom, or Namespace).
     */
    public function __construct(
        public readonly string $id,
        public readonly string $class,
        \Closure|string|null $config = null,
        public readonly ?array $alias = null,
        public readonly ServiceType $type = ServiceType::Custom,
    ) {
        $this->config = $this->normalizeConfig($config);
    }

    /**
     * Normalizes the provided configuration payload into a uniform ServiceConfiguratorInterface instance.
     * 
     * @param \Closure|string|null $config
     * @return ServiceConfiguratorInterface|null
     * @throws \InvalidArgumentException If the configuration payload does not meet type constraints.
     */
    private function normalizeConfig(\Closure|string|null $config): ?ServiceConfiguratorInterface
    {
        return match (true) {
            is_null($config) => null,
            $config instanceof \Closure => new CallableServiceConfigurator($config),
            is_subclass_of($config, ServiceConfiguratorInterface::class) => new $config,
            default => throw new \InvalidArgumentException(
                "Invalid config for service [{$this->id}]: must be a Closure or implement ServiceConfiguratorInterface."
            ),
        };
    }
}