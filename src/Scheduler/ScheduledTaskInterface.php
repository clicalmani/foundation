<?php
namespace Clicalmani\Core\Scheduler;

use Symfony\Component\Scheduler\RecurringMessage;

interface ScheduledTaskInterface
{
    /**
     * Définit la fréquence et le message à envoyer
     */
    public static function schedule(): \Symfony\Component\Scheduler\RecurringMessage;
}