<?php

namespace Clicalmani\Foundation\Events;

use Broadcaster\BroadcastManager;
use Broadcaster\Event\ShouldBroadcastInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Clicalmani\Foundation\Filesystem\RecursiveFilter;
use ReflectionClass;

class ListenerDiscovery
{
    public function __construct(private string $path, private string $namespace, private EventDispatcherInterface $dispatcher)
    {}

    public function discover(): void
    {
        $directory = new \RecursiveDirectoryIterator($this->path, \RecursiveDirectoryIterator::SKIP_DOTS);
        $filter = new \RecursiveCallbackFilterIterator($directory, function ($current, $key, $iterator) {
            if ($iterator->hasChildren()) {
                return true;
            }
            return $current->isFile() && preg_match('/\.php$/', $current->getFilename());
        });

        $rootPath = rtrim(realpath($this->path), DIRECTORY_SEPARATOR);
        $baseNamespace = rtrim($this->namespace, '\\');

        /** @var \SplFileInfo $file */
        foreach (new \RecursiveIteratorIterator($filter) as $file) {
            $currentSubDir = dirname($file->getRealPath());
            $relativeSubDir = str_replace($rootPath, '', $currentSubDir);
            $subNamespace = str_replace(DIRECTORY_SEPARATOR, '\\', $relativeSubDir);
            $classNameOnly = $file->getBasename('.php');
            
            $className = $baseNamespace . $subNamespace . '\\' . $classNameOnly;
            $className = str_replace('\\\\', '\\', $className); // Double slash safety guard
            
            if (class_exists($className)) {
                $reflection = new ReflectionClass($className);
                $attributes = $reflection->getAttributes(AsEventListener::class);

                foreach ($attributes as $attribute) {
                    /** @var AsEventListener $instance */
                    $instance = $attribute->newInstance();
                    
                    // Retrieve the event name configured inside the attribute
                    $event = $instance->event;

                    // If the event name is not specified, attempt to infer it 
                    // from the typehint of the first argument on the target method.
                    $method = $instance->method ?? '__invoke';
                    
                    if (!$event && $reflection->hasMethod($method)) {
                        $parameters = $reflection->getMethod($method)->getParameters();
                        
                        if (isset($parameters[0]) && $parameters[0]->getType()) {
                            $event = $parameters[0]->getType()->getName();
                        }
                    }

                    if ($event) {
                        // Attach the listener to the dispatcher.
                        // Symfony accepts an [Instance, Method] callable array syntax.
                        $this->dispatcher->addListener($event, [new $className(), $method], $instance->priority);
                    }
                }
            }
        }
    }

    public function dispatch(object $event, ?string $eventName = null): object
    {
        return $this->dispatcher->dispatch($event, $eventName);
    }
}