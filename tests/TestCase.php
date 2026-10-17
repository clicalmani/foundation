<?php

declare(strict_types=1);

namespace Clicalmani\Core\Tests;

use Clicalmani\Core\Acme\Configure;
use Clicalmani\Core\Providers\LogServiceProvider;
use PHPUnit\Framework\TestCase as BaseTestCase;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Socle des tests du package foundation.
 *
 * L'amorçage (conteneur DI, providers storage/cache/validation) est fait une fois
 * pour toutes dans tests/bootstrap.php. Cette classe se charge de l'isolation :
 * la configuration statique, les superglobales et les services factices injectés
 * pendant un test sont restaurés après coup.
 */
abstract class TestCase extends BaseTestCase
{
    /** Stockage statique de Configure au début du test. */
    private array $configSnapshot = [];

    private array $serverSnapshot = [];

    private array $filesSnapshot = [];

    private array $requestSnapshot = [];

    /**
     * État des services remplacés par un double, pour restauration.
     *
     * @var array<string, array{definition: ?Definition, instance: ?object}>
     */
    private array $serviceSnapshots = [];

    /** Répertoires temporaires créés par le test, supprimés au tearDown. */
    private array $tempDirs = [];

    private ?bool $debugModeSnapshot = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configSnapshot  = self::configureStorage();
        $this->serverSnapshot  = $_SERVER;
        $this->filesSnapshot   = $_FILES;
        $this->requestSnapshot = $_REQUEST;
    }

    protected function tearDown(): void
    {
        $this->restoreServices();
        $this->removeTempDirs();
        $this->restoreDebugMode();

        self::setConfigureStorage($this->configSnapshot);

        $_SERVER  = $this->serverSnapshot;
        $_FILES   = $this->filesSnapshot;
        $_REQUEST = $this->requestSnapshot;

        $this->serviceSnapshots = [];

        parent::tearDown();
    }

    /* -----------------------------------------------------------------
     |  Conteneur & configuration
     | -----------------------------------------------------------------
     */

    protected function builder(): ContainerBuilder
    {
        $builder = app()->getContainer();

        $this->assertInstanceOf(ContainerBuilder::class, $builder);

        return $builder;
    }

    /**
     * Écrit dans la configuration globale (notation par points acceptée).
     */
    protected function setConfig(string $key, mixed $value): void
    {
        container()->get('config')->set($key, $value);
    }

    protected function getConfig(?string $key = null, mixed $default = null): mixed
    {
        return container()->get('config')->get($key, $default);
    }

    /**
     * Remplace un service du conteneur par un double pour la durée du test.
     * Les façades (Metrics::, DB::, Cache::, ...) résolvent via app()->getContainer().
     */
    protected function bindService(string $id, object $instance): void
    {
        $builder = $this->builder();

        if (!array_key_exists($id, $this->serviceSnapshots)) {
            $this->serviceSnapshots[$id] = [
                'definition' => $builder->hasDefinition($id) ? $builder->getDefinition($id) : null,
                'instance'   => $builder->initialized($id) ? $builder->get($id) : null,
            ];
        }

        if ($builder->hasDefinition($id)) {
            $builder->removeDefinition($id);
        }

        $builder->setDefinition($id, (new Definition($instance::class))->setSynthetic(true)->setPublic(true));
        $builder->set($id, $instance);
    }

    /* -----------------------------------------------------------------
     |  Journalisation
     | -----------------------------------------------------------------
     */

    /**
     * Bascule le logger en mode debug : logger()->error() lève alors une exception
     * au lieu de terminer le processus (exit), ce qui rend la branche testable.
     */
    protected function enableDebugLogging(): void
    {
        if ($this->debugModeSnapshot === null) {
            $this->debugModeSnapshot = self::debugMode();
        }

        self::setDebugMode(true);
    }

    /* -----------------------------------------------------------------
     |  Système de fichiers
     | -----------------------------------------------------------------
     */

    /**
     * Répertoire temporaire unique, supprimé récursivement au tearDown.
     */
    protected function tempDir(string $prefix = 'tonka_'): string
    {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $prefix . bin2hex(random_bytes(6));

        if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
            $this->fail("Impossible de créer le répertoire temporaire {$dir}");
        }

        $this->tempDirs[] = $dir;

        return $dir;
    }

    /* -----------------------------------------------------------------
     |  Internes
     | -----------------------------------------------------------------
     */

    private function restoreServices(): void
    {
        $builder = app()->getContainer();

        if (!$builder instanceof ContainerBuilder) {
            return;
        }

        foreach ($this->serviceSnapshots as $id => $state) {
            $builder->set($id, null);

            if ($state['definition'] !== null) {
                $builder->setDefinition($id, $state['definition']);
            } else {
                $builder->removeDefinition($id);
            }

            if ($state['instance'] !== null) {
                $builder->set($id, $state['instance']);
            }
        }
    }

    private function removeTempDirs(): void
    {
        foreach ($this->tempDirs as $dir) {
            if (is_dir($dir)) {
                $this->deleteDirectory($dir);
            }
        }

        $this->tempDirs = [];
    }

    private function deleteDirectory(string $dir): void
    {
        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $entry;

            is_dir($path) && !is_link($path)
                ? $this->deleteDirectory($path)
                : @unlink($path);
        }

        @rmdir($dir);
    }

    private function restoreDebugMode(): void
    {
        if ($this->debugModeSnapshot !== null) {
            self::setDebugMode($this->debugModeSnapshot);
            $this->debugModeSnapshot = null;
        }
    }

    private static function configureStorage(): array
    {
        return (new ReflectionProperty(Configure::class, 'storage'))->getValue();
    }

    private static function setConfigureStorage(array $storage): void
    {
        (new ReflectionProperty(Configure::class, 'storage'))->setValue(null, $storage);
    }

    private static function debugMode(): bool
    {
        return (new ReflectionProperty(LogServiceProvider::class, 'is_debug_mode'))->getValue();
    }

    private static function setDebugMode(bool $enabled): void
    {
        (new ReflectionProperty(LogServiceProvider::class, 'is_debug_mode'))->setValue(null, $enabled);
    }
}
