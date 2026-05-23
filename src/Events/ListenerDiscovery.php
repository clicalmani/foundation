<?php

namespace Clicalmani\Foundation\Events;

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
        $filter = new RecursiveFilter(
            new \RecursiveDirectoryIterator($this->path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        $filter->setPattern("\\.php$");

        foreach (new \RecursiveIteratorIterator($filter) as $file) {

            $classNameOnly = $file->getBasename('.php');
            $className = rtrim($this->namespace, '\\') . '\\' . $classNameOnly;
            
            if (class_exists($className)) {
                $reflection = new ReflectionClass($className);
                $attributes = $reflection->getAttributes(AsEventListener::class);

                foreach ($attributes as $attribute) {
                    /** @var AsEventListener $instance */
                    $instance = $attribute->newInstance();
                    
                    // On récupère le nom de l'événement configuré dans l'attribut
                    $event = $instance->event;

                    // Si l'événement n'est pas spécifié, on essaie de le deviner 
                    // via le type du premier argument de la méthode exécutée
                    $method = $instance->method ?? '__invoke';
                    
                    if (!$event && $reflection->hasMethod($method)) {
                        $parameters = $reflection->getMethod($method)->getParameters();
                        if (isset($parameters[0]) && $parameters[0]->getType()) {
                            $event = $parameters[0]->getType()->getName();
                        }
                    }

                    if ($event) {
                        // On attache le listener au dispatcher
                        // Symfony accepte un callable de type [Instance, Méthode]
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