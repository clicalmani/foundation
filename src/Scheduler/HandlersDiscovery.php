<?php
namespace Clicalmani\Foundation\Scheduler;

use Clicalmani\Foundation\Filesystem\RecursiveFilter;

class HandlersDiscovery
{
    public function __construct(private ?string $handlersPath = 'app/Handlers', private ?string $namespace = 'App\\Handlers')
    {}

    public function discover(): array
    {
        $handlersMapping = [];
        $this->handlersPath = root_path($this->handlersPath);
        
        if (!is_dir($this->handlersPath)) {
            return []; // On ne crée pas le dossier à la volée
        }

        $filter = new RecursiveFilter(
            new \RecursiveDirectoryIterator($this->handlersPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        $filter->setPattern("\\.php$");

        foreach (new \RecursiveIteratorIterator($filter) as $file) {
            $class = rtrim($this->namespace, '\\') . '\\' . $file->getBasename('.php');
            
            if (class_exists($class, false)) {
                $reflection = new \ReflectionClass($class);
                if ($reflection->hasMethod('__invoke') && $reflection->isInstantiable()) {
                    $method = $reflection->getMethod('__invoke');
                    $parameters = $method->getParameters();
                    
                    if (isset($parameters[0]) && $type = $parameters[0]->getType()) {
                        // CORRECTION : Sécurise contre les Union Types (PHP 8+) et les types scalaires
                        if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                            $messageClass = $type->getName();
                            app()->addService($class, [$class]);
                            $handlersMapping[$messageClass] = new $class;
                        }
                    }
                }
            }
        }

        return $handlersMapping;
    }
}