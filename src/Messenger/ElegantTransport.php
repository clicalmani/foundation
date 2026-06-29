<?php

namespace Clicalmani\Foundation\Messenger;

use Clicalmani\Foundation\Messenger\Stamp\ElegantTransportStamp;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;

class ElegantTransport implements TransportInterface
{
    public function __construct(
        protected string $model, 
        protected ?SerializerInterface $serializer = null,
        protected array $options = []
    ) {
        $this->serializer = $serializer ?? new PhpSerializer();
    }

    /**
     * Retrieves messages from the database (for the Worker)
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
            'body'    => $record->body,
            'headers' => (array) $record->headers
        ]);
        
        // ⚠️ IMPORTANT: Inject the ID into the Envelope to find it back in ack/reject
        return [$envelope->with(
            new ElegantTransportStamp($record->id),
            new ReceivedStamp('messenger.transport.elegant')
        )];
    }

    /**
     * Sends a message to the database (for the Dispatcher)
     */
    public function send(Envelope $envelope): Envelope
    {
        $encoded = $this->serializer->encode($envelope);

        /** @var RedeliveryStamp|null $redelivery */
        $redelivery = $envelope->last(\Symfony\Component\Messenger\Stamp\RedeliveryStamp::class);

        /** @var ElegantTransportStamp|null $idStamp */
        $idStamp = $envelope->last(ElegantTransportStamp::class);
        
        if ($redelivery !== null && $idStamp !== null) {
            // ── Retry: update the existing record ────────────────────────────────
            // Do NOT create a new record — overwrite the body/headers with the new
            // stamps (updated RedeliveryStamp) and reset delivered_at to null so
            // that the worker can process it again.
            $this->model::find($idStamp->getId())?->update([
                'body'         => $encoded['body'],
                'headers'      => json_encode($encoded['headers'] ?? []),
                'delivered_at' => null,  // Puts the message back into the queue
                'available_at' => now(), // Available immediately (or add a delay if desired)
            ]);

            return $envelope;
        }

        /** @var \Symfony\Component\Messenger\Stamp\DelayStamp|null $delayStamp */
        $delayStamp = $envelope->last(\Symfony\Component\Messenger\Stamp\DelayStamp::class);

        $availableAt = now(); 

        if (isset($this->options['delay'])) {
            $availableAt = now()->addSeconds($this->options['delay'] / 1000);
        }

        if ($delayStamp) {
            $seconds = $delayStamp->getDelay() / 1000;
            $availableAt = now()->addSeconds($seconds); 
        }

        // ── First dispatch: normal insertion ────────────────────────────────────
        $model = new $this->model;
        $model->body         = $encoded['body'];
        $model->headers      = json_encode($encoded['headers'] ?? []);
        $model->queue_name   = $this->options['queue_name'] ?? 'default';
        $model->created_at   = now();
        $model->available_at = $availableAt;
        $model->save();

        return $envelope->with(
            new ElegantTransportStamp($model->id)
        );
    }

    /**
     * Called when the Worker successfully processes the message WITHOUT errors
     */
    public function ack(Envelope $envelope): void 
    {
        // Retrieve our custom Stamp containing the ID
        $stamp = $envelope->last(ElegantTransportStamp::class);
        
        if (null === $stamp) {
            return; // Cannot proceed without the ID
        } 
        
        if (false === $this->options['keep']) $this->model::find($stamp->getId())?->delete();
        else $this->model::find($stamp->getId())?->update(['completed_at' => now()]);
    }

    /**
     * Called when the Worker fails (Exception thrown in the Handler)
     */
    public function reject(Envelope $envelope): void
    {
        // If RetryingStamp is present → retry in progress, send() has already
        // updated the record — do nothing here
        if ($envelope->last(\Clicalmani\Foundation\Messenger\Stamp\RetryingStamp::class)) {
            return;
        }

        // Permanent failure (retries exhausted) or manual rejection — delete/archive
        /** @var ElegantTransportStamp|null $stamp */
        $stamp = $envelope->last(ElegantTransportStamp::class);
        if (!$stamp) return;

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