<?php
namespace Clicalmani\Foundation\Resources;

class ErrorRenderer
{
    // Configuration
    protected $ide = 'vscode'; 

    public function render(\Throwable $exception)
    {
        $data = [
            'title'    => get_class($exception),
            'message'  => $exception->getMessage(),
            'file'     => $exception->getFile(),
            'line'     => $exception->getLine(),
            'trace'    => $this->formatTrace($exception),
            'snippet'  => $this->getSnippet($exception->getFile(), $exception->getLine()),
            'editorLink' => $this->createEditorLink($exception->getFile(), $exception->getLine()),
            'request'  => $this->getRequestContext(),
        ];

        if ( ! \Clicalmani\Foundation\Support\Facades\Route::isApi() ) {
            return \Clicalmani\Foundation\Support\Facades\Response::status(500)->view('500', ['error' => $data]);
        }

        return response()->status(500)->json($data);
    }

    private function getSnippet(string $file, int $line, int $context = 10): array
    {
        if (!file_exists($file)) return [];

        $this->setThemeColors();

        $content = file_get_contents($file);
        
        $lines = explode("\n", $content);
        $start = max(0, $line - $context - 1);
        $length = ($context * 2) + 1;
        $snippetLines = array_slice($lines, $start, $length, true);
        $xdt = xdt();

        $result = [];
        foreach ($snippetLines as $num => $codeLine) {
            $xdt->load(\highlight_string("<?php " . $codeLine, true), true, true);
            $first = $xdt->select('code > span:first');
            $first->html(str_replace('&lt;?php', '', $first->html()));
            $result[$num + 1] = $xdt->getDocumentRootElement()->html();
        }

        return $result;
    }

    private function formatTrace(\Throwable $exception): array
    {
        $trace = $exception->getTrace();
        $formatted = [];

        foreach ($trace as $index => $item) {
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
        return count($args) > 0 ? '...' : '';
    }

    private function createEditorLink(string $file, int $line): ?string
    {
        if (!env('APP_DEBUG', true) || !$this->ide) {
            return null;
        }

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
    
    private function setThemeColors(): void
    {
        // Comments : Slate 500
        ini_set("highlight.comment", "#64748b"); 
        // Keywords (functino, if, ...): Violet 400
        ini_set("highlight.keyword", "#c084fc"); 
        // String: Green 400
        ini_set("highlight.string", "#4ade80"); 
        // Standard Text (variables, proper) : Slate 200
        ini_set("highlight.default", "#e2e8f0"); 
        // HTML Tags (if mixet): White
        ini_set("highlight.html", "#ffffff"); 
    }

    private function getRequestContext(): array
    {
        $context = [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'url'    => $_SERVER['REQUEST_URI'] ?? 'Unknown',
            'ip'     => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        ];
        
        /** @var \Clicalmani\Routing\Route */
        if ($route = \Clicalmani\Routing\Memory::currentRoute()) {
            $context['routeName'] = $route->name;
            $context['handler'] = $route->action;
            $context['query'] = $_GET;
        }

        return $context;
    }
}