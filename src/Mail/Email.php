<?php
namespace Clicalmani\Core\Mail;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Messenger\Stamp\DelayStamp;

/**
 * @package clicalmani/core
 * @author clicalmani
 */
class Email
{
    private array $to = [];
    private ?DelayStamp $delayStamp = null;

    public function __construct(
        private MailerInterface $mailer
    )
    {}

    public function to(string|array $email, ?string $name = null) : static
    {
        $this->to = ['email' => $email, 'name' => $name];
        return $this;
    }

    public function send(MailableInterface $mailable, ?string $transport = null, ?Envelope $envelope = null) : void
    {
        if ($this->to) $mailable->to(...$this->to);

        if ($transport) {
            $mailer = new Mailer(
                app()->getContainer()->get(Factory\MailerTransportFactory::class)
            );
            $mailer->send($mailable, $transport, $envelope);
            return;
        }

        $this->mailer->send($mailable, $envelope ?? $this->delayStamp);
    }

    public function queue(MailableInterface $mailable, ?Envelope $envelope = null) : void
    {
        if ($this->to) $mailable->to(...$this->to);

        /** @var \Symfony\Component\Mailer\Mailer */
        $this->mailer = app()->getContainer()->get('mailer');
        $this->mailer->send($mailable, $envelope);
    }

    public function later(int $delay) : static
    {
        $this->delayStamp = new DelayStamp($delay/1000);
        /** @var \Symfony\Component\Mailer\Mailer */
        $this->mailer = app()->getContainer()->get('mailer');
        return $this;
    }
}