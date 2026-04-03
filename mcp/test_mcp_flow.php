<?php
/**
 * Simulates a Copilot MCP client talking to the dropin-script-server.
 * Sends a sequence of JSON-RPC messages over stdin/stdout pipes.
 */

$serverCmd = 'php ' . __DIR__ . '/dropin-script-server.php';
$descriptors = [
    0 => ['pipe', 'r'], // child stdin  (we write to it)
    1 => ['pipe', 'w'], // child stdout (we read from it)
    2 => ['pipe', 'w'], // child stderr (we read from it)
];

$process = proc_open($serverCmd, $descriptors, $pipes);
if (!is_resource($process)) {
    fwrite(STDERR, "FAIL: Could not start server process.\n");
    exit(1);
}

$stdin  = $pipes[0];
$stdout = $pipes[1];
$stderr = $pipes[2];

stream_set_blocking($stdout, true);
stream_set_blocking($stderr, false);

function sendMessage($stdin, array $message): void
{
    $json = json_encode($message, JSON_UNESCAPED_SLASHES);
    fwrite($stdin, $json . "\n");
    fflush($stdin);
}

function readResponse($stdout): ?array
{
    $line = fgets($stdout);
    if ($line === false) return null;
    $trimmed = trim($line);
    if ($trimmed === '') return readResponse($stdout);
    return json_decode($trimmed, true);
}

function drainStderr($stderr): string
{
    $out = '';
    while (($line = fgets($stderr)) !== false) {
        $out .= $line;
    }
    return $out;
}

function printSection(string $title): void
{
    echo "\n" . str_repeat('=', 70) . "\n";
    echo "  " . $title . "\n";
    echo str_repeat('=', 70) . "\n";
}

function printResult(array $response, bool $showFull = false): void
{
    if (isset($response['error'])) {
        echo "  ERROR: " . json_encode($response['error'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        return;
    }
    $result = $response['result'] ?? null;
    if ($result === null) {
        echo "  (null result)\n";
        return;
    }
    if ($showFull) {
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    } else {
        // For tool call results, show the text content
        if (isset($result['content'][0]['text'])) {
            echo $result['content'][0]['text'] . "\n";
        } else {
            echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        }
    }
}

$testsPassed = 0;
$testsFailed = 0;

// ─── Step 1: Initialize ───
printSection("Step 1: Initialize handshake");
sendMessage($stdin, [
    'jsonrpc' => '2.0',
    'id' => 1,
    'method' => 'initialize',
    'params' => [
        'protocolVersion' => '2024-11-05',
        'capabilities' => new stdClass(),
        'clientInfo' => ['name' => 'test-client', 'version' => '1.0.0'],
    ],
]);
$resp = readResponse($stdout);
if ($resp && isset($resp['result']['serverInfo']['name'])) {
    echo "  Server: " . $resp['result']['serverInfo']['name'] . " v" . $resp['result']['serverInfo']['version'] . "\n";
    echo "  Protocol: " . $resp['result']['protocolVersion'] . "\n";
    echo "  Tools capability: " . (isset($resp['result']['capabilities']['tools']) ? 'yes' : 'no') . "\n";
    $testsPassed++;
} else {
    echo "  FAIL: Bad initialize response\n";
    $testsFailed++;
}

// Send initialized notification
sendMessage($stdin, [
    'jsonrpc' => '2.0',
    'method' => 'notifications/initialized',
]);

// ─── Step 2: List tools ───
printSection("Step 2: tools/list");
sendMessage($stdin, [
    'jsonrpc' => '2.0',
    'id' => 2,
    'method' => 'tools/list',
    'params' => [],
]);
$resp = readResponse($stdout);
if ($resp && isset($resp['result']['tools'])) {
    $tools = $resp['result']['tools'];
    echo "  Available tools (" . count($tools) . "):\n";
    foreach ($tools as $t) {
        echo "    - " . $t['name'] . "\n";
    }
    $testsPassed++;
} else {
    echo "  FAIL: No tools returned\n";
    $testsFailed++;
}

// ─── Step 3: list_databases ───
printSection("Step 3: list_databases");
sendMessage($stdin, [
    'jsonrpc' => '2.0',
    'id' => 3,
    'method' => 'tools/call',
    'params' => [
        'name' => 'list_databases',
        'arguments' => new stdClass(),
    ],
]);
$resp = readResponse($stdout);
if ($resp && !($resp['result']['isError'] ?? false)) {
    printResult($resp);
    $testsPassed++;
} elseif ($resp) {
    echo "  FAIL: "; printResult($resp);
    $testsFailed++;
}

// ─── Step 4: list_ai_tasks ───
printSection("Step 4: list_ai_tasks");
sendMessage($stdin, [
    'jsonrpc' => '2.0',
    'id' => 4,
    'method' => 'tools/call',
    'params' => [
        'name' => 'list_ai_tasks',
        'arguments' => new stdClass(),
    ],
]);
$resp = readResponse($stdout);
if ($resp && !isset($resp['error'])) {
    $data = json_decode($resp['result']['content'][0]['text'] ?? '{}', true);
    echo "  Task count: " . ($data['taskCount'] ?? '?') . "\n";
    if (isset($data['tasks'])) {
        foreach ($data['tasks'] as $task) {
            echo "    - " . $task['taskName'] . " (" . $task['file'] . ")\n";
        }
    }
    $testsPassed++;
} else {
    echo "  FAIL\n"; $testsFailed++;
}

// ─── Step 5: get_ai_task_instructions (Create Table Grid) ───
printSection("Step 5: get_ai_task_instructions -> Create Table Grid");
sendMessage($stdin, [
    'jsonrpc' => '2.0',
    'id' => 5,
    'method' => 'tools/call',
    'params' => [
        'name' => 'get_ai_task_instructions',
        'arguments' => ['taskName' => 'Create Table Grid'],
    ],
]);
$resp = readResponse($stdout);
if ($resp && !isset($resp['error'])) {
    $data = json_decode($resp['result']['content'][0]['text'] ?? '{}', true);
    $instLen = strlen($data['instructions'] ?? '');
    echo "  Task: " . ($data['taskName'] ?? '?') . "\n";
    echo "  File: " . ($data['file'] ?? '?') . "\n";
    echo "  Instructions length: " . $instLen . " chars\n";
    echo "  First 200 chars:\n    " . substr($data['instructions'] ?? '', 0, 200) . "\n";
    $testsPassed++;
} else {
    echo "  FAIL\n"; $testsFailed++;
}

// ─── Step 6: list_database_tables (couch_data) ───
printSection("Step 6: list_database_tables -> couch_data");
sendMessage($stdin, [
    'jsonrpc' => '2.0',
    'id' => 6,
    'method' => 'tools/call',
    'params' => [
        'name' => 'list_database_tables',
        'arguments' => ['databaseName' => 'couch_data'],
    ],
]);
$resp = readResponse($stdout);
if ($resp && !isset($resp['error'])) {
    $data = json_decode($resp['result']['content'][0]['text'] ?? '{}', true);
    $tables = $data['tables'] ?? [];
    echo "  Database: " . ($data['database'] ?? '?') . " (id=" . ($data['databaseId'] ?? '?') . ")\n";
    echo "  Tables (" . count($tables) . "):\n";
    foreach (array_slice($tables, 0, 15) as $t) {
        echo "    - " . $t['name'] . " (" . $t['type'] . ")\n";
    }
    if (count($tables) > 15) echo "    ... and " . (count($tables) - 15) . " more\n";
    $testsPassed++;
} else {
    echo "  FAIL: "; printResult($resp);
    $testsFailed++;
}

// ─── Step 7: generate_dropin_script (the main developer prompt) ───
printSection("Step 7: generate_dropin_script");
echo "  Prompt: \"Create a grid based on the invoice table in the couch_data database\"\n\n";
sendMessage($stdin, [
    'jsonrpc' => '2.0',
    'id' => 7,
    'method' => 'tools/call',
    'params' => [
        'name' => 'generate_dropin_script',
        'arguments' => [
            'request' => 'Create a grid based on the invoice table in the couch_data database',
        ],
    ],
]);
$resp = readResponse($stdout);
if ($resp && !isset($resp['error'])) {
    $data = json_decode($resp['result']['content'][0]['text'] ?? '{}', true);
    echo "  Task type: " . ($data['taskType'] ?? '?') . "\n";
    echo "  Task name: " . ($data['taskName'] ?? '?') . "\n";
    echo "  Database: " . ($data['database'] ?? '?') . "\n";
    echo "  Table: " . ($data['table'] ?? '?') . "\n";
    echo "  Task file: " . ($data['source']['taskFile'] ?? '?') . "\n";

    if (isset($data['instructions'])) {
        echo "\n  --- Instructions (first 300 chars) ---\n";
        echo "  " . str_replace("\n", "\n  ", substr($data['instructions'], 0, 300)) . "\n";
    }
    if (isset($data['supplementaryInstructions'])) {
        echo "\n  --- Supplementary Instructions ---\n";
        foreach ($data['supplementaryInstructions'] as $si) {
            echo "  [" . $si['taskName'] . "] (" . strlen($si['instructions']) . " chars)\n";
        }
    }
    if (isset($data['tableStructure']['fields'])) {
        echo "\n  --- Table Fields (" . count($data['tableStructure']['fields']) . ") ---\n";
        foreach ($data['tableStructure']['fields'] as $f) {
            $fk = isset($f['foreignKey']) && $f['foreignKey'] ? ' -> FK' : '';
            echo "  " . str_pad($f['name'], 25) . $f['type'] . ($f['required'] ? ' REQUIRED' : '') . $fk . "\n";
        }
    }
    if (isset($data['suggestedScript'])) {
        echo "\n  --- Suggested Script (unexpected — builders should be disabled) ---\n";
        echo $data['suggestedScript'] . "\n";
    }
    $isError = $resp['result']['isError'] ?? false;
    if (!$isError) { $testsPassed++; } else { $testsFailed++; }
} else {
    echo "  FAIL: "; printResult($resp);
    $testsFailed++;
}

// ─── Step 8: get_container_structure (not yet implemented) ───
printSection("Step 8: get_container_structure (expect not-implemented)");
sendMessage($stdin, [
    'jsonrpc' => '2.0',
    'id' => 8,
    'method' => 'tools/call',
    'params' => [
        'name' => 'get_container_structure',
        'arguments' => ['containerName' => 'invoiceGrid'],
    ],
]);
$resp = readResponse($stdout);
if ($resp) {
    $isError = $resp['result']['isError'] ?? false;
    $data = json_decode($resp['result']['content'][0]['text'] ?? '{}', true);
    if ($isError && strpos($data['error'] ?? '', 'not yet implemented') !== false) {
        echo "  Correctly returned not-implemented error: " . $data['error'] . "\n";
        $testsPassed++;
    } else {
        echo "  Unexpected response:\n"; printResult($resp);
        $testsFailed++;
    }
}

// ─── Shutdown ───
printSection("Shutdown");
sendMessage($stdin, ['jsonrpc' => '2.0', 'id' => 9, 'method' => 'shutdown']);
$resp = readResponse($stdout);
sendMessage($stdin, ['jsonrpc' => '2.0', 'method' => 'exit']);

// Drain stderr logs
usleep(200000);
$stderrOutput = drainStderr($stderr);

fclose($stdin);
fclose($stdout);
fclose($stderr);
proc_close($process);

printSection("STDERR (server log)");
echo $stderrOutput ?: "  (empty)\n";

printSection("Results: " . $testsPassed . " passed, " . $testsFailed . " failed");
exit($testsFailed > 0 ? 1 : 0);
