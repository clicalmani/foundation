<?php

namespace Clicalmani\Core\Mail;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

/**
 * Class Mailable
 *
 * @package Clicalmani\Core\Mail
 * @author @clicalmani
 */
abstract class Mailable extends TemplatedEmail implements MailableInterface
{
    public ?string $layout = null;

    public bool $layoutDisabled = false;

    abstract public function build(): static;

    public function subject(string $subject): static
    {
        parent::subject($subject);
        return $this;
    }

    public function view(string $template): static
    {
        $this->htmlTemplate($template);
        return $this;
    }

    public function text($body, string $charset = 'utf-8'): static
    {
        parent::text($body, $charset);
        return $this;
    }

    public function with(array $data = []): static
    {
        $this->context([...$this->getContext(), ...$data]);
        return $this;
    }

    public function layout(string $layout): static
    {
        $this->layout = $layout;
        $this->layoutDisabled = false;
        return $this;
    }

    public function withoutLayout(): static
    {
        $this->layout = null;
        $this->layoutDisabled = true;
        return $this;
    }

    /**
     * Formes acceptées :
     *   ->to('a@x.com')
     *   ->to('a@x.com', 'Alice')                 (2e chaîne sans '@' = nom)
     *   ->to('a@x.com', 'b@x.com')               (deux destinataires, comportement natif)
     *   ->to(new Address('a@x.com'))
     *   ->to(['a@x.com', 'b@x.com'])
     *   ->to(['a@x.com' => 'Alice', 'b@x.com' => 'Bob'])
     *   ->to([['a@x.com', 'Alice'], ['b@x.com', null]])
     */
    public function to(Address|string|array ...$addresses): static
    {
        parent::to(...$this->normalizeAddresses($addresses));
        return $this;
    }

    /**
     * Définit l'expéditeur, mêmes formes d'entrée que to().
     */
    public function from(Address|string|array ...$addresses): static
    {
        parent::from(...$this->normalizeAddresses($addresses));
        return $this;
    }

    /**
     * Compatible avec Email::attach($body, ?string $name, ?string $contentType)
     * et avec la forme ->attach('/chemin/fichier.pdf', ['as' => 'x.pdf', 'mime' => '...']).
     */
    public function attach($body, string|array|null $name = null, ?string $contentType = null): static
    {
        if (is_array($name)) {
            $contentType = $name['mime'] ?? $contentType;
            $name        = $name['as'] ?? null;
        }

        if (is_string($body) && is_file($body)) {
            parent::attachFromPath($body, $name, $contentType);
        } else {
            parent::attach($body, $name, $contentType);
        }

        return $this;
    }

    /**
     * @param resource $stream
     */
    public function attachStream($stream, array $options = []): static
    {
        parent::attach($stream, $options['as'] ?? null, $options['mime'] ?? null);
        return $this;
    }

    public function attachData(string $data, ?string $as = null, array $options = []): static
    {
        parent::attach($data, $as ?? $options['as'] ?? null, $options['mime'] ?? null);
        return $this;
    }

    /**
     * Compatible avec Email::embed($body, ?string $name, ?string $contentType)
     * et avec la forme ->embed($body, ['as' => 'logo.png', 'mime' => 'image/png']).
     */
    public function embed($body, string|array|null $name = null, ?string $contentType = null): static
    {
        if (is_array($name)) {
            $contentType = $name['mime'] ?? $contentType;
            $name        = $name['as'] ?? null;
        }

        if (is_string($body) && is_file($body)) {
            parent::embedFromPath($body, $name, $contentType);
        } else {
            parent::embed($body, $name, $contentType);
        }

        return $this;
    }

    public function clearTo(): static
    {
        $this->getHeaders()->remove('To');
        return $this;
    }

    /**
     * @return array<int, array{email: string, name: string}>
     */
    public function recipients(): array
    {
        return array_map(
            fn(Address $address) => ['email' => $address->getAddress(), 'name' => $address->getName()],
            $this->getTo()
        );
    }

    public function getLayout(): ?string
    {
        return $this->layout;
    }

    public function layoutDisabled(): bool
    {
        return $this->layoutDisabled;
    }

    /**
     * Normalise tous les formats acceptés par to()/from() en liste d'Address.
     *
     * @param array<int, Address|string|array> $args
     * @return Address[]
     */
    private function normalizeAddresses(array $args): array
    {
        // Cas "email, nom" : deux chaînes dont la 2e n'est pas une adresse
        if (count($args) === 2
            && is_string($args[0]) && is_string($args[1])
            && !str_contains($args[1], '@')
        ) {
            return [new Address($args[0], $args[1])];
        }

        $addresses = [];

        foreach ($args as $arg) {
            if (is_array($arg)) {
                array_push($addresses, ...$this->expandArray($arg));
            } else {
                $addresses[] = $arg instanceof Address ? $arg : Address::create($arg);
            }
        }

        return $addresses;
    }

    /**
     * @param array<int|string, mixed> $list
     * @return Address[]
     */
    private function expandArray(array $list): array
    {
        $addresses = [];

        foreach ($list as $key => $value) {
            $addresses[] = match (true) {
                // Objet Address déjà prêt
                $value instanceof Address => $value,

                // Paire explicite : [['a@x.com', 'Alice'], ...]
                is_array($value) => new Address($value[0], $value[1] ?? ''),

                // Forme associative : ['a@x.com' => 'Alice', ...]
                is_string($key) => new Address($key, (string) $value),

                // Liste simple : ['a@x.com', 'b@x.com', ...]
                default => Address::create($value),
            };
        }

        return $addresses;
    }
}