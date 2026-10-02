<?php
namespace Clicalmani\Core\Support\Facades;

/**
 * @method static \Clicalmani\Core\Mail\Email to(string|array $email, ?string $name = null)
 * @method static void send(\Clicalmani\Core\Mail\MailableInterface $mailable, ?string $transport = null, ?\Symfony\Component\Mailer\Envelope $envelope = null
 */
class Mail extends Facade
{
    public static function getFacadeAccessor(): string
    {
        return 'mail.simple';
    }
}