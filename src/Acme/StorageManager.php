<?php
namespace Clicalmani\Core\Acme;

use Clicalmani\Core\Support\Facades\Response;

class StorageManager
{
    private $manager;
    private $disk;

    public function __construct()
    {
        /** @var \Clicalmani\Core\Filesystem\StorageManager */
        $this->manager = container()->get('storage.manager');
        $this->disk    = $this->manager->getConfig()['default'] ?? 'local';
    }

    public function drive(string $name): static
    {
        $this->disk = $name;
        return $this; 
    }

    /* =========================================================================
     *  LECTURE & ÉCRITURE (READ & WRITE)
     * ========================================================================= */

    public function get(string $filename): string
    {
        return $this->manager->disk($this->disk)
            ->read($this->resolve($filename));
    }

    public function put(string $filename, mixed $content, array $options = []): bool
    {
        $destination = $this->resolve($filename);
        
        if (is_resource($content)) {
            $this->manager->disk($this->disk)->writeStream($destination, $content, $this->manager->resolveConfig($options));
            return true;
        }

        $this->manager->disk($this->disk)->write($destination, (string) $content, $this->manager->resolveConfig($options));
        return true;
    }

    public function readStream(string $filename)
    {
        return $this->manager->disk($this->disk)
            ->readStream($this->resolve($filename));
    }

    public function json(string $filename, bool $assoc = false, int $depth = 512, int $flag = 0): mixed
    {
        return json_decode(
            $this->get($filename),
            $assoc, $depth, $flag
        );
    }

    public function prepend(string $filename, string $data): bool
    {
        if ($this->fileExists($filename)) {
            return $this->put($filename, $data . $this->get($filename));
        }

        return $this->put($filename, $data);
    }

    public function append(string $filename, string $data): bool
    {
        if ($this->fileExists($filename)) {
            return $this->put($filename, $this->get($filename) . $data);
        }

        return $this->put($filename, $data);
    }

    /* =========================================================================
     *  COPIE & DÉPLACEMENT
     * ========================================================================= */

    public function copy(string $source, string $destination): string
    {
        $source = $this->resolve($source);
        $destination = $this->resolve($destination);

        $this->manager->disk($this->disk)->copy($source, $destination, $this->manager->getConfig($this->disk));

        return $destination;
    }

    public function move(string $source, string $destination): string
    {
        $source = $this->resolve($source);
        $destination = $this->resolve($destination);

        $this->manager->disk($this->disk)->move($source, $destination, $this->manager->getConfig($this->disk));

        return $destination;
    }

    public function store(string $source, string $filename): string
    {
        return $this->move($source, $filename, $this->manager->getConfig($this->disk));
    }

    /* =========================================================================
     *  SUPPRESSION (DELETE)
     * ========================================================================= */

    public function delete(string|array $paths): bool
    {
        $paths = is_array($paths) ? $paths : func_get_args();

        foreach ($paths as $path) {
            $this->manager->disk($this->disk)->delete($this->resolve($path));
        }

        return true;
    }

    public function deleteDirectory(string $directory): bool
    {
        $this->manager->disk($this->disk)->deleteDirectory($this->resolve($directory));
        return true;
    }

    public function cleanDirectory(string $directory): bool
    {
        return $this->deleteDirectory($directory) && $this->makeDirectory($directory);
    }

    /* =========================================================================
     *  REPERTOIRES (DIRECTORIES)
     * ========================================================================= */

    public function makeDirectory(string $path): bool
    {
        $this->manager->disk($this->disk)->createDirectory($this->resolve($path), $this->manager->getConfig($this->disk));
        return true;
    }

    public function files(string $directory = '', bool $recursive = false): array
    {
        return $this->manager->disk($this->disk)
            ->listContents($this->resolve($directory), $recursive)
            ->filter(fn ($attributes) => $attributes->isFile())
            ->map(fn ($attributes) => $attributes->path())
            ->toArray();
    }

    public function allFiles(string $directory = ''): array
    {
        return $this->files($directory, true);
    }

    public function directories(string $directory = '', bool $recursive = false): array
    {
        return $this->manager->disk($this->disk)
            ->listContents($this->resolve($directory), $recursive)
            ->filter(fn ($attributes) => $attributes->isDir())
            ->map(fn ($attributes) => $attributes->path())
            ->toArray();
    }

    public function allDirectories(string $directory = ''): array
    {
        return $this->directories($directory, true);
    }

    /* =========================================================================
     *  MÉTADONNÉES ET VISIBILITÉ
     * ========================================================================= */

    public function fileExists(string $location): bool
    {
        return $this->manager->disk($this->disk)
            ->fileExists($this->resolve($location));
    }

    public function missing(string $filename): bool
    {
        return !$this->fileExists($filename);
    }

    public function size(string $filename): int
    {
        return $this->manager->disk($this->disk)
            ->fileSize($this->resolve($filename));
    }

    public function mimeType(string $filename): string
    {
        return $this->manager->disk($this->disk)
            ->mimeType($this->resolve($filename));
    }

    public function lastModified(string $filename): int
    {
        return $this->manager->disk($this->disk)
            ->lastModified($this->resolve($filename));
    }

    public function visibility(string $path): string
    {
        return $this->manager->disk($this->disk)
            ->visibility($this->resolve($path));
    }

    public function setVisibility(string $path, string $visibility): bool
    {
        $visibility = match (strtolower($visibility)) {
            'public' => \League\Flysystem\Visibility::PUBLIC,
            'private' => \League\Flysystem\Visibility::PRIVATE,
            default => throw new \InvalidArgumentException("Invalid visibility: {$visibility}. Allowed values are 'public' or 'private'."),
        };

        $this->manager->disk($this->disk)
            ->setVisibility($this->resolve($path), $visibility);
            
        return true;
    }

    /* =========================================================================
     *  LIENS ET TÉLÉCHARGEMENT
     * ========================================================================= */

    /**
     * Crée un lien symbolique sur le système de fichiers local.
     *
     * @param string $target Le dossier/fichier cible réel (ex: storage/app/public)
     * @param string $link Le chemin où créer le raccourci (ex: public/storage)
     * @return bool True en cas de succès, false en cas d'échec
     */
    public function symlink(string $target, string $link): bool
    {
        // Vérification si le lien existe déjà
        if (file_exists($link) || is_link($link)) {
            return true;
        }

        // Assure que le dossier parent du lien existe
        $linkDirectory = dirname($link);
        if (!is_dir($linkDirectory)) {
            mkdir($linkDirectory, 0755, true);
        }

        // Fonction native PHP pour créer le lien symbolique
        return @symlink($target, $link);
    }

    public function download(string $filename, ?string $as = null, ?string $type = null)
    {
        $filename = $this->resolve($filename);
        return Response::sendFile($filename, $as, $type);
    }

    public function url(string $path): string
    {
        $config = $this->manager->getConfig($this->disk);
        $url = $config['url'] ?? '';

        return rtrim($url, '/') . '/' . ltrim($path, '/');
    }

    /* =========================================================================
     *  MAGICAL CALL
     * ========================================================================= */

    public function __call($method, $arguments)
    {
        $diskInstance = $this->manager->disk($this->disk);

        if (method_exists($diskInstance, $method)) {
            return call_user_func_array([$diskInstance, $method], $arguments);
        }

        if (method_exists($this->manager, $method)) {
            return call_user_func_array([$this->manager, $method], $arguments);
        }

        throw new \BadMethodCallException("Method {$method} does not exist on StorageManager.");
    }

    private function resolve(string $location): string
    {
        $root = $this->manager->getConfig($this->disk)['root'] ?? '';
        
        if (empty($root)) {
            return $location;
        }

        return rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($location, DIRECTORY_SEPARATOR);
    }
}