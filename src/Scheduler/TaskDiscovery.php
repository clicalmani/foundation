<?php
namespace Clicalmani\Foundation\Scheduler;

use Symfony\Component\Scheduler\Schedule;

class TaskDiscovery
{
    public static function buildSchedule(string $path, string $namespace): Schedule
    {
        $schedule = new Schedule();
        
        // On récupère tous les fichiers PHP du dossier
        $files = glob($path . '/*.php');

        foreach ($files as $file) {
            $class = $namespace . '\\' . basename($file, '.php');

            // Si la classe existe et implémente notre interface
            if (class_exists($class) && is_subclass_of($class, ScheduledTaskInterface::class)) {
                $schedule->add($class::schedule());
            }
        }

        return $schedule;
    }
}