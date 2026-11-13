<?php

namespace Clicalmani\Core\Mail;

use Symfony\Component\Messenger\Envelope as MessengerEnvelope;
use Symfony\Component\Mailer\Envelope as SmtpEnvelope;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;

class MailableMiddleware implements MiddlewareInterface
{
    public function __construct(private TransportInterface $transport) {}

    public function handle(MessengerEnvelope $envelope, StackInterface $stack): MessengerEnvelope
    {
        $message = $envelope->getMessage();

        if ($message instanceof SendEmailMessage && $envelope->last(ReceivedStamp::class)) {
            $mail = $message->getMessage();

            if ($mail instanceof Mailable) {
                $mail->build();
                $smtpEnvelope = SmtpEnvelope::create($mail);   // Mailer\Envelope
            } else {
                $smtpEnvelope = $message->getEnvelope();       // Mailer\Envelope|null
            }

            $this->transport->send($mail, $smtpEnvelope);      // pas $envelope !

            return $envelope->with(new HandledStamp(null, self::class));
        }

        return $stack->next()->handle($envelope, $stack);
    }
}