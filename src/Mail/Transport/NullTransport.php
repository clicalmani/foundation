<?php

namespace Clicalmani\Core\Mail\Transport;

use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\RawMessage;

/**
 * Transport de test : n'envoie rien, mais conserve en mémoire les messages
 * envoyés pour permettre des assertions.
 *
 * Usage : mail.mailers.array.schema = 'array' (ou tout alias mappé ici).
 */
class NullTransport extends AbstractTransport
{
    /**
     * @var array<int, array{envelope: ?\Symfony\Component\Mailer\Envelope, message: RawMessage}>
     */
    private array $sent = [];

    protected function doSend(SentMessage $message): void
    {
        $this->sent[] = [
            'envelope' => $message->getEnvelope(),
            'message'  => $message->getOriginalMessage(),
        ];
    }

    /**
     * Retourne tous les messages capturés.
     *
     * @return array<int, array{envelope: ?\Symfony\Component\Mailer\Envelope, message: RawMessage}>
     */
    public function getSent(): array
    {
        return $this->sent;
    }

    /**
     * Nombre de messages capturés.
     */
    public function count(): int
    {
        return count($this->sent);
    }

    /**
     * Vide la mémoire (utile entre deux tests).
     */
    public function flush(): void
    {
        $this->sent = [];
    }

    public function __toString(): string
    {
        return 'null';
    }
}