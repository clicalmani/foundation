<?php
namespace Clicalmani\Foundation\Messenger;

use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Override;

/**
 * Class ElegantTransportFactory
 * 
 * Factory responsible for initializing database-backed Messenger transports inside 
 * the framework. Resolves connection DSN architectures and provisions appropriate 
 * Elegant transport structures matching operational configurations (e.g., standard vs. failed queues).
 * 
 * @package Clicalmani\Foundation\Messenger
 * @author @clicalmani
 */
class ElegantTransportFactory implements TransportFactoryInterface
{
    /**
     * Creates and configures a concrete Messenger transport layer instance based on the current context parameters.
     * 
     * @param string $dsn Custom connection endpoint definition string (e.g., 'elegant://default').
     * @param array<string, mixed> $options Run-time operational queue configurations.
     * @param SerializerInterface|null $serializer Serializer instance used for envelope payload encoding/decoding.
     * @return TransportInterface Fully provisioned transport engine matching structural routing criteria.
     */
    #[Override]
    public function createTransport(
        string $dsn, 
        array $options, 
        ?SerializerInterface $serializer = null
    ): TransportInterface {
        $serializer = $serializer ?? new PhpSerializer();
        $model      = \App\Models\MessengerMessage::class;
        
        // Dynamically route to a dedicated failure model and transport engine if explicit options match
        if (isset($options['table_name']) && $options['table_name'] === 'failed_messages') {
            $model = \App\Models\FailedMessage::class;
            return new FailedTransport($model, $serializer, $options);
        }
        
        return new ElegantTransport($model, $serializer, $options);
    }

    /**
     * Determines whether this factory is capable of handling the specified connection DSN parameter.
     * 
     * @param string $dsn Distinct transport scheme endpoint definition evaluated.
     * @param array<string, mixed> $options Run-time structural routing configurations.
     * @return bool True if the factory supports the requested connection prefix protocol, false otherwise.
     */
    #[Override]
    public function supports(string $dsn, array $options): bool
    {
        return str_starts_with($dsn, 'elegant://');
    }
}