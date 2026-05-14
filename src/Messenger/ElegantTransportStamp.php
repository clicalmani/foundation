<?php

namespace Clicalmani\Foundation\Messenger;

use Symfony\Component\Messenger\Stamp\StampInterface;

/**
 * Stamp personnalisé pour conserver l'identifiant de la base de données
 * tout au long du cycle de vie du message dans le Worker.
 */
class ElegantTransportStamp implements StampInterface
{
    /**
     * @param int $id L'identifiant unique du message dans la table de base de données
     */
    public function __construct(
        private readonly int $id
    ) {}

    /**
     * Récupère l'identifiant du message en base de données.
     * 
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
}