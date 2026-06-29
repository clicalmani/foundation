<?php

namespace Clicalmani\Foundation\Messenger;

use Clicalmani\Foundation\Messenger\Stamp\ElegantTransportStamp;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\ErrorDetailsStamp;

class FailedTransport implements TransportInterface
{
    protected SerializerInterface $serializer;

    private $failedIds = [];

    public function __construct(
        protected string $model, 
        ?SerializerInterface $serializer = null,
        protected array $options = []
    ) {
        $this->serializer = $serializer ?? new PhpSerializer();
    }

    /**
     * Sends (saves) the failed message into the database
     */
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
        
        if ($stamp = $envelope->last(ErrorDetailsStamp::class)) {
            $exceptionClass   = $stamp->getExceptionClass();
            $exceptionMessage = $stamp->getExceptionMessage();
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

        $this->failedIds[$idStamp->getId()] = true;

        // Attach the DB ID to the envelope so ack()/reject() know what to delete
        return $envelope->with(new ElegantTransportStamp($model->id));
    }

    public function get(): iterable
    {
        $modelClass = $this->model;
        $records = $modelClass::where('queue_name = ?', [$this->options['queue_name'] ?? 'failed'])->get();

        foreach ($records as $record) {
            $envelope = $this->serializer->decode([
                'body'    => $record->body,
                'headers' => is_string($record->headers)
                                ? json_decode($record->headers, true)
                                : ($record->headers ?? []),
            ]);

            // Attach the ID so ack()/reject() can delete the correct record
            yield $envelope->with(new ElegantTransportStamp($record->id));
        }
    }

    public function ack(Envelope $envelope): void
    {
        if ($stamp = $envelope->last(ElegantTransportStamp::class)) {
            $modelClass = $this->model;
            $modelClass::where('id = ?', [$stamp->getId()])->delete();
        }
    }

    public function reject(Envelope $envelope): void
    {
        $this->ack($envelope);
    }
}