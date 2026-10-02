<?php
namespace Clicalmani\Core\Mail;

use Symfony\Component\Mailer\MailerInterface;

final class MailerService
{
    public function __construct(
        private MailerInterface $mailer,
        /**
         * Layout par défaut défini en config (app.mail.default_layout).
         */
        private string $defaultLayout
    ) {}

    public function send(Mailable $mailable): void
    {
        $mailable->build();

        $this->applyLayout($mailable);

        $this->mailer->send($mailable);
    }

    /**
     * Résout le layout à appliquer et l'injecte dans le contexte Twig existant,
     * sans écraser les variables déjà posées via view()/with().
     *
     * Résolution :
     * 1. Le dev a explicitement désactivé  → aucun layout
     * 2. Le dev a précisé un layout        → on l'utilise
     * 3. Sinon                             → layout par défaut de la config
     *
     * @param \Clicalmani\Core\Mail\Mailable $mailable
     * @return void
     */
    private function applyLayout(Mailable $mailable): void
    {
        $layout = match (true) {
            $mailable->layoutDisabled  => null,
            $mailable->layout !== null => $mailable->layout,
            default                    => $this->defaultLayout,
        };

        if (null !== $layout) {
            // Le template de vue utilisera {% extends layout %}
            $mailable->context([...$mailable->getContext(), 'layout' => $layout]);
        }
    }
}