<?php
namespace Clicalmani\Foundation\Http\Controllers;

use Clicalmani\Database\Factory\Models\Elegant;

/**
 * Trait ResolvesRouteBinding
 *
 * Résout le binding implicite d'un modèle sur sa route (route model binding) :
 * détermine le nom de la clé à utiliser (scoped ou clé primaire par défaut),
 * invoque resolveRouteBinding() du modèle, puis le callback global éventuel
 * enregistré via RouteServiceProvider::resolveRouteBindingUsing().
 *
 * Utilisé par les classes qui portent une propriété $route
 * (\Clicalmani\Routing\Route) : RequestController, InjectResource, etc.
 *
 * @package Clicalmani\Foundation\Http\Controllers
 * @author Clicalmani\Foundation
 */
trait ResolveRouteBinding
{
    /**
     * Résout le binding de route pour un modèle donné.
     *
     * @param \Clicalmani\Database\Factory\Models\Elegant $model
     * @return void
     */
    private function resolveRouteBinding(Elegant $model) : void
    {
        $keyName = $this->resolveBindingKeyName($model);

        // Lecture directe via l'entité : évite tout passage par __get() du modèle,
        // qui pourrait déclencher relation/attribut custom/requête additionnelle
        // dans un contexte d'appel qu'on ne maîtrise pas ici.
        $keyValue = $model->getAttributeValue($keyName);

        // Resolve route binding inside the model
        $reflector = new MethodReflector(new \ReflectionMethod($model, 'resolveRouteBinding'));
        $reflector($model, $keyValue, $keyName);

        // Global route binding
        if (NULL !== $callback = \App\Providers\RouteServiceProvider::routeBindingCallback()) {
            $callback($keyValue, $keyName, $model);
        }
    }

    /**
     * Détermine le nom de la clé à utiliser pour le binding : soit celle
     * définie par le scope de la route (#[scoped]), soit la clé primaire
     * du modèle par défaut.
     *
     * @param \Clicalmani\Database\Factory\Models\Elegant $model
     * @return string
     */
    private function resolveBindingKeyName(Elegant $model) : string
    {
        if ($scope = $this->route->scoped()) {
            $scope_name = collection(explode('_', $model))
                            ->map(fn(string $part) => ucfirst($part))
                            ->join('');

            return $scope[$scope_name];
        }

        return $model->getKey();
    }
}