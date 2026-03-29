<?php
namespace Clicalmani\Foundation\Resources;

use Clicalmani\Foundation\Support\Facades\Response;

class ErrorRenderer
{
    // Configuration : Peut être déplacé dans un fichier de config global
    protected $ide = 'vscode'; // 'vscode', 'phpstorm', 'sublime', ou null
    protected $isDebug = true; // Mettre à false en production

    public function render(\Throwable $exception): string
    {
        // 1. Récupérer les infos de base
        $data = [
            'title'    => get_class($exception),
            'message'  => $exception->getMessage(),
            'file'     => $exception->getFile(),
            'line'     => $exception->getLine(),
            'trace'    => $this->formatTrace($exception),
            'snippet'  => $this->getSnippet($exception->getFile(), $exception->getLine()),
            
            // On passe la fonction de génération de lien à la vue
            'editorLink' => $this->createEditorLink($exception->getFile(), $exception->getLine()),
            'request'  => $this->getRequestContext(),
        ];

        // 2. Charger la vue Twig
        // On suppose que $this->twig est ton instance Twig configurée
        // On désactive le cache pour cette vue spécifique
        try {
            return Response::status(500)->view('500', ['error' => $data]);
        } catch (\Throwable $e) {
            // Si Twig plante aussi, on affiche un texte brut (fallback de sécurité)
            return Response::status(500)->send("<h1>Critical Error</h1><p>{$exception->getMessage()}</p>");
        }
    }

    /**
     * Extrait les lignes de code autour de l'erreur
     */
    private function getSnippet(string $file, int $line, int $context = 10): array
    {
        if (!file_exists($file)) return [];

        // 1. Appliquer le thème AVANT de colorer
        $this->setThemeColors();

        // 2. Récupérer le contenu du fichier
        $content = file_get_contents($file);
        
        // 3. Découper les lignes
        $lines = explode("\n", $content);
        $start = max(0, $line - $context - 1);
        $length = ($context * 2) + 1;
        $snippetLines = array_slice($lines, $start, $length, true);
        $xdt = xdt();

        // 4. Colorer chaque ligne individuellement
        $result = [];
        foreach ($snippetLines as $num => $codeLine) {
            $xdt->load(\highlight_string("<?php " . $codeLine, true), true, true);
            $first = $xdt->select('code > span:first');
            $first->html(str_replace('&lt;?php', '', $first->html()));
            $result[$num + 1] = $xdt->getDocumentRootElement()->html();
        }

        return $result;
    }

    /**
     * Formate la stack trace pour la rendre lisible
     */
    private function formatTrace(\Throwable $exception): array
    {
        $trace = $exception->getTrace();
        $formatted = [];

        foreach ($trace as $index => $item) {
            // On saute les éléments internes si nécessaire
            $formatted[] = [
                'id'     => $index,
                'file'   => $item['file'] ?? 'internal',
                'line'   => $item['line'] ?? '?',
                'class'  => $item['class'] ?? '',
                'func'   => $item['function'] ?? '',
                'type'   => $item['type'] ?? '', // -> ou ::
                'args'   => $this->formatArgs($item['args'] ?? []),
                'snippet'=> isset($item['file']) && file_exists($item['file']) 
                            ? $this->getSnippet($item['file'], $item['line'], 5) 
                            : [],
                'editorLink' => isset($item['file']) ? $this->createEditorLink($item['file'], $item['line']) : null
            ];
        }
        return $formatted;
    }
    
    private function formatArgs(array $args): string 
    {
        // Logique simplifiée pour afficher les arguments
        return count($args) > 0 ? '...' : '';
    }

    /**
     * Génère l'URL magique pour ouvrir l'éditeur
     */
    private function createEditorLink(string $file, int $line): ?string
    {
        if (!$this->isDebug || !$this->ide) {
            return null;
        }

        // Nettoyage du chemin pour éviter les soucis sous Windows
        $realPath = realpath($file);
        if (!$realPath) return null;

        switch ($this->ide) {
            case 'vscode':
                // vscode://file/c:/Users/.../file.php:10
                return "vscode://file/" . urlencode($realPath) . ":" . $line;
            
            case 'phpstorm':
                // phpstorm://open?file=c:/Users/.../file.php&line=10
                return "phpstorm://open?file=" . urlencode($realPath) . "&line=" . $line;
            
            case 'sublime':
                return "subl://open?url=file://" . urlencode($realPath) . "&line=" . $line;
                
            default:
                return null;
        }
    }

    /**
     * Configure les couleurs de PHP pour matcher Tailwind CSS
     */
    private function setThemeColors(): void
    {
        // Commentaires : Slate 500
        ini_set("highlight.comment", "#64748b"); 
        // Mots clés (default, if, function) : Violet 400
        ini_set("highlight.keyword", "#c084fc"); 
        // Chaînes de caractères : Vert 400
        ini_set("highlight.string", "#4ade80"); 
        // Texte standard (variables, propriétés) : Slate 200
        ini_set("highlight.default", "#e2e8f0"); 
        // Balises HTML (si mixté) : Blanc
        ini_set("highlight.html", "#ffffff"); 
    }

    private function getRequestContext(): array
    {
        // 1. Infos de base HTTP
        $context = [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'url'    => $_SERVER['REQUEST_URI'] ?? 'Unknown',
            'ip'     => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        ];

        // 2. Infos spécifiques à Tonka Router
        // On suppose que Tonka stocke la route courante quelque part (ex: en statique ou dans un container)
        // Adapte cette partie selon ton architecture réelle
        if (class_exists('Tonka\Router')) {
             // Exemple fictif : tu dois adapter selon ton API
             // $route = \Tonka\Router::getCurrentRoute();
             // if ($route) {
             //     $context['routeName'] = $route->getName();
             //     $context['handler'] = $route->getHandler(); // Ex: "HomeController@index"
             // }
        }
        
        // 3. Paramètres de la requête (Query Params)
        if (!empty($_GET)) {
            $context['query'] = $_GET;
        }

        return $context;
    }
}