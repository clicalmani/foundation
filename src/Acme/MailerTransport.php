<?php
namespace Clicalmani\Foundation\Acme;

use Clicalmani\Foundation\Support\Facades\Env;

class MailerTransport implements TransportInterface
{
    /**
     * Creates a mailer transport instance.
     *
     * @return \Symfony\Component\Mailer\Transport\TransportInterface
     */
    public function create() : \Symfony\Component\Mailer\Transport\TransportInterface
    {
        return \Symfony\Component\Mailer\Transport::fromDsn(
            new \Clicalmani\Psr\Uri(
                Env::get('MAIL_MAILER', 'smtp'),
                Env::get('MAIL_HOST', 'localhost'),
                Env::get('MAIL_PORT', '465'),
                Env::get('MAIL_USERNAME', ''),
                Env::get('MAIL_PASSWORD', '')
            )
        );
    }
}