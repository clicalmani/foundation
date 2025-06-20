<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
                    ->defaults()
                    ->autowire()
                    ->autoconfigure();
    
    /**
     * Logger service
     */
    $services->set('logger', \Clicalmani\Foundation\Acme\Logger::class);

    /**
     * String service
     */
    $services->set('str', \Clicalmani\Foundation\Acme\Stringable::class);

    /**
     * Router service
     */
    $services->set('router', \Clicalmani\Foundation\Acme\Router::class);

    /**
     * Array service
     */
    $services->set('array', \Clicalmani\Foundation\Acme\Arrayable::class);

    /**
     * Env service
     */
    $services->set('env', \Clicalmani\Foundation\Acme\Environment::class);

    /**
     * Config service
     */
    $services->set('config', \Clicalmani\Foundation\Acme\Configure::class);

    /**
     * Console service
     */
    $services->set('console', \Clicalmani\Foundation\Acme\Console::class);

    /**
     * Console service
     */
    $services->set('storage', \Clicalmani\Foundation\Acme\StorageManager::class);

    /**
     * Controller service
     */
    $services->set('controller', \Clicalmani\Foundation\Acme\Controller::class);

    /**
     * Function service
     */
    $services->set('func', \Clicalmani\Foundation\Acme\Invokable::class);

    /**
     * Inertia service
     */
    if (class_exists(\Inertia\Response::class)) {
        $services->set('inertia', \Inertia\Response::class);
    }
};