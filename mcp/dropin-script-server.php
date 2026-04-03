<?php

declare(strict_types=1);

// On Windows, PHP opens STDIN/STDOUT/STDERR in text mode which translates
// \n <-> \r\n.  This corrupts the Content-Length-based MCP framing because
// the VS Code client receives extra \r bytes.  Reopen all three streams in
// binary mode to prevent translation.
if (PHP_OS_FAMILY === 'Windows') {
    $GLOBALS['_mcpStdin']  = fopen('php://stdin',  'rb');
    $GLOBALS['_mcpStdout'] = fopen('php://stdout', 'wb');
    $GLOBALS['_mcpStderr'] = fopen('php://stderr', 'wb');
} else {
    $GLOBALS['_mcpStdin']  = STDIN;
    $GLOBALS['_mcpStdout'] = STDOUT;
    $GLOBALS['_mcpStderr'] = STDERR;
}

// Suppress any stray output (PHP notices, warnings, extension messages) that
// would corrupt the MCP JSON-RPC framing on STDOUT.
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/src/ConnConfigLoader.php';
require_once __DIR__ . '/src/DatabaseConnectionFactory.php';
require_once __DIR__ . '/src/DatabaseSchemaService.php';
require_once __DIR__ . '/src/AiTaskInfoService.php';
require_once __DIR__ . '/src/DropinScriptGenerator.php';
require_once __DIR__ . '/src/McpToolsExtension.php';

use DropinScript\Mcp\AiTaskInfoService;
use DropinScript\Mcp\McpToolsExtension;
use DropinScript\Mcp\ConnConfigLoader;
use DropinScript\Mcp\DatabaseConnectionFactory;
use DropinScript\Mcp\DatabaseSchemaService;
use DropinScript\Mcp\DropinScriptGenerator;

const MCP_PROTOCOL_VERSION = '2024-11-05';
const SERVER_NAME = 'dropin-script-server';
const SERVER_VERSION = '0.2.0';

// --- Resolve paths dynamically from configs/Dib.php ---
$projectRoot = dirname(__DIR__);
$dibConfigPath = $projectRoot . DIRECTORY_SEPARATOR . 'configs' . DIRECTORY_SEPARATOR . 'Dib.php';

require_once $dibConfigPath;

$secretsPath = rtrim(\DIB::$SECRETSPATH, '/\\') . DIRECTORY_SEPARATOR;
$connFilePath = $secretsPath . 'Conn.php';
$defaultDatabaseId = isset(\DIB::$LOGINDB) ? (int) \DIB::$LOGINDB : null;
$dibDbIndex = isset(\DIB::$DIBDB) ? (int) \DIB::$DIBDB : null;

logStderr('Resolved secrets path: ' . $secretsPath);
logStderr('Dib config: DIBDB=' . ($dibDbIndex ?? 'null') . ', LOGINDB=' . ($defaultDatabaseId ?? 'null'));

$loader = new ConnConfigLoader();
$databaseConfigs = $loader->load($connFilePath);

$connectionFactory = new DatabaseConnectionFactory();
$schemaService = new DatabaseSchemaService($databaseConfigs, $connectionFactory);
try {
    $aiTaskInfoService = new AiTaskInfoService(
        $projectRoot . DIRECTORY_SEPARATOR . 'dropinbase' . DIRECTORY_SEPARATOR . 'dropins'
            . DIRECTORY_SEPARATOR . 'dibAi' . DIRECTORY_SEPARATOR . 'aiInfo',
        'Main List of Ai Tasks.txt'
    );
} catch (Throwable $e) {
    // Non-fatal: the server can still handle database tools.
    // AI task tools will return errors until the aiInfo directory is accessible.
    logStderr('CATALOG BUILD FAILED: ' . $e->getMessage());
    $aiTaskInfoService = null;
}
$mcpToolsExtension = ($dibDbIndex !== null && $defaultDatabaseId !== null)
    ? new McpToolsExtension($databaseConfigs, $connectionFactory, $dibDbIndex, $defaultDatabaseId)
    : null;
$dropinScriptGenerator = ($aiTaskInfoService !== null)
    ? new DropinScriptGenerator($schemaService, $aiTaskInfoService, $defaultDatabaseId, $mcpToolsExtension)
    : null;
$running = true;

// Discard any output captured during initialization, then stop buffering
$startupOutput = ob_get_clean();
if ($startupOutput !== '' && $startupOutput !== false) {
    logStderr('Discarded startup output (' . strlen($startupOutput) . ' bytes): ' . substr($startupOutput, 0, 500));
}

logStderr(SERVER_NAME . ' v' . SERVER_VERSION . ' started');

if ($aiTaskInfoService !== null) {
    foreach ($aiTaskInfoService->getWarnings() as $warning) {
        logStderr('CATALOG WARNING: ' . $warning);
    }
    $entryCount = count($aiTaskInfoService->listTasks()['tasks'] ?? array());
    logStderr('Catalog: ' . $entryCount . ' top-level tasks indexed');
}

while ($running) {
    $message = readMessage();
    if ($message === null) {
        break;
    }

    $id = array_key_exists('id', $message) ? $message['id'] : null;
    $method = isset($message['method']) ? (string) $message['method'] : '';
    $params = isset($message['params']) && is_array($message['params']) ? $message['params'] : array();

    try {
        switch ($method) {
            case 'initialize':
                writeResponse($id, array(
                    'protocolVersion' => isset($params['protocolVersion']) ? (string) $params['protocolVersion'] : MCP_PROTOCOL_VERSION,
                    'capabilities' => array(
                        'tools' => new stdClass(),
                    ),
                    'serverInfo' => array(
                        'name' => SERVER_NAME,
                        'version' => SERVER_VERSION,
                    ),
                ));
                break;

            case 'notifications/initialized':
                break;

            case 'tools/list':
                writeResponse($id, array(
                    'tools' => getToolsDefinition(),
                ));
                break;

            case 'tools/call':
                $toolName = isset($params['name']) ? (string) $params['name'] : '';
                $arguments = isset($params['arguments']) && is_array($params['arguments']) ? $params['arguments'] : array();
                writeResponse($id, handleToolCall($schemaService, $aiTaskInfoService, $dropinScriptGenerator, $mcpToolsExtension, $toolName, $arguments));
                break;

            case 'ping':
                writeResponse($id, array());
                break;

            case 'shutdown':
                writeResponse($id, null);
                break;

            case 'exit':
                $running = false;
                break;

            default:
                if ($id !== null) {
                    writeError($id, -32601, 'Method not found: ' . $method);
                }
                break;
        }
    } catch (Throwable $exception) {
        if ($id !== null) {
            writeError($id, -32000, $exception->getMessage());
        }
    }
}

/**
 * Read a single JSON-RPC message from STDIN.
 * MCP stdio transport uses newline-delimited JSON: one JSON object per line.
 *
 * @return array<string, mixed>|null
 */
function readMessage(): ?array
{
    $line = fgets($GLOBALS['_mcpStdin']);

    if ($line === false) {
        return null;
    }

    $trimmed = trim($line);
    if ($trimmed === '') {
        return readMessage();
    }

    $message = json_decode($trimmed, true);
    if (!is_array($message)) {
        logStderr('readMessage: invalid JSON: ' . substr($trimmed, 0, 200));
        throw new RuntimeException('Invalid JSON payload received.');
    }

    logStderr('<- ' . ($message['method'] ?? 'response') . ' (id=' . ($message['id'] ?? 'null') . ')');
    return $message;
}

/**
 * @param mixed $id
 * @param mixed $result
 */
function writeResponse($id, $result): void
{
    writeMessage(array(
        'jsonrpc' => '2.0',
        'id' => $id,
        'result' => $result,
    ));
}

/**
 * @param mixed $id
 */
function writeError($id, int $code, string $message): void
{
    writeMessage(array(
        'jsonrpc' => '2.0',
        'id' => $id,
        'error' => array(
            'code' => $code,
            'message' => $message,
        ),
    ));
}

/**
 * Write a JSON-RPC message to STDOUT.
 * MCP stdio transport uses newline-delimited JSON: one JSON object per line.
 *
 * @param array<string, mixed> $message
 */
function writeMessage(array $message): void
{
    $payload = json_encode($message, JSON_UNESCAPED_SLASHES);
    if ($payload === false) {
        throw new RuntimeException('Failed to encode MCP response.');
    }

    logStderr('-> response (id=' . ($message['id'] ?? 'null') . ', ' . strlen($payload) . 'B)');
    fwrite($GLOBALS['_mcpStdout'], $payload . "\n");
    fflush($GLOBALS['_mcpStdout']);
}

/**
 * @return array<int, array<string, mixed>>
 */
function getToolsDefinition(): array
{
    return array(
        array(
            'name' => 'list_databases',
            'description' => 'List all configured databases from secrets/Conn.php with their IDs, names, types, and hosts. Call this first to discover which databases are available before listing tables or generating scripts.',
            'inputSchema' => array(
                'type' => 'object',
                'properties' => new stdClass(),
                'additionalProperties' => false,
            ),
            'annotations' => array(
                'title' => 'List Databases',
                'readOnlyHint' => true,
                'destructiveHint' => false,
                'openWorldHint' => false,
            ),
        ),
        array(
            'name' => 'list_database_tables',
            'description' => 'List all tables in a configured database. Use `list_databases` first to discover the available database IDs and names.',
            'inputSchema' => array(
                'type' => 'object',
                'properties' => array(
                    'databaseId' => array(
                        'type' => 'integer',
                        'description' => 'The numeric database id from secrets/Conn.php.',
                    ),
                    'databaseName' => array(
                        'type' => 'string',
                        'description' => 'The database name from secrets/Conn.php.',
                    ),
                    'schema' => array(
                        'type' => 'string',
                        'description' => 'Optional schema name for PostgreSQL or SQL Server.',
                    ),
                ),
                'additionalProperties' => false,
                'anyOf' => array(
                    array('required' => array('databaseId')),
                    array('required' => array('databaseName')),
                ),
            ),
            'annotations' => array(
                'title' => 'List Database Tables',
                'readOnlyHint' => true,
                'destructiveHint' => false,
                'openWorldHint' => false,
            ),
        ),
        array(
            'name' => 'get_database_table_structure',
            'description' => 'Get the column definitions (name, type, required, length, foreign keys) for one table. Useful when you need to know the exact fields before generating Dropin Script.',
            'inputSchema' => array(
                'type' => 'object',
                'properties' => array(
                    'databaseId' => array(
                        'type' => 'integer',
                        'description' => 'The numeric database id from secrets/Conn.php.',
                    ),
                    'databaseName' => array(
                        'type' => 'string',
                        'description' => 'The database name from secrets/Conn.php.',
                    ),
                    'schema' => array(
                        'type' => 'string',
                        'description' => 'Optional schema name for PostgreSQL or SQL Server.',
                    ),
                    'tableName' => array(
                        'type' => 'string',
                        'description' => 'The table name to inspect.',
                    ),
                ),
                'required' => array('tableName'),
                'additionalProperties' => false,
                'anyOf' => array(
                    array('required' => array('databaseId', 'tableName')),
                    array('required' => array('databaseName', 'tableName')),
                ),
            ),
            'annotations' => array(
                'title' => 'Get Table Structure',
                'readOnlyHint' => true,
                'destructiveHint' => false,
                'openWorldHint' => false,
            ),
        ),
        array(
            'name' => 'list_ai_tasks',
            'description' => 'List available Dropin Script task guides from the aiInfo folder. Each task has a name, description, and instruction file. Use this to discover what kinds of Dropin Script the server can help generate (grids, forms, foreign keys, etc.) and to get the background context about the Dropinbase framework.',
            'inputSchema' => array(
                'type' => 'object',
                'properties' => new stdClass(),
                'additionalProperties' => false,
            ),
            'annotations' => array(
                'title' => 'List AI Tasks',
                'readOnlyHint' => true,
                'destructiveHint' => false,
                'openWorldHint' => false,
            ),
        ),
        array(
            'name' => 'get_ai_task_instructions',
            'description' => 'Get the full Dropin Script instructions and syntax reference for a specific task (e.g. "Create Table Grid", "Create Form", "Handle Foreign Key Fields"). The instructions contain examples and rules you MUST follow when producing Dropin Script.',
            'inputSchema' => array(
                'type' => 'object',
                'properties' => array(
                    'taskName' => array(
                        'type' => 'string',
                        'description' => 'Task name from the index, e.g. Create Table Grid.',
                    ),
                    'taskFile' => array(
                        'type' => 'string',
                        'description' => 'Task file name, e.g. Create Table Grid.txt.',
                    ),
                ),
                'additionalProperties' => false,
                'anyOf' => array(
                    array('required' => array('taskName')),
                    array('required' => array('taskFile')),
                ),
            ),
            'annotations' => array(
                'title' => 'Get AI Task Instructions',
                'readOnlyHint' => true,
                'destructiveHint' => false,
                'openWorldHint' => false,
            ),
        ),
        array(
            'name' => 'generate_dropin_script',
            'description' => 'Generate Dropin Script from a natural-language request. Returns the matching task instructions (syntax reference), the table structure, any supplementary instructions (e.g. foreign-key handling), and a template script as a starting point. Use the returned instructions and table structure to produce the final Dropin Script. Example request: "Create a grid based on table invoice in the couch_data database".',
            'inputSchema' => array(
                'type' => 'object',
                'properties' => array(
                    'request' => array(
                        'type' => 'string',
                        'description' => 'Natural language request describing what Dropin Script to produce. Should mention the task (form/grid/etc.), table name, and database.',
                    ),
                    'taskType' => array(
                        'type' => 'string',
                        'description' => 'Optional explicit task type override (e.g. create_form, create_table_grid). If omitted, detected from the request text.',
                    ),
                    'databaseId' => array(
                        'type' => 'integer',
                        'description' => 'Optional database id from secrets/Conn.php.',
                    ),
                    'databaseName' => array(
                        'type' => 'string',
                        'description' => 'Optional database name from secrets/Conn.php.',
                    ),
                    'schema' => array(
                        'type' => 'string',
                        'description' => 'Optional schema name for PostgreSQL or SQL Server.',
                    ),
                    'tableName' => array(
                        'type' => 'string',
                        'description' => 'Optional explicit table name override.',
                    ),
                ),
                'additionalProperties' => false,
                'required' => array('request'),
            ),
            'annotations' => array(
                'title' => 'Generate Dropin Script',
                'readOnlyHint' => true,
                'destructiveHint' => false,
                'openWorldHint' => false,
            ),
        ),
        array(
            'name' => 'get_app_defaults',
            'description' => 'Get application-level defaults from the Dropinbase system database. Returns the default dropin_id, port item_id, grid template, form template, SQL grid template, and menu item parent to use when the user does not specify them. Optionally provide databaseId or databaseName to fetch defaults for a specific database; otherwise $LOGINDB is used.',
            'inputSchema' => array(
                'type' => 'object',
                'properties' => array(
                    'databaseId' => array(
                        'type' => 'integer',
                        'description' => 'Optional database id from secrets/Conn.php / pef_database.id to fetch defaults for.',
                    ),
                    'databaseName' => array(
                        'type' => 'string',
                        'description' => 'Optional database name from secrets/Conn.php to resolve and fetch defaults for.',
                    ),
                ),
                'additionalProperties' => false,
            ),
            'annotations' => array(
                'title' => 'Get App Defaults',
                'readOnlyHint' => true,
                'destructiveHint' => false,
                'openWorldHint' => false,
            ),
        ),
        array(
            'name' => 'get_container_structure',
            'description' => 'Get the existing structure of a container before making changes to it, or linking other containers to it. Provide a comma-separated list of container names or captions.',
            'inputSchema' => array(
                'type' => 'object',
                'properties' => array(
                    'containerName' => array(
                        'type' => 'string',
                        'description' => 'Comma-separated list of container names or captions to inspect, e.g. "mainPage,invoiceTab".',
                    ),
                ),
                'required' => array('containerName'),
                'additionalProperties' => false,
            ),
            'annotations' => array(
                'title' => 'Get Container Structure',
                'readOnlyHint' => true,
                'destructiveHint' => false,
                'openWorldHint' => false,
            ),
        ),
        array(
            'name' => 'get_container_ports',
            'description' => 'Get the detailed port records of an existing container. Provide a comma-separated list of container names or captions.',
            'inputSchema' => array(
                'type' => 'object',
                'properties' => array(
                    'containerName' => array(
                        'type' => 'string',
                        'description' => 'Comma-separated list of container names or captions to inspect, e.g. "mainPage,invoiceTab".',
                    ),
                ),
                'required' => array('containerName'),
                'additionalProperties' => false,
            ),
            'annotations' => array(
                'title' => 'Get Container Ports',
                'readOnlyHint' => true,
                'destructiveHint' => false,
                'openWorldHint' => false,
            ),
        ),
    );
}

/**
 * @param array<string, mixed> $arguments
 * @return array<string, mixed>
 */
function handleToolCall(DatabaseSchemaService $schemaService, ?AiTaskInfoService $aiTaskInfoService, ?DropinScriptGenerator $dropinScriptGenerator, ?McpToolsExtension $mcpToolsExtension, string $toolName, array $arguments): array
{
    logStderr('Tool call: ' . $toolName);

    try {
        switch ($toolName) {
            case 'list_databases':
                $data = $schemaService->listDatabases();
                break;

            case 'list_database_tables':
                $data = $schemaService->listTables($arguments);
                break;

            case 'get_database_table_structure':
                $data = $schemaService->getTableStructure($arguments);
                break;

            case 'list_ai_tasks':
                if ($aiTaskInfoService === null) {
                    throw new RuntimeException('AI task tools are unavailable because the aiInfo catalog could not be loaded at startup.');
                }
                $data = $aiTaskInfoService->listTasks();
                break;

            case 'get_ai_task_instructions':
                if ($aiTaskInfoService === null) {
                    throw new RuntimeException('AI task tools are unavailable because the aiInfo catalog could not be loaded at startup.');
                }
                $data = $aiTaskInfoService->getTaskInstructions($arguments);
                break;

            case 'generate_dropin_script':
                if ($dropinScriptGenerator === null) {
                    throw new RuntimeException('Script generation is unavailable because the aiInfo catalog could not be loaded at startup.');
                }
                $data = $dropinScriptGenerator->generateFromRequest($arguments);
                break;

            case 'get_app_defaults':
                if ($mcpToolsExtension === null) {
                    throw new RuntimeException('The `get_app_defaults` tool is unavailable because $DIBDB or $LOGINDB could not be resolved from configs/Dib.php.');
                }
                $data = $mcpToolsExtension->getAppDefaults($arguments);
                break;

            case 'get_container_structure':
                if ($mcpToolsExtension === null) {
                    throw new RuntimeException('The `get_container_structure` tool is unavailable because $DIBDB or $LOGINDB could not be resolved from configs/Dib.php.');
                }
                $containerName = isset($arguments['containerName']) ? (string) $arguments['containerName'] : '';
                if ($containerName === '') {
                    throw new RuntimeException('`containerName` is required.');
                }
                $data = array('structure' => $mcpToolsExtension->getContainerStructure($containerName));
                break;

            case 'get_container_ports':
                if ($mcpToolsExtension === null) {
                    throw new RuntimeException('The `get_container_ports` tool is unavailable because $DIBDB or $LOGINDB could not be resolved from configs/Dib.php.');
                }
                $containerName = isset($arguments['containerName']) ? (string) $arguments['containerName'] : '';
                if ($containerName === '') {
                    throw new RuntimeException('`containerName` is required.');
                }
                $data = array('ports' => $mcpToolsExtension->getContainerPorts($containerName));
                break;

            default:
                throw new RuntimeException('Unknown tool `' . $toolName . '`.');
        }

        return formatToolResult($data, false);
    } catch (Throwable $exception) {
        logStderr('Tool error (' . $toolName . '): ' . $exception->getMessage());
        return formatToolResult(array(
            'error' => $exception->getMessage(),
            'tool' => $toolName,
        ), true);
    }
}

/**
 * @param array<string, mixed> $data
 * @return array<string, mixed>
 */
function formatToolResult(array $data, bool $isError): array
{
    $text = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($text === false) {
        $text = $isError ? '{"error":"Unable to encode tool error."}' : '{"error":"Unable to encode tool result."}';
    }

    return array(
        'content' => array(
            array(
                'type' => 'text',
                'text' => $text,
            ),
        ),
        'structuredContent' => $data,
        'isError' => $isError,
    );
}

function logStderr(string $message): void
{
    $line = '[' . SERVER_NAME . '] ' . date('Y-m-d H:i:s') . ' ' . $message . "\n";
    fwrite($GLOBALS['_mcpStderr'], $line);

    // Also write to a log file for diagnostics
    $logPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'logs'
        . DIRECTORY_SEPARATOR . 'mcp-server.log';
    @file_put_contents($logPath, $line, FILE_APPEND | LOCK_EX);
}
