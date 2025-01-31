<?php
namespace Clicalmani\Foundation\FileSystem;

use Clicalmani\Foundation\Support\Facades\Tonka;

abstract class Storage
{
    private $filesystem;

    public function __construct()
    {
        $this->filesystem = app()->filesystem;
    }

    /**
     * Create symbolic links
     * 
     * @return void
     */
    public function link() : void
    {
        foreach ($this->filesystem->links as $source => $destination) {
            Tonka::link($source, $destination);
        }
    }

    public function disk(string $name)
    {}
}