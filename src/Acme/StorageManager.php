<?php
namespace Clicalmani\Foundation\Acme;

class StorageManager
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
     * @return mixed
     */
    public static function link() : mixed
    {
        return \Clicalmani\Foundation\Support\Facades\Tonka::link(storage_path(), root_path('public'));
    }
}