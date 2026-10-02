<?php
namespace Clicalmani\Core\Mail;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Mailer as SymfonyMailer;
use Symfony\Component\Mailer\MailerInterface as SymfonyMailerInterface;
use Symfony\Component\Mime\Email;

class Mailer implements MailerInterface
{
    /**
     * Fabrique de transport (résout un transport Symfony depuis la config).
     *
     * @var Factory\TransportFactoryInterface
     */
    private Factory\TransportFactoryInterface $transportFactory;

    /**
     * Instances Symfony Mailer déjà construites, mises en cache PAR NOM de
     * mailer. Un cache unique renverrait systématiquement le premier transport 
     * construit, même si un mailer différent était demandé ensuite dans le même processus.
     *
     * @var array<string, SymfonyMailerInterface>
     */
    private array $mailers = [];

    public function __construct(Factory\TransportFactoryInterface $transportFactory)
    {
        $this->transportFactory = $transportFactory;
    }

    /**
     * Envoie un email directement via le transport résolu depuis la config
     * (sans passer par le bus Messenger).
     *
     * @param \Symfony\Component\Mime\Email $email
     * @param ?string $mailer Nom du mailer (ex: 'smtp', 'postmark', 'failover'). Null = mail.default.
     * @param ?\Symfony\Component\Mailer\Envelope $envelope
     * @return void
     */
    public function send(Email $email, ?string $mailer = null, ?Envelope $envelope = null) : void
    {
        $this->get($mailer)->send($email, $envelope);
    }

    /**
     * Récupère (ou construit puis met en cache) l'instance Symfony Mailer
     * correspondant au mailer nommé $name.
     *
     * @param ?string $name
     * @return SymfonyMailerInterface
     */
    public function get(?string $name = null) : SymfonyMailerInterface
    {
        $key = $name ?? '__default__';

        if (!isset($this->mailers[$key])) {
            $this->mailers[$key] = new SymfonyMailer($this->transportFactory->create($name));
        }

        return $this->mailers[$key];
    }
}