<?php

namespace Clicalmani\Foundation\Messenger;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SubscriberDiscovery
{
    /**
     * Scan a directory and its subdirectories to register Subscribers
     * * @param string $dir Absolute path of the directory (e.g., /~/app/EventSubscribers)
     * @param string $namespace Root namespace (e.g., App\EventSubscribers)
     * @return EventSubscriberInterface[]
     */
    public static function discover(string $dir, string $namespace): array
    {
        $subscribers = [];

        if (!is_dir($dir)) {
            return $subscribers;
        }

        // Safely retrieve all files and folders (excluding . and ..)
        $items = array_diff(scandir($dir), ['.', '..']);

        foreach ($items as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            
            // ── 1. Recursive handling of subdirectories ─────────────────────
            if (is_dir($path)) {
                // Append the folder name to the namespace to preserve the PSR-4 structure
                $subSubscribers = self::discover($path, $namespace . '\\' . $item);
                $subscribers = array_merge($subscribers, $subSubscribers);
                continue;
            }

            // ── 2. Processing Subscriber files ─────────────────────────────
            if (pathinfo($item, PATHINFO_EXTENSION) === 'php') {
                $className = $namespace . '\\' . pathinfo($item, PATHINFO_FILENAME);

                // Verify that the class exists and that it implements the correct Symfony interface
                if (class_exists($className) && is_subclass_of($className, EventSubscriberInterface::class)) {
                    
                    // Resolve via Tonka's Container if available, otherwise fallback to standard instantiation
                    $subscribers[] = container()->has($className) 
                        ? container()->get($className) 
                        : new $className();
                }
            }
        }

        return $subscribers;
    }
}