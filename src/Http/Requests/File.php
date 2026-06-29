<?php
namespace Clicalmani\Foundation\Http\Requests;

use Clicalmani\Foundation\Support\Facades\Storage;
use Clicalmani\Psr\Stream;
use Override;
use Psr\Http\Message\StreamInterface;

class File implements FileInterface, \JsonSerializable
{
    protected string $file;
    protected string $name;
    protected string $type;
    protected int $size;
    protected int $error;
    protected bool $sapi = false;
    protected bool $moved = false;

    private \Clicalmani\Foundation\Filesystem\StorageManager $manager;

    public function __construct(
        string $file,
        string $name,
        string $type,
        int $size,
        int $error,
        bool $sapi = false
    )
    {
        $this->file = $file;
        $this->name = $name;
        $this->type = $type;
        $this->size = $size;
        $this->error = $error;
        $this->sapi = $sapi;

        /** @var \Clicalmani\Foundation\Filesystem\StorageManager */
        $this->manager = container()->get('storage.manager');
    }

    /**
     * Get the file stream
     *
     * @return string
     */
    public function getStream(): StreamInterface
    {
        if ($this->sapi) {
            return new Stream($this->file);
        }

        return new Stream(fopen($this->file, 'r'));
    }

    public function move(string $source, string $destination, ?string $disk = null): string
    {
        $disk        = $disk ?: $this->disk;
        $config      = $this->manager->getConfig($disk);
        $absoluteDst = $config['root'] . DIRECTORY_SEPARATOR . $destination;

        // Déplace le fichier physiquement si hors du filesystem Flysystem
        // (ex: fichier temporaire PHP upload)
        if (!$this->manager->disk($disk)->fileExists($source)) {
            if (!rename($source, $absoluteDst)) {
                throw new \RuntimeException(
                    sprintf('Cannot move "%s" to "%s"', $source, $absoluteDst)
                );
            }
        } else {
            // Source gérée par Flysystem — chemins relatifs
            $this->manager->disk($disk)->move($source, $destination);
        }

        $this->moved = true;

        return $absoluteDst;
    }

    public function moveTo(string $targetPath, ?string $disk = null): void
    {
        if ($this->moved) {
            throw new \RuntimeException('File has already been moved.');
        }

        if ($this->error !== UPLOAD_ERR_OK) {
            throw new \RuntimeException(
                sprintf('Cannot move file, upload error code: %d', $this->error)
            );
        }

        try {
            if ($disk !== null) {
                // Déplacement via StorageManager — disk explicite
                $destination = Storage::move($this->file, $targetPath, $disk);
            } else {
                // Déplacement bas niveau — chemin absolu direct (comportement PSR-7)
                if (!is_dir(dirname($targetPath))) {
                    mkdir(dirname($targetPath), 0755, recursive: true);
                }

                $moved = is_uploaded_file($this->file)
                    ? move_uploaded_file($this->file, $targetPath)
                    : rename($this->file, $targetPath);

                if (!$moved) {
                    throw new \RuntimeException(
                        sprintf('Cannot move file to "%s"', $targetPath)
                    );
                }

                $destination = $targetPath;
            }
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                sprintf('Failed to move "%s": %s', $targetPath, $e->getMessage()),
                previous: $e
            );
        }

        $this->file  = $destination;
        $this->moved = true;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function getClientFilename(): string
    {
        return $this->name;
    }

    /**
     * Get the file media type
     *
     * @return string
     */
    public function getClientMediaType(): string
    {
        return $this->type;
    }

    public function getClientExtension(): ?string
    {
        return pathinfo($this->name, PATHINFO_EXTENSION);
    }

    public function isValid() : bool
    {
        return $this->error === UPLOAD_ERR_OK;
    }

    public function store(?string $disk = null): string
    {
        $disk   = $disk ?: $this->disk;
        $config = $this->manager->getConfig($disk);
        $destination = $config['root'] . DIRECTORY_SEPARATOR . $this->name;

        $stream = fopen($this->file, 'r');

        try {
            $this->manager->disk($disk)->writeStream($this->name, $stream);
        } finally {
            if (is_resource($stream)) fclose($stream);
        }

        return $destination;
    }

    public function jsonSerialize(): array
    {
        return [
            'name' => $this->getClientFilename(),
            'type' => $this->getClientMediaType(),
            'size' => $this->getSize(),
            'error' => $this->getError(),
        ];
    }
}