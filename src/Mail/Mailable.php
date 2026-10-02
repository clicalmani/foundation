<?php

namespace Clicalmani\Core\Mail;

use Override;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Part\File;
use Symfony\Component\Mime\Part\DataPart;

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

    /**
     * Binds a dedicated template layout identity path alongside an associated rendering payload to the instance.
     *
     * @param string $template The relative file path key or reference identifying the template view resource.
     * @param array<string, mixed> $data Dynamic payload context variables mapped to the template scope.
     * @return static
     */
    public function view(string $template, array $data = []): static
    {
        $this->htmlTemplate($template);
        $this->context($data);
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
     * Définit le(s) destinataire(s), en acceptant plusieurs formes d'entrée
     * puis en délégant à Email::to() (qui attend des Address|string natifs).
     *
     * Formes acceptées :
     *   ->to('a@x.com')
     *   ->to('a@x.com', 'Alice')
     *   ->to(['a@x.com', 'b@x.com'])
     *   ->to(['a@x.com' => 'Alice', 'b@x.com' => 'Bob'])
     *   ->to([['a@x.com', 'Alice'], ['b@x.com', null]])
     *
     * @param string|array<int|string, mixed> $email
     * @param string|null $name Utilisé uniquement quand $email est une chaîne unique.
     * @return static
     */
    public function to(string|array $email, ?string $name = null): static
    {
        parent::to(...$this->toAddresses($email, $name));
        return $this;
    }

    /**
     * Définit l'expéditeur, mêmes formes d'entrée que to().
     *
     * @param string|array<int|string, mixed> $email
     * @param string|null $name
     * @return static
     */
    public function from(string|array $email, ?string $name = null): static
    {
        parent::from(...$this->toAddresses($email, $name));
        return $this;
    }

    public function attach(string $path, array $options = []): static
    {
        return parent::attach(new File($path), $options['as'] ?? null, $options['mime'] ?? null);
    }

    public function attachStream($stream, array $options = []) : static
    {
        return parent::addPart(new DataPart($stream), $options['as'] ?? null, $options['mime'] ?? null);
    }

    public function attachData(string $data, ?string $as = null, array $options = []) : static
    {
        return parent::addPart(new DataPart($data), $as ?? $options['as'] ?? null, $options['mime'] ?? null);
    }

    public function embed($body, array $options = []): static
    {
        return parent::addPart(((new DataPart($body, $options['as'] ?? null, $options['mime'] ?? null))->asInline()));
    }

    /**
     * Vide la liste des destinataires courants (en-tête To réel),
     * pour repartir d'une enveloppe propre avant de rappeler to().
     *
     * @return static
     */
    public function clearTo(): static
    {
        $this->getHeaders()->remove('To');
        return $this;
    }

    /**
     * Retourne les destinataires actuels sous une forme simple [email, name],
     * en lisant l'état réel de l'Email (pas une structure parallèle) —
     * pratique pour du logging/debug sans manipuler des objets Address.
     *
     * @return array<int, array{email: string, name: string}>
     */
    public function recipients(): array
    {
        return array_map(
            fn(Address $address) => ['email' => $address->getAddress(), 'name' => $address->getName()],
            $this->getTo()
        );
    }

    public function getLayout() : ?string
    {
        return $this->layout;
    }

    public function layoutDisabled() : bool
    {
        return $this->layoutDisabled;
    }

    /**
     * Normalise n'importe laquelle des formes acceptées par to()/from() en
     * une liste d'objets Address, prête pour le parent::to()/from() natif.
     *
     * @param string|array<int|string, mixed> $email
     * @param string|null $name
     * @return Address[]
     */
    private function toAddresses(string|array $email, ?string $name): array
    {
        if (is_string($email)) {
            return [new Address($email, $name ?? '')];
        }

        $addresses = [];

        foreach ($email as $key => $value) {
            $addresses[] = match (true) {
                // Forme paire explicite : [['a@x.com', 'Alice'], ...]
                is_array($value) => new Address($value[0], $value[1] ?? ''),

                // Forme associative : ['a@x.com' => 'Alice', ...]
                is_string($key) => new Address($key, $value),

                // Forme liste simple : ['a@x.com', 'b@x.com', ...]
                default => new Address($value, ''),
            };
        }

        return $addresses;
    }
}