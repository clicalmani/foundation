<?php

declare(strict_types=1);

namespace Clicalmani\Core\Tests\Stubs;

/**
 * Contrôleur factice pour les tests de RequestReflection.
 *
 * Chaque méthode couvre une forme de signature différente afin d'exercer
 * getParameters(), getParamsTypes(), getParamTypeAt() et getParamsNames().
 */
class RequestReflectionControllerStub
{
    /** Action sans aucun paramètre. */
    public function noParameter(): void
    {
    }

    /** Paramètre sans déclaration de type. */
    public function untyped($anything)
    {
    }

    /** Paramètres scalaires typés, dont un avec valeur par défaut. */
    public function typed(string $name, int $age = 30)
    {
    }

    /** Paramètre nullable. */
    public function nullable(?string $tag = null)
    {
    }

    /** Paramètre typé par une classe. */
    public function classTyped(StubModel $model)
    {
    }

    /** Paramètre en type union (ReflectionUnionType, sans getName()). */
    public function union(string|array $value)
    {
    }

    /** Paramètre variadique. */
    public function variadic(string ...$items)
    {
    }

    /** Contrôleur invoquant. */
    public function __invoke(string $id)
    {
    }

    /** Méthode non publique : ReflectionMethod la lit quand même. */
    private function privateAction(string $secret)
    {
    }
}
