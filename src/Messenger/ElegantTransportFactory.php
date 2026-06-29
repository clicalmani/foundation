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
        
        if (isset($options['table_name']) && $options['table_name'] == 'failed_messages') {
            $model = \App\Models\FailedMessage::class;
            return new FailedTransport($model, $serializer, $options);
        }
        
        return new ElegantTransport($model, $serializer, $options);
    }

    public function supports(string $dsn, array $options): bool
    {
        return str_starts_with($dsn, 'elegant://');
    }
}