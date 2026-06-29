<?php
namespace Clicalmani\Foundation\Scheduler;

use Symfony\Component\Scheduler\Schedule;

class TaskDiscovery
{
    public static function buildSchedule(string $path, string $namespace): Schedule
    {
        $schedule = new Schedule;
        
        $directory = new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS);
        $filter = new \RecursiveCallbackFilterIterator($directory, function ($current, $key, $iterator) {
            if ($iterator->hasChildren()) {
                return true;
            }
            return $current->isFile() && preg_match('/\.php$/', $current->getFilename());
        });

        $rootPath = rtrim(realpath($path), DIRECTORY_SEPARATOR);
        $baseNamespace = rtrim($namespace, '\\');

        /** @var \SplFileInfo $file */
        foreach (new \RecursiveIteratorIterator($filter) as $file) {
            $currentSubDir = dirname($file->getRealPath());
            $relativeSubDir = str_replace($rootPath, '', $currentSubDir);
            $subNamespace = str_replace(DIRECTORY_SEPARATOR, '\\', $relativeSubDir);
            $classNameOnly = $file->getBasename('.php');
            
            $className = $baseNamespace . $subNamespace . '\\' . $classNameOnly;
            $className = str_replace('\\\\', '\\', $className); // Safeguard against double backslashes
            
            $schedule->add($className::schedule());
        }

        return $schedule;
    }
}