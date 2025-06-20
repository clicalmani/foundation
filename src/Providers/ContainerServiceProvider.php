<?php
namespace Clicalmani\Foundation\Providers;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

/**
 * ContainerServiceProvider class
 * 
 * @package Clicalmani\Foundation/flesco 
 * @author @Clicalmani\Foundation
 */
class ContainerServiceProvider extends ServiceProvider
{
    /**
     * App Service Container
     * 
     * @var \Symfony\Component\DependencyInjection\ContainerBuilder
     */
    protected static $container;
    
    public function boot(): void
    {
        with( new PhpFileLoader(self::$container = new ContainerBuilder, new FileLocator(__DIR__)) )
            ->load('services.php');
    }

    public static function get()
    {
        return self::$container;
    }
}