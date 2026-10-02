<?php

namespace Clicalmani\Core\Acme;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

class ControllerResolver
{
    private bool $compiled = false;

    /**
     * @param array<string, object|string> $sharedServices Service pré-existants ou définitions personnalisées à enregistrer
     */
    public function __construct(private ContainerBuilder $container, array $sharedServices = [])
    {
        // Enregistre les services déjà instanciés ou partagés (ex: Request, Config, PDO, etc.)
        foreach ($sharedServices as $id => $service) {
            if (is_object($service)) {
                $this->container->set($id, $service);
            } else {
                $this->container->autowire($id, $service);
            }
        }
    }

    /**
     * Instancie un contrôleur en résolvant automatiquement ses dépendances du constructeur.
     *
     * @template T
     * @param class-string<T> $controllerClass Le FQCN du contrôleur (ex: App\Controllers\UserController::class)
     * @return T
     */
    public function resolve(string $controllerClass): object
    {
        // 1. Si la classe n'est pas encore déclarée dans le conteneur, on l'autowire
        if (!$this->container->has($controllerClass)) {
            $this->container->autowire($controllerClass)
                ->setPublic(true); // Doit être public pour être extrait via ->get()
        }

        // 2. Si un nouveau service a été ajouté après une compilation, on autorise la re-compilation
        if (!$this->container->isCompiled()) {
            $this->container->compile();
        }

        // 3. Récupération et instanciation automatique de la classe et de toutes ses dépendances
        return $this->container->get($controllerClass);
    }

    /**
     * Permet d'enregistrer explicitement des services/dépendances de manière à ce que
     * le conteneur puisse les injecter dans les constructeurs des contrôleurs.
     */
    public function registerService(string $id, ?string $class = null): void
    {
        $this->container->autowire($id, $class ?? $id);
    }

    /**
     * Récupère l'instance sous-jacente du ContainerBuilder si besoin de configurations avancées
     */
    public function getContainer(): ContainerBuilder
    {
        return $this->container;
    }
}