<?php
namespace Clicalmani\Foundation\Messenger;

use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class ElegantTransportFactory implements TransportFactoryInterface
{
    public function createTransport(?string $dsn = 'elegant://default', ?array $options = [], ?SerializerInterface $serializer = null): TransportInterface
    {
        $serializer = $serializer ?? new \Symfony\Component\Messenger\Transport\Serialization\PhpSerializer();
        $model = \App\Models\MessengerMessage::class;
        
        return new ElegantTransport($model, $serializer, $options);
    }

    public function supports(string $dsn, array $options): bool
    {
        return str_starts_with($dsn, 'elegant://');
    }
}