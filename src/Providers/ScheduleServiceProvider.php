<?php

namespace Clicalmani\Foundation\Providers;

use Symfony\Component\DependencyInjection\Loader\Configurator\DefaultsConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServiceConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Override;

/**
 * Class ScheduleServiceProvider
 * 
 * Boots, provisions, and configures the framework's internal task scheduling infrastructure.
 * Handles automatic runtime discovery of both crontab-like tasks and conditional handlers,
 * and maintains continuous execution checkpoint tracks across stateful runs.
 * 
 * @package Clicalmani\Foundation\Providers
 * @author @clicalmani
 */
class ScheduleServiceProvider implements ServiceProviderInterface
{
    /**
     * Relative path to the application scheduled tasks directory.
     * 
     * @var string
     */
    protected string $tasksPath = 'app/Tasks';

    /**
     * Root PHP namespace prefix assigned to application scheduled tasks.
     * 
     * @var string
     */
    protected string $namespace = 'App\\Tasks';

    /**
     * Flag indicating if the schedule should maintain chronological execution states between runs.
     * 
     * @var bool
     */
    protected bool $statefull = false;

    /**
     * Relative path to the application schedule event handlers directory.
     * 
     * @var string
     */
    protected string $handlersPath = 'app/Handlers';

    /**
     * Root PHP namespace prefix assigned to schedule event handlers.
     * 
     * @var string
     */
    protected string $handlersNamespace = 'App\\Handlers';

    /**
     * Registers scheduler engine dependencies, discovery maps, and stateful checkpoint layers.
     * 
     * @return void
     */
    #[Override]
    public function register(): void
    {
        // Core framework Schedule orchestration factory service compiling tracked task objects
        app()->addService('scheduler.main', 
            \Symfony\Component\Scheduler\Schedule::class,
            function(ServiceConfigurator|DefaultsConfigurator $config) {
                $config->factory([
                    \Clicalmani\Foundation\Scheduler\TaskDiscovery::class, 
                    'buildSchedule'
                ])->args([
                    app()->rootPath() . '/' . $this->tasksPath,
                    $this->namespace
                ]);
                
                // Inject the framework application cache adapter to preserve progress state across runs
                if ($this->statefull) {
                    $config->call('stateful', [
                        app()->dependency('service', 'cache.app')
                    ]);
                }
            }
        );

        // Provision the automated schedule lifecycle event handler map discovery utility
        app()->addService('scheduler.handlers', 
            \Clicalmani\Foundation\Scheduler\HandlersDiscovery::class,
            function(ServiceConfigurator|DefaultsConfigurator $config) {
                $config->args([
                    $this->handlersPath,
                    $this->handlersNamespace
                ]);
            }
        );
    }

    /**
     * Synchronizes global configuration instances inside application boot cycles.
     * 
     * @return void
     */
    #[Override]
    public function boot(): void
    {
        if ( is_file(config_path('/scheduler.php')) ) {
            app()->config->set('scheduler', require_once config_path('/scheduler.php'));
        }
    }

    /**
     * Mutates the relative application directories targeted for automatic system scanning.
     * 
     * @param string $tasks Relative application tasks directory pathway.
     * @param string|null $handlers Relative application handlers directory pathway.
     * @return void
     */
    public function setPaths(string $tasks, ?string $handlers = 'app/Handlers'): void
    {
        $this->tasksPath = $tasks;
        $this->handlersPath = $handlers ?? 'app/Handlers';
    }

    /**
     * Mutates the root PHP structural class namespace prefixes for tasks and handlers.
     * 
     * @param string $tasks The targeted tasks structural namespace string.
     * @param string|null $handlers The targeted handlers structural namespace string.
     * @return void
     */
    public function setNamespaces(string $tasks, ?string $handlers = 'App\\Handlers\\'): void
    {
        $this->namespace = $tasks;
        $this->handlersNamespace = $handlers ?? 'App\\Handlers\\';
    }

    /**
     * Modifies the persistence mode profile determining if running tracks maintain state history.
     * 
     * @param bool $statefull True to toggle persistent state caching tracking on, false otherwise.
     * @return void
     */
    public function setStatefull(bool $statefull = false): void
    {
        $this->statefull = $statefull;
    }
}