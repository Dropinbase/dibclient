<?php

declare(strict_types=1);

namespace DropinScript\Mcp;

use RuntimeException;

final class DropinScriptGenerator
{
    /**
     * @var DatabaseSchemaService
     */
    private $databaseSchemaService;

    /**
     * @var AiTaskInfoService
     */
    private $aiTaskInfoService;

    /**
     * Default database ID (from $LOGINDB in configs/Dib.php) used when the
     * user does not specify which database contains the target table.
     *
     * @var int|null
     */
    private $defaultDatabaseId;

    /**
     * Optional McpToolsExtension instance used to fetch app defaults for the resolved
     * database, so they are included directly in the generate response.
     *
     * @var McpToolsExtension|null
     */
    private $mcpToolsExtension;

    public function __construct(DatabaseSchemaService $databaseSchemaService, AiTaskInfoService $aiTaskInfoService, ?int $defaultDatabaseId = null, ?McpToolsExtension $mcpToolsExtension = null)
    {
        $this->databaseSchemaService = $databaseSchemaService;
        $this->aiTaskInfoService = $aiTaskInfoService;
        $this->defaultDatabaseId = $defaultDatabaseId;
        $this->mcpToolsExtension = $mcpToolsExtension;
    }

    /**
     * Gather task instructions, table structure, supplementary guidance, and a
     * template script from a natural-language request. Returns all context the
     * LLM needs to produce the final Dropin Script.
     *
     * @param array<string, mixed> $arguments
     * @return array<string, mixed>
     */
    public function generateFromRequest(array $arguments): array
    {
        $request = isset($arguments['request']) ? trim((string) $arguments['request']) : '';
        $taskType = isset($arguments['taskType']) ? trim((string) $arguments['taskType']) : '';
        $databaseName = isset($arguments['databaseName']) ? trim((string) $arguments['databaseName']) : '';
        $databaseId = $arguments['databaseId'] ?? null;
        $tableName = isset($arguments['tableName']) ? trim((string) $arguments['tableName']) : '';
        $schema = isset($arguments['schema']) ? trim((string) $arguments['schema']) : '';

        $parsed = $this->parseIntent($request);

        if ($taskType === '') {
            $taskType = $parsed['taskType'];
        }
        if ($tableName === '') {
            $tableName = $parsed['tableName'];
        }
        if ($databaseName === '' && $databaseId === null) {
            $databaseName = $parsed['databaseName'];
        }

        // Fall back to the configured default database ($LOGINDB) when the
        // user did not specify a database and it could not be parsed from the
        // natural-language request.
        if ($databaseName === '' && $databaseId === null && $this->defaultDatabaseId !== null) {
            $databaseId = $this->defaultDatabaseId;
        }

        // --- Resolve matching task from the catalog ---
        $matchedEntry = $this->resolveTask($taskType, $request);
        $taskInstructions = null;
        $selectedSubTask = null;
        $availableSubTasks = null;
        $taskName = null;
        $taskFile = null;

        if ($matchedEntry !== null) {
            try {
                $parentName = isset($matchedEntry['parent']) ? $matchedEntry['parent'] : null;

                if ($parentName !== null) {
                    // Match was a sub-task: always include parent instructions too
                    $parentResult = $this->aiTaskInfoService->getTaskInstructions(
                        array('taskName' => $parentName)
                    );
                    $taskInstructions = $parentResult['instructions'];
                    $taskName = $parentName;
                    $taskFile = $parentResult['file'];
                    $availableSubTasks = isset($parentResult['subTasks'])
                        ? $parentResult['subTasks'] : null;

                    $selectedSubTask = array(
                        'taskName' => $matchedEntry['taskName'],
                        'file' => $matchedEntry['file'],
                        'instructions' => $this->aiTaskInfoService->getFileContent(
                            $matchedEntry['file']
                        ),
                    );
                } else {
                    // Match was a top-level task
                    $taskName = $matchedEntry['taskName'];
                    $taskFile = $matchedEntry['file'];
                    $taskResult = $this->aiTaskInfoService->getTaskInstructions(
                        array('taskName' => $taskName)
                    );
                    $taskInstructions = $taskResult['instructions'];
                    $availableSubTasks = isset($taskResult['subTasks'])
                        ? $taskResult['subTasks'] : null;

                    // Auto-select best sub-task when parent has sub-tasks
                    if ($availableSubTasks !== null && $availableSubTasks !== array()) {
                        $bestSub = $this->aiTaskInfoService->findBestSubTask(
                            $taskName, $request
                        );
                        if ($bestSub !== null) {
                            $selectedSubTask = array(
                                'taskName' => $bestSub['taskName'],
                                'file' => $bestSub['file'],
                                'instructions' => $this->aiTaskInfoService->getFileContent(
                                    $bestSub['file']
                                ),
                            );
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Non-fatal: instructions unavailable
            }
        }

        // --- Fetch table structure (when table + database are known) ---
        $tableStructure = null;
        $fields = array();

        if ($tableName !== '' && ($databaseName !== '' || $databaseId !== null)) {
            $schemaArgs = array('tableName' => $tableName);
            if ($databaseId !== null && $databaseId !== '') {
                $schemaArgs['databaseId'] = $databaseId;
            } else {
                $schemaArgs['databaseName'] = $databaseName;
            }
            if ($schema !== '') {
                $schemaArgs['schema'] = $schema;
            }

            $tableStructure = $this->databaseSchemaService->getTableStructure($schemaArgs);
            $fields = isset($tableStructure['fields']) && is_array($tableStructure['fields'])
                ? $tableStructure['fields']
                : array();
        }

        // --- Supplementary instructions (e.g. FK handling, component mapping) ---
        $supplementary = $this->getSupplementaryInstructions($fields);

        $result = array(
            'taskType' => $taskType !== '' ? $taskType : null,
            'taskName' => $taskName,
            'request' => $request,
            'database' => $tableStructure !== null
                ? ($tableStructure['database'] ?? $databaseName)
                : ($databaseName !== '' ? $databaseName : null),
            'databaseId' => $tableStructure !== null
                ? ($tableStructure['databaseId'] ?? $databaseId)
                : $databaseId,
            'schema' => $tableStructure !== null ? ($tableStructure['schema'] ?? null) : null,
            'table' => $tableName !== '' ? $tableName : null,
        );

        if ($taskInstructions !== null) {
            $result['instructions'] = $taskInstructions;
        }
        if ($selectedSubTask !== null) {
            $result['selectedSubTaskInstructions'] = $selectedSubTask;
        }
        if ($availableSubTasks !== null) {
            $result['availableSubTasks'] = $availableSubTasks;
        }
        if ($supplementary !== array()) {
            $result['supplementaryInstructions'] = $supplementary;
        }
        if ($tableStructure !== null) {
            $result['tableStructure'] = array(
                'fields' => $fields,
                'foreignKeys' => $tableStructure['foreignKeys'] ?? array(),
            );
        }

        $result['source'] = array(
            'taskFile' => $taskFile,
        );

        // Include app defaults for the resolved database so the LLM does not
        // need a separate get_app_defaults call (which risks using the wrong
        // database when arguments are omitted).
        $resolvedDatabaseId = $result['databaseId'] ?? null;
        if ($this->mcpToolsExtension !== null && $resolvedDatabaseId !== null) {
            try {
                $appDefaults = $this->mcpToolsExtension->getAppDefaults(
                    array('databaseId' => $resolvedDatabaseId)
                );
                $result['appDefaults'] = $appDefaults['defaults'] ?? null;
            } catch (\Throwable $e) {
                // Non-fatal: app defaults are supplementary context
            }
        }

        return $result;
    }

    /**
     * @return array{taskType:string, tableName:string, databaseName:string}
     */
    private function parseIntent(string $request): array
    {
        $taskType = '';
        if (preg_match('/\b(form)\b/i', $request) === 1) {
            $taskType = 'create_form';
        } elseif (preg_match('/\b(table\s+grid|grid)\b/i', $request) === 1) {
            $taskType = 'create_table_grid';
        }

        $tableName = '';
        if (preg_match('/\b([a-zA-Z_][a-zA-Z0-9_]*)\s+table\b/i', $request, $matches) === 1) {
            $tableName = $matches[1];
        } elseif (preg_match('/\btable\s+([a-zA-Z_][a-zA-Z0-9_]*)\b/i', $request, $matches) === 1) {
            $candidate = strtolower($matches[1]);
            $invalidCandidates = array('in', 'on', 'at', 'for', 'to', 'from', 'of', 'the', 'a', 'an');
            if (!in_array($candidate, $invalidCandidates, true)) {
                $tableName = $matches[1];
            }
        }

        $databaseName = '';
        if (preg_match('/\bin\s+(?:the\s+)?([a-zA-Z0-9_]+)\s+database\b/i', $request, $matches) === 1) {
            $databaseName = $matches[1];
        } elseif (preg_match('/\bdatabase\s+([a-zA-Z0-9_]+)\b/i', $request, $matches) === 1) {
            $databaseName = $matches[1];
        }

        return array(
            'taskType' => $taskType,
            'tableName' => $tableName,
            'databaseName' => $databaseName,
        );
    }

    /**
     * Map a task type keyword or natural-language request to a task in the index.
     *
     * Known task type shortcuts (create_form, create_table_grid) are resolved
     * directly. For anything else, the AiTaskInfoService keyword matcher is
     * used, allowing new task files to be picked up without code changes.
     *
     * @return array{taskName:string, description:string, file:string}|null
     */
    private function resolveTask(string $taskType, string $request): ?array
    {
        $directMap = array(
            'create_form' => 'Create Form',
            'create_table_grid' => 'Create Table Grid',
        );

        if ($taskType !== '' && isset($directMap[$taskType])) {
            $tasks = $this->aiTaskInfoService->listTasks()['tasks'] ?? array();
            foreach ($tasks as $task) {
                if (strcasecmp((string) $task['taskName'], $directMap[$taskType]) === 0) {
                    return $task;
                }
            }
        }

        // Fall back to keyword matching against the full task index
        return $this->aiTaskInfoService->findMatchingTask(
            $taskType !== '' ? $taskType . ' ' . $request : $request
        );
    }

    /**
     * Gather supplementary task instructions that always apply or are triggered
     * by table structure (e.g. foreign-key fields).  The component-mapping
     * guide is always included so the LLM knows which component to use per
     * database column type.
     *
     * @param array<int, array<string, mixed>> $fields
     * @return array<int, array{taskName:string, instructions:string}>
     */
    private function getSupplementaryInstructions(array $fields): array
    {
        $supplementary = array();

        // Always include the component-mapping guide
        try {
            $mappingResult = $this->aiTaskInfoService->getTaskInstructions(
                array('taskName' => 'Field Type Component Mapping')
            );
            $supplementary[] = array(
                'taskName' => (string) $mappingResult['taskName'],
                'instructions' => (string) $mappingResult['instructions'],
            );
        } catch (\Throwable $e) {
            // Non-fatal
        }

        // Include FK handling guide when the table has foreign keys
        $hasForeignKeys = false;
        foreach ($fields as $field) {
            if (isset($field['foreignKey']) && is_array($field['foreignKey']) && $field['foreignKey'] !== array()) {
                $hasForeignKeys = true;
                break;
            }
        }

        if ($hasForeignKeys) {
            try {
                $fkResult = $this->aiTaskInfoService->getTaskInstructions(
                    array('taskName' => 'Handle Foreign Key Fields')
                );
                $supplementary[] = array(
                    'taskName' => (string) $fkResult['taskName'],
                    'instructions' => (string) $fkResult['instructions'],
                );
            } catch (\Throwable $e) {
                // Supplementary instructions are optional
            }
        }

        return $supplementary;
    }
}
