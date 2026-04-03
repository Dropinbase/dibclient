<?php

declare(strict_types=1);

namespace DropinScript\Mcp;

use RuntimeException;

/**
 * Manages the catalog of AI task instruction files in the aiInfo directory.
 * Builds a searchable index on first access, caches it for the lifetime of the
 * process, and validates manual index entries against the filesystem.
 */
final class AiTaskInfoService
{
    /** @var array<int, string> */
    private static $knownExtensions = array('.txt', '.php', '.ts', '.html', '.js', '.css', '.json');

    /** @var array<string, bool> Lookup map for stop-word filtering (O(1) isset). */
    private static $stopWords = array(
        'a' => true, 'an' => true, 'the' => true, 'to' => true, 'in' => true,
        'on' => true, 'at' => true, 'for' => true, 'of' => true, 'or' => true,
        'and' => true, 'is' => true, 'are' => true, 'how' => true, 'it' => true,
        'its' => true, 'be' => true, 'by' => true, 'can' => true, 'etc' => true,
        'eg' => true, 'that' => true, 'this' => true, 'with' => true,
    );

    /** @var string */
    private $aiInfoDirectory;

    /** @var string */
    private $indexFilePath;

    // --- Cached catalog (built lazily on first access) ---

    /** @var array<int, array<string, mixed>> Flat list of every entry */
    private $allEntries = array();

    /** @var array<int, array<string, mixed>> Top-level entries only */
    private $topLevelEntries = array();

    /** @var array<string, array<string, mixed>> Keyed by relative file path */
    private $entriesByFile = array();

    /** @var array<string, array<string, mixed>> Keyed by lowercase taskName */
    private $entriesByName = array();

    /** @var array<string, array<int, array<string, mixed>>> Parent taskName => sub-task entries */
    private $subTaskMap = array();

    /** @var array<int, string> Validation warnings from catalog build */
    private $warnings = array();

    public function __construct(string $aiInfoDirectory, string $indexFileName)
    {
        $this->aiInfoDirectory = rtrim($aiInfoDirectory, '/\\');
        $this->indexFilePath = $this->aiInfoDirectory . DIRECTORY_SEPARATOR . $indexFileName;
        $this->buildCatalog();
    }

    // ======================================================================
    // Public API
    // ======================================================================

    /**
     * List top-level tasks from the main index.
     *
     * @return array<string, mixed>
     */
    public function listTasks(): array
    {
        return array(
            'indexFile' => basename($this->indexFilePath),
            'indexPath' => $this->toRelativePath($this->indexFilePath),
            'indexContent' => $this->readFile($this->indexFilePath),
            'taskCount' => count($this->topLevelEntries),
            'tasks' => array_map(function (array $e): array {
                return array(
                    'taskName' => $e['taskName'],
                    'description' => $e['description'],
                    'file' => $e['file'],
                    'parent' => null,
                    'hasSubTasks' => $e['hasSubTasks'],
                );
            }, $this->topLevelEntries),
        );
    }

    /**
     * Return the raw content of the main task index file.
     */
    public function getIndexContent(): string
    {
        return $this->readFile($this->indexFilePath);
    }

    /**
     * Search the full catalog (top-level + sub-tasks) for the best match.
     *
     * @return array{taskName:string, description:string, file:string, parent:string|null, hasSubTasks:bool}|null
     */
    public function findMatchingTask(string $query): ?array
    {
        $queryLower = strtolower($query);
        $queryKeywords = $this->extractKeywords($query);
        if ($queryKeywords === array()) {
            return null;
        }

        $bestEntry = null;
        $bestScore = 0;

        foreach ($this->allEntries as $entry) {
            $score = $this->scoreMatch($entry, $queryLower, $queryKeywords);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestEntry = $entry;
            }
        }

        return $bestScore >= 3 ? $this->toPublicEntry($bestEntry) : null;
    }

    /**
     * Find the best matching sub-task within a specific parent task.
     *
     * @return array{taskName:string, description:string, file:string, parent:string|null, hasSubTasks:bool}|null
     */
    public function findBestSubTask(string $parentTaskName, string $query): ?array
    {
        $subTasks = isset($this->subTaskMap[$parentTaskName])
            ? $this->subTaskMap[$parentTaskName]
            : array();
        if ($subTasks === array()) {
            return null;
        }

        $queryLower = strtolower($query);
        $queryKeywords = $this->extractKeywords($query);
        if ($queryKeywords === array()) {
            return null;
        }

        $bestEntry = null;
        $bestScore = 0;

        foreach ($subTasks as $entry) {
            $score = $this->scoreMatch($entry, $queryLower, $queryKeywords);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestEntry = $entry;
            }
        }

        return $bestScore >= 3 ? $this->toPublicEntry($bestEntry) : null;
    }

    /**
     * Get full instructions for a specific task by name or file path.
     * For parent tasks with sub-tasks: includes sub-task metadata list (no file contents).
     *
     * @param array<string, mixed> $arguments
     * @return array<string, mixed>
     */
    public function getTaskInstructions(array $arguments): array
    {
        $taskFile = isset($arguments['taskFile']) ? trim((string) $arguments['taskFile']) : '';
        $taskName = isset($arguments['taskName']) ? trim((string) $arguments['taskName']) : '';

        if ($taskFile === '' && $taskName === '') {
            throw new RuntimeException('Provide either `taskFile` or `taskName`.');
        }

        $entry = null;
        if ($taskFile !== '') {
            $entry = $this->lookupByFile($taskFile);
            if ($entry === null) {
                throw new RuntimeException('Task file `' . $taskFile . '` not found in catalog.');
            }
        } else {
            $entry = $this->lookupByName($taskName);
            if ($entry === null) {
                throw new RuntimeException('Task name `' . $taskName . '` not found in catalog.');
            }
        }

        $taskPath = $this->resolvePath($entry['file']);

        $result = array(
            'taskName' => $entry['taskName'],
            'description' => $entry['description'],
            'file' => $entry['file'],
            'path' => $this->toRelativePath($taskPath),
            'parent' => $entry['parent'],
            'instructions' => $this->readFile($taskPath),
        );

        // Include sub-task metadata (not file contents) for parent tasks
        if ($entry['hasSubTasks'] && isset($this->subTaskMap[$entry['taskName']])) {
            $result['subTasks'] = array_map(function (array $sub): array {
                return array(
                    'taskName' => $sub['taskName'],
                    'file' => $sub['file'],
                    'description' => $sub['description'],
                );
            }, $this->subTaskMap[$entry['taskName']]);
        }

        return $result;
    }

    /**
     * Efficient content fetch by relative path (no catalog lookup overhead).
     */
    public function getFileContent(string $relativePath): string
    {
        return $this->readFile($this->resolvePath($relativePath));
    }

    /**
     * Validation warnings collected during catalog build.
     *
     * @return array<int, string>
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    // ======================================================================
    // Catalog building
    // ======================================================================

    private function buildCatalog(): void
    {
        $indexContent = $this->readFile($this->indexFilePath);
        $topLevel = $this->parseIndexLines($indexContent);

        if ($topLevel === array()) {
            throw new RuntimeException(
                'No tasks parsed from `' . basename($this->indexFilePath) . '`.'
            );
        }

        foreach ($topLevel as $raw) {
            $entry = array(
                'taskName' => $raw['taskName'],
                'file' => $raw['file'],
                'description' => $raw['description'],
                'keywords' => $this->extractKeywords(
                    $raw['taskName'] . ' ' . $raw['description']
                ),
                'parent' => null,
                'hasSubTasks' => false,
            );

            $subfolderPath = $this->aiInfoDirectory
                . DIRECTORY_SEPARATOR . $raw['taskName'];

            if (is_dir($subfolderPath)) {
                $entry['hasSubTasks'] = true;
                $this->buildSubTasks($entry, $subfolderPath);
            } else {
                // Validate top-level file exists
                if (!is_file($this->resolvePath($entry['file']))) {
                    $this->warnings[] = "Main index references '"
                        . $entry['file'] . "' but file not found";
                }
            }

            $this->registerEntry($entry, true);
        }
    }

    /**
     * @param array<string, mixed> $parentEntry
     */
    private function buildSubTasks(array $parentEntry, string $subfolderPath): void
    {
        // Parse sub-index from the parent task's file
        $parentFilePath = $this->resolvePath($parentEntry['file']);
        $parentContent = is_file($parentFilePath)
            ? (string) @file_get_contents($parentFilePath)
            : '';
        $subIndexEntries = $this->parseIndexLines($parentContent);

        // Scan filesystem for validation
        $filesOnDisk = $this->scanDirectory($subfolderPath);
        $indexedLower = array();

        foreach ($subIndexEntries as $raw) {
            $subRelFile = $parentEntry['taskName'] . '/' . $raw['file'];
            $indexedLower[strtolower($raw['file'])] = true;

            if (!is_file($this->resolvePath($subRelFile))) {
                $this->warnings[] = "Index in '" . $parentEntry['file']
                    . "' references '" . $raw['file'] . "' but file not found in '"
                    . $parentEntry['taskName'] . "/'";
                continue;
            }

            $subEntry = array(
                'taskName' => $raw['taskName'],
                'file' => $subRelFile,
                'description' => $raw['description'],
                'keywords' => $this->extractKeywords(
                    $raw['taskName'] . ' ' . $raw['description']
                ),
                'parent' => $parentEntry['taskName'],
                'hasSubTasks' => false,
            );

            $this->registerEntry($subEntry, false);

            if (!isset($this->subTaskMap[$parentEntry['taskName']])) {
                $this->subTaskMap[$parentEntry['taskName']] = array();
            }
            $this->subTaskMap[$parentEntry['taskName']][] = $subEntry;
        }

        // Warn about files on disk not listed in any index
        foreach ($filesOnDisk as $diskFile) {
            if (!isset($indexedLower[strtolower($diskFile)])) {
                $this->warnings[] = "File '" . $diskFile . "' exists in '"
                    . $parentEntry['taskName'] . "/' but not listed in '"
                    . $parentEntry['file'] . "'";
            }
        }
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function registerEntry(array $entry, bool $isTopLevel): void
    {
        $this->allEntries[] = $entry;
        $this->entriesByFile[$entry['file']] = $entry;
        $this->entriesByName[strtolower($entry['taskName'])] = $entry;

        if ($isTopLevel) {
            $this->topLevelEntries[] = $entry;
        }
    }

    // ======================================================================
    // Index line parsing
    // ======================================================================

    /**
     * Parse index lines from content.  Two modes:
     *  - If content has an INDEX section header: parse `- name` lines in that section.
     *  - Without INDEX header: only parse `- name -> description` lines (strict fallback).
     *
     * @return array<int, array{taskName:string, file:string, description:string}>
     */
    private function parseIndexLines(string $content): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $content);
        if ($lines === false) {
            return array();
        }

        $hasIndexHeader = preg_match(
            '/^\s*#{0,3}\s*INDEX\s*:?\s*$/mi',
            $content
        ) === 1;
        $inIndexSection = false;
        $entries = array();

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Detect INDEX section header
            if (preg_match('/^#{0,3}\s*INDEX\s*:?\s*$/i', $trimmed) === 1) {
                $inIndexSection = true;
                continue;
            }

            // Skip lines before INDEX section when a header exists
            if ($hasIndexHeader && !$inIndexSection) {
                continue;
            }

            if ($trimmed === '') {
                continue;
            }

            if (strpos($trimmed, '- ') !== 0) {
                if ($inIndexSection) {
                    $inIndexSection = false; // Non-list, non-empty line ends section
                }
                continue;
            }

            $name = '';
            $description = '';

            if (preg_match('/^-\s*(.+?)\s*->\s*(.+)$/', $trimmed, $matches)) {
                // `- name -> description` format (always accepted)
                $name = trim($matches[1]);
                $description = trim($matches[2]);
            } elseif ($inIndexSection
                && preg_match('/^-\s*(.+)$/', $trimmed, $matches)
            ) {
                // `- name` without description (only in explicit INDEX section)
                $name = trim($matches[1]);
            } else {
                continue;
            }

            if ($name === '') {
                continue;
            }

            $hasExt = $this->hasKnownExtension($name);
            $taskName = $hasExt
                ? (string) pathinfo($name, PATHINFO_FILENAME)
                : $name;
            $fileName = $hasExt ? $name : $name . '.txt';

            $entries[] = array(
                'taskName' => $taskName,
                'file' => $fileName,
                'description' => $description,
            );
        }

        return $entries;
    }

    // ======================================================================
    // Matching
    // ======================================================================

    /**
     * Extract searchable keywords from text, filtering stop words and short tokens.
     *
     * @return array<int, string>
     */
    private function extractKeywords(string $text): array
    {
        $words = preg_split('/[\s_\-\/\(\),\.]+/', strtolower($text));
        if ($words === false) {
            return array();
        }

        $seen = array();
        $keywords = array();
        foreach ($words as $w) {
            if (strlen($w) > 1 && !isset(self::$stopWords[$w]) && !isset($seen[$w])) {
                $keywords[] = $w;
                $seen[$w] = true;
            }
        }

        return $keywords;
    }

    /**
     * Score how well a catalog entry matches a query.
     *
     * @param array<string, mixed> $entry
     * @param array<int, string> $queryKeywords
     */
    private function scoreMatch(array $entry, string $queryLower, array $queryKeywords): int
    {
        $score = 0;

        // Entry keywords found in query text
        foreach ($entry['keywords'] as $kw) {
            if (strpos($queryLower, (string) $kw) !== false) {
                $score += 3;
            }
        }

        // Query keywords found in entry task name (reverse direction)
        $nameLower = strtolower((string) $entry['taskName']);
        foreach ($queryKeywords as $qw) {
            if (strpos($nameLower, $qw) !== false) {
                $score += 2;
            }
        }

        return $score;
    }

    // ======================================================================
    // Lookups
    // ======================================================================

    /**
     * @return array<string, mixed>|null
     */
    private function lookupByFile(string $file): ?array
    {
        if (isset($this->entriesByFile[$file])) {
            return $this->entriesByFile[$file];
        }

        // Case-insensitive fallback
        $lower = strtolower($file);
        foreach ($this->entriesByFile as $k => $v) {
            if (strtolower($k) === $lower) {
                return $v;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function lookupByName(string $name): ?array
    {
        $key = strtolower($name);
        if (isset($this->entriesByName[$key])) {
            return $this->entriesByName[$key];
        }

        // Try stripping a known extension
        if ($this->hasKnownExtension($name)) {
            $stripped = strtolower((string) pathinfo($name, PATHINFO_FILENAME));
            if (isset($this->entriesByName[$stripped])) {
                return $this->entriesByName[$stripped];
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed>|null $entry
     * @return array{taskName:string, description:string, file:string, parent:string|null, hasSubTasks:bool}|null
     */
    private function toPublicEntry(?array $entry): ?array
    {
        if ($entry === null) {
            return null;
        }

        return array(
            'taskName' => (string) $entry['taskName'],
            'description' => (string) $entry['description'],
            'file' => (string) $entry['file'],
            'parent' => $entry['parent'],
            'hasSubTasks' => (bool) $entry['hasSubTasks'],
        );
    }

    // ======================================================================
    // Filesystem helpers
    // ======================================================================

    /**
     * Resolve a catalog-relative path to an absolute filesystem path.
     */
    private function resolvePath(string $relativePath): string
    {
        return $this->aiInfoDirectory . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    }

    /**
     * List files (not directories) in a directory.
     *
     * @return array<int, string>
     */
    private function scanDirectory(string $dirPath): array
    {
        $entries = @scandir($dirPath);
        if ($entries === false) {
            return array();
        }

        $files = array();
        foreach ($entries as $e) {
            if ($e !== '.' && $e !== '..'
                && is_file($dirPath . DIRECTORY_SEPARATOR . $e)
            ) {
                $files[] = $e;
            }
        }

        return $files;
    }

    private function readFile(string $filePath): string
    {
        if (!is_file($filePath)) {
            throw new RuntimeException('File not found: ' . $filePath);
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new RuntimeException('Unable to read file: ' . $filePath);
        }

        return $content;
    }

    private function hasKnownExtension(string $filename): bool
    {
        $lower = strtolower($filename);
        foreach (self::$knownExtensions as $ext) {
            if (substr($lower, -strlen($ext)) === $ext) {
                return true;
            }
        }
        return false;
    }

    private function toRelativePath(string $absolutePath): string
    {
        $projectRoot = dirname(__DIR__, 2);
        if (strpos($absolutePath, $projectRoot) === 0) {
            $relative = ltrim(substr($absolutePath, strlen($projectRoot)), '/\\');
            return str_replace('\\', '/', $relative);
        }

        return str_replace('\\', '/', $absolutePath);
    }
}
