<?php
namespace Clicalmani\Foundation\Acme;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mime\Email;

interface MailerInterface
{
    /**
     * Sends an email.
     *
     * @param \Symfony\Component\Mime\Email $email
     * @param \Symfony\Component\Mailer\Envelope|null $envelope
     * @return void
     */
    public function send(Email $email, ?Envelope $envelope = null);

    /**
     * Gets the mailer instance.
     *
     * @return \Symfony\Component\Mailer\MailerInterface
     */
    public function get();
}