<?php
namespace Clicalmani\Foundation\Acme;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

class Mailer
{
    /**
     * Mailer transport instance
     * 
     * @var \Clicalmani\Foundation\Acme\MailerTransport
     */
    private $transport;

    /**
     * Mailer instance
     * 
     * @var \Symfony\Component\Mailer\MailerInterface
     */
    private $mailer;

    public function __construct(TransportInterface $transport)
    {
        $this->transport = $transport;
    }

    /**
     * Sends an email.
     * 
     * @param \Symfony\Component\Mime\Email $email
     * @return void
     */
    public function send(Email $email, ?Envelope $envelope = null)
    {
        // Ensure the mailer is initialized before sending
        if (!$this->mailer) {
            $this->get();
        }

        $this->mailer->send($email, $envelope);
    }

    /**
     * Gets the mailer instance.
     * 
     * @return \Symfony\Component\Mailer\MailerInterface
     */
    public function get()
    {
        if (!$this->mailer) {
            $this->mailer = new \Symfony\Component\Mailer\Mailer($this->transport->create());
        }
        return $this->mailer;
    }
}