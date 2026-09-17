<?php

namespace Clicalmani\Core\Mail;

/**
 * Class Mailable
 * 
 * Abstract base class providing a fluent configuration interface for defining 
 * transaction-driven application outbound emails, structural templates, attachments, 
 * and routing recipient envelopes.
 * 
 * @package Clicalmani\Core\Mail
 * @author @clicalmani
 */
abstract class Mailable implements MailableInterface
{
    /**
     * The template view file identifier used to render the electronic body content.
     * 
     * @var string
     */
    public string $template = '';

    /**
     * Associative key-value data container passed down into the rendering engine view scope.
     * 
     * @var array<string, mixed>
     */
    public array $data = [];

    /**
     * The subject header line text summarizing the message contents.
     * 
     * @var string
     */
    public string $subject = '(No Subject)';

    /**
     * The targeted recipient collection detailing each route endpoint address and optional
     * descriptive name. Accumulates across successive to() calls, so the mailable naturally
     * supports one or several recipients.
     * 
     * @var array<int, array{0: string, 1: string|null}>
     */
    public array $to = [];

    /**
     * Collection containing structural metadata parameters for absolute filesystem attachments.
     * 
     * @var array<int, array{0: string, 1: string|null, 2: string|null}>
     */
    public array $pathAttachments = [];

    abstract public function send(): void;

    /**
     * Binds a dedicated template layout identity path alongside an associated rendering payload to the instance.
     * 
     * @param string $template The relative file path key or reference identifying the template view resource.
     * @param array<string, mixed> $data Dynamic payload context variables mapped to the template scope.
     * @return self The current configured mailable instance to sustain fluent call chains.
     */
    public function view(string $template, array $data = []): self
    {
        $this->template = $template;
        $this->data     = $data;
        return $this;
    }

    /**
     * Configures a custom descriptive subject string layout for the outbound message metadata.
     * 
     * @param string $subject The clear text description identifying the incoming message intent.
     * @return self The current configured mailable instance to sustain fluent call chains.
     */
    public function subject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Configures the delivery endpoint criteria routing the outbound message to one or several
     * recipients. Accumulates across successive calls — chain ->to(...)->to(...) to add more
     * recipients, or pass several at once via un tableau.
     *
     * Formes acceptées :
     *   ->to('a@x.com')                                    // un seul destinataire, sans nom
     *   ->to('a@x.com', 'Alice')                            // un seul destinataire, avec nom
     *   ->to(['a@x.com', 'b@x.com'])                        // plusieurs, sans nom
     *   ->to(['a@x.com' => 'Alice', 'b@x.com' => 'Bob'])    // plusieurs, avec nom (clé => valeur)
     *   ->to([['a@x.com', 'Alice'], ['b@x.com', null]])     // plusieurs, sous forme de paires explicites
     * 
     * @param string|array<int|string, mixed> $email Une adresse, ou une collection de destinataires
     *   sous l'une des formes ci-dessus.
     * @param string|null $name Nom du destinataire, utilisé uniquement quand $email est une chaîne unique.
     * @return self The current configured mailable instance to sustain fluent call chains.
     */
    public function to(string|array $email, ?string $name = null): self
    {
        foreach ($this->normalizeRecipients($email, $name) as $recipient) {
            $this->to[] = $recipient;
        }
        
        return $this;
    }

    /**
     * Vide la liste des destinataires courants, pour repartir d'un envelope propre
     * avant de définir une nouvelle liste via to().
     * 
     * @return self
     */
    public function clearTo(): self
    {
        $this->to = [];
        return $this;
    }

    /**
     * Normalise n'importe laquelle des formes acceptées par to() en une liste plate
     * de paires [email, name|null], prête à être fusionnée dans $this->to.
     *
     * @param string|array<int|string, mixed> $email
     * @param string|null $name
     * @return array<int, array{0: string, 1: string|null}>
     */
    private function normalizeRecipients(string|array $email, ?string $name): array
    {
        // Un seul destinataire, passé sous forme de chaîne : ->to('a@x.com', 'Alice')
        if (is_string($email)) {
            return [[$email, $name]];
        }

        $recipients = [];

        foreach ($email as $key => $value) {
            $recipients[] = match (true) {
                // Forme paire explicite : [['a@x.com', 'Alice'], ...]
                is_array($value) => [$value[0], $value[1] ?? null],

                // Forme associative : ['a@x.com' => 'Alice', ...]
                is_string($key) => [$key, $value],

                // Forme liste simple : ['a@x.com', 'b@x.com', ...]
                default => [$value, null],
            };
        }

        return $recipients;
    }

    /**
     * Retourne la liste des destinataires sous une forme directement exploitable
     * par Symfony Mailer, prête pour Address::create() ou new Address($email, $name).
     *
     * @return array<int, array{email: string, name: string}>
     */
    public function recipients(): array
    {
        return array_map(
            fn(array $recipient) => ['email' => $recipient[0], 'name' => $recipient[1] ?? ''],
            $this->to
        );
    }

    /**
     * Appends an active local file resource to the outbound email delivery envelope using an absolute filesystem address.
     * 
     * @param string $path The absolute storage path locator directing toward the actual file asset.
     * @param string|null $name An optional alternative descriptive file name display parameter exposed to recipients.
     * @param string|null $contentType The exact semantic internet media type (MIME type) descriptor matching the asset.
     * @return self The current configured mailable instance to sustain fluent call chains.
     */
    public function attachFromPath(string $path, ?string $name = null, ?string $contentType = null): self
    {
        $this->pathAttachments[] = [$path, $name, $contentType];
        return $this;
    }
}