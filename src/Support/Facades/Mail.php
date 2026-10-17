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

    /**
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic(string $method, array $arguments): mixed
    {
        return static::getFacadeRoot()?->$method(...$arguments);
    }
}