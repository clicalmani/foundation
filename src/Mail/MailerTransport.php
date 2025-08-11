<?php
namespace Clicalmani\Foundation\Mail;

use Clicalmani\Foundation\Support\Facades\Arr;
use Clicalmani\Foundation\Support\Facades\Env;
use Clicalmani\Psr\Uri;

class MailerTransport implements TransportInterface
{
    /**
     * Creates a mailer transport instance.
     *
     * @return \Symfony\Component\Mailer\Transport\TransportInterface
     */
    public function create() : \Symfony\Component\Mailer\Transport\TransportInterface
    {
        $mailers = config('mail.mailers', []);
        $default = config('mail.default', 'smtp');

        $dns = new Uri(
            ...array_values(
                config("mail.mailers.$default", [
                    Env::get('MAIL_MAILER', 'smtp'),
                    Env::get('MAIL_HOST', 'localhost'),
                    Env::get('MAIL_PORT', '465'),
                    Env::get('MAIL_USERNAME', ''),
                    Env::get('MAIL_PASSWORD', '')
                ])
            )
        );
        
        if (Arr::has(Arr::get($mailers, $default), "mailers")) {
            $dns_array = [];
            foreach (Arr::get($mailers, "$default.mailers") as $value) {
                $dns_array[] = new Uri(...$value);
            }

            $dns = implode(' ', $dns_array);

            $dns = match ($default) {
                'failover' => "failover($dns)",
                'roundrobin' => "roundrobin($dns)",
                default => $dns,
            };
        }

        return \Symfony\Component\Mailer\Transport::fromDsn($dns);
    }
}