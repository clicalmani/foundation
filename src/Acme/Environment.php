<?php
namespace Clicalmani\Foundation\Acme;

use Dotenv\Dotenv;
use Dotenv\Repository\Adapter\EnvConstAdapter;
use Dotenv\Repository\Adapter\PutenvAdapter;
use Dotenv\Repository\RepositoryBuilder;

class Environment
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
    public function enablePutenv() : void
    {
        self::$putenv = true;
        self::$repository = null;
    }

    /**
     * Disable the putenv adapter
     * 
     * @return void
     */
    public function disablePutenv() : void
    {
        self::$putenv = false;
        self::$repository = null;
    }

    /**
     * Gets the environment repository instance.
     * 
     * @return \Dotenv\Repository\RepositoryInterface
     */
    public function getRepository()
    {
        if (self::$repository === null) {

            $builder = RepositoryBuilder::createWithDefaultAdapters();
            
            if (self::$putenv) {
                $builder = RepositoryBuilder::createWithNoAdapters();
                $builder = $builder->addAdapter(EnvConstAdapter::class);
                $builder = $builder->addWriter(PutenvAdapter::class);
            }

            self::$repository = $builder->immutable()->make();
        }
        
        return self::$repository;
    }

    /**
     * Get the value of the environment variable.
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
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
    public function set(string $key, $value) : void
    {
        self::getRepository()->set($key, $value);
    }

    protected static function getFacadeAccessor() : string
    {
        return 'env';
    }
}