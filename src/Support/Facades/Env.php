<?php 
namespace Clicalmani\Foundation\Support\Facades;

use Clicalmani\Foundation\Support\Facades\Facade;
use Dotenv\Dotenv;
use Dotenv\Repository\Adapter\EnvConstAdapter;
use Dotenv\Repository\Adapter\PutenvAdapter;
use Dotenv\Repository\RepositoryBuilder;

global $dotenv;

/**
 * Class Env
 * 
 * @package Clicalmani\Foundation
 * @author @Clicalmani\Foundation
 */
class Env extends Facade
{
    /**
     * Indicate if the putenv adapter is enabled.
     * 
     * @var bool
     */
    protected static $putenv = false;

    /**
     * The environment repository instance.
     * 
     * @var \Dotenv\Repository\RepositoryInterface|null
     */
    protected static $repository;

    /**
     * Enable the putenv adapter
     * 
     * @return void
     */
    public static function enablePutenv() : void
    {
        static::$putenv = true;
        static::$repository = null;
    }

    /**
     * Disable the putenv adapter
     * 
     * @return void
     */
    public static function disablePutenv() : void
    {
        static::$putenv = false;
        static::$repository = null;
    }

    /**
     * Gets the environment repository instance.
     * 
     * @return \Dotenv\Repository\RepositoryInterface
     */
    public static function getRepository()
    {
        if (static::$repository === null) {

            $builder = RepositoryBuilder::createWithDefaultAdapters();
            
            if (static::$putenv) {
                $builder = RepositoryBuilder::createWithNoAdapters();
                $builder = $builder->addAdapter(EnvConstAdapter::class);
                $builder = $builder->addWriter(PutenvAdapter::class);
            }

            static::$repository = $builder->immutable()->make();
        }
        
        return static::$repository;
    }

    /**
     * Get the value of the environment variable.
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        return static::getRepository()->get($key, $default);
    }

    /**
     * Set the value of the environment variable.
     * 
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function set(string $key, $value) : void
    {
        static::getRepository()->set($key, $value);
    }
}
