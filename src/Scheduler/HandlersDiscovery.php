<?php

namespace Clicalmani\Foundation\Scheduler;

use Clicalmani\Foundation\Filesystem\RecursiveFilter;

class HandlersDiscovery
{
    public function __construct(
        private ?string $handlersPath = 'app/Handlers', 
        private ?string $namespace = 'App\\Handlers'
    ) {}

    public function discover(): array
    {
        $handlersMapping = [];
        $this->handlersPath = root_path($this->handlersPath);
        
        if (!is_dir($this->handlersPath)) {
            return [];
        }

        $directory = new \RecursiveDirectoryIterator($this->handlersPath, \RecursiveDirectoryIterator::SKIP_DOTS);
        $filter = new \RecursiveCallbackFilterIterator($directory, function ($current, $key, $iterator) {
            if ($iterator->hasChildren()) {
                return true;
            }
            return $current->isFile() && preg_match('/\.php$/', $current->getFilename());
        });

        $rootPath = rtrim(realpath($this->handlersPath), DIRECTORY_SEPARATOR);
        $baseNamespace = rtrim($this->namespace, '\\');

        /** @var \SplFileInfo $file */
        foreach (new \RecursiveIteratorIterator($filter) as $file) {
            $currentSubDir = dirname($file->getRealPath());
            $relativeSubDir = str_replace($rootPath, '', $currentSubDir);
            $subNamespace = str_replace(DIRECTORY_SEPARATOR, '\\', $relativeSubDir);
            $classNameOnly = $file->getBasename('.php');

            $className = $baseNamespace . $subNamespace . '\\' . $classNameOnly;
            $className = str_replace('\\\\', '\\', $className); // Safeguard against double backslashes
            
            if (class_exists($className)) {
                $reflection = new \ReflectionClass($className);
                
                if ($reflection->hasMethod('__invoke') && $reflection->isInstantiable()) {
                    $method = $reflection->getMethod('__invoke');
                    $parameters = $method->getParameters();
                    
                    if (isset($parameters[0]) && $type = $parameters[0]->getType()) {
                        // Protects against Union/Intersection Types and scalar types
                        if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                            $messageClass = $type->getName();
                            
                            // Register within your Tonka container
                            app()->addService($className, [$className]);
                            
                            // Resolve via container to allow dependency injection, fallback to standard instantiation
                            $handlersMapping[$messageClass] = container()->has($className) 
                                ? container()->get($className)
                                : new $className();
                        }
                    }
                }
            }
        }

        return $handlersMapping;
    }
}