<?php
namespace Clicalmani\Foundation\Providers;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

/**
 * Class ContainerServiceProvider
 * 
 * Sets up, bootstraps, and exposes the underlying Symfony dependency injection container 
 * by parsing native configuration service manifests.
 * 
 * @package Clicalmani\Foundation\Providers
 * @author @clicalmani
 */
class ContainerServiceProvider extends ServiceProvider
{
    /**
     * The structural application service container builder instance.
     * 
     * @var \Symfony\Component\DependencyInjection\ContainerBuilder|null
     */
    protected static $container;
    
    /**
     * Boots the prerequisite service container infrastructure.
     * Initializes the builder and loads the explicit PHP services registration blueprint.
     * 
     * @return void
     * @throws \Exception If the services configuration file cannot be loaded or parsed.
     */
    public function boot(): void
    {
        with( new PhpFileLoader(self::$container = new ContainerBuilder, new FileLocator(__DIR__)) )
            ->load('services.php');
    }

    /**
     * Retrieves the globally managed dependency injection container builder instance.
     * 
     * @return \Symfony\Component\DependencyInjection\ContainerBuilder|null
     */
    public static function get() : ?\Symfony\Component\DependencyInjection\ContainerBuilder
    {
        return self::$container;
    }
}