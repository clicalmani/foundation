<?php

namespace Clicalmani\Core\Messenger;

use Clicalmani\Core\Filesystem\DirectoryScanner;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class SubscriberDiscovery
 * 
 * Provides an automated filesystem scanner designed to discover and register event 
 * subscribers across application directory trees. Validates class inheritance 
 * structures and lazily provisions subscriber instances via the core framework container.
 * 
 * @package Clicalmani\Core\Messenger
 * @author @clicalmani
 */
class SubscriberDiscovery
{
    /**
     * Recursively scans a targeted directory map to instantiate and register event subscriber layers.
     * Maps physical components into compliant PSR-4 namespace mappings.
     * 
     * @param string $dir The absolute path to the directory resource evaluated.
     * @param string $namespace The root namespace context representing the current location.
     * @return array<int, EventSubscriberInterface> Collection containing the fully constructed subscriber instances.
     */
    public static function discover(string $dir, string $namespace): array
    {
        /** @var array<int, EventSubscriberInterface> $subscribers */
        $subscribers = [];

        if (!is_dir($dir)) {
            return $subscribers;
        }

        $subscriberClasses = (new DirectoryScanner(
            rootPath: $dir,
            baseNamespace: $namespace,
            ignore: ['.', '..']
        ))->discoverClasses(
            fn(string $className) => is_subclass_of($className, EventSubscriberInterface::class)
        );

        foreach ($subscriberClasses as $className) {
            /** @var EventSubscriberInterface $instance */
            $instance = container()->has($className) 
                ? container()->get($className) 
                : new $className();
                
            $subscribers[] = $instance;
        }

        return $subscribers;
    }
}