<?php
namespace Clicalmani\Foundation\Mail;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Mailer\MailerInterface;

#[AsEventListener]
class MailableListener
{
    public function __invoke(MailableInterface $event, MailerInterface $mailer)
    {
        if (method_exists($event, 'send')) {
            $event->send();
        }
        
        if (!$event->template) {
            throw new \RuntimeException('Mailable event must have a template defined.');
        }

        $email = (new \Clicalmani\Foundation\Mail\Email($event->subject, view("/{$event->template}", $event->data)->render()))
                    ->to(...array_map(fn(array $recipient) => new \Symfony\Component\Mime\Address($recipient[0], $recipient[1] ?? ''), $event->to));
        foreach ($event->pathAttachments as $attachment) {
            $email->attachFromPath(...$attachment);
        }
        $mailer->send($email);
    }
}