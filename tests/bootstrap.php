<?php

declare(strict_types=1);

use Clicalmani\Core\Maker\Application;
use Clicalmani\Core\Providers\CacheServiceProvider;
use Clicalmani\Core\Providers\ContainerServiceProvider;
use Clicalmani\Core\Providers\StorageServiceProvider;
use Clicalmani\Core\Providers\ValidationServiceProvider;

require_once __DIR__ . '/../vendor/autoload.php';

// Les helpers du framework (app(), config(), cache(), storage(), collection(), ...)
// ne sont jamais chargés par l'autoload PSR-4.
require_once dirname(__DIR__) . '/src/helpers.php';

// Racine d'une application factice : fournit config/storage.php et un storage/ inscriptible.
if (!defined('TESTS_FIXTURE_ROOT')) {
    define('TESTS_FIXTURE_ROOT', __DIR__ . '/Fixtures/app');
}

// Amorçage minimal : pas de kernel HTTP ni de console. Les providers doivent être
// enregistrés AVANT le boot du conteneur, car registerCoreContainerServices()
// fige la liste des services au chargement de services.php.
$app = Application::getInstance(TESTS_FIXTURE_ROOT);
$app->register(new StorageServiceProvider);
$app->register(new CacheServiceProvider);

// Conteneur DI cœur (config, logger, storage, cache, database, injection.manager, ...)
(new ContainerServiceProvider)->boot();

// Registre des règles de validation (numeric, int, ...) utilisé par Validator
(new ValidationServiceProvider)->boot();
