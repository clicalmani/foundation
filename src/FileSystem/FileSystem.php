<?php
namespace Clicalmani\Foundation\FileSystem;

use Clicalmani\Foundation\Maker\Application;
use League\Flysystem\Local\LocalFilesystemAdapter;

class FileSystem
{
    private $config;

    public function __construct(private Application $app)
    {
        $this->config = require_once config_path('/filesystem.php');
    }

    public function getDriver(string $name)
    {
        return match ($name) {
            'local' => new LocalFilesystemAdapter($this->app->rootPath()),
        };
    }

    public function __get($name)
    {
        return match ($name) {
            'links' => $this->config['links']
        };
    }
}