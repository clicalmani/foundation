<?php
namespace Clicalmani\Core\Filesystem;

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use Aws\S3\S3Client;
use League\Flysystem\Visibility;

class StorageManager
{
    protected array $disks = [];

    public function __construct(protected array $config) {}

    public function disk(?string $name = null): Filesystem
    {
        $name = $name ?: $this->config['default'];

        if (!isset($this->disks[$name])) {
            $this->disks[$name] = $this->resolve($name);
        }

        return $this->disks[$name];
    }

    public function getConfig(?string $disk = null): array
    {
        if ($disk === null) return $this->config;
        return $this->resolveConfig($this->config['disks'][$disk] ?? []);
    }

    protected function resolve(string $name): Filesystem
    {
        $config = $this->config['disks'][$name];

        return match ($config['driver']) {
            'local' => new Filesystem(new LocalFilesystemAdapter($config['root'])),
            's3'    => $this->createS3Driver($config),
            default => throw new \InvalidArgumentException("Driver [{$config['driver']}] non supporté.")
        };
    }

    protected function createS3Driver(array $config): Filesystem
    {
        $client = new S3Client([
            'region'  => $config['region'],
            'version' => 'latest',
            'credentials' => [
                'key'    => $config['key'],
                'secret' => $config['secret'],
            ],
        ]);

        return new Filesystem(new AwsS3V3Adapter($client, $config['bucket']));
    }

    public function resolveConfig(array $config): array
    {
        if ($config['visibility'] ?? null) {
            $config['visibility'] = match ($config['visibility']) {
                'public' => Visibility::PUBLIC,
                'private' => Visibility::PRIVATE,
                default => Visibility::PRIVATE
            };
        }

        if ($config['directory_visibility'] ?? null) {
            $config['directory_visibility'] = match ($config['directory_visibility']) {
                'public' => Visibility::PUBLIC,
                'private' => Visibility::PRIVATE,
                default => Visibility::PRIVATE
            };
        }

        return $config;
    }
}