<?php

namespace Clicalmani\Foundation\Messenger;

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
     * Récupère les messages depuis la base de données (pour le Worker)
     */
    public function get(): iterable
    {
        // On cherche un message non encore délivré et dont l'heure est passée
        $record = $this->model::where('delivered_at IS NULL')
                    ->andWhere('available_at <= NOW()')
                    ->first();
        
        if (null === $record) {
            return [];
        }

        // On marque comme "en cours" pour éviter que d'autres workers ne le prennent
        $record->update(['delivered_at' => now()]);

        // On déserialize pour redonner une Envelope à Symfony
        $envelope = $this->serializer->decode([
            'body'    => $record->body,
            'headers' => (array) $record->headers
        ]);

        // ⚠️ IMPORTANT : On injecte l'ID dans l'Envelope pour le retrouver dans ack/reject
        return [$envelope->with(
            new ElegantTransportStamp($record->id),
            new ReceivedStamp('messenger.transport.elegant')
        )];
    }

    /**
     * Envoie un message vers la base de données (pour le Dispatcher)
     */
    public function send(Envelope $envelope): Envelope
    {
        $encoded = $this->serializer->encode($envelope);
        
        $model = new $this->model;
        $model->body = $encoded['body'];
        $model->headers = $encoded['headers'] ?? [];
        $model->queue_name = $this->options['queue_name'] ?? 'default';
        $model->available_at = now();
        $model->created_at = now();
        $model->ignore();
        $model->save();

        return $envelope;
    }

    /**
     * Appelé quand le Worker a réussi à traiter le message SANS erreur
     */
    public function ack(Envelope $envelope): void 
    {
        // On récupère notre Stamp contenant l'ID
        $stamp = $envelope->last(ElegantTransportStamp::class);

        if (null === $stamp) {
            return; // On ne peut rien faire sans l'ID
        } 
        
        if (false === $this->options['keep']) $this->model::find($stamp->getId())?->delete();
        else $this->model::find($stamp->getId())?->update(['completed_at' => now()]);
    }

    /**
     * Appelé quand le Worker échoue (Exception levée dans le Handler)
     */
    public function reject(Envelope $envelope): void 
    {
        $stamp = $envelope->last(ElegantTransportStamp::class);
        
        if (null === $stamp) {
            return;
        }

        $record = $this->model::find($stamp->getId());
        
        if (null === $record) {
            return;
        }

        // Logique de rejet : on réessaie plus tard
        // (Nécessite d'avoir une colonne 'retries' dans votre table)
        $retries = ($record->retries ?: 0) + 1;
        $maxRetries = $this->options['retries'] ?? 3;
        
        if ($retries >= $maxRetries) {
            // Trop de tentatives, on abandonne et on supprime pour ne pas bloquer la file
            $record->delete();
            // TODO: Vous pourriez logger l'erreur ici ou l'envoyer dans une table "failed_jobs"
        } else {
            // On remet le message dans la file pour un essai ultérieur
            // Ex: 1ère erreur = réessaie dans 5min, 2ème = 10min, etc (Backoff exponentiel)
            $delayMinutes = pow(2, $retries); // 2, 4, 8, 16 minutes...

            $record->update([
                'delivered_at' => null, // On le rend à nouveau disponible
                'available_at' => now()->addMinutes($delayMinutes),
                'retries'      => $retries
            ]);
        }
    }
}