<?php

namespace Clicalmani\Foundation\Providers;

use Symfony\Component\DependencyInjection\Loader\Configurator\DefaultsConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServiceConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Override;

class ScheduleServiceProvider implements ServiceProviderInterface
{
    protected string $tasksPath = 'app/Tasks';
    protected string $namespace = 'App\\Tasks';
    protected bool $statefull = false;

    protected string $handlersPath = 'app/Handlers';
    protected string $handlersNamespace = 'App\\Handlers';

    #[Override]
    public function register(): void
    {
        app()->addService('scheduler.main', [
            \Symfony\Component\Scheduler\Schedule::class,
            function(ServiceConfigurator|DefaultsConfigurator $config) {
                $config->factory([
                    \Clicalmani\Foundation\Scheduler\TaskDiscovery::class, 
                    'buildSchedule'
                ])->args([
                    app()->rootPath() . '/' . $this->tasksPath, // Chemin vers les tâches
                    $this->namespace                            // Namespace associé
                ]);
                
                if ($this->statefull) {
                    // On injecte le cache comme état (Checkpoint)
                    $config->call('stateful', [
                        $app->dependency('service', 'cache.app')
                    ]);
                }
            }
        ]);

        app()->addService('scheduler.handlers', [
            \Clicalmani\Foundation\Scheduler\HandlersDiscovery::class,
            function(ServiceConfigurator|DefaultsConfigurator $config) {
                $config->args([
                    $this->handlersPath,
                    $this->handlersNamespace
                ]);
            }
        ]);
    }

    #[Override]
    public function boot(): void
    {
        if ( is_file(config_path('/scheduler.php')) ) {
            app()->config->set('scheduler', require_once config_path('/scheduler.php'));
        }
    }

    public function setPaths(string $tasks, ?string $handlers = 'app/Handlers'): void
    {
        $this->tasksPath = $tasks;
        $this->handlersPath = $handlers;
    }

    public function setNamespaces(string $tasks, ?string $handlers = 'App\\Handlers\\'): void
    {
        $this->namespace = $tasks;
        $this->handlersNamespace = $handlers;
    }

    public function setStatefull(bool $statefull = false): void
    {
        $this->statefull = $statefull;
    }
}