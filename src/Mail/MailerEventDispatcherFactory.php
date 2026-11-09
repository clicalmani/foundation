<?php
namespace Clicalmani\Core\Mail;

use Clicalmani\Core\Resources\TemplateLoader;
use Clicalmani\Core\Resources\TonkaTwigExtension;
use Symfony\Bridge\Twig\Mime\BodyRenderer;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\EventListener\EnvelopeListener;
use Symfony\Component\Mailer\EventListener\MessageListener;
use Symfony\Component\Mime\Address;
use Twig\Environment;

class MailerEventDispatcherFactory
{
    public function create(): EventDispatcherInterface
    {
        $dispatcher = new EventDispatcher();

        $fromAddress = config('mail.from.address');
        $fromName    = config('mail.from.name', config('app.name', 'Tonka'));

        if ($fromAddress) {
            $dispatcher->addSubscriber(
                new EnvelopeListener(new Address($fromAddress, $fromName))
            );
        }

        $dispatcher->addSubscriber(
            new MessageListener(null, new BodyRenderer($this->twig()))
        );

        return $dispatcher;
    }

    private function twig(): Environment
    {
        $twig = new Environment(new TemplateLoader, []);
        $twig->addExtension(new TonkaTwigExtension);

        return $twig;
    }
}