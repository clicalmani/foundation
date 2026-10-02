<?php
namespace Clicalmani\Core\Mail\Factory;

use Clicalmani\Core\Mail\Transport\LogTransport;
use Clicalmani\Core\Support\Facades\Arr;
use Clicalmani\Core\Support\Facades\Env;
use Clicalmani\Psr\Uri;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface as SymfonyTransportInterface;

class MailerTransportFactory implements TransportFactoryInterface
{
    /**
     * Pilotes propres à ce framework, sans équivalent DSN Symfony natif,
     * pas encore câblés explicitement. 'log' est désormais géré directement
     * (voir resolve()) ; 'array' reste à implémenter.
     */
    private const UNRESOLVED_DRIVERS = ['array'];

    public function create(?string $name = null) : SymfonyTransportInterface
    {
        $container = app()->getContainer();

        if ($name && $container && $transport = $container->get("{$name}.mailer")) {
            return $transport;
        }

        $mailerName = $name ?? config('mail.default', 'smtp');

        return $this->resolve($mailerName);
    }

    /**
     * @param string $mailerName
     * @throws \InvalidArgumentException Si le mailer n'est pas configuré.
     * @return SymfonyTransportInterface
     */
    private function resolve(string $mailerName) : SymfonyTransportInterface
    {
        // 'log' n'a pas de scheme DSN Symfony natif : transport custom
        // instancié directement, sans passer par Transport::fromDsn().
        if ($mailerName === 'log') {
            return new LogTransport();
        }

        $config = config("mail.mailers.$mailerName");

        if (null === $config) {
            throw new \InvalidArgumentException(
                sprintf("Aucun mailer nommé [%s] n'est configuré sous mail.mailers.", $mailerName)
            );
        }

        // --- Mailer composite (failover / roundrobin) ---
        if (Arr::has($config, 'mailers')) {
            $dsnList = array_map(
                fn(string $sub) => (string) $this->buildDsn($sub),
                Arr::get($config, 'mailers', [])
            );

            $joined = implode(' ', $dsnList);

            $dsn = match ($mailerName) {
                'failover'   => "failover($joined)",
                'roundrobin' => "roundrobin($joined)",
                default      => $joined,
            };

            return Transport::fromDsn($dsn);
        }

        // --- Mailer simple ---
        return Transport::fromDsn((string) $this->buildDsn($mailerName));
    }

    /**
     * @param string $mailerName
     * @throws \InvalidArgumentException Si le mailer n'est pas configuré.
     * @throws \RuntimeException Si le mailer est un pilote spécial non câblé (voir UNRESOLVED_DRIVERS).
     * @return Uri
     */
    private function buildDsn(string $mailerName) : Uri
    {
        $config = config("mail.mailers.$mailerName");

        if (null === $config) {
            throw new \InvalidArgumentException(
                sprintf("Aucun mailer nommé [%s] n'est configuré sous mail.mailers.", $mailerName)
            );
        }

        if (in_array($mailerName, self::UNRESOLVED_DRIVERS, true)) {
            throw new \RuntimeException(
                sprintf(
                    "Le mailer [%s] est un pilote spécial à ce framework, sans équivalent DSN " .
                    "Symfony natif. Il doit être câblé explicitement (ex: Transport custom, ou " .
                    "mappage vers 'null://null') avant utilisation.",
                    $mailerName
                )
            );
        }

        return new Uri(
            Arr::get($config, 'schema', Env::get('MAIL_MAILER', 'smtp')),
            Arr::get($config, 'host', Env::get('MAIL_HOST', 'localhost')),
            Arr::get($config, 'port', Env::get('MAIL_PORT', '465')),
            Arr::get($config, 'username', Env::get('MAIL_USERNAME', '')),
            Arr::get($config, 'password', Env::get('MAIL_PASSWORD', '')),
        );
    }
}