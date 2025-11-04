<?php
namespace Clicalmani\Foundation\Messenger;

use Clicalmani\Foundation\Maker\Application;
use Clicalmani\Foundation\Support\Facades\Env;
use Clicalmani\Psr\Uri;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;

class Messenger
{
    private Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function transport(?string $name = null)
    {}

    private function createTransport(array $options = [])
    {
        $messenger = config('messenger.transports', []);
        $default = config('messenger.default', 'elegant');

        $dns = new Uri(
            ...array_values(
                config("messenger.transports.$default", [
                    Env::get('MESSENGER_TRANSPORT', 'elegant'),
                    Env::get('MESSENGER_HOST', 'localhost'),
                    Env::get('MESSENGER_PORT', '465'),
                    Env::get('MESSENGER_USERNAME', ''),
                    Env::get('MESSENGER_PASSWORD', '')
                ])
            )
        );

        $serializer = new PhpSerializer;

        return \Symfony\Component\Messenger\Transport\TransportFactory::createTransport($dns, $options, new PhpSerializer);
    }
}