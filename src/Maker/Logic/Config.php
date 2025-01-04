<?php
namespace Clicalmani\Foundation\Maker\Logic;

use Clicalmani\Foundation\Providers\ServiceProvider;
use Clicalmani\Foundation\Support\Facades\Env;

class Config
{
    public function app(?string $key = null)
    {
        $app_config = ServiceProvider::getAppConfig();
        return $key ? $app_config[$this->parseKey($key)] : $app_config;
    }

    public function http(?string $key = null)
    {
        $http_config = ServiceProvider::getHttpConfig();
        return $key ? $http_config[$this->parseKey($key)] : $http_config;
    }

    public function bootstrap(?string $key = null)
    {
        $bootstrap_config = ServiceProvider::getBootstrapConfig();
        return $key ? $bootstrap_config[$this->parseKey($key)] : $bootstrap_config;
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
        $database_config = require_once config_path( '/database.php' );
        return $key ? $database_config[$this->parseKey($key)] : $database_config;
    }

    public function env(?string $key = null)
    {
        return $key ? Env::get($this->parseKey($key)) : $_ENV;
    }

    private function parseKey(string $key)
    {
        return collection(explode('.', $key))->map(fn($key) => strtolower($key))->join('_');
    }
}