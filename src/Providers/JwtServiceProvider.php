<?php
namespace Clicalmani\Foundation\Providers;

use Clicalmani\Container\Application;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Symfony\Component\DependencyInjection\Loader\Configurator\DefaultsConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServiceConfigurator;
use Override;

/**
 * Class JwtServiceProvider
 * 
 * Provisions JSON Web Token (JWT) capabilities into the framework container,
 * specifically setting up symmetric signing configurations for the Mercure hub protocol.
 * 
 * @package Clicalmani\Foundation\Providers
 * @author @clicalmani
 */
class JwtServiceProvider implements ServiceProviderInterface
{
    /**
     * Registers JWT encryption signatures and configuration keys into the container.
     * 
     * @return void
     * @throws \RuntimeException If application encryption keys or Mercure secrets are not configured in the environment.
     */
    #[Override]
    public function register(): void
    {
        // Enforce structural application-wide crypt key requirement upfront
        if ( ! isset($_ENV['APP_KEY']) || ! $_ENV['APP_KEY'] ) {
            throw new \RuntimeException('The APP_KEY environment variable is missing. Please run the "key:generate" command to generate an API key.');
        }

        // Register the Sha256 Signer engine as an independent service
        app()->addService(Sha256::class, 
            Sha256::class,
            static function(ServiceConfigurator|DefaultsConfigurator $config) {
                // Instantiated strictly for initial service visibility inside the container
            }
        );

        // Provision the in-memory symmetric key storage mapping dedicated to Mercure authentication
        app()->addService('mercure.jwt.key', 
            InMemory::class,
            static function(ServiceConfigurator|DefaultsConfigurator $config) {
                $secret = $_ENV['MERCURE_JWT_SECRET']
                    ?? throw new \RuntimeException('MERCURE_JWT_SECRET environment variable is missing.');
                
                $config->factory([InMemory::class, 'plainText'])
                    ->args([$secret]);
            }
        );

        // Compile the dynamic symmetric signer setup orchestration interface for the hub
        app()->addService('mercure.jwt.config', 
            Configuration::class,
            static function(ServiceConfigurator|DefaultsConfigurator $config) {
                $config->factory([Configuration::class, 'forSymmetricSigner'])
                    ->args([
                        app()->dependency('service', Sha256::class),
                        app()->dependency('service', 'mercure.jwt.key')
                    ]);
            }
        );
    }

    /**
     * Boots runtime options right before service handling triggers.
     * 
     * @return void
     */
    #[Override]
    public function boot(): void
    {
        // Reserved; no runtime initialization tasks are required for stateless token parameters
    }
}