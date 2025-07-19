<?php
namespace Clicalmani\Foundation\Http\Requests;

use Clicalmani\Psr\Stream;
use Psr\Http\Message\StreamInterface;

/**
 * Class UploadedFile
 * 
 * @package Clicalmani\Foundation
 * @author @Clicalmani\Foundation
 */
class UploadedFile implements \Psr\Http\Message\UploadedFileInterface
{
    /**
     * Uploaded file path
     * 
     * @var string
     */
    protected string $file;

    /**
     * File name
     * 
     * @var string
     */
    protected string $name;

    /**
     * File type
     * 
     * @var string
     */
    protected string $type;

    /**
     * File size
     * 
     * @var int
     */
    protected int $size;

    /**
     * File error
     * 
     * @var int
     */
    protected int $error;

    /**
     * File SAPI
     * 
     * @var bool
     */
    protected bool $sapi = false;

    /**
     * File stream
     * 
     * @var \Psr\Http\Message\StreamInterface
     */
    protected $stream;

    /**
     * File moved
     * 
     * @var bool
     */
    protected $moved = false;

    /**
     * File storage
     * 
     * @var \Clicalmani\Foundation\Maker\Logic\Storage
     */
    protected $storage;

    /**
     * Controller
     * 
     * @param string $name File key
     */
    public function __construct(
        string $name,
        // ?string $name = null,
        ?string $type = null,
        ?int $size = null,
        int $error = UPLOAD_ERR_OK,
        bool $sapi = false
    ) {
        $this->name = $name;
        $this->type = mime_content_type($this->getFile()->tmp_name);
        $this->size = $this->getFile()->size;
        $this->error = $this->getFile()->error;
        $this->sapi = is_uploaded_file($this->getFile()->tmp_name);
    }

    public function getStream(): StreamInterface
    {
        if ($this->moved) {
            throw new \RuntimeException('Cannot retrieve stream after it has already been moved');
        }

        if ($this->stream === null) {
            $this->stream = Stream::createFromFile($this->getFile()->tmp_name);
        }

        return $this->stream;
    }

    /**
     * Get the uploaded file
     * 
     * @return \stdClass
     */
    public function getFile() : \stdClass
    {
        return (object) @ $_FILES[$this->name];
    }

    /**
     * Get file name
     * 
     * @return string|string[]
     */
    public function getName() : string|array
    {
        return $this->getFile()->name;
    }

    public function getClientOriginalName() : string|array
    {
        return $this->getName();
    }

    /**
     * Get file size
     * 
     * @return ?int
     */
    public function getSize() : ?int
    {
        return $this->size;
    }

    /**
     * Get file type
     * 
     * @return string
     */
    public function getType() : string
    {
        return $this->type;
    }

    /**
     * Check if file is valid
     * 
     * @return bool
     */
    public function isValid() : bool
    {
        $error = $this->getFile()->error;
        
        if ( is_numeric($error) ) return $this->getFile()->error == FALSE;
        
        return collection()->exchange($error)
                    ->map(fn($err) => $err == FALSE)
                    ->filter(fn(bool $b) => $b === FALSE)
                    ->count() === 0;
    }

    /**
     * Get the stored file extension
     * 
     * @return string
     */
    public function getExtension() : string
    {
        return pathinfo($this->getFile()->tmp_name, PATHINFO_EXTENSION);
    }

    /**
     * Get the original file extension
     * 
     * @return string|string[]
     */
    public function getClientOriginalExtension() : string|array
    {
        if (FALSE === $this->isMultiple()) return substr($this->getFile()->name, strrpos($this->getFile()->name, '.') + 1);

        return collection()->exchange($this->getFile()->name)
                    ->map(fn(string $name) => substr($name, strrpos($name, '.') + 1))
                    ->toArray();
    }

    /**
     * Multiple files upload
     * 
     * @return bool
     */
    public function isMultiple() : bool
    {
        $file = $this->getFile();
        if ($this->getFile() && property_exists($this->getFile(), 'name') && is_array( $this->getFile()?->name )) return true;
        return false;
    }

    /**
     * Count files
     * 
     * @return int
     */
    public function count() : int
    {
        if ( $this->isMultiple() ) return count( $this->getName() );

        return 1;
    }

    public function moveTo(string $targetPath): void
    {
        if ($this->moved) {
            throw new \RuntimeException('Cannot move file; already moved!');
        }

        if ($this->error !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Cannot retrieve stream due to upload error');
        }

        if ($this->sapi) {
            if (FALSE === move_uploaded_file($this->getFile()->tmp_name, $targetPath)) {
                throw new \RuntimeException('Error moving uploaded file');
            }
        } else {
            if (FALSE === rename($this->getFile()->tmp_name, $targetPath)) {
                throw new \RuntimeException('Error moving uploaded file');
            }
        }

        $this->moved = true;
    }

    /**
     * Move uploaded file from the temp directory
     * 
     * @param ?string $dir Directory to move the file to. If omitted storage/uploads will be used.
     * @param ?string $name New file name. If omitted the uploaded file name will be used.
     * @return bool
     */
    public function move(?string $dir = null, string|array $name = null)  : bool
    {
        if (!$dir) {
            if (FALSE === file_exists(storage_path('/public'))) 
                mkdir(storage_path('/public'));
            $dir = storage_path('/public');
        }

        $name = isset($name) ? $name: $this->getName();
        
        if (FALSE === $this->isMultiple()) return $this->moveFile($this->getFile()->tmp_name, $dir . DIRECTORY_SEPARATOR . $name, is_uploaded_file($this->getFile()->tmp_name));
        else {
            $success = 0;
            
            foreach ($this->getFile()->tmp_name as $index => $tn) $success += $this->moveFile($tn, $dir . DIRECTORY_SEPARATOR . $name[$index], is_uploaded_file($tn));

            return count($name) === $success;
        }
            
        return false;
    }

    /**
     * Add uploaded file
     * 
     * @param string $name File name
     * @param string $path File temp path
     * @param string $size File size
     * @param string $type File mimetype
     * @return void
     */
    public function addFile(string $name, string $path, int $size, string $type) : void
    {
        if (FALSE === array_key_exists($this->name, $_FILES)) {
            $_FILES[$this->name] = [
                'name'      => $name,
                'full_path' => $name,
                'type'      => $type,
                'tmp_name'  => $path,
                'error'     => !$size ? 1: 0,
                'size'      => $size,
                'time'      => time()
            ];
        } elseif ($this->isMultiple()) {
            $_FILES[$this->name]['name'][]      = $name;
            $_FILES[$this->name]['full_path'][] = $name;
            $_FILES[$this->name]['type'][]      = $type;
            $_FILES[$this->name]['tmp_name'][]  = $path;
            $_FILES[$this->name]['error'][]     = !$size ? 1: 0;
            $_FILES[$this->name]['size'][]      = $size;
            $_FILES[$this->name]['time'][]      = time();
        }
    }

    private function moveFile(string $from, string $to, ?bool $is_builtin = true) : bool
    {
        if ($is_builtin) return !!move_uploaded_file($from, $to);
        
        return !!rename($from, $to);
    }

    /**
     * Store the uploaded file to the default directory.
     * 
     * @param string|array $name
     * @return bool
     */
    public function store(string|array $name) : bool
    {
        return $this->move(null, $name);
    }

    public function __get($name)
    {
        return match ($name) {
            'storage' => new \Clicalmani\Foundation\Maker\Logic\Storage(
                new \Clicalmani\Foundation\Filesystem\FilesystemManager( app() )
            )
        };
    }
}
