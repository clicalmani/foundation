<?php 
namespace Clicalmani\Foundation\Maker\Logic;

use Clicalmani\Foundation\FileSystem\FilesystemManager;
use Clicalmani\Foundation\Support\Facades\Facade;
use Clicalmani\Foundation\Support\Facades\Tonka;

class Storage extends Facade
{
    private $manager;
    private $disk;

    public function __construct()
    {
        $this->manager = app()->filesystem;
        $this->disk = $this->manager->getDefaultDriver();
    }

    public function move(string $source, string $destination, ?string $disk = null)
    {
        $disk = $disk ?: $this->disk;
        $config = $this->manager->getConfig($disk);
        $destination = $config['root'] . DIRECTORY_SEPARATOR . $destination;
        $this->manager->drive($disk)->move($source, $destination, $config);

        return $destination;
    }

    public function store(string $source, string $filename, ?string $disk = null)
    {
        $disk = $disk ?: $this->disk;
        $config = $this->manager->getConfig($disk);
        $destination = $config['root'] . DIRECTORY_SEPARATOR . $filename;

        $this->manager->disk($disk)->move(
            $source, 
            $destination
        );
    }

    /**
     * Create storage symbolic link in the public directory
     * 
     * @return bool True on success, false on failure
     */
    public static function link()
    {
        return Tonka::link(storage_path(), root_path('public'));
    }
}
