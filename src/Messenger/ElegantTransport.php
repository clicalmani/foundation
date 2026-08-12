<?php

namespace Clicalmani\Foundation\Messenger;

use Clicalmani\Foundation\Messenger\Stamp\ElegantTransportStamp;
use Clicalmani\Foundation\Messenger\Stamp\RetryingStamp;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;
use Symfony\Component\Messenger\Stamp\DelayStamp;

/**
 * Class ElegantTransport
 * 
 * Implements a database-backed Symfony Messenger transport interface leveraging 
 * your framework's Elegant ORM models. Manages advanced message queuing pipelines 
 * including delayed delivery, conditional retries, and failure state handling.
 * 
 * @package Clicalmani\Foundation\Messenger
 * @author @clicalmani
 */
class ElegantTransport implements TransportInterface
{
    /**
     * ElegantTransport constructor.
     * 
     * @param string $model The fully qualified class name of the Elegant ORM Model.
     * @param SerializerInterface|null $serializer Serializer instance for message payload encoding/decoding.
     * @param array<string, mixed> $options Transport run-time configuration attributes.
     */
    public function __construct(
        protected string $model, 
        protected ?SerializerInterface $serializer = null,
        protected array $options = []
    ) {
        $this->serializer = $serializer ?? new PhpSerializer();
    }

    /**
     * Retrieves available message envelopes from the persistent database queue.
     * Marks targeted records as in-progress to prevent concurrent consumption by separate workers.
     * 
     * @return iterable<Envelope> Collection containing the decoded message envelope if found, or empty array.
     */
    public function get(): iterable
    {
        // Look for an undelivered message whose availability time has passed
        $record = $this->model::where('delivered_at IS NULL AND available_at <= NOW()')
                    ->first();
        
        if (null === $record) {
            return [];
        }

        // Mark as "in progress" to prevent other workers from picking it up
        $record->update(['delivered_at' => now()]);
        
        // Deserialize to return an Envelope back to Symfony
        $envelope = $this->serializer->decode([
            'body'    => (string) $record->body,
            'headers' => (array) json_decode((string) $record->headers, true)
        ]);
        
        // Inject operational tracking stamps into the active lifecycle envelope
        return [$envelope->with(
            new ElegantTransportStamp((int) $record->id),
            new ReceivedStamp('messenger.transport.elegant')
        )];
    }

    /**
     * Dispatches or schedules a message envelope into the underlying persistent database layer.
     * 
     * @param Envelope $envelope The message envelope payload ready for delivery.
     * @return Envelope The updated message envelope appended with transactional queue metadata stamps.
     */
    public function send(Envelope $envelope): Envelope
    {
        $encoded = $this->serializer->encode($envelope);

        /** @var RedeliveryStamp|null $redelivery */
        $redelivery = $envelope->last(RedeliveryStamp::class);

        /** @var ElegantTransportStamp|null $idStamp */
        $idStamp = $envelope->last(ElegantTransportStamp::class);
        
        if ($redelivery !== null && $idStamp !== null) {
            // ── Retry Execution Flow: Update existing historical record ───────────────────────
            // Overwrite payload parameters without introducing disjointed row records.
            // Reset delivery states to bring the message entity back into worker visibility.
            $this->model::find($idStamp->getId())?->update([
                'body'         => $encoded['body'],
                'headers'      => json_encode($encoded['headers'] ?? []),
                'delivered_at' => null,  
                'available_at' => now(), 
            ]);

            return $envelope;
        }

        /** @var DelayStamp|null $delayStamp */
        $delayStamp = $envelope->last(DelayStamp::class);

        $availableAt = now(); 

        if (isset($this->options['delay'])) {
            $availableAt = now()->addSeconds((int) $this->options['delay'] / 1000);
        }

        if ($delayStamp) {
            $seconds = (int) $delayStamp->getDelay() / 1000;
            $availableAt = now()->addSeconds($seconds); 
        }

        // ── Standard Dispatch Flow: Persistent state insertion ───────────────────────
        $model = new $this->model;
        $model->body         = $encoded['body'];
        $model->headers      = json_encode($encoded['headers'] ?? []);
        $model->queue_name   = $this->options['queue_name'] ?? 'default';
        $model->created_at   = now();
        $model->available_at = $availableAt;
        $model->save();

        return $envelope->with(
            new ElegantTransportStamp((int) $model->id)
        );
    }

    /**
     * Acknowledges successful processing of a message envelope by the consumer worker.
     * Purges or flags records based on storage retention strategies.
     * 
     * @param Envelope $envelope The target successfully consumed message wrapper.
     * @return void
     */
    public function ack(Envelope $envelope): void 
    {
        /** @var ElegantTransportStamp|null $stamp */
        $stamp = $envelope->last(ElegantTransportStamp::class);
        
        if (null === $stamp) {
            return; 
        } 
        
        if (false === ($this->options['keep'] ?? false)) {
            $this->model::find($stamp->getId())?->delete();
        } else {
            $this->model::find($stamp->getId())?->update(['completed_at' => now()]);
        }
    }

    /**
     * Rejects an envelope when processing terminates due to a structural exception.
     * Determines whether to hold or scrap records based on current retry counts.
     * 
     * @param Envelope $envelope The message envelope causing runtime execution faults.
     * @return void
     */
    public function reject(Envelope $envelope): void
    {
        // Skip routine actions if external retries remain active on this envelope
        if ($envelope->last(RetryingStamp::class)) {
            return;
        }

        /** @var ElegantTransportStamp|null $stamp */
        $stamp = $envelope->last(ElegantTransportStamp::class);
        if (!$stamp) {
            return;
        }

        if (false === ($this->options['keep'] ?? false)) {
            $this->model::find($stamp->getId())?->delete();
        } else {
            $this->model::find($stamp->getId())?->update([
                'completed_at' => now(),
                'delivered_at' => now(),
            ]);
        }
    }
}