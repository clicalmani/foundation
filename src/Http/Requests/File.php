<?php
namespace Clicalmani\Foundation\Http\Requests;

use Clicalmani\Foundation\Filesystem\FilesystemManager;
use Clicalmani\Foundation\Support\Facades\Storage;
use Clicalmani\Psr7\Stream;
use Psr\Http\Message\StreamInterface;

class File implements FileInterface
{
    protected string $file;
    protected string $name;
    protected string $type;
    protected int $size;
    protected int $error;
    protected bool $sapi = false;
    protected bool $moved = false;

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
    }

    public function getStream(): StreamInterface
    {
        if ($this->sapi) {
            return new Stream($this->file);
        }

        return new Stream(fopen($this->file, 'r'));
    }

    public function moveTo(string $targetPath): void
    {
        if ($this->moved) {
            throw new \RuntimeException('File has already been moved');
        }

        if ($this->error !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Cannot move file, upload error');
        }

        if (!is_uploaded_file($this->file)) {
            if (!rename($this->file, $targetPath))
                throw new \RuntimeException('Cannot rename file');
        }

        if (!move_uploaded_file($this->file, $targetPath)) {
            throw new \RuntimeException('Cannot move file');
        }

        $this->file = $targetPath;
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

    /**
     * Store the file on the disk
     *
     * @param string $filename
     * @param string|null $disk
     * @return string
     */
    public function store(string $filename, ?string $disk = null)
    {
        $manager = new FilesystemManager(app());
        $disk = $disk ?: $manager->getDefaultDriver();
        Storage::store($this->file, $filename, $disk);
        return $manager->get($disk)->publicUrl($filename);
    }
}