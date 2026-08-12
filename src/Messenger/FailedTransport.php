<?php

namespace Clicalmani\Foundation\Messenger;

use Clicalmani\Foundation\Messenger\Stamp\ElegantTransportStamp;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\ErrorDetailsStamp;
use Override;

/**
 * Class FailedTransport
 * 
 * Implements a database-backed Symfony Messenger transport interface reserved for dead-letter 
 * and poison message containment. Leverages your framework's Elegant ORM models to log failure details, 
 * track exception trace markers, and facilitate programmatic message retries or purges.
 * 
 * @package Clicalmani\Foundation\Messenger
 * @author @clicalmani
 */
class FailedTransport implements TransportInterface
{
    /**
     * Serializer instance for message payload encoding/decoding operations.
     * 
     * @var SerializerInterface
     */
    protected SerializerInterface $serializer;

    /**
     * Internal tracking map keeping records of explicitly acknowledged failure identifiers during the current request.
     * 
     * @var array<int, bool>
     */
    private array $failedIds = [];

    /**
     * FailedTransport constructor.
     * 
     * @param string $model The fully qualified class name of the associated Elegant ORM Model.
     * @param SerializerInterface|null $serializer Serializer instance for envelope body handling.
     * @param array<string, mixed> $options Run-time configuration attributes.
     */
    public function __construct(
        protected string $model, 
        ?SerializerInterface $serializer = null,
        protected array $options = []
    ) {
        $this->serializer = $serializer ?? new PhpSerializer();
    }

    /**
     * Persists or updates an unrecoverable failed message envelope into the underlying persistent database store.
     * Captures incoming Exception context details via standard Messenger stamps.
     * 
     * @param Envelope $envelope The failing message envelope ready for isolation.
     * @return Envelope The updated message envelope appended with transactional database tracking stamps.
     */
    #[Override]
    public function send(Envelope $envelope): Envelope
    {
        $encoded = $this->serializer->encode($envelope);

        /** @var ElegantTransportStamp|null $idStamp */
        $idStamp = $envelope->last(ElegantTransportStamp::class);

        if ($idStamp && isset($this->failedIds[$idStamp->getId()])) {
            $this->model::find($idStamp->getId())?->update([
                'body'    => $encoded['body'],
                'headers' => json_encode($encoded['headers'] ?? []),
            ]);
            return $envelope;
        }

        $exceptionClass   = 'UnknownException';
        $exceptionMessage = 'No error message provided by Messenger.';
        
        /** @var ErrorDetailsStamp|null $errorStamp */
        if ($errorStamp = $envelope->last(ErrorDetailsStamp::class)) {
            $exceptionClass   = $errorStamp->getExceptionClass();
            $exceptionMessage = $errorStamp->getExceptionMessage();
        }

        $model = new $this->model;
        $model->body              = $encoded['body'];
        $model->headers           = isset($encoded['headers']) && is_array($encoded['headers'])
                                        ? json_encode($encoded['headers'])
                                        : '[]';
        $model->queue_name        = $this->options['queue_name'] ?? 'failed';
        $model->exception_class   = $exceptionClass;
        $model->exception_message = $exceptionMessage;
        $model->save();

        $this->failedIds[(int) $model->id] = true;

        // Attach the DB ID to the envelope so ack()/reject() know what to delete
        return $envelope->with(new ElegantTransportStamp((int) $model->id));
    }

    /**
     * Retrieves an iterable stream of failed envelopes currently residing inside the dead-letter persistent registry.
     * 
     * @return iterable<Envelope> A generator yielding decoded message envelopes.
     */
    #[Override]
    public function get(): iterable
    {
        $modelClass = $this->model;
        $records    = $modelClass::where('queue_name = ?', [$this->options['queue_name'] ?? 'failed'])->get();

        foreach ($records as $record) {
            $envelope = $this->serializer->decode([
                'body'    => (string) $record->body,
                'headers' => is_string($record->headers)
                                ? json_decode($record->headers, true)
                                : ($record->headers ?? []),
            ]);

            // Attach the ID so ack()/reject() can target the correct record
            yield $envelope->with(new ElegantTransportStamp((int) $record->id));
        }
    }

    /**
     * Acknowledges absolute consumption or removal of an isolated failure record from the poison database queue.
     * 
     * @param Envelope $envelope The envelope wrapper representing the message being acknowledged.
     * @return void
     */
    #[Override]
    public function ack(Envelope $envelope): void
    {
        /** @var ElegantTransportStamp|null $stamp */
        if ($stamp = $envelope->last(ElegantTransportStamp::class)) {
            $modelClass = $this->model;
            $modelClass::where('id = ?', [$stamp->getId()])->delete();
        }
    }

    /**
     * Rejects the failed message envelope, triggering permanent erasure from the dead-letter tracking list.
     * 
     * @param Envelope $envelope The message envelope causing structural processing failures.
     * @return void
     */
    #[Override]
    public function reject(Envelope $envelope): void
    {
        $this->ack($envelope);
    }
}