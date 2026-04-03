<?php

declare(strict_types=1);

namespace DropinScript\Mcp;

use PDO;
use PDOException;
use RuntimeException;

final class DatabaseConnectionFactory
{
    /**
     * @param array<string, mixed> $config
     */
    public function create(array $config): PDO
    {
        $dbType = strtolower((string) ($config['dbType'] ?? ''));
        if ($dbType === '') {
            throw new RuntimeException('The database configuration is missing `dbType`.');
        }

        $options = $this->buildPdoOptions($config);
        $dsn = $this->buildDsn($dbType, $config);
        $username = isset($config['username']) ? (string) $config['username'] : null;
        $password = isset($config['password']) ? (string) $config['password'] : null;

        try {
            $pdo = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $exception) {
            throw new RuntimeException('Unable to connect to database `' . ($config['database'] ?? 'unknown') . '`: ' . $exception->getMessage(), 0, $exception);
        }

        if ($dbType === 'pgsql' && !empty($config['schema'])) {
            $schema = $this->quotePgIdentifier((string) $config['schema']);
            $pdo->exec('SET search_path TO ' . $schema);
        }

        return $pdo;
    }

    /**
     * @param array<string, mixed> $config
     * @return array<int, mixed>
     */
    private function buildPdoOptions(array $config): array
    {
        $options = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        );

        if (array_key_exists('emulatePrepare', $config)) {
            $options[PDO::ATTR_EMULATE_PREPARES] = (bool) $config['emulatePrepare'];
        }

        if (array_key_exists('timeout', $config) && is_numeric($config['timeout'])) {
            $options[PDO::ATTR_TIMEOUT] = (int) $config['timeout'];
        }

        if (!empty($config['connectionOptionsExtra']) && is_array($config['connectionOptionsExtra'])) {
            foreach ($config['connectionOptionsExtra'] as $key => $value) {
                if (is_numeric($key)) {
                    $options[(int) $key] = $value;
                }
            }
        }

        return $options;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function buildDsn(string $dbType, array $config): string
    {
        switch ($dbType) {
            case 'mysql':
                return sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=%s%s',
                    (string) ($config['host'] ?? 'localhost'),
                    (string) ($config['port'] ?? 3306),
                    (string) ($config['database'] ?? ''),
                    (string) ($config['charset'] ?? 'utf8mb4'),
                    (string) ($config['connectionStringExtra'] ?? '')
                );

            case 'pgsql':
                return sprintf(
                    'pgsql:host=%s;port=%s;dbname=%s%s',
                    (string) ($config['host'] ?? 'localhost'),
                    (string) ($config['port'] ?? 5432),
                    (string) ($config['database'] ?? ''),
                    (string) ($config['connectionStringExtra'] ?? '')
                );

            case 'sqlite':
                $path = (string) ($config['host'] ?? $config['database'] ?? '');
                if ($path === '') {
                    throw new RuntimeException('The sqlite configuration is missing the database file path.');
                }

                return 'sqlite:' . $path;

            case 'mssql':
            case 'sqlsrv':
                $server = (string) ($config['host'] ?? 'localhost');
                if (!empty($config['port'])) {
                    $server .= ',' . (string) $config['port'];
                }

                return sprintf(
                    'sqlsrv:Server=%s;Database=%s%s',
                    $server,
                    (string) ($config['database'] ?? ''),
                    (string) ($config['connectionStringExtra'] ?? '')
                );

            default:
                throw new RuntimeException('Unsupported database type `' . $dbType . '`.');
        }
    }

    private function quotePgIdentifier(string $identifier): string
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }
}
