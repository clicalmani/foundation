<?php 
namespace Clicalmani\Foundation\Maker\Logic;

use Clicalmani\Foundation\FileSystem\FilesystemManager;
use Clicalmani\Foundation\Support\Facades\Facade;
use Clicalmani\Foundation\Support\Facades\Tonka;

class Storage extends Facade
{
    private $manager;

    public function __construct(FilesystemManager $manager)
    {
        $this->manager = $manager;
    }

    public function move(string $source, string $destination, ?string $disk = null)
    {
        $disk = $disk ?: $this->manager->getDefaultDriver();
        $config = $this->manager->getConfig($disk);
        $destination = $config['root'] . DIRECTORY_SEPARATOR . $destination;
        $this->manager->drive($disk)->move($source, $destination, $config);

        return $destination;
    }

    public function store(string $filename, ?string $driver = null)
    {
        $driver = $driver ?: $this->manager->getDefaultDriver();
        $config = $this->manager->getConfig($driver);
        $destination = $config['root'] . DIRECTORY_SEPARATOR . basename($filename);

        $this->manager->disk($driver)->move(
            $filename, 
            $destination,
            $config
        );

        return $destination;
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
