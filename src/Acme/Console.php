<?php
namespace Clicalmani\Core\Acme;

use Clicalmani\Core\Filesystem\DirectoryScanner;
use Clicalmani\Core\Support\Facades\DB;
use Clicalmani\XPower\XDTNodeList;

/**
 * Class Console
 * 
 * Manages framework database operations including topological migrations,
 * table drops, database seeding, SQL exports, and routine creation.
 * 
 * @package Clicalmani\Core\Acme
 * @author @clicalmani
 */
class Console
{
    /**
     * Migrated Tables
     * 
     * @var \Clicalmani\XPower\XDTNodeList[]
     */
    private $migratedTables = [];

    /**
     * Dropped nodes
     * 
     * @var \Clicalmani\XPower\XDTNodeList[]
     */
    private $dropped = [];

    /**
     * Console output object
     * 
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    private static $output;

    /**
     * Manifest name
     * 
     * @var string
     */
    private string $manifest = 'manifest';

    /**
     * Dump file name
     * 
     * @var ?string
     */
    private ?string $dump_file = NULL;
    
    /**
     * Migrate each node in the provided file.
     * 
     * @param string $filename
     * @return void
     */
    public function migrate(string $filename) : void
    {
        $this->maybeGenerateManifest($filename);

        $xdt = xdt();
        $xdt->setDirectory(database_path('/manifests'));
        $xdt->connect($filename, true, true);

        /** @var \Clicalmani\XPower\XDTNodeList[] */
        $nodes = [];
        
        DB::getInstance()->getPdo()->query('SET FOREIGN_KEY_CHECKS = ' . (int)config('database.strict'));

        foreach ($xdt->getDocumentRootElement()->children('entity') as $node) {
            $node = $xdt->parse($node);
            $nodes[] = $node;
        }

        $this->processMigrate($nodes);

        // ── Alter ──────────────────────────────────────────────────────
        foreach ($nodes as $node) {
            if (!$node->hasChildren('alter')) continue;
            $this->alterTable($node);
        }

        // ── Updates ──────────────────────────────────────────────────────
        $updates = $xdt->getDocumentRootElement()->children('updates > entity');

        if ($updates->length) {
            $nodes = [];
            foreach ($updates as $node) {
                $nodes[] = $xdt->parse($node);
            }
            $this->processMigrate($nodes);
        }

        $xdt->close();
        $this->migratedTables = [];
        DB::getInstance()->getPdo()->query('SET FOREIGN_KEY_CHECKS = 1');
    }

    /**
     * Drop all tables from the current database.
     * 
     * @param string $filename Migration file
     * @return void
     */
    public function clearDB(string $filename) : void
    {
        $this->maybeGenerateManifest($filename);

        $xdt = xdt();
        $xdt->setDirectory(database_path('/manifests'));
        $xdt->connect($filename, true, true);

        /** @var \Clicalmani\XPower\XDTNodeList[] */
        $nodes = [];

        foreach ($xdt->getDocumentRootElement()->children('entity') as $node) {
            $node = $xdt->parse($node);
            $nodes[] = $node;
        }

        $this->processDrop($nodes);

        $xdt->close();
        $this->dropped = [];
    }

    /**
     * Make a fresh database migration.
     * 
     * @param string $filename
     * @param ?bool $seed Run database seeders
     * @param ?bool $execute_routines Execute database routines
     * @return void
     */
    public function migrateFresh(string $filename, ?bool $seed = true, ?bool $execute_routines = true) : void
    {
        $this->clearDB($filename);
        $this->migrate($filename);
        
        if (TRUE === $seed) $this->seed(null, $filename);

        if (TRUE === $execute_routines) {
            $this->routineFunctions();
            $this->routineProcs();
            $this->routineViews();
        }
    }

    /**
     * Export database migration.
     * 
     * @param string $filename File to export to
     * @return void
     */
    public function exportSQL(string $filename) : void
    {
        $this->setOutput(NULL);
        $this->setDumpFile($filename);
        $this->migrate( time() );
    }

    /**
     * Output setter.
     * 
     * @param \Symfony\Component\Console\Output\OutputInterface|null $output
     * @return void
     */
    public function setOutput(\Symfony\Component\Console\Output\OutputInterface|null $output) : void
    {
        self::$output = $output;
    }

    /**
     * Dump file setter.
     * 
     * @param ?string $filename File name
     * @return void
     */
    public function setDumpFile(?string $filename = NULL) : void
    {
        $this->dump_file = $filename;
    }

    /**
     * Seed the default database.
     * 
     * @param ?string $class
     * @param ?string $filename Migration file
     * @return bool
     */
    public function seed(?string $class = null, ?string $filename = null) : bool
    {
        if (NULL !== $class) {
            require_once "{$class}.php";
            $seeder = new $class;

            $this->writeln('Running ' . $class, true, 'comment');

            if ( $this->runSeed($seeder) ) {
                $this->writeln('Success', true, 'info');

                return true;
            }

            $this->writeln('Failure', true, 'error');

            return false;
        }
        
        if (NULL === $filename) return false;

        $xdt = xdt();
        $xdt->setDirectory(database_path('/manifests'));
        $xdt->connect($filename, true, true);

        try {
            foreach ($xdt->getDocumentRootElement()->children('seeder') as $node) {
                $node = $xdt->parse($node);
                $classNs = $node->attr('name');
                $seeder = new $classNs;
    
                $this->writeln('Running ' . $classNs, true, 'comment');
    
                if ( $this->runSeed($seeder) ) {
                    $this->writeln('Success', true, 'info');
                } else {
                    $this->writeln('Failure', true, 'error');
                }
            }

            return true;

        } catch(\PDOException $e) {
            $this->writeln($e->getMessage(), false, 'error');
            return false;
        }
    }

    /**
     * Create a function routine.
     * 
     * @return bool
     */
    public function routineFunctions() : bool
    {
        try {
            $scanner = new DirectoryScanner(
                rootPath: app()->databasePath('routines/functions'),
                extensions: [''],
            );

            $this->writeln('Migrating routine functions ...', true, 'comment');

            foreach ($scanner->files() as $pathname) { 
                $function = require $pathname;
                $filename = basename($pathname);
                $this->writeln("Creating $filename ...", true, 'comment');
                $this->dropRoutine($filename, 'FUNCTION');
                
                if (false == $this->create($function)) {
                    $this->writeln('Failure', true, 'error');
                } else $this->writeln('Success', true, 'info');
            }

            return true;

        } catch(\PDOException $e) {
            $this->writeln($e->getMessage(), false, 'error');
            return false;
        }
    }

    /**
     * Create procedure routine.
     * 
     * @return bool
     */
    public function routineProcs() : bool
    {
        try {
            $scanner = new DirectoryScanner(
                rootPath: app()->databasePath('routines/procedures'),
                extensions: [''],
            );

            $this->writeln('Migrating stored procedures ...', true, 'comment');

            foreach ($scanner->files() as $pathname) { 
                $function = require $pathname;
                $filename = basename($pathname);
                $this->writeln("Creating $filename ...", true, 'comment');
                $this->dropRoutine($filename, 'PROCEDURE', true, 'comment');
                
                if (false == $this->create($function)) {
                    $this->writeln('Failure', true, 'error');
                } else $this->writeln('Success', true, 'info');
            }

            return true;

        } catch(\PDOException $e) {
            $this->writeln($e->getMessage(), false, 'error');
            return false;
        }
    }

    /**
     * Create view routine.
     * 
     * @return bool
     */
    public function routineViews() : bool
    {
        try {
            $scanner = new DirectoryScanner(
                rootPath: app()->databasePath('routines/views'),
                extensions: [''],
            );

            $this->writeln('Migrating routine views ...', true, 'comment');

            foreach ($scanner->files() as $pathname) { 
                $function = require $pathname;
                $filename = basename($pathname);
                $this->writeln("Creating $filename ...", true, 'comment');
                $this->dropRoutine($filename, 'VIEW');
                
                if (false == $this->create($function)) {
                    $this->writeln('Failure', true, 'error');
                } else $this->writeln('Success', true, 'info');
            }

            return true;

        } catch(\PDOException $e) {
            $this->writeln($e->getMessage(), false, 'error');
            return false;
        }
    }

    /**
     * Create a symbolic link.
     * 
     * @param string $target Target of the link
     * @param string $link Link name
     * @return bool
     */
    public function link(string $target, string $link) : bool
    {
        return symlink($target, $link);
    }

    /**
     * Alter an existing table structure.
     * 
     * @param \Clicalmani\XPower\XDTNodeList $node
     * @return void
     */
    private function alterTable(XDTNodeList $node): void
    {
        /** @var class-string<\Clicalmani\Database\Factory\Models\Elegant> */
        $modelClass = $node->attr('model');
        $definitions = base64_decode($node->find('alter')->text() ?? '');
        $model = new $modelClass;

        $table = $model->getTable()->name();
        $query = $model->newQuery();
        
        if ($definitions) {
            try {
                $this->writeln(sprintf('Altering %s%s', env('DB_TABLE_PREFIX', ''), $table), true, 'comment');
                $query->set('table', $table);
                $query->set('definition', [$definitions]);
                $query->set('type', \Clicalmani\Database\DBQuery::ALTER);
                $query->exec();
                $this->writeln('Success', true, 'info');
            } catch (\PDOException $e) {
                $this->writeln(sprintf('An error occurred while altering %s: %s', $model->getTable()->name(), $e->getMessage()), true, 'error');
            }
        }
    }

    /**
     * Generate migration manifest file.
     * 
     * @param string $filename File name
     * @return bool TRUE on success, FALSE otherwise.
     */
    private function generateManifest(string $filename) : bool
    {
        $manifests_path = database_path('/manifests');

        $scanner = new DirectoryScanner(
            rootPath: app()->appPath('Models'),
            baseNamespace: 'App\\Models',
        );

        $xdt = xdt();
        $xdt->setDirectory($manifests_path);
        $xdt->newFile("$filename.xml", '<migration></migration>');
        $xdt->connect($filename, true, true);

        $tables = []; // Database tables

        /**
         * Walkthrough models
         * Keep track of each model and its entity.
         * 
         * @var class-string<\Clicalmani\Database\Factory\Models\Elegant> 
         */
        foreach ($scanner->classes() as $modelClass) {
            $model = new $modelClass;
            $entity = $model->getEntity();

            $tables[$model->getTable()->name()] = $modelClass;
            $definitions = null; // Alter definitions

            // ── Alter ──────────────────────────────────────────────────────
            // Verify if the Entity has an alter attribute
            if ($attributes = (new \ReflectionClass($entity))->getAttributes(\Clicalmani\Database\Factory\AlterOption::class)) {
                try {
                    $definitions = $entity->alter(new \Clicalmani\Database\Factory\AlterOption);
                } catch(\Exception $e) {
                    $this->writeln($e->getMessage(), true, 'error');
                }
            }

            if ($definitions) $definitions = '<alter>' . base64_encode($definitions) . '</alter>';

            $xdt->getDocumentRootElement()->append('<entity model="' . $modelClass . '">' . get_class($entity) . $definitions . '</entity>');
        }

        /**
         * Establish relationships
         * Each entity must have its dependencies migrated before migrating itself.
         * 
         * @var \DOMNode $node
         */
        foreach ($xdt->select('entity') as $node) {
            $node = $xdt->parse($node);
            /** @var \Clicalmani\Database\Factory\Models\Elegant */
            $modelClass = $node->attr('model');
            $model = new $modelClass;
            $entity = $model->getEntity();
            $entity->setModel($model);

            if ($attributes = (new \ReflectionClass($entity))->getAttributes(\Clicalmani\Database\Factory\Index::class)) {

                foreach ($attributes as $attribute) {

                    $instance = $attribute->newInstance();
                    $refs = $instance->references;

                    if ($table = @$refs['table']) {
                        if (false == $node->hasChildren('dependences')) {
                            $node->append('<dependences></dependences>');
                        }

                        $depModelClass = $tables[$table];

                        // Avoid referencing a model by itself
                        if ($node->attr('model') !== $depModelClass) 
                            $node->children()->first()->append('<entity model="' . $depModelClass . '">' . get_class(( new $depModelClass )->getEntity()) . '</entity>');
                    }
                }
            }
        }

        $scanner = new DirectoryScanner(
            rootPath: app()->databasePath('seeders'),
            baseNamespace: 'Database\\Seeders',
        );

        $seeders = [];
        
        foreach ($scanner->classes() as $classNs) { 
            if ($attributes = (new \ReflectionClass($classNs))->getAttributes(\Clicalmani\Database\Factory\Priority::class)) {
                $attribute = $attributes[0];
                $instance  = $attribute->newInstance();
                $seeders[(int)$instance->priority] = $classNs;
            }
        }

        ksort($seeders);

        foreach ($seeders as $seeder) {
            $xdt->getDocumentRootElement()->append('<seeder name="' . $seeder . '"/>');
        }
        
        return $xdt->close();
    }

    /**
     * Run the specified seeder.
     * 
     * @param \Clicalmani\Database\Seeders\Seeder $seeder
     * @return bool TRUE on success, FALSE otherwise.
     */
    private function runSeed(\Clicalmani\Database\Seeders\Seeder $seeder) : bool
    {
        try {
            $seeder->run();
            return true;
        } catch(\PDOException $e) {
            $this->writeln($e->getMessage(), false, 'error');
            return false;
        }
    }

    /**
     * Writes a message to the output and adds a newline at the end.
     * 
     * @param ?string $message
     * @param ?bool $format Format output
     * @param ?string $format_tye
     * @return void
     */
    private function writeln(?string $message = '', ?bool $format = true, ?string $format_tye = null) : void
    {
        if (self::$output) {
            self::$output->writeln($format ? $this->formatOutput($message, $format_tye): $message);
        } else {
            printf("%s", $format ? $this->formatOutput($message, $format_tye): $message);
            print("<br/>");
        }
    }

    /**
     * Run a CREATE FUNCTION routine.
     * 
     * @param callable $function
     * @return bool
     */
    private function create(callable $function) : bool
    {
        try {
            $sql = str_replace('%DB_TABLE_PREFIX%', $_ENV['DB_TABLE_PREFIX'], $function());
            DB::getInstance()->query($sql);
            return true;
        } catch(\PDOException $e) {
            $this->writeln($e->getMessage(), false, 'error');
            return false;
        }
    }

    /**
     * Drop the specified routine.
     * 
     * @param string $name
     * @param ?string $type
     * @return void
     */
    private function dropRoutine(string $name, string $type = 'FUNCTION') : void
    {
        DB::getInstance()->query("DROP $type IF EXISTS `$name`");
    }

    /**
     * Calculates the migration order using topological sorting (Kahn's algorithm).
     *
     * Each node is processed exactly once using the canonical node indexed by model
     * (never via a parsed copy from another node's <dependences>), and true circular
     * dependencies are detected with precision (isolating the exact models involved).
     * 
     * @param \Clicalmani\XPower\XDTNodeList[] $nodes
     * @return \Clicalmani\XPower\XDTNodeList[] Nodes in resolved execution order
     */
    private function topologicalOrder(array $nodes): array
    {
        // Model index: guarantees referencing the exact SAME node instance,
        // never a parsed XML copy from a dependency declaration.
        $byModel = [];
        foreach ($nodes as $node) {
            $byModel[$node->attr('model')] = $node;
        }

        // Build adjacency graph: model => [direct dependencies]
        //
        // MANDATORY deduplication: manifests may declare the same dependency multiple times
        // for a model (e.g., <dependences> listing <entity model="...Branch"> twice).
        // Without deduplication, array_diff() removes all occurrences of a value at once
        // while $inDegree is only decremented once per resolution — preventing $inDegree from
        // reaching zero, causing nodes to remain stuck and falsely marked as circular.
        $dependencies = [];
        foreach ($nodes as $node) {
            $model = $node->attr('model');
            $deps  = []; // Associative set: deduplicates by structure

            if ($node->hasChildren('dependences')) {
                foreach ($node->find('dependences > entity') as $dep) {
                    $depModel = xdt()->parse($dep)->attr('model');

                    // Ignore self-references and dependencies outside current batch
                    // (e.g., during "updates" migrations containing only a subset)
                    if ($depModel !== $model && isset($byModel[$depModel])) {
                        $deps[$depModel] = true;
                    }
                }
            }

            // array_keys() returns contiguous 0..n-1 integer keys, which is
            // required for extractCycles() to reliably access index [0] after array_diff().
            $dependencies[$model] = array_keys($deps);
        }

        // ── Kahn's algorithm ──────────────────────────────────────────
        $inDegree = array_map('count', $dependencies);
        $queue    = array_keys(array_filter($inDegree, fn($d) => $d === 0));
        $ordered  = [];

        while ($queue) {
            $current   = array_shift($queue);
            $ordered[] = $byModel[$current];

            foreach ($dependencies as $model => $deps) {
                if (in_array($current, $deps, true)) {
                    // array_values() re-indexes keys after array_diff() leaves gaps —
                    // without this, missing index [0] silently breaks traversal in extractCycles().
                    $dependencies[$model] = array_values(array_diff($deps, [$current]));

                    if (0 === --$inDegree[$model]) {
                        $queue[] = $model;
                    }
                }
            }
        }

        // Any unresolved node belongs to a TRUE circular dependency cycle.
        if (count($ordered) < count($nodes)) {
            $resolvedModels = array_map(fn($n) => $n->attr('model'), $ordered);
            $stuck = array_diff(array_keys($byModel), $resolvedModels);

            // At this point, $dependencies only contains unresolved edges for stuck nodes —
            // representing the exact cyclic subgraph used to extract real dependency paths.
            $this->handleCircularDependency($stuck, $dependencies, $byModel);

            // Append stuck cyclic nodes sequentially to prevent blocking non-cyclic execution.
            foreach ($stuck as $model) {
                $ordered[] = $byModel[$model];
            }
        }

        return $ordered;
    }

    /**
     * Handles circular dependencies detected during topological sorting.
     *
     * Under strict mode (FK checks enabled), circularities cannot be automatically
     * resolved without explicit user intervention (e.g., deferred constraint or #[AlterOption]):
     * throws an exception to prevent lower-level SQL errors.
     * Under non-strict mode, FK checks are disabled during execution: emits a console warning
     * and continues execution.
     *
     * @param string[] $stuck Model names involved in circular dependency cycles
     * @param array<string, string[]> $dependencies Residual adjacency graph (model => unresolved dependencies)
     * @param array<string, \Clicalmani\XPower\XDTNodeList> $byModel Map of model FQCN to node instances
     * @return void
     * @throws \RuntimeException When running under strict database configuration
     */
    private function handleCircularDependency(array $stuck, array $dependencies, array $byModel): void
    {
        $cycles = $this->extractCycles($stuck, $dependencies);

        // Fallback: if cycle extraction returns empty, fall back to the entire stuck array.
        if (empty($cycles)) {
            $cycles = [$stuck];
        }

        foreach ($cycles as $cycle) {
            $path = $this->formatCyclePath($cycle);

            if (config('database.strict')) {
                throw new \RuntimeException(
                    sprintf(
                        "Circular dependency detected between models: %s. --> Strict resolution order impossible. --> Add a deferred constraint or use #[AlterOption] to break the cycle.",
                        $path
                    )
                );
            }

            $this->writeln(
                sprintf(
                    "[WARNING] Circular dependency detected between models: %s. --> Strict resolution order impossible. --> Permissive fallback mode active: check foreign key constraints.",
                    $path
                ),
                true,
                'question'
            );
        }
    }

    /**
     * Extracts exact cycle paths from the residual subgraph left by Kahn's algorithm.
     *
     * Traverses dependency edges from unresolved nodes until encountering an already visited node,
     * isolating the exact cyclic loop.
     *
     * @param string[] $stuck Model names involved in circular dependency cycles
     * @param array<string, string[]> $dependencies Residual graph
     * @return array<int, string[]> List of extracted cycles closed upon themselves
     */
    private function extractCycles(array $stuck, array $dependencies): array
    {
        $cycles  = [];
        $visited = [];

        foreach ($stuck as $start) {
            if (isset($visited[$start])) continue;

            $path    = [];
            $indexOf = [];
            $current = $start;
            $deadEnd = false;

            while (!isset($indexOf[$current])) {
                $indexOf[$current] = count($path);
                $path[]             = $current;
                $visited[$current]  = true;

                $next = $dependencies[$current][0] ?? null;

                if (null === $next) {
                    // Dead-end node with no remaining dependencies: not part of a cycle.
                    $deadEnd = true;
                    break;
                }

                $current = $next;
            }

            // Record cycle only if traversal re-visited a node in the current path.
            if (!$deadEnd && isset($indexOf[$current])) {
                $cycle   = array_slice($path, $indexOf[$current]);
                $cycle[] = $current; // Close cycle loop
                $cycles[] = $cycle;
            }
        }

        return $cycles;
    }

    /**
     * Formats a cycle path into a human-readable representation.
     * Example output: "[User] <--> [Team]" or "[A] --> [B] --> [C] --> [A]"
     *
     * @param string[] $cycle Array of model names closing on themselves (last element equals first)
     * @return string Formatted cycle string
     */
    private function formatCyclePath(array $cycle): string
    {
        $names = array_map([$this, 'shortModelName'], $cycle);

        // Direct two-node cycle A -> B -> A: display as bidirectional
        if (count($names) === 3 && $names[0] === $names[2]) {
            return sprintf('[%s] <--> [%s]', $names[0], $names[1]);
        }

        return '[' . implode('] --> [', $names) . ']';
    }

    /**
     * Extracts short class name (without namespace) for console formatting.
     *
     * @param string $model Fully qualified class name
     * @return string Short class name
     */
    private function shortModelName(string $model): string
    {
        $parts = explode('\\', $model);
        return end($parts);
    }

    /**
     * Migration process: resolves topological execution order then executes each node once.
     * 
     * @param \Clicalmani\XPower\XDTNodeList[] $nodes
     * @return void
     */
    private function processMigrate(array $nodes) : void
    {
        foreach ($this->topologicalOrder($nodes) as $node) {
            if (!$this->isMigrated($node)) {
                $this->execute($node, 'migrate');
            }
        }
    }

    /**
     * Verify if a node is migrated.
     * 
     * @param \Clicalmani\XPower\XDTNodeList $node
     * @return bool TRUE on success, FALSE otherwise.
     */
    private function isMigrated(XDTNodeList $node) : bool
    {
        /** @var \Clicalmani\Database\Factory\Models\Elegant */
        $modelClass = $node->attr('model');
        $model = new $modelClass;
        $table1 = $model->getTable()->name();

        foreach ($this->migratedTables as $n) {
            /** @var \Clicalmani\Database\Factory\Models\Elegant */
            $modelClass = $n->attr('model');
            $model = new $modelClass;
            $table2 = $model->getTable()->name();

            if ($table1 == $table2) return true;
        }

        return false;
    }

    /**
     * Execute a migration or drop action on a node.
     * 
     * @param \Clicalmani\XPower\XDTNodeList $node
     * @param ?string $command 'migrate' or 'drop'
     * @return void
     */
    private function execute(XDTNodeList $node, ?string $command = 'migrate') : void
    {
        /** @var string */
        $modelClass = $node->attr('model');
        /** @var \Clicalmani\Database\Factory\Models\Elegant */
        $model = new $modelClass;
        $entity = $model->getEntity();
        $entity->setModel($model);

        $table = $model->getTable()->name();

        $check = ( $command === 'migrate' ) ? $this->isMigrated($node): $this->isDroped($node);

        if (FALSE === $check) $this->writeln(( ($command === 'migrate') ? 'Migrating ': 'Dropping ' ) . env('DB_TABLE_PREFIX', '') . $table, true, 'comment');

        try {

            if (FALSE === $check) {
                if (NULL === $this->dump_file) $entity->{$command}();
                else $entity->{$command}(false, $this->dump_file);

                $this->writeln('Success', true, 'info');
                
                if ( $command === 'migrate' ) $this->migratedTables[] = $node;
                else $this->dropped[] = $node;
            }

        } catch (\PDOException $e) {

            if ( config('database.strict') ) throw new \Exception($e->getMessage(), (int)$e->getCode(), $e);
            
            else
            /**
             * |------------------------------------------------------------------------
             * | SQL Error Codes
             * |------------------------------------------------------------------------
             * | 1217 Occurs when a user tries to modify or delete a table that is part
             * | of a foreign key relationship, without addressing the dependency first.
             * | 23000 Integrity constraint violation
             */
            if (in_array($e->getCode(), ['HY000', '1217', '23000'])) $this->writeln($e->getMessage(), true, 'error'); 
            else throw new \Exception($e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * Format output message for console display.
     * 
     * @param string $message
     * @param ?string $type info, comment, error, question
     * @return string
     */
    private function formatOutput(string $message, ?string $type = null) : string
    {
        if ($type) $message = "<$type>$message</$type>";
        return str_pad("$message ", 100, '-');
    }

    /**
     * Dropping process: resolves inverse topological order (dependents before dependencies)
     * then executes each drop action once.
     * 
     * @param \Clicalmani\XPower\XDTNodeList[] $nodes
     * @return void
     */
    private function processDrop(array $nodes) : void
    {
        DB::getInstance()->getPdo()->query('SET FOREIGN_KEY_CHECKS = 0');

        foreach (array_reverse($this->topologicalOrder($nodes)) as $node) {
            if (!$this->isDroped($node)) {
                $this->execute($node, 'drop');
            }
        }

        DB::getInstance()->getPdo()->query('SET FOREIGN_KEY_CHECKS = 1');
    }

    /**
     * Verify if a node table has been dropped.
     * 
     * @param \Clicalmani\XPower\XDTNodeList $node
     * @return bool TRUE on success, FALSE otherwise.
     */
    private function isDroped(XDTNodeList $node) : bool
    {
        /** @var \Clicalmani\Database\Factory\Models\Elegant */
        $modelClass = $node->attr('model');
        $model = new $modelClass;
        $table1 = $model->getTable()->name();

        foreach ($this->dropped as $n) {
            /** @var \Clicalmani\Database\Factory\Models\Elegant */
            $modelClass = $n->attr('model');
            $model = new $modelClass;
            $table2 = $model->getTable()->name();

            if ($table1 == $table2) return true;
        }

        return false;
    }

    /**
     * Generate manifest file if it does not already exist.
     * 
     * @param string $filename
     * @return void
     */
    private function maybeGenerateManifest(string $filename) : void
    {
        /** @var string */
        $manifests_path = database_path('/manifests');
        if ( !file_exists("$manifests_path/$filename.xml") ) $this->generateManifest($filename);
    }
}