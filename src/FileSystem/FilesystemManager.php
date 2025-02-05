<?php
namespace Clicalmani\Foundation\FileSystem;

use Clicalmani\Foundation\Maker\Application;
use Clicalmani\Foundation\Support\Facades\Arr;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\Visibility;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\Local\LocalFilesystemAdapter as LocalAdapter;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;

class FilesystemManager
{
    public $app;

    /**
     * The array of resolved filesystem drivers.
     *
     * @var array
     */
    public $disks = [];

    /**
     * The registered custom driver creators.
     *
     * @var array
     */
    public $customCreators = [];

    private $config;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->config = require $this->app->rootPath().'/config/filesystem.php';
    }

    /**
     * Get a filesystem instance.
     *
     * @param  string|null  $name
     * @return \League\Flysystem\FilesystemOperator
     */
    public function drive(?string $name = null)
    {
        return $this->disk($name);
    }

    /**
     * Get a filesystem instance.
     *
     * @param  string|null  $name
     * @return \League\Flysystem\FilesystemOperator
     */
    public function disk(?string $name = null)
    {
        $name = $name ?: $this->getDefaultDriver();
        return $this->get($name);
    }

    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->config['default'];
    }

    /**
     * Attempt to get the disk from the local cache.
     *
     * @param  string  $name
     * @return \League\Flysystem\FilesystemOperator
     */
    public function get(string $name)
    {
        return $this->disks[$name] ?? $this->resolve($name);
    }

    /**
     * Resolve the given disk.
     *
     * @param  string  $name
     * @param  array|null  $config
     * @return \League\Flysystem\FilesystemOperator
     * @throws \InvalidArgumentException
     */
    public function resolve(string $name, ?array $config = null)
    {
        $config ??= $this->getConfig("disks.{$name}");

        if (empty($config['driver'])) {
            throw new \InvalidArgumentException("Disk [{$name}] does not have a configured driver.");
        }

        $driver = $config['driver'];

        if (isset($this->customCreators[$driver])) {
            return $this->callCustomCreator($config);
        }

        $driverMethod = 'create'.ucfirst($driver).'Driver';

        if (! method_exists($this, $driverMethod)) {
            throw new \InvalidArgumentException("Driver [{$driver}] is not supported.");
        }

        return $this->disks[$name] = $this->{$driverMethod}($config, $name);
    }

    /**
     * Get the filesystem connection configuration.
     *
     * @param  string  $name
     * @return array
     */
    public function getConfig(string $name)
    {
        return Arr::get($this->config, $name, []);
    }

    /**
     * Call a custom driver creator.
     *
     * @param  array  $config
     * @return \League\Flysystem\FilesystemOperator
     */
    public function callCustomCreator(array $config)
    {
        return $this->customCreators[$config['driver']]($this->app, $config);
    }

    /**
     * Create an instance of the local driver.
     *
     * @param  array  $config
     * @param  string  $name
     * @return \League\Flysystem\FilesystemOperator
     */
    public function createLocalDriver(array $config, string $name = 'local')
    {
        $visibility = PortableVisibilityConverter::fromArray(
            $config['permissions'] ?? [],
            $config['directory_visibility'] ?? $config['visibility'] ?? Visibility::PRIVATE
        );

        $links = ($config['links'] ?? null) === 'skip'
            ? LocalAdapter::SKIP_LINKS
            : LocalAdapter::DISALLOW_LINKS;

        $adapter = new LocalAdapter(
            $config['root'], $visibility, $config['lock'] ?? LOCK_EX, $links
        );

        return $this->createFlysystem($adapter, $config);
    }

    /**
     * Create an instance of the ftp driver.
     *
     * @param  array  $config
     * @return \League\Flysystem\FilesystemOperator
     */
    public function createFtpDriver(array $config)
    {
        if (! isset($config['root'])) {
            $config['root'] = '';
        }

        $config['port'] = (int)$config['port'] ?? 21;

        $adapter = new FtpAdapter(FtpConnectionOptions::fromArray($config));

        return $this->createFlysystem($adapter, $config);
    }

    /**
     * Create an instance of the sftp driver.
     *
     * @param  array  $config
     * @return \League\Flysystem\FilesystemOperator
     */
    // public function createSftpDriver(array $config)
    // {
    //     $provider = SftpConnectionProvider::fromArray($config);

    //     $root = $config['root'] ?? '';

    //     $visibility = PortableVisibilityConverter::fromArray(
    //         $config['permissions'] ?? []
    //     );

    //     $adapter = new SftpAdapter($provider, $root, $visibility);

    //     return $this->createFlysystem($adapter, $config);
    // }

    /**
     * Create an instance of the Amazon S3 driver.
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Filesystem\Cloud
     */
    // public function createS3Driver(array $config)
    // {
    //     $s3Config = $this->formatS3Config($config);

    //     $root = (string) ($s3Config['root'] ?? '');

    //     $visibility = new AwsS3PortableVisibilityConverter(
    //         $config['visibility'] ?? Visibility::PUBLIC
    //     );

    //     $streamReads = $s3Config['stream_reads'] ?? false;

    //     $client = new S3Client($s3Config);

    //     $adapter = new S3Adapter($client, $s3Config['bucket'], $root, $visibility, null, $config['options'] ?? [], $streamReads);

    //     return new AwsS3V3Adapter(
    //         $this->createFlysystem($adapter, $config), $adapter, $s3Config, $client
    //     );
    // }

    /**
     * Create a Flysystem instance with the given adapter.
     *
     * @param  \League\Flysystem\FilesystemAdapter  $adapter
     * @param  array  $config
     * @return \League\Flysystem\FilesystemOperator
     */
    public function createFlysystem(FilesystemAdapter $adapter, array $config)
    {
        // if ($config['read-only'] ?? false === true) {
        //     $adapter = new \League\Flysystem\ReadOnly\ReadOnlyFilesystemAdapter($adapter);
        // }

        // if (! empty($config['prefix'])) {
        //     $adapter = new \League\Flysystem\PathPrefixing\PathPrefixedAdapter($adapter, $config['prefix']);
        // }

        return new Flysystem(
            $adapter, 
            Arr::only($config, [
                'directory_visibility',
                'disable_asserts',
                'retain_visibility',
                'temporary_url',
                'url',
                'visibility',
            ]),
            null,
            new PublicUrlGenerator
        );
    }

    /**
     * Format the given S3 configuration with the default options.
     *
     * @param  array  $config
     * @return array
     */
    public function formatS3Config(array $config)
    {
        $config += ['version' => 'latest'];

        if (! empty($config['key']) && ! empty($config['secret'])) {
            $config['credentials'] = Arr::only($config, ['key', 'secret']);

            if (! empty($config['token'])) {
                $config['credentials']['token'] = $config['token'];
            }
        }

        return Arr::except($config, ['token']);
    }
}