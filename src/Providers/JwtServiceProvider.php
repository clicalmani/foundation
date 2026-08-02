<?php
namespace Clicalmani\Foundation\Providers;

use Clicalmani\Container\Application;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Symfony\Component\DependencyInjection\Loader\Configurator\DefaultsConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServiceConfigurator;
use Override;

class JwtServiceProvider implements ServiceProviderInterface
{
    #[Override]
    public function register(): void
    {
        if ( ! isset($_ENV['APP_KEY']) || ! $_ENV['APP_KEY'] ) {
            throw new \RuntimeException('The APP_KEY environment variable is missing. Please run the "key:generate" command to generate an API key.');
        }

        // Register the Sha256 Signer as an independent service
        app()->addService(Sha256::class, [
            Sha256::class,
            static function(ServiceConfigurator|DefaultsConfigurator $config) {}
        ]);

        // Key dedicated to Mercure
        app()->addService('mercure.jwt.key', [
            InMemory::class,
            static function(ServiceConfigurator|DefaultsConfigurator $config) {
                $secret = $_ENV['MERCURE_JWT_SECRET']
                    ?? throw new \RuntimeException('MERCURE_JWT_SECRET is missing.');
                
                $config->factory([InMemory::class, 'plainText'])
                    ->args([$secret]);
            }
        ]);

        // Configuration dedicated to Mercure
        app()->addService('mercure.jwt.config', [
            Configuration::class,
            static function(ServiceConfigurator|DefaultsConfigurator $config) {
                $config->factory([Configuration::class, 'forSymmetricSigner'])
                    ->args([
                        app()->dependency('service', Sha256::class),
                        app()->dependency('service', 'mercure.jwt.key')
                    ]);
            }
        ]);
    }

    #[Override]
    public function boot(): void
    {
        // Nothing to initialize at boot time for JWT
    }
}