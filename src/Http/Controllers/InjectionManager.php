<?php
namespace Clicalmani\Core\Http\Controllers;

use Symfony\Component\DependencyInjection\ContainerBuilder as Container;
use Clicalmani\Routing\Route;

class InjectionManager
{
    /** @var InjectionResolverInterface[] */
    private array $resolvers = [];

    public function __construct(private Container $container) {}

    public function register(InjectionResolverInterface $resolver): static
    {
        $this->resolvers[] = $resolver;
        return $this;
    }

    /**
     * Enregistre un resolver par nom de classe.
     * Il sera instancié à la volée si nécessaire.
     */
    public function registerClass(string $resolverClass): static
    {
        $this->resolvers[] = is_string($resolverClass) && class_exists($resolverClass)
            ? new $resolverClass()
            : $resolverClass;
        return $this;
    }

    /**
     * Enregistre la liste des resolvers d'injection.
     * L'ordre du tableau détermine l'ordre de priorité.
     *
     * @param InjectionResolverInterface[] $resolvers
     */
    public function registerResolvers(array $resolvers): static
    {
        foreach ($resolvers as $resolver) {
            if (!$resolver instanceof InjectionResolverInterface) {
                throw new \InvalidArgumentException(sprintf(
                    'Resolver must implement %s, %s given.',
                    InjectionResolverInterface::class,
                    get_debug_type($resolver)
                ));
            }
            $this->resolvers[] = $resolver;
        }

        return $this;
    }

    /**
     * Résout tous les paramètres d'un reflector.
     *
     * @return array Arguments ordonnés prêts pour l'invocation.
     */
    public function resolve(ReflectorInterface $reflector, Route $route): array
    {
        $parameters = $reflector->getParameters();
        $routeParams = collection($route->getParameters())
            ->map(fn($segment) => $segment->value)
            ->toArray();

        $args = array_fill(0, count($parameters), null);
        
        foreach ($parameters as $i => $param) {
            if ($param->getType()?->isBuiltin()) {
                // Paramètre scalaire → on consomme un segment de route
                $args[$i] = array_shift($routeParams);
                continue;
            }

            foreach ($this->resolvers as $resolver) {
                if ($resolver->resolve($param, $reflector, $route, $args, $i)) {
                    continue 2; // paramètre résolu, on passe au suivant
                }
            }

            // Aucun resolver : on tente le container PSR-11 comme fallback
            $type = $param->getType();
            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                try {
                    $args[$i] = $this->container->get($type->getName());
                } catch (\Throwable) {
                    // laisse null, l'appel échouera explicitement
                }
            }
        }

        return $args;
    }
}