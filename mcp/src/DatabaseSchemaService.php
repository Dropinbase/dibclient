<?php

declare(strict_types=1);

namespace DropinScript\Mcp;

use PDO;
use RuntimeException;

final class DatabaseSchemaService
{
    /**
     * @var array<int|string, array<string, mixed>>
     */
    private $databaseConfigs;

    /**
     * @var DatabaseConnectionFactory
     */
    private $connectionFactory;

    /**
     * @var array<string, PDO>
     */
    private $connections = array();

    /**
     * @param array<int|string, array<string, mixed>> $databaseConfigs
     */
    public function __construct(array $databaseConfigs, DatabaseConnectionFactory $connectionFactory)
    {
        $this->databaseConfigs = $databaseConfigs;
        $this->connectionFactory = $connectionFactory;
    }

    /**
     * List all configured databases (without connecting).
     *
     * @return array<string, mixed>
     */
    public function listDatabases(): array
    {
        $databases = array();
        foreach ($this->databaseConfigs as $id => $config) {
            $databases[] = array(
                'databaseId' => $id,
                'database' => (string) ($config['database'] ?? ''),
                'dbType' => strtolower((string) ($config['dbType'] ?? '')),
                'host' => (string) ($config['host'] ?? ''),
                'schema' => isset($config['schema']) ? (string) $config['schema'] : null,
            );
        }

        return array('databases' => $databases);
    }

    /**
     * @param array<string, mixed> $arguments
     * @return array<string, mixed>
     */
    public function listTables(array $arguments): array
    {
        $resolved = $this->resolveDatabase($arguments);
        $config = $resolved['config'];
        $dbType = strtolower((string) $config['dbType']);
        $schema = $this->resolveSchema($config, $arguments);
        $pdo = $this->getConnection($config, $resolved['databaseId']);

        switch ($dbType) {
            case 'mysql':
                $tables = $this->listMySqlTables($pdo, (string) $config['database']);
                break;

            case 'pgsql':
                $tables = $this->listPgSqlTables($pdo, (string) $config['database'], $schema);
                break;

            case 'sqlite':
                $tables = $this->listSqliteTables($pdo);
                break;

            case 'mssql':
            case 'sqlsrv':
                $tables = $this->listMsSqlTables($pdo, $schema);
                break;

            default:
                throw new RuntimeException('Unsupported database type `' . $dbType . '`.');
        }

        return array(
            'databaseId' => $resolved['databaseId'],
            'database' => (string) ($config['database'] ?? ''),
            'dbType' => $dbType,
            'schema' => $schema,
            'tables' => $tables,
        );
    }

    /**
     * @param array<string, mixed> $arguments
     * @return array<string, mixed>
     */
    public function getTableStructure(array $arguments): array
    {
        $tableName = isset($arguments['tableName']) ? trim((string) $arguments['tableName']) : '';
        if ($tableName === '') {
            throw new RuntimeException('`tableName` is required.');
        }

        $resolved = $this->resolveDatabase($arguments);
        $config = $resolved['config'];
        $dbType = strtolower((string) $config['dbType']);
        $schema = $this->resolveSchema($config, $arguments);
        $pdo = $this->getConnection($config, $resolved['databaseId']);

        switch ($dbType) {
            case 'mysql':
                $columns = $this->describeMySqlTable($pdo, (string) $config['database'], $tableName);
                $foreignKeys = $this->getMySqlForeignKeys($pdo, (string) $config['database'], $tableName);
                break;

            case 'pgsql':
                $columns = $this->describePgSqlTable($pdo, (string) $config['database'], $schema, $tableName);
                $foreignKeys = $this->getPgSqlForeignKeys($pdo, $schema, $tableName);
                break;

            case 'sqlite':
                $columns = $this->describeSqliteTable($pdo, $tableName);
                $foreignKeys = $this->getSqliteForeignKeys($pdo, $tableName);
                break;

            case 'mssql':
            case 'sqlsrv':
                $columns = $this->describeMsSqlTable($pdo, (string) $config['database'], $schema, $tableName);
                $foreignKeys = $this->getMsSqlForeignKeys($pdo, $schema, $tableName);
                break;

            default:
                throw new RuntimeException('Unsupported database type `' . $dbType . '`.');
        }

        if ($columns === array()) {
            throw new RuntimeException('Table `' . $tableName . '` was not found.');
        }

        $foreignKeysByColumn = array();
        foreach ($foreignKeys as $foreignKey) {
            $foreignKeysByColumn[$foreignKey['column']] = $foreignKey;
        }

        $fields = array();
        foreach ($columns as $column) {
            $nullable = (bool) $column['nullable'];
            $defaultValue = $column['default'];
            $isAutoIncrement = (bool) $column['isAutoIncrement'];
            $required = !$nullable && $defaultValue === null && !$isAutoIncrement;
            $name = (string) $column['name'];

            $fields[] = array(
                'name' => $name,
                'type' => $column['type'],
                'fullType' => $column['fullType'],
                'required' => $required,
                'nullable' => $nullable,
                'length' => $column['length'],
                'precision' => $column['precision'],
                'scale' => $column['scale'],
                'default' => $defaultValue,
                'isPrimaryKey' => (bool) $column['isPrimaryKey'],
                'isAutoIncrement' => $isAutoIncrement,
                'foreignKey' => isset($foreignKeysByColumn[$name]) ? $foreignKeysByColumn[$name] : null,
            );
        }

        return array(
            'databaseId' => $resolved['databaseId'],
            'database' => (string) ($config['database'] ?? ''),
            'dbType' => $dbType,
            'schema' => $schema,
            'table' => $tableName,
            'fields' => $fields,
            'foreignKeys' => array_values($foreignKeys),
        );
    }

    /**
     * Get or create a cached PDO connection for a database.
     * Recovers from stale connections (e.g. server timeout) by reconnecting.
     *
     * @param array<string, mixed> $config
     * @param int|string $databaseId
     */
    private function getConnection(array $config, $databaseId): PDO
    {
        $key = (string) $databaseId;

        if (isset($this->connections[$key])) {
            try {
                $this->connections[$key]->query('SELECT 1');
                return $this->connections[$key];
            } catch (\PDOException $e) {
                unset($this->connections[$key]);
            }
        }

        $this->connections[$key] = $this->connectionFactory->create($config);
        return $this->connections[$key];
    }

    /**
     * @param array<string, mixed> $arguments
     * @return array{databaseId:int|string, config:array<string, mixed>}
     */
    private function resolveDatabase(array $arguments): array
    {
        if (isset($arguments['databaseId']) && $arguments['databaseId'] !== '') {
            $databaseId = is_numeric($arguments['databaseId']) ? (int) $arguments['databaseId'] : (string) $arguments['databaseId'];
            if (!isset($this->databaseConfigs[$databaseId])) {
                throw new RuntimeException('No database configuration exists for databaseId `' . $databaseId . '`.');
            }

            return array(
                'databaseId' => $databaseId,
                'config' => $this->databaseConfigs[$databaseId],
            );
        }

        $databaseName = isset($arguments['databaseName']) ? trim((string) $arguments['databaseName']) : '';
        if ($databaseName === '') {
            throw new RuntimeException('Provide either `databaseId` or `databaseName`.');
        }

        foreach ($this->databaseConfigs as $databaseId => $config) {
            if ((string) ($config['database'] ?? '') === $databaseName) {
                return array(
                    'databaseId' => $databaseId,
                    'config' => $config,
                );
            }
        }

        throw new RuntimeException('No database configuration exists for databaseName `' . $databaseName . '`.');
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $arguments
     */
    private function resolveSchema(array $config, array $arguments): ?string
    {
        if (isset($arguments['schema']) && trim((string) $arguments['schema']) !== '') {
            return trim((string) $arguments['schema']);
        }

        $dbType = strtolower((string) ($config['dbType'] ?? ''));
        if ($dbType === 'pgsql') {
            return (string) ($config['schema'] ?? 'public');
        }

        if ($dbType === 'mssql' || $dbType === 'sqlsrv') {
            return isset($config['schema']) ? (string) $config['schema'] : 'dbo';
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function listMySqlTables(PDO $pdo, string $databaseName): array
    {
        $statement = $pdo->prepare(
            'SELECT table_name, table_type
             FROM information_schema.tables
             WHERE table_schema = :databaseName
             ORDER BY table_name'
        );
        $statement->execute(array('databaseName' => $databaseName));

        $tables = array();
        foreach ($statement->fetchAll() as $row) {
            $tables[] = array(
                'schema' => $databaseName,
                'name' => $row['table_name'],
                'type' => $row['table_type'],
            );
        }

        return $tables;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function listPgSqlTables(PDO $pdo, string $databaseName, ?string $schema): array
    {
        $sql = 'SELECT table_schema, table_name, table_type
                FROM information_schema.tables
                WHERE table_catalog = :databaseName
                  AND table_schema NOT IN (\'pg_catalog\', \'information_schema\')';
        $params = array('databaseName' => $databaseName);

        if ($schema !== null) {
            $sql .= ' AND table_schema = :schema';
            $params['schema'] = $schema;
        }

        $sql .= ' ORDER BY table_schema, table_name';

        $statement = $pdo->prepare($sql);
        $statement->execute($params);

        $tables = array();
        foreach ($statement->fetchAll() as $row) {
            $tables[] = array(
                'schema' => $row['table_schema'],
                'name' => $row['table_name'],
                'type' => $row['table_type'],
            );
        }

        return $tables;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function listSqliteTables(PDO $pdo): array
    {
        $statement = $pdo->query(
            "SELECT name, type
             FROM sqlite_master
             WHERE type IN ('table', 'view')
               AND name NOT LIKE 'sqlite_%'
             ORDER BY name"
        );

        $tables = array();
        foreach ($statement->fetchAll() as $row) {
            $tables[] = array(
                'schema' => null,
                'name' => $row['name'],
                'type' => strtoupper((string) $row['type']),
            );
        }

        return $tables;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function listMsSqlTables(PDO $pdo, ?string $schema): array
    {
        $sql = 'SELECT TABLE_SCHEMA AS table_schema, TABLE_NAME AS table_name, TABLE_TYPE AS table_type
                FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_TYPE IN (\'BASE TABLE\', \'VIEW\')';
        $params = array();

        if ($schema !== null) {
            $sql .= ' AND TABLE_SCHEMA = :schema';
            $params['schema'] = $schema;
        }

        $sql .= ' ORDER BY TABLE_SCHEMA, TABLE_NAME';

        $statement = $pdo->prepare($sql);
        $statement->execute($params);

        $tables = array();
        foreach ($statement->fetchAll() as $row) {
            $tables[] = array(
                'schema' => $row['table_schema'],
                'name' => $row['table_name'],
                'type' => $row['table_type'],
            );
        }

        return $tables;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function describeMySqlTable(PDO $pdo, string $databaseName, string $tableName): array
    {
        $statement = $pdo->prepare(
            'SELECT c.column_name,
                    c.data_type,
                    c.column_type,
                    c.is_nullable,
                    c.character_maximum_length,
                    c.numeric_precision,
                    c.numeric_scale,
                    c.datetime_precision,
                    c.column_default,
                    c.extra,
                    CASE WHEN pk.column_name IS NULL THEN 0 ELSE 1 END AS is_primary_key
             FROM information_schema.columns c
             LEFT JOIN (
                 SELECT kcu.column_name
                 FROM information_schema.table_constraints tc
                 INNER JOIN information_schema.key_column_usage kcu
                     ON tc.constraint_name = kcu.constraint_name
                    AND tc.table_schema = kcu.table_schema
                    AND tc.table_name = kcu.table_name
                 WHERE tc.constraint_type = \'PRIMARY KEY\'
                   AND tc.table_schema = :databaseNamePk
                   AND tc.table_name = :tableNamePk
             ) pk ON pk.column_name = c.column_name
             WHERE c.table_schema = :databaseName
               AND c.table_name = :tableName
             ORDER BY c.ordinal_position'
        );
        $statement->execute(array(
            'databaseNamePk' => $databaseName,
            'tableNamePk' => $tableName,
            'databaseName' => $databaseName,
            'tableName' => $tableName,
        ));

        $columns = array();
        foreach ($statement->fetchAll() as $row) {
            $columns[] = array(
                'name' => $row['column_name'],
                'type' => $row['data_type'],
                'fullType' => $row['column_type'],
                'nullable' => strtoupper((string) $row['is_nullable']) === 'YES',
                'length' => $this->deriveLength($row),
                'precision' => $this->toNullableInt($row['numeric_precision']),
                'scale' => $this->toNullableInt($row['numeric_scale']),
                'default' => $row['column_default'],
                'isPrimaryKey' => (bool) $row['is_primary_key'],
                'isAutoIncrement' => stripos((string) $row['extra'], 'auto_increment') !== false,
            );
        }

        return $columns;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getMySqlForeignKeys(PDO $pdo, string $databaseName, string $tableName): array
    {
        $statement = $pdo->prepare(
            'SELECT kcu.column_name,
                    kcu.referenced_table_schema,
                    kcu.referenced_table_name,
                    kcu.referenced_column_name,
                    rc.update_rule,
                    rc.delete_rule,
                    kcu.constraint_name
             FROM information_schema.key_column_usage kcu
             LEFT JOIN information_schema.referential_constraints rc
                ON rc.constraint_schema = kcu.constraint_schema
               AND rc.constraint_name = kcu.constraint_name
             WHERE kcu.table_schema = :databaseName
               AND kcu.table_name = :tableName
               AND kcu.referenced_table_name IS NOT NULL
             ORDER BY kcu.ordinal_position'
        );
        $statement->execute(array(
            'databaseName' => $databaseName,
            'tableName' => $tableName,
        ));

        $foreignKeys = array();
        foreach ($statement->fetchAll() as $row) {
            $foreignKeys[] = array(
                'column' => $row['column_name'],
                'referencesSchema' => $row['referenced_table_schema'],
                'referencesTable' => $row['referenced_table_name'],
                'referencesColumn' => $row['referenced_column_name'],
                'onUpdate' => $row['update_rule'],
                'onDelete' => $row['delete_rule'],
                'constraintName' => $row['constraint_name'],
            );
        }

        return $foreignKeys;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function describePgSqlTable(PDO $pdo, string $databaseName, ?string $schema, string $tableName): array
    {
        $schema = $schema ?: 'public';
        $statement = $pdo->prepare(
            'SELECT c.column_name,
                    c.data_type,
                    COALESCE(ft.full_type, c.udt_name) AS full_type,
                    c.is_nullable,
                    c.character_maximum_length,
                    c.numeric_precision,
                    c.numeric_scale,
                    c.datetime_precision,
                    c.column_default,
                    c.is_identity,
                    CASE WHEN pk.column_name IS NULL THEN 0 ELSE 1 END AS is_primary_key
             FROM information_schema.columns c
             LEFT JOIN (
                 SELECT a.attname AS column_name,
                        pg_catalog.format_type(a.atttypid, a.atttypmod) AS full_type
                 FROM pg_attribute a
                 INNER JOIN pg_class cls ON cls.oid = a.attrelid
                 INNER JOIN pg_namespace ns ON ns.oid = cls.relnamespace
                 WHERE cls.relname = :tableNameFormat
                   AND ns.nspname = :schemaFormat
                   AND a.attnum > 0
                   AND NOT a.attisdropped
             ) ft ON ft.column_name = c.column_name
             LEFT JOIN (
                 SELECT kcu.column_name
                 FROM information_schema.table_constraints tc
                 INNER JOIN information_schema.key_column_usage kcu
                     ON tc.constraint_name = kcu.constraint_name
                    AND tc.table_schema = kcu.table_schema
                    AND tc.table_name = kcu.table_name
                 WHERE tc.constraint_type = \'PRIMARY KEY\'
                   AND tc.table_schema = :schemaPk
                   AND tc.table_name = :tableNamePk
             ) pk ON pk.column_name = c.column_name
             WHERE c.table_catalog = :databaseName
               AND c.table_schema = :schema
               AND c.table_name = :tableName
             ORDER BY c.ordinal_position'
        );
        $statement->execute(array(
            'tableNameFormat' => $tableName,
            'schemaFormat' => $schema,
            'schemaPk' => $schema,
            'tableNamePk' => $tableName,
            'databaseName' => $databaseName,
            'schema' => $schema,
            'tableName' => $tableName,
        ));

        $columns = array();
        foreach ($statement->fetchAll() as $row) {
            $defaultValue = $row['column_default'];
            $isIdentity = strtoupper((string) ($row['is_identity'] ?? 'NO')) === 'YES';
            $isSequence = is_string($defaultValue) && strpos($defaultValue, 'nextval(') === 0;

            $columns[] = array(
                'name' => $row['column_name'],
                'type' => $row['data_type'],
                'fullType' => $row['full_type'],
                'nullable' => strtoupper((string) $row['is_nullable']) === 'YES',
                'length' => $this->deriveLength($row),
                'precision' => $this->toNullableInt($row['numeric_precision']),
                'scale' => $this->toNullableInt($row['numeric_scale']),
                'default' => $defaultValue,
                'isPrimaryKey' => (bool) $row['is_primary_key'],
                'isAutoIncrement' => $isIdentity || $isSequence,
            );
        }

        return $columns;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getPgSqlForeignKeys(PDO $pdo, ?string $schema, string $tableName): array
    {
        $schema = $schema ?: 'public';
        $statement = $pdo->prepare(
            'SELECT kcu.column_name,
                    ccu.table_schema AS referenced_table_schema,
                    ccu.table_name AS referenced_table_name,
                    ccu.column_name AS referenced_column_name,
                    rc.update_rule,
                    rc.delete_rule,
                    tc.constraint_name
             FROM information_schema.table_constraints tc
             INNER JOIN information_schema.key_column_usage kcu
                 ON tc.constraint_name = kcu.constraint_name
                AND tc.table_schema = kcu.table_schema
                AND tc.table_name = kcu.table_name
             INNER JOIN information_schema.referential_constraints rc
                 ON rc.constraint_name = tc.constraint_name
                AND rc.constraint_schema = tc.table_schema
             INNER JOIN information_schema.constraint_column_usage ccu
                 ON ccu.constraint_name = rc.unique_constraint_name
                AND ccu.constraint_schema = rc.unique_constraint_schema
             WHERE tc.constraint_type = \'FOREIGN KEY\'
               AND tc.table_schema = :schema
               AND tc.table_name = :tableName
             ORDER BY kcu.ordinal_position'
        );
        $statement->execute(array(
            'schema' => $schema,
            'tableName' => $tableName,
        ));

        $foreignKeys = array();
        foreach ($statement->fetchAll() as $row) {
            $foreignKeys[] = array(
                'column' => $row['column_name'],
                'referencesSchema' => $row['referenced_table_schema'],
                'referencesTable' => $row['referenced_table_name'],
                'referencesColumn' => $row['referenced_column_name'],
                'onUpdate' => $row['update_rule'],
                'onDelete' => $row['delete_rule'],
                'constraintName' => $row['constraint_name'],
            );
        }

        return $foreignKeys;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function describeSqliteTable(PDO $pdo, string $tableName): array
    {
        $identifier = $this->quoteSqliteIdentifier($tableName);
        $statement = $pdo->query('PRAGMA table_info(' . $identifier . ')');
        $rows = $statement->fetchAll();

        $columns = array();
        foreach ($rows as $row) {
            $parsedType = $this->parseSqliteType((string) $row['type']);
            $isPrimaryKey = (int) $row['pk'] > 0;
            $isIntegerPk = $isPrimaryKey && strtoupper($parsedType['type']) === 'INTEGER';

            $columns[] = array(
                'name' => $row['name'],
                'type' => $parsedType['type'],
                'fullType' => (string) $row['type'],
                'nullable' => !((int) $row['notnull'] === 1 || $isPrimaryKey),
                'length' => $parsedType['length'],
                'precision' => $parsedType['precision'],
                'scale' => $parsedType['scale'],
                'default' => $row['dflt_value'],
                'isPrimaryKey' => $isPrimaryKey,
                'isAutoIncrement' => $isIntegerPk,
            );
        }

        return $columns;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getSqliteForeignKeys(PDO $pdo, string $tableName): array
    {
        $identifier = $this->quoteSqliteIdentifier($tableName);
        $statement = $pdo->query('PRAGMA foreign_key_list(' . $identifier . ')');

        $foreignKeys = array();
        foreach ($statement->fetchAll() as $row) {
            $foreignKeys[] = array(
                'column' => $row['from'],
                'referencesSchema' => null,
                'referencesTable' => $row['table'],
                'referencesColumn' => $row['to'],
                'onUpdate' => $row['on_update'],
                'onDelete' => $row['on_delete'],
                'constraintName' => 'fk_' . $tableName . '_' . $row['id'],
            );
        }

        return $foreignKeys;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function describeMsSqlTable(PDO $pdo, string $databaseName, ?string $schema, string $tableName): array
    {
        $schema = $schema ?: 'dbo';
        $statement = $pdo->prepare(
            'SELECT c.COLUMN_NAME AS column_name,
                    c.DATA_TYPE AS data_type,
                    c.IS_NULLABLE AS is_nullable,
                    c.CHARACTER_MAXIMUM_LENGTH AS character_maximum_length,
                    c.NUMERIC_PRECISION AS numeric_precision,
                    c.NUMERIC_SCALE AS numeric_scale,
                    c.DATETIME_PRECISION AS datetime_precision,
                    c.COLUMN_DEFAULT AS column_default,
                    COLUMNPROPERTY(OBJECT_ID(c.TABLE_SCHEMA + \'.\' + c.TABLE_NAME), c.COLUMN_NAME, \'IsIdentity\') AS is_identity,
                    CASE WHEN pk.COLUMN_NAME IS NULL THEN 0 ELSE 1 END AS is_primary_key
             FROM INFORMATION_SCHEMA.COLUMNS c
             LEFT JOIN (
                 SELECT ku.TABLE_SCHEMA, ku.TABLE_NAME, ku.COLUMN_NAME
                 FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc
                 INNER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE ku
                     ON tc.CONSTRAINT_NAME = ku.CONSTRAINT_NAME
                    AND tc.TABLE_SCHEMA = ku.TABLE_SCHEMA
                    AND tc.TABLE_NAME = ku.TABLE_NAME
                 WHERE tc.CONSTRAINT_TYPE = \'PRIMARY KEY\'
             ) pk ON pk.TABLE_SCHEMA = c.TABLE_SCHEMA
                AND pk.TABLE_NAME = c.TABLE_NAME
                AND pk.COLUMN_NAME = c.COLUMN_NAME
             WHERE c.TABLE_CATALOG = :databaseName
               AND c.TABLE_SCHEMA = :schema
               AND c.TABLE_NAME = :tableName
             ORDER BY c.ORDINAL_POSITION'
        );
        $statement->execute(array(
            'databaseName' => $databaseName,
            'schema' => $schema,
            'tableName' => $tableName,
        ));

        $columns = array();
        foreach ($statement->fetchAll() as $row) {
            $columns[] = array(
                'name' => $row['column_name'],
                'type' => $row['data_type'],
                'fullType' => $this->buildMsSqlFullType($row),
                'nullable' => strtoupper((string) $row['is_nullable']) === 'YES',
                'length' => $this->deriveLength($row),
                'precision' => $this->toNullableInt($row['numeric_precision']),
                'scale' => $this->toNullableInt($row['numeric_scale']),
                'default' => $row['column_default'],
                'isPrimaryKey' => (bool) $row['is_primary_key'],
                'isAutoIncrement' => (int) $row['is_identity'] === 1,
            );
        }

        return $columns;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getMsSqlForeignKeys(PDO $pdo, ?string $schema, string $tableName): array
    {
        $schema = $schema ?: 'dbo';
        $statement = $pdo->prepare(
            'SELECT pc.name AS column_name,
                    rs.name AS referenced_table_schema,
                    rt.name AS referenced_table_name,
                    rc.name AS referenced_column_name,
                    fk.update_referential_action_desc AS update_rule,
                    fk.delete_referential_action_desc AS delete_rule,
                    fk.name AS constraint_name
             FROM sys.foreign_key_columns fkc
             INNER JOIN sys.tables pt ON pt.object_id = fkc.parent_object_id
             INNER JOIN sys.schemas ps ON ps.schema_id = pt.schema_id
             INNER JOIN sys.columns pc ON pc.object_id = fkc.parent_object_id
                AND pc.column_id = fkc.parent_column_id
             INNER JOIN sys.tables rt ON rt.object_id = fkc.referenced_object_id
             INNER JOIN sys.schemas rs ON rs.schema_id = rt.schema_id
             INNER JOIN sys.columns rc ON rc.object_id = fkc.referenced_object_id
                AND rc.column_id = fkc.referenced_column_id
             INNER JOIN sys.foreign_keys fk ON fk.object_id = fkc.constraint_object_id
             WHERE ps.name = :schema
               AND pt.name = :tableName
             ORDER BY fkc.constraint_column_id'
        );
        $statement->execute(array(
            'schema' => $schema,
            'tableName' => $tableName,
        ));

        $foreignKeys = array();
        foreach ($statement->fetchAll() as $row) {
            $foreignKeys[] = array(
                'column' => $row['column_name'],
                'referencesSchema' => $row['referenced_table_schema'],
                'referencesTable' => $row['referenced_table_name'],
                'referencesColumn' => $row['referenced_column_name'],
                'onUpdate' => $row['update_rule'],
                'onDelete' => $row['delete_rule'],
                'constraintName' => $row['constraint_name'],
            );
        }

        return $foreignKeys;
    }

    /**
     * @param array<string, mixed> $row
     * @return int|string|null
     */
    private function deriveLength(array $row)
    {
        $characterLength = $this->toNullableInt($row['character_maximum_length'] ?? null);
        if ($characterLength !== null) {
            return $characterLength;
        }

        $precision = $this->toNullableInt($row['numeric_precision'] ?? null);
        $scale = $this->toNullableInt($row['numeric_scale'] ?? null);
        if ($precision !== null && $scale !== null) {
            return $precision . ',' . $scale;
        }

        if ($precision !== null) {
            return $precision;
        }

        $datetimePrecision = $this->toNullableInt($row['datetime_precision'] ?? null);
        if ($datetimePrecision !== null) {
            return $datetimePrecision;
        }

        return null;
    }

    private function toNullableInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    /**
     * @return array{type:string, length:int|string|null, precision:?int, scale:?int}
     */
    private function parseSqliteType(string $type): array
    {
        $normalized = trim($type);
        if ($normalized === '') {
            return array(
                'type' => 'TEXT',
                'length' => null,
                'precision' => null,
                'scale' => null,
            );
        }

        if (preg_match('/^([a-zA-Z0-9_ ]+)\(([^\)]+)\)$/', $normalized, $matches) === 1) {
            $baseType = strtoupper(trim($matches[1]));
            $lengthPart = trim($matches[2]);
            if (strpos($lengthPart, ',') !== false) {
                $parts = array_map('trim', explode(',', $lengthPart, 2));
                return array(
                    'type' => $baseType,
                    'length' => $lengthPart,
                    'precision' => isset($parts[0]) && $parts[0] !== '' ? (int) $parts[0] : null,
                    'scale' => isset($parts[1]) && $parts[1] !== '' ? (int) $parts[1] : null,
                );
            }

            return array(
                'type' => $baseType,
                'length' => is_numeric($lengthPart) ? (int) $lengthPart : $lengthPart,
                'precision' => null,
                'scale' => null,
            );
        }

        return array(
            'type' => strtoupper($normalized),
            'length' => null,
            'precision' => null,
            'scale' => null,
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function buildMsSqlFullType(array $row): string
    {
        $type = (string) $row['data_type'];
        $characterLength = $this->toNullableInt($row['character_maximum_length'] ?? null);
        if ($characterLength !== null) {
            return $type . '(' . ($characterLength < 0 ? 'max' : $characterLength) . ')';
        }

        $precision = $this->toNullableInt($row['numeric_precision'] ?? null);
        $scale = $this->toNullableInt($row['numeric_scale'] ?? null);
        if ($precision !== null && $scale !== null) {
            return $type . '(' . $precision . ',' . $scale . ')';
        }

        return $type;
    }

    private function quoteSqliteIdentifier(string $identifier): string
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }
}
