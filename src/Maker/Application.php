<?php
namespace Clicalmani\Foundation\Maker;

use Clicalmani\Foundation\Http\Requests\RequestController;
use Composer\Autoload\ClassLoader;

/**
 * Make an application
 * 
 * @package Clicalmani\Foundation
 * @author Clicalmani\Foundation
 */
class Application extends \Symfony\Component\Console\Application
{
    protected static $instance;

    public function __construct(private ?string $rootPath = null)
    {
        parent::__construct();
    }

    public static function getInstance(?string $rootPath)
    {
        if ( isset(static::$instance) ) return static::$instance;

        static::$instance = new self($rootPath);
        return static::$instance;
    }

    /**
     * Configuring a new Tonka application instance.
     *
     * @param  string|null  $rootPath
     * @return \Clicalmani\Foundation\Maker\ApplicationBuilder
     */
    public static function setup(?string $rootPath = null) : ApplicationBuilder
    {
        $rootPath = match (true) {
            is_string($rootPath) => $rootPath,
            default => static::inferRootPath(),
        };

        return (new ApplicationBuilder(static::getInstance($rootPath)));

        // return (new ApplicationBuilder(new static($rootPath)))
        //     ->withKernels()
        //     ->withEvents()
        //     ->withCommands()
        //     ->withProviders();
    }

    public function initProviders()
    {
        \Clicalmani\Foundation\Providers\ServiceProvider::init(
            require $this->rootPath . '/config/app.php',
            require $this->rootPath . '/bootstrap/kernel.php',
            require $this->rootPath . '/app/Http/kernel.php'
        );
    }

    public function handleRequest()
    {
        $this->initProviders();
        return \Clicalmani\Foundation\Http\Requests\RequestController::render();
    }

    public function handleCommands()
    {
        $this->make();
        $this->run();
    }

    /**
     * Infer the application's root directory from the environment.
     *
     * @return string
     */
    public static function inferRootPath()
    {
        return match (true) {
            isset($_ENV['APP_ROOT_PATH']) => $_ENV['APP_ROOT_PATH'],
            default => dirname(array_keys(ClassLoader::getRegisteredLoaders())[0]),
        };
    }

    public function make(mixed $abstract = null, ?array $parameters = [])
    {
        // Console Kernel
        $kernel = \Clicalmani\Console\Kernel::$kernel;

        foreach ($kernel as $command) {
            $this->add(new $command($this->rootPath));
        }

        $this->initProviders();
    }
}
