<?php
namespace Clicalmani\Foundation\Filesystem;

/**
 * Class DirectoryScanner
 *
 * Utilitaire générique de scan récursif de répertoire.
 * Permet de lister des fichiers (avec filtre d'extension), de résoudre
 * des noms de classes PHP à partir de chemins, et de construire des
 * listes de fichiers/classes pour de l'auto-discovery (modèles, providers,
 * commandes, événements, etc.).
 *
 * @package Clicalmani\Foundation\Filesystem
 * @author Clicalmani\Foundation
 */
final class DirectoryScanner
{
    /**
     * @param string $rootPath Répertoire racine à scanner
     * @param ?string $baseNamespace Namespace de base correspondant à $rootPath, utilisé pour résoudre les noms de classes
     * @param string[] $extensions Extensions de fichiers à retenir (sans le point). Vide = toutes.
     * @param string[] $ignore Noms de fichiers/dossiers à ignorer en plus de '.' et '..'
     */
    public function __construct(
        private readonly string $rootPath,
        private readonly ?string $baseNamespace = null,
        private readonly array $extensions = ['php'],
        private readonly array $ignore = [],
    ) {}

    /**
     * Instancie un scanner pour un répertoire donné.
     *
     * @param string $path
     * @param ?string $namespace
     * @return self
     */
    public static function forPath(string $path, ?string $namespace = null): self
    {
        return new self($path, $namespace);
    }

    /**
     * Scanne récursivement le répertoire et retourne les chemins absolus
     * de tous les fichiers correspondant aux extensions retenues.
     *
     * @return string[] Chemins absolus des fichiers trouvés
     */
    public function files(): array
    {
        return $this->scan($this->rootPath);
    }

    /**
     * Scanne récursivement le répertoire et retourne, pour chaque fichier PHP trouvé,
     * le nom de classe pleinement qualifié correspondant (déduit de l'arborescence
     * de dossiers relative à $rootPath, mappée sur $baseNamespace).
     *
     * @return string[] Noms de classes pleinement qualifiés
     * @throws \LogicException Si $baseNamespace n'a pas été fourni au constructeur
     */
    public function classes(): array
    {
        if (null === $this->baseNamespace) {
            throw new \LogicException(
                sprintf('%s: un namespace de base doit être fourni pour résoudre des noms de classes.', static::class)
            );
        }

        return array_values(array_map(
            fn(string $path) => $this->resolveClassName($path),
            $this->files()
        ));
    }

    /**
     * Comme classes(), mais ne retient que les classes qui existent réellement
     * (fichier chargé avec succès et classe/interface/trait défini dedans) et,
     * si un filtre est fourni, qui le satisfont (ex: is_subclass_of).
     *
     * @param ?\Closure $filter fn(string $className): bool
     * @return string[]
     */
    public function discoverClasses(?\Closure $filter = null): array
    {
        $classes = [];

        foreach ($this->classes() as $className) {
            if (!class_exists($className) && !interface_exists($className) && !trait_exists($className)) {
                continue;
            }

            if ($filter && !$filter($className)) {
                continue;
            }

            $classes[] = $className;
        }

        return $classes;
    }

    /**
     * Scan récursif interne.
     *
     * @param string $path
     * @return string[]
     */
    private function scan(string $path): array
    {
        $found = [];

        $entries = scandir($path);

        if (false === $entries) {
            return $found;
        }

        $entries = array_diff($entries, array_merge(['.', '..'], $this->ignore));

        foreach ($entries as $entry) {
            $entryPath = $path . DIRECTORY_SEPARATOR . $entry;

            if (is_dir($entryPath)) {
                $found = array_merge($found, $this->scan($entryPath));
                continue;
            }

            if ($this->matchesExtension($entryPath)) {
                $found[] = $entryPath;
            }
        }

        return $found;
    }

    /**
     * Vérifie si un chemin de fichier correspond à l'une des extensions retenues.
     */
    private function matchesExtension(string $path): bool
    {
        if (empty($this->extensions)) return true;

        $ext = pathinfo($path, PATHINFO_EXTENSION);

        return in_array($ext, $this->extensions, true);
    }

    /**
     * Déduit le nom de classe pleinement qualifié d'un fichier, en combinant
     * $baseNamespace avec le chemin relatif du fichier (sous-dossiers inclus)
     * par rapport à $rootPath.
     *
     * Exemple : rootPath=/app/Models, baseNamespace=App\Models,
     * fichier /app/Models/Admin/User.php → App\Models\Admin\User
     */
    private function resolveClassName(string $filePath): string
    {
        $relative = ltrim(
            str_replace($this->rootPath, '', $filePath),
            DIRECTORY_SEPARATOR
        );

        $withoutExtension = preg_replace('/\.[^.]+$/', '', $relative);

        $classSuffix = str_replace(DIRECTORY_SEPARATOR, '\\', $withoutExtension);

        return rtrim($this->baseNamespace, '\\') . '\\' . $classSuffix;
    }
}