<?php

namespace Clicalmani\Core\Support\Facades;

use Clicalmani\Core\Acme\StorageManager;

/**
 * @method static \Clicalmani\Core\Acme\StorageManager drive(string $name)
 * @method static string get(string $filename)
 * @method static bool put(string $filename, mixed $content, array $options = [])
 * @method static resource readStream(string $filename)
 * @method static mixed json(string $filename, bool $assoc = false, int $depth = 512, int $flag = 0)
 * @method static bool prepend(string $filename, string $data)
 * @method static bool append(string $filename, string $data)
 * @method static string copy(string $source, string $destination, array $config = [])
 * @method static string move(string $source, string $destination, array $config = [])
 * @method static string store(string $source, string $filename, array $config = [])
 * @method static bool delete(string|array $paths)
 * @method static bool deleteDirectory(string $directory)
 * @method static bool cleanDirectory(string $directory)
 * @method static bool makeDirectory(string $path, array $config = [])
 * @method static array files(string $directory = '', bool $recursive = false)
 * @method static array allFiles(string $directory = '')
 * @method static array directories(string $directory = '', bool $recursive = false)
 * @method static array allDirectories(string $directory = '')
 * @method static bool fileExists(string $location)
 * @method static bool missing(string $filename)
 * @method static int size(string $filename)
 * @method static string mimeType(string $filename)
 * @method static int lastModified(string $filename)
 * @method static string visibility(string $path)
 * @method static bool setVisibility(string $path, string $visibility)
 * @method static mixed symlink(string $link, ?string $target = null)
 * @method static mixed download(string $filename, ?string $as = null, ?string $type = null)
 * @method static string url(string $path)
 * 
 * @see \Clicalmani\Core\Acme\StorageManager
 */
class Storage extends Facade
{
    /**
     * Résout l'instance sous-jacente du StorageManager.
     *
     * @return \Clicalmani\Core\Acme\StorageManager
     */
    protected static function getFacadeAccessor(): StorageManager
    {
        return 'storage';
    }

    /**
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic(string $method, array $arguments): mixed
    {
        return static::getFacadeRoot()?->$method(...$arguments);
    }
}