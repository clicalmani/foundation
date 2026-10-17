<?php

declare(strict_types=1);

namespace Clicalmani\Core\Http\Events;

/**
 * Événement émis à la fin du cycle de vie d'une requête HTTP.
 *
 * DTO immutable en lecture seule : il transporte les mesures observées (statut,
 * durée, taille de la réponse) sans aucune logique métier. Il est consommé tel
 * quel par {@see \Clicalmani\Core\Events\Listeners\SystemHttpListener}.
 *
 * Note : les paramètres obligatoires précèdent les paramètres optionnels afin
 * d'éviter la dépréciation PHP « Optional parameter declared before required
 * parameter », qui ferait échouer une suite PHPUnit en failOnDeprecation.
 */
final class HttpRequest
{
    /**
     * @param string      $method       Verbe HTTP (GET, POST, ...).
     * @param string      $path         Chemin brut de la requête.
     * @param int         $status       Code de statut HTTP de la réponse.
     * @param string|null $route        Nom de la route résolue, null si aucune route n'a correspondu.
     * @param float       $duration     Durée de traitement en millisecondes.
     * @param int         $responseSize Taille du corps de réponse en octets.
     * @param array<string, mixed> $tags Étiquettes supplémentaires fusionnées aux métriques.
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly int $status,
        public readonly ?string $route = null,
        public readonly float $duration = 0.0,
        public readonly int $responseSize = 0,
        public readonly array $tags = [],
    ) {}
}
