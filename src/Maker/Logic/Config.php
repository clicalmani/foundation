<?php
namespace Clicalmani\Foundation\Maker\Logic;

use Clicalmani\Foundation\Support\Facades\Env;
use JsonSerializable;

class Config implements \ArrayAccess, JsonSerializable
{
    /**
     * App config
     * 
     * @var array
     */
    protected static $app_config;

    /**
     * Service kernel
     * 
     * @var array
     */
    protected static $kernel;

    /**
     * Http middlewares
     * 
     * @var array
     */
    protected static $http_kernel;

    protected static $paths = [];

    public function app(?string $key = null)
    {
        return $key ? static::$app_config[$this->parseKey($key)] : static::$app_config;
    }

    public function http(?string $key = null)
    {
        return $key ? static::$http_kernel[$this->parseKey($key)] : static::$http_kernel;
    }

    public function bootstrap(?string $key = null)
    {
        return $key ? static::$kernel[$this->parseKey($key)] : static::$kernel;
    }

    public function route(?string $key = null)
    {
        $provider = new \App\Providers\RouteServiceProvider;
        $route_config = [
            'api_prefix' => $provider->getApiPrefix(),
            'parameter_prefix' => $provider->getParameterPrefix(),
            'api_handler' => $provider->getApiHandler(),
            'web_handler' => $provider->getWebHandler(),
            'default_builder' => $provider->getDefaultBuilder(),
            'builders' => $provider->getBuilders()
        ];
        return $key ? $route_config[$this->parseKey($key)] : $route_config;
    }

    public function database(?string $key = null)
    {
        if ( ! isset($key) ) return $database_config = app()->database;
        return app()->database[$this->parseKey($key)];
    }

    public function env(?string $key = null)
    {
        return $key ? Env::get($this->parseKey($key)) : $_ENV;
    }

    private function parseKey(string $key)
    {
        return collection(explode('.', $key))->map(fn($key) => strtolower($key))->join('_');
    }

    public function offsetExists(mixed $offset): bool
    {
        return !!in_array($offset, ['app', 'bootstrap', 'http']);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return match ($offset) {
            'app' => static::$app_config,
            'bootstrap' => static::$kernel,
            'http' => static::$http_kernel,
            'paths' => static::$paths
        };
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        match ($offset) {
            'app' => static::$app_config = $value,
            'bootstrap' => static::$kernel = $value,
            'http' => static::$http_kernel = $value,
            'paths' => static::$paths = $value
        };
    }

    public function offsetUnset(mixed $offset): void
    {
        switch ($offset) {
            case 'app': unset(static::$app_config[$offset]); break;
            case 'bootstrap': unset(static::$kernel[$offset]); break;
            case 'http': unset(static::$http_kernel[$offset]); break;
            case 'paths': unset(static::$paths[$offset]); break;
        };
    }

    public function jsonserialize(): array
    {
        return [
            'app' => static::$app_config,
            'bootstrap' => static::$kernel,
            'http' => static::$http_kernel
        ];
    }
}