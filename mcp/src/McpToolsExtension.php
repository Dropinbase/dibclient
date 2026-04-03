<?php

declare(strict_types=1);

namespace DropinScript\Mcp;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Additional MCP tools useful for creating Dropin Script.
 *
 * Each public method corresponds to a tool that the MCP server exposes.
 */
final class mcpToolsExtension
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
     * Database index (from $DIBDB in configs/Dib.php) where pef_database and
     * related system tables live.
     *
     * @var int
     */
    private $dibDbIndex;

    /**
     * Database index (from $LOGINDB in configs/Dib.php) used as the default
     * DATABASE_INDEX when querying app defaults from pef_database.
     *
     * @var int
     */
    private $loginDbIndex;

    /**
     * @var array<string, PDO>
     */
    private $connections = array();

    private $classesLoaded = false;

    /**
     * @param array<int|string, array<string, mixed>> $databaseConfigs
     */
    public function __construct(
        array $databaseConfigs,
        DatabaseConnectionFactory $connectionFactory,
        int $dibDbIndex,
        int $loginDbIndex
    ) {
        $this->databaseConfigs = $databaseConfigs;
        $this->connectionFactory = $connectionFactory;
        $this->dibDbIndex = $dibDbIndex;
        $this->loginDbIndex = $loginDbIndex;
    }

    // Load Dropinbase framework and call AiTools.php method
    private function runDibTool(string $function, array $params): string
    {

        if(!$this->classesLoaded) {
            $path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'configs' . DIRECTORY_SEPARATOR . 'Dib.php';

            if(!file_exists($path)) 
                throw new RuntimeException('MCP server could not find Dib.php config file at expected path: ' . $path);

            require_once $path;
            
            require_once \DIB::$SYSTEMPATH . 'DibApp.php';
            require_once \DIB::$SYSTEMPATH . 'system' . DIRECTORY_SEPARATOR . 'Controller.php';
            require_once \DIB::$SYSTEMPATH . 'system' . DIRECTORY_SEPARATOR . 'Cache.php';
            require_once \DIB::$SYSTEMPATH . 'system' . DIRECTORY_SEPARATOR . 'Log.php';
            require_once \DIB::$SECRETSPATH . 'Conn.php';

            $compPath = \DIB::$SYSTEMPATH . 'components' . DIRECTORY_SEPARATOR;

            date_default_timezone_set(\DIB::$TIMEZONE);

            \DIB::$USER = 'system';
            \DIB::$PERMGROUP = 'x1x';
            \DibApp::$containerName = 'none';
            
            $_SERVER['REMOTE_ADDR']= 'system';
            $_SERVER['REQUEST_URI'] = 'system';

            $_SESSION = array();

                \DibApp::$sessionId = \DibApp::randomToken(8);

            // Load useful Dropinbase framework files

            require_once $compPath . 'database' . DIRECTORY_SEPARATOR . 'Database.php';
            require_once $compPath . 'database' . DIRECTORY_SEPARATOR . 'DAuditTrail.php';
            require_once $compPath . 'database' . DIRECTORY_SEPARATOR . 'DSqlConvert.php';
            require_once $compPath . 'database' . DIRECTORY_SEPARATOR . 'Crud.php';
            require_once $compPath . 'database' . DIRECTORY_SEPARATOR . 'DCrudParams.php';
            require_once $compPath . 'actions' . DIRECTORY_SEPARATOR . 'ClientFunctions.php';
            require_once $compPath . 'actions' . DIRECTORY_SEPARATOR . 'SyncFunctions.php';
            require_once $compPath . 'actions' . DIRECTORY_SEPARATOR . 'DibFunctions.php';
            require_once $compPath . 'security' . DIRECTORY_SEPARATOR . 'PPermission.php';
            
            $this->classesLoaded = true;
        }

        \DibApp::load('dibAi', 'AiTools.php', 'components');
        \DibApp::load('dibAi', 'ExportDropinScript.php', 'components');
        \DibApp::load('dibAi', 'AiUtilityFn.php', 'components');
        $a = new \AiTools();

        if(!method_exists($a, $function))
            throw new RuntimeException('Method ' . $function . ' does not exist in DIB framework AiTools class.');
        
        $threadId=null; $chatId=null; $model=null; $promptId=null;
        $result =  $a->$function($params, $body, $memory, $threadId, $chatId, $model, $promptId);

        if(isset($result[0]) && $result[0] === 'error')
            throw new RuntimeException('Error from DIB framework method ' . $function . ': ' . ($result[1] ?? 'Unknown error'));

        $replaceWith = $result['replace_with'] ?? null;

        if($replaceWith === 'error')
            throw new RuntimeException('Error from DIB framework method ' . $function . ': ' . $passOnTo);

        return $replaceWith;
    }

    /**
     * Retrieve the detailed structure of the specified container(s).
     *
     * @param string $containerNameList Comma-separated list of container names or captions, e.g. "mainPage,invoiceTab"
     * @return string structure of the container(s) as returned by the Dropinbase framework method AiTools::get_container_structure, which includes details of all nested sub-containers, ports, items, and their relationships.
     */
    public function getContainerStructure(string $containerNameList): string
    {
        // Call Dropinbase framework method using Async call
        $params = array(
            'container_name_list' => $containerNameList
        );
        $result = $this->runDibTool('get_container_structure', $params);

        return $result;

    }

    /**
     * Retrieve the ports of the specified container(s).
     *
     * @param string $containerNameList Comma-separated list of container names or captions, e.g. "mainPage,invoiceTab"
     * @return string detailed port records of the container(s)
     */
    public function getContainerPorts(string $containerNameList): string
    {
        // Call Dropinbase framework method using Async call
        $params = array(
            'container_name_list' => $containerNameList
        );
        $result = $this->runDibTool('get_container_ports', $params);

        return $result;
    }

    /**
     * Retrieve application-level defaults from the pef_database record.
     *
     * By default this uses the configured $LOGINDB index, but callers can
     * override via databaseId or databaseName in $arguments.
     *
     * @param array<string, mixed> $arguments
     * @return array<string, mixed>
     */
    public function getAppDefaults(array $arguments = array()): array
    {
        $databaseIndex = $this->resolveAppDefaultsDatabaseIndex($arguments);

        $pdo = $this->getConnection($this->dibDbIndex);
        $dbType = $this->getDbType($this->dibDbIndex);

        $concatPortItem = $this->buildConcat($dbType, array("c.name", "'.'", "i.name"));
        $concatMenuItem = $this->buildConcat($dbType, array("cmn.name", "'.'", "mn.name"));

        $sql = "SELECT d.name AS default_dropin_id,
                    {$concatPortItem} AS default_port_item_id,
                    cg.name AS default_table_grid_template,
                    cf.name AS default_form_template,
                    cqg.name AS default_sql_grid_template,
                    {$concatMenuItem} AS default_menu_item_parent

                FROM pef_database db
                    INNER JOIN pef_dropin d ON db.pef_dropin_id = d.id
                    INNER JOIN pef_item i ON db.pef_port_item_id = i.id
                    INNER JOIN pef_container c ON i.pef_container_id = c.id
                    INNER JOIN pef_design dg ON db.pef_grid_design_id = dg.id
                    INNER JOIN pef_container cg ON dg.pef_container_id = cg.id
                    INNER JOIN pef_design df ON db.pef_form_design_id = df.id
                    INNER JOIN pef_container cf ON df.pef_container_id = cf.id
                    INNER JOIN pef_design dqg ON db.pef_sqlgrid_design_id = dqg.id
                    INNER JOIN pef_container cqg ON dqg.pef_container_id = cqg.id
                    LEFT JOIN pef_item mn ON db.pef_menu_item_id = mn.id
                    LEFT JOIN pef_container cmn ON mn.pef_container_id = cmn.id

                WHERE db.id = :databaseIndex";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(array('databaseIndex' => $databaseIndex));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            throw new RuntimeException(
                'No app defaults found in pef_database for database index ' . $databaseIndex . '.'
            );
        }

        return array(
            'defaults' => $row,
            'description' => array(
                'default_dropin_id' => 'The dropin_id field value when creating a container.',
                'default_port_item_id' => 'The default item_id field value for port records of containers, e.g. - port: item_id=mainPage.invoiceTab',
                'default_table_grid_template' => 'The default template name to use when creating grids under option A.',
                'default_form_template' => 'The default template name to use when creating forms under option A.',
                'default_sql_grid_template' => 'The default template name to use when creating grids based on SQL under option A.',
                'default_menu_item_parent' => 'The default parent item_id of new menu items added to the main/base container.',
            ),
            'databaseIndex' => $databaseIndex,
        );
    }

    /**
     * Resolve which pef_database.id to use for app-default lookups.
     *
     * @param array<string, mixed> $arguments
     */
    private function resolveAppDefaultsDatabaseIndex(array $arguments): int
    {
        if (isset($arguments['databaseId']) && $arguments['databaseId'] !== '') {
            $databaseIdRaw = $arguments['databaseId'];
            if (!is_numeric($databaseIdRaw)) {
                throw new RuntimeException('`databaseId` must be numeric.');
            }

            $databaseId = (int) $databaseIdRaw;
            if (!isset($this->databaseConfigs[$databaseId])) {
                throw new RuntimeException('No database configuration exists for databaseId `' . $databaseId . '`.');
            }

            return $databaseId;
        }

        $databaseName = isset($arguments['databaseName']) ? trim((string) $arguments['databaseName']) : '';
        if ($databaseName !== '') {
            foreach ($this->databaseConfigs as $databaseId => $config) {
                $configuredName = (string) ($config['database'] ?? '');
                if (strcasecmp($configuredName, $databaseName) === 0) {
                    if (!is_numeric($databaseId)) {
                        throw new RuntimeException('Resolved database id for `' . $databaseName . '` is not numeric.');
                    }

                    return (int) $databaseId;
                }
            }

            throw new RuntimeException('No database configuration exists for databaseName `' . $databaseName . '`.');
        }

        return $this->loginDbIndex;
    }

    // ------------------------------------------------------------------
    // Internal helpers
    // ------------------------------------------------------------------

    /**
     * Build a string-concatenation expression appropriate for the database
     * engine in use.
     *
     * @param string[] $parts SQL expressions / literals to concatenate
     */
    private function buildConcat(string $dbType, array $parts): string
    {
        switch ($dbType) {
            case 'mysql':
                return 'CONCAT(' . implode(', ', $parts) . ')';

            case 'pgsql':
            case 'sqlite':
                return implode(' || ', $parts);

            case 'mssql':
            case 'sqlsrv':
                return implode(' + ', $parts);

            default:
                return 'CONCAT(' . implode(', ', $parts) . ')';
        }
    }

    /**
     * @return string The normalised dbType (mysql, pgsql, sqlite, mssql, …)
     */
    private function getDbType(int $databaseId): string
    {
        if (!isset($this->databaseConfigs[$databaseId])) {
            throw new RuntimeException('No database configuration exists for databaseId `' . $databaseId . '`.');
        }

        return strtolower((string) ($this->databaseConfigs[$databaseId]['dbType'] ?? ''));
    }

    /**
     * Get or create a cached PDO connection, with stale-connection recovery.
     */
    private function getConnection(int $databaseId): PDO
    {
        $key = (string) $databaseId;

        if (isset($this->connections[$key])) {
            try {
                $this->connections[$key]->query('SELECT 1');
                return $this->connections[$key];
            } catch (PDOException $e) {
                unset($this->connections[$key]);
            }
        }

        if (!isset($this->databaseConfigs[$databaseId])) {
            throw new RuntimeException('No database configuration exists for databaseId `' . $databaseId . '`.');
        }

        $this->connections[$key] = $this->connectionFactory->create($this->databaseConfigs[$databaseId]);
        return $this->connections[$key];
    }
}
