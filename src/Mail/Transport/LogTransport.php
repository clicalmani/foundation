<?php

namespace Clicalmani\Core\Mail\Transport;

use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\MessageConverter;
use Symfony\Component\Mime\RawMessage;

/**
 * Transport de développement : n'envoie rien, écrit le contenu de l'email
 * dans un fichier de log pour inspection.
 *
 * Usage : mail.mailers.log.schema = 'log' (ou driver dédié), aucun DSN requis.
 */
class LogTransport extends AbstractTransport
{
    /**
     * Chemin absolu du fichier de log. Si null, utilise le log système PHP.
     */
    private ?string $logFile;

    public function __construct(?string $logFile = null)
    {
        parent::__construct();

        $this->logFile = $logFile ?? $this->defaultLogFile();
    }

    /**
     * Résout le fichier de log par défaut : storage/logs/mail.log si le dossier
     * existe, sinon null (→ error_log).
     */
    private function defaultLogFile(): ?string
    {
        return storage_path('/logs/mail.log');
    }

    protected function doSend(SentMessage $message): void
    {
        $original = $message->getOriginalMessage();

        // On tente de convertir en Email pour extraire les headers utiles.
        // Si l'objet n'est pas convertible, on log le brut.
        try {
            $email = MessageConverter::toEmail($original);
            $entry = $this->formatEmail($email);
        } catch (\Throwable) {
            $entry = $this->formatRaw($original);
        }

        $entry .= "\n" . str_repeat('─', 80) . "\n";

        if ($this->logFile !== null) {
            file_put_contents($this->logFile, $entry, FILE_APPEND | LOCK_EX);
        } else {
            error_log($entry);
        }
    }

    /**
     * Formate un Email Symfony en texte lisible.
     */
    private function formatEmail(\Symfony\Component\Mime\Email $email): string
    {
        $to = implode(', ', array_map(
            fn($a) => $a->getName() ? "{$a->getName()} <{$a->getAddress()}>" : $a->getAddress(),
            $email->getTo()
        ));

        $from = implode(', ', array_map(
            fn($a) => $a->getName() ? "{$a->getName()} <{$a->getAddress()}>" : $a->getAddress(),
            $email->getFrom()
        ));

        $body = $email->getHtmlBody() ?? $email->getTextBody() ?? '(corps vide)';

        return sprintf(
            "[%s] MAIL LOG\nTo: %s\nFrom: %s\nSubject: %s\n\n%s\n",
            (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            $to,
            $from,
            $email->getSubject() ?? '(sans sujet)',
            $body
        );
    }

    /**
     * Fallback : log du message brut.
     */
    private function formatRaw(RawMessage $message): string
    {
        return sprintf(
            "[%s] MAIL LOG (raw)\n%s\n",
            (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            $message->toString()
        );
    }

    public function __toString(): string
    {
        return 'log';
    }
}