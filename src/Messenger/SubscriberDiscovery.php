<?php

namespace Clicalmani\Foundation\Messenger;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class SubscriberDiscovery
 * 
 * Provides an automated filesystem scanner designed to discover and register event 
 * subscribers across application directory trees. Validates class inheritance 
 * structures and lazily provisions subscriber instances via the core framework container.
 * 
 * @package Clicalmani\Foundation\Messenger
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

        // Safely extract directories and files while filtering out dot reference markers
        $scannedItems = scandir($dir);
        $items        = false !== $scannedItems ? array_diff($scannedItems, ['.', '..']) : [];

        foreach ($items as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            
            // ── 1. Recursive handling of subdirectories ─────────────────────
            // Propagate the subdirectory item names upward to safely follow nested PSR-4 structures.
            if (is_dir($path)) {
                $subSubscribers = self::discover($path, $namespace . '\\' . $item);
                $subscribers    = array_merge($subscribers, $subSubscribers);
                continue;
            }

            // ── 2. Processing Subscriber files ─────────────────────────────
            if (pathinfo($item, PATHINFO_EXTENSION) === 'php') {
                $className = $namespace . '\\' . pathinfo($item, PATHINFO_FILENAME);

                // Ensure the extracted class string is loaded and conforms to Symfony's contract
                if (class_exists($className) && is_subclass_of($className, EventSubscriberInterface::class)) {
                    
                    // Route resolution through Tonka's dependency container or fall back to native setup
                    /** @var EventSubscriberInterface $instance */
                    $instance = container()->has($className) 
                        ? container()->get($className) 
                        : new $className();
                        
                    $subscribers[] = $instance;
                }
            }
        }

        return $subscribers;
    }
}