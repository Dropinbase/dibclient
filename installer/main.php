<?php 

/* 
 * Copyright (C) Dropinbase - All Rights Reserved
 * This code, along with all other code under the root /dropinbase folder, is provided "As Is" and is proprietary and confidential
 * Unauthorized copying or use, of this or any related file, is strictly prohibited
 * Please see the License Agreement at www.dropinbase.com/license for more info
*/

ini_set('display_errors', 0); // so that a fatal error will still send a valid json response to the client

register_shutdown_function('shutDownFunction');

require_once "Db.php";

Install::init($DIR);

function shutDownFunction() {
	$error = error_get_last();

	// Handle PHP errors
	if(isset($error['type']) && (int)$error['type'] < 16 && (int)$error['type'] > 0) {
		$errfile = $error["file"];
		
		if(!empty($errfile)) {
			$errline = $error["line"];
			$errstr  = $error["message"];
			
			$msg = "$errstr\r\n in $errfile on line $errline.";
			
			$response = array();
			$response[] = array(
				"name"  => 'Fatal PHP Error',
				"ready" => false,
				"notes" => $msg
			);
			
			// Change the 500 header status code to 200, else response will not show
			header($_SERVER['SERVER_PROTOCOL'] . ' 200 OK', true, 200);
			
			echo json_encode(array('success'=>FALSE, 'messages'=>$response, 'override'=>false));
			die();
		}
	}
	
	// Note that success=>FALSE is also used in some cases for just communicating information to the client in DIB::$ACTION
	if (!empty(DIB::$ACTION)) {
		echo json_encode(array('success'=>FALSE, 'messages'=>DIB::$ACTION, 'override' => Install::$showOverrideButton));

	} else {
		// All good, echo success	
		echo json_encode(array('success'=>TRUE, 'messages'=>null, 'override' => Install::$showOverrideButton));
	}
	
}

class Install {
	
	private static $dibAngularVersion = '19.2.7';
	private static $dibNodeVersion = '20.19.0';
	private static $nodeJsCheckSumx86 = "68d09bc053428bec1e1c76db1d3b73db8976af1387a0458a53c95b4f972f7427"; // get from eg https://nodejs.org/dist/v20.19.0/SHASUMS256.txt.asc
	private static $nodeJsCheckSumx64 = "e9032adb422bf332001a5d8c799621307b8f2f2f8bcf3071b8b6998923ddc20e";

	public static $basePath = '';
	public static $dibPath = '';
	public static $query = null;
	public static $showOverrideButton = FALSE;
	private static $curl = null;

	private static $dibDomain = 'https://dibdev.cdn.co.za';
	
	public static function init($path) {
		self::$basePath = $path . DIRECTORY_SEPARATOR;

		if(!empty(DIB::$SYSTEMPATH) && file_exists(DIB::$SYSTEMPATH))
			self::$dibPath = DIB::$SYSTEMPATH;

		elseif(file_exists($path . DIRECTORY_SEPARATOR . 'dropinbase' . DIRECTORY_SEPARATOR . 'DibApp.php'))
			self::$dibPath = $path . DIRECTORY_SEPARATOR . 'dropinbase' . DIRECTORY_SEPARATOR;

		elseif(file_exists($path . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'dropinbase' . DIRECTORY_SEPARATOR . 'dropinbase' . DIRECTORY_SEPARATOR . 'DibApp.php'))
			self::$dibPath = $path . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'dropinbase' . DIRECTORY_SEPARATOR . 'dropinbase' . DIRECTORY_SEPARATOR;
			
    }

    private static function getResponse($name, $ready, $exceptionMsg) {
		return array(
			"name"  => $name,
        	"ready" => $ready,
        	"notes" => $exceptionMsg
       	);
    }

	public static function signIn($params) {
		$response = array();

		self::signUserIn($response, $params);

		$result = self::signUserIn($response, $params);
		if($result !== true) {
			DIB::$ACTION = $response[0];
			return false;
		}

		if(!empty($response)) {
			DIB::$ACTION = (empty(DIB::$ACTION)) ? array() : array_merge(DIB::$ACTION, $response);
		}

		return TRUE;
	}

	public static function checkDibInstall() {
		if(!empty(self::$dibPath))
			DIB::$ACTION[] = self::getResponse('File Exists', false, "An installation of the Dropinbase framework was found in " . self::$dibPath . "<br>You can Skip this step below, or<br>if you proceed the existing framework files will be upgraded to the latest version");
		else
			DIB::$ACTION[] = self::getResponse('File Exists', false, "Installation path: " . self::$basePath . 'dropinbase');

		return TRUE;
	}

	public static function configureDib($params) {

		$result = self::checkFiles($response);
		if($result !== true) {
			DIB::$ACTION = $response[0];
			return false;
		}

		if (strtolower(substr(php_uname(), 0, 7)) === "windows")
			self::createScriptWindows($params);
		else
			self::createScriptLinux($params);

		return TRUE;
	}
	
    public static function testPhp($params=array()) {
    	$response = array();
        
    	self::checkRequiredPhpApacheModules($response);
    	
    	self::phpVer($response);
    	
    	if(!empty($response)) {
			self::$showOverrideButton = true;
			DIB::$ACTION = $response;
		}
    	
    	// No errors
		return TRUE;
    }

    public static function saveTestDb($params=array()) {
    	$response = array();
		
    	$result = self::checkFilesAndDbConn($response, $params);
    	
    	if(!empty($response))
            DIB::$ACTION = $response;
    	
    	// No errors
		return TRUE;
    }
    
    public static function updateIndex($params=array()) {
    	$response = array();
		
    	$result = self::updateIndexFile($response);
    	
    	if(!empty($response))
            DIB::$ACTION = $response;
    	
    	// No errors
		return TRUE;
    }

	/**
	* Changes permissions recusively on files and directories within $dir
	* @param string $dir folderpath
	* @param int $dirPermissions permissions for folders
	* @param int $filePermissions permissions for files
	*/
	private static function chmod_r($dir, $dirPermissions, $filePermissions) {
		//if(strtolower(substr(php_uname(), 0, 7)) === 'windows') return TRUE;

		$dp = opendir($dir);
	   	while($file = readdir($dp)) {
	     	if (($file == ".") || ($file == ".."))
	        	continue;

	    	$fullPath = $dir."/".$file;

		    if(is_dir($fullPath)) {
		        // echo('DIR:' . $fullPath . "\n");
		        if(@chmod($fullPath, $dirPermissions)===FALSE) return FALSE;
		        if(!self::chmod_r($fullPath, $dirPermissions, $filePermissions)) return FALSE;
		    } else {
		        // echo('FILE:' . $fullPath . "\n");
		        if(@chmod($fullPath, $filePermissions)===FALSE) return FALSE;
		    }
	    }
		closedir($dp);
		return TRUE;
	}

    private static function checkConnAttributes($c) {
		$dbType = (isset($c['dbType'])) ? $c['dbType'] : 'mysql';

		if($dbType === 'sqlite')
			$perfect = array('foreignKeyConstraints', 'host', 'charset', 'dbType', 'emulatePrepare', 'dbDropin', 'systemDropin');
		else
    		$perfect = array('database', 'dbType', 'username', 'password', 'host', 'port', 'emulatePrepare', 'charset', 'dbDropin', 'systemDropin');

		if($dbType === 'pgsql')
			$perfect[] = 'schema';
		
		$missing = array();
		foreach ($perfect as $key) {
			if(!array_key_exists($key, $c))
				$missing[]=$key;
		}
		
		if(!empty($missing))
			return implode (', ', $missing);
		return '';
	}
	
    private static function checkFilesAndDbConn(&$response, $params, $finalVerifyInstall=FALSE) {
		$apache = array();
		$php = array();
		
		// Check for required files and folders
        $result = self::checkFiles($response, TRUE);
		if($result !== true) return false;

		if($finalVerifyInstall === FALSE) {

			if(!array_key_exists('schema', $params))
				$params['schema'] = '';

			list($dbType, $host, $port, $database, $username, $password, $schema) = array(
				$params['dbType'], $params['host'], $params['port'], $params['database'], $params['username'], $params['password'], $params['schema']
			);
			
			if($dbType !== 'sqlite' && (empty($params['port']) || $params['port'] != (string)(int)$params['port'])) {
				$mysql = ($dbType == 'mysql') ? "(for MySQL it is normally 3306, and for MariaDb, 3307)" : '';
				$response[] = self::getResponse('Database Connection', false, "The 'port' attribute must be an integer $mysql. Please amend and try again.");
				return FALSE;
			}

			if($dbType === 'pgsql' && empty($params['schema'])) {
				$response[] = self::getResponse('Database Connection', false, "The 'schema' attribute is required for PostgreSQL connections. Please amend and try again.");
				return FALSE;
			}

			if($dbType === 'sqlite') {
				if(empty($params['host'])) {
					$response[] = self::getResponse('Database Connection', false, "The 'host' attribute is required for SQLite connections. Please amend and try again.");
					return FALSE;
				}

			} elseif(empty($params['host']) || empty($params['database']) || empty($params['username'])) {
				$response[] = self::getResponse('Database Connection', false, "The following values are all required: host, database, username, port. Please amend and try again.");
				return FALSE;
			}
		}

	  	// Load Conn.php and try to connect to db's
		$connPath = (empty(DIB::$SECRETSPATH)) ? self::$basePath  . 'secrets' . DIRECTORY_SEPARATOR . 'Conn.php' : DIB::$SECRETSPATH . 'Conn.php';
		$usingExistingConn = FALSE;

        if(!file_exists($connPath)) {

			if($finalVerifyInstall === FALSE) {
				if(!is_writable(dirname($connPath))) {
					$response[] = self::getResponse('File Permissions', true, "The '$connPath' file does not exist, and the webserver does not have permissions to create the file. Either provide the necessary permissions or create the Conn.php file manually (see /secrets/Example_Conn.php).");
					return FALSE;
				}

				// Create the file
				$result = self::createConn($connPath, 1, $dbType, $host, $port, $database, $schema, $username, $password, $response);
				if($result === FALSE) return FALSE;

			} else {
				$response[] = self::getResponse('Database Connection', false, "The database connection file ($connPath) does not exist. Please create the file manually (see /secrets/Example_Conn.php).");
				return FALSE;
			}
		
		} else
			$usingExistingConn = TRUE;
		
		require $connPath;
		
		if(empty(DIB::$DATABASES) || empty(DIB::$DATABASES[1]) || empty(DIB::$DATABASES[1]['host'])) { // The dibclient default Conn.php has an empty host
			if($finalVerifyInstall === TRUE) {
				$response[] = self::getResponse('Database Connection', false, "The database connection file ($connPath) is corrupt or empty. It should have a entry for the Dropinbase database in index position 1. Please create the file manually (see /secrets/Example_Conn.php).");
				return FALSE;
				
			} else {
				$result = self::createConn($connPath, 1, $dbType, $host, $port, $database, $schema, $username, $password, $response);
				if($result === FALSE) return FALSE;
				sleep(1); // If we don't sleep, we get the old results
				require $connPath;
			}
		}

		if($finalVerifyInstall === FALSE && $usingExistingConn && (($dbType == 'sqlite' && DIB::$DATABASES[1]['host'] !== 'host') || ($dbType != 'sqlite' && (DIB::$DATABASES[1]['database'] !== $database || DIB::$DATABASES[1]['dbType'] !== $dbType)))) {
			$result = self::createConn($connPath, 1, $dbType, $host, $port, $database, $schema, $username, $password, $response);
			if($result === FALSE) return FALSE;

			sleep(1); // If we don't sleep, we get the old results
			require $connPath;
			$usingExistingConn = false;
		}

		$dbKeys = array_keys(DIB::$DATABASES);
        $dbIndex = $dbKeys[0];

		// Check the db connection details

		$c = DIB::$DATABASES[$dbIndex];
		$missing = self::checkConnAttributes($c);

		if($missing !== '') {
			$response[] = self::getResponse('Database Connection', true, "The entry in the database connection file ($connPath) is missing the following attributes:<br><b>$missing</b><br>Please amend, or delete the Conn.php file so that it can be created again.");
			return FALSE;
		}

		// Get the connection string
		
		$host = $c['host'];
		$port = $c['port'] ?? '';
		$database = $c['database'] ?? '';
		$username = $c['username'] ?? '';
		$password = $c['password'] ?? '';
		$schema = $c['schema'] ?? '';

		if(empty(self::$dibPath) || !file_exists(self::$dibPath . 'DibApp.php')) {
			$response[] = self::getResponse('File Permissions', true, "The Dropinbase framework has not been installed yet. First execute that step and try again.");
			return FALSE;
		}

		$dropinArr = array (
			'mysql' => 'dibMySqlPdo',
			'pgsql' => 'dibPgSqlPdo',
			'sqlite' => 'dibSqlitePdo',
			'mssql' => 'dibMsSqlPdo'
		);

		if (!isset($dropinArr[$dbType])) {
			$response[] = self::getResponse('Database Connection', true, "A Dropinbase framework class $port file for '$dbType' does not exist. Please check the Dropinbase installation and try again.");
			return FALSE;
		}

		$dbDropin = $dropinArr[$dbType];

		$dropinFile = self::$dibPath . 'dropins' . DIRECTORY_SEPARATOR . 'setData' . DIRECTORY_SEPARATOR . $dbDropin . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . $dbDropin . 'Schema.php';
		if(!file_exists($dropinFile)) {
			$response[] = self::getResponse('Database Connection', true, "The dropinbase database connection file ($dropinFile) does not exist. Please check the Dropinbase installation or the Dropinbase database connection file.");
			return FALSE;
		}

		require_once $dropinFile;

		$dbDropinCls = $dbDropin . 'Schema';

		$conCls = New $dbDropinCls();

		$connArray = $conCls->getConnDetails($host, $port, '');
		
		// Test connection to dropinbase db - see if pef_content_type exists
		$connStr = str_replace('{database}', $database, $connArray['connStr']);

		Db::setConn($connStr, $dbType, $username, $password);

		$result = Db::execute("SELECT content_type FROM pef_content_type");
		if(Db::count()>1) {

			if($usingExistingConn === TRUE && $finalVerifyInstall === FALSE) {
				$response[] = self::getResponse('Database Connection', false, "<br>Note, the /secrets/Conn.php file already existed, referencing an existing Dropinbase database which will be used.<br><br>You can proceed to the next step.");
				return FALSE;
			}
			return TRUE;

		} elseif($finalVerifyInstall === TRUE) {
			$response[] = self::getResponse('Database Connection', false, "Could not retrieve any data from the pef_content_type table in the dropinbase database. The database connection is faulty, or the database tables/records were not created correctly.<br>Please review the first entry of the database connection file ($connPath) that should reference the dropinbase database, and check if the tables and records exist.<br>Database error: " . Db::lastErrorAdminMsg());
			return FALSE;
		}

		// Check if the database exists, and if not, create it
		$dbExists = false;

		if($dbType == 'sqlite')
			$dbExists =  file_exists($host);

		elseif($dbType =='pgsql') {
			$sql = "SELECT 1 FROM pg_database WHERE datname = '$database'";
			$result = Db::execute($sql);
			$dbExists = (Db::count() > 0);

		} elseif($dbType == 'mssql' || $dbType == 'sqlsrv') {
			$sql = "SELECT name FROM sys.databases WHERE name = '$database'";
			$result = Db::execute($sql);
			$dbExists = (Db::count() > 0);

		} else {
			// MySQL and other databases that support SHOW DATABASES
			$sql = "SHOW DATABASES LIKE '$database'";
			$result = Db::execute($sql);
			$dbExists = (Db::count() > 0);
		}

		if($dbExists === false) {
			if($dbType == 'sqlite') {
				$username = null;
				$password = null;
				$database = $host;
			}

			$createStmt = str_replace('{database}', $database, $connArray['createStmt']);

			if($dbType == 'pgsql' && $schema != 'public') {
				$createSchemaStmt = str_replace('{schema}', $schema, $connArray['createSchemaStmt']);
			} else {
				$createSchemaStmt = '';
			}

			$fallbackDbList = $connArray['fallbackDbList'] ?? array();

			foreach ($fallbackDbList as $db) {
				$dsn = str_replace('{fallbackDb}', $db, $connArray['createDbConnStr']);
				
				try {
					$pdo = new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

					if(!empty($createStmt)) // skip following for Sqlite
						$result = $pdo->exec($createStmt);

					if(!empty($createSchemaStmt)) {
						try {
							$pdo->exec($createSchemaStmt);
						} catch (PDOException $e) {
							$response[] = self::getResponse('Database Connection', false, "Created database '$database' successfully, but could not create the schema using:<br>$createSchemaStmt<br> Database error: " .  $e->getMessage());
							return FALSE;
						}
					}

					if($result === 0 || $result === true) break; // 0 rows affected

				} catch (PDOException $e) {
					$response[] = self::getResponse('Database Connection', false, "Could not create the Dropinbase database ($database) using the following connection properties:<br>connection string=$connStr, username=$username, password=$password.<br>Please check entry $dbIndex of the database connection file (/secrets/Conn.php).<br> Database error: " .  $e->getMessage() . "<br>The following SQL failed:<br> " . $createStmt);
					return FALSE;
				}
			}
		}

		// Add tables to dropinbase database
		Db::setConn($connStr, $dbType, $username, $password);

		$result = self::createTables($dbType, $response);

		if($result === TRUE && $usingExistingConn === TRUE) {
			$response[] = self::getResponse('Database Connection', false, "<br>Note, the /secrets/Conn.php file already existed, which was reused. The installer created the '$database' database successfully.<br><br>You can proceed to the next step.");
			return FALSE;
		}

		if($result === true) {
			$response[] = self::getResponse('Database Connection', true, "The database connection was successful and the '$database' database was created successfully.");
		}

		return $result;
	}

	private static function checkFiles(&$response, $finalVerifyInstall=FALSE) {
		// Check folder structure
		$configsPath = self::$basePath  . 'configs';

        if(!file_exists($configsPath . DIRECTORY_SEPARATOR . 'DibTmpl.php')) {
            $response[] = self::getResponse('Folder structure', true, "The '" . self::$basePath  . 'configs'. "' folder with its required files like 'DibTmpl.php' does not exist. Download the .zip file from Github and recreate the dropinbase client folder structure. Alternatively, copy this folder (and other missing files) from another DIB installation.");
            return FALSE;
        }

		if(!file_exists($configsPath . DIRECTORY_SEPARATOR . 'AllowedFileExtAndFolders.php')) {
            $response[] = self::getResponse('Folder structure', true, "The '" . self::$basePath  . 'configs'. "' folder with its required file 'AllowedFileExtAndFolders.php' does not exist. Download the .zip file from Github and recreate the dropinbase client folder structure. Alternatively, copy this folder (and other missing files) from another DIB installation.");
            return FALSE;
        }

		if(!file_exists($configsPath . DIRECTORY_SEPARATOR . 'Environment.php')) {
            $response[] = self::getResponse('Folder structure', true, "The '" . self::$basePath  . 'configs'. "' folder with its required file 'Environment.php' does not exist. Download the .zip file from Github and recreate the dropinbase client folder structure. Alternatively, copy this folder (and other missing files) from another DIB installation.");
            return FALSE;
        }
		
        if(!file_exists($configsPath . DIRECTORY_SEPARATOR . 'Dib.php') && !is_writable($configsPath) && $finalVerifyInstall === FALSE) {
            $response[] = self::getResponse('File Permissions', true, "The webserver lacks permissions to create the Dib.php file in the '$configsPath' folder. Please amend using the chmod and chown commands. Once it is created, you can reset the folder permissions to read-only.");
            return FALSE;
        }
        
        $filesPath = self::$basePath  . 'files';
        if(!file_exists($filesPath)) {
            $response[] = self::getResponse('Folder structure', true, "The '$filesPath' folder does not exist. Download the dibclient project's .zip file from Github and recreate the dropinbase client folder structure. Alternatively, get this folder (and other missing files) from another working DIB installation.");
            return FALSE;
        }
        
        // Check runtime path
		$runtimePath = self::$basePath  . 'runtime' . DIRECTORY_SEPARATOR . 'tmp';

		$result = self::createPath($runtimePath);
		if($result === false) {
			$response[] = self::getResponse('File Permissions', true, "The webserver lacks permissions to create the '$runtimePath' folder. Please amend using the chmod and chown commands, and try again.");
			return FALSE;
		}
		
        // Note, runtime path is configurable in Dib.php
        
        if(!is_writable($runtimePath)) {
            $response[] = self::getResponse('File Permissions', true, "The webserver lacks permissions to create files in the '$runtimePath' folder. Please amend using the chmod and chown commands, and try again.");
            return FALSE;
        }

		return TRUE;
	}
	
	private static function createTables($dbType, &$response) {

		// use glob to get file named dropinbase_xxx_$dbType.sql where xxx is a number
		$files = glob(self::$dibPath . 'sql' . DIRECTORY_SEPARATOR . 'dropinbase_*_' . $dbType . '.sql');
		if(empty($files)) {
			$response[] = self::getResponse('Database Connection', false, "Could not find the Dropinbase SQL file for the database type '$dbType'. Please check the installation files and try again.");
			return FALSE;
		}

		$filePath = $files[0];
		
		// Get sql file contents
		$sql = file_get_contents($filePath);
		
		// Temporary variable, used to store current query
		$templine = '';

		// Loop through each line
		$lines = explode("\n", $sql);

		unset($sql);

		foreach ($lines as $key=>$line){
			// Skip it if it's a comment
			//if ($line!=='' && substr($line, 0, 2) !== '--') {
			// Add this line to the current segment
			$templine .= $line;
			

			// If it has a semicolon at the end, it's the end of the query
			if (substr(trim($line), -1, 1) == ';'){
				// sqlite and mssql requires new lines "as is" in values in the SQL, so we need to double-check that this is the end of the SQL statement
				if(strpos(trim($templine), 'INSERT ') === 0 && substr(trim($line), -16) !== ' -- END OF STMT;')
					continue;

				// Perform the query
				$result = Db::execute($templine);


				if($result === FALSE) {
					file_put_contents('C:/temp' . DIRECTORY_SEPARATOR . 'aaaaaaa.log',$templine);

					$response[] = self::getResponse('Database Connection', false, "Could not create the Dropinbase tables. Please check the database user permissions and the connection properties (/secrets/Conn.php).<br> Database error: " . Db::lastErrorAdminMsg() . "<br>The following SQL failed: " . $templine);
					return FALSE;
				}
					
				// Reset temp variable to empty
				$templine = '';
			}	
		}

        return TRUE;
		
	}
	
	private static function signUserIn(&$response, $params) {

		if(empty($params['email']) || empty($params['password'])) {
			$response[] = self::getResponse('SignIn', false, "First provide both your registered Dropinbase 'email' and 'password' and try again.");
			return false;
		}

		$un = $params['email'];
		$pw = $params['password'];

	    if(strlen($un) > 180) {
			$response[] = self::getResponse('SignIn', false, "The email address provided to sign-in is not valid. Please try again.");
			return false;
		}

		if(strlen($pw) > 80) {
			$response[] = self::getResponse('SignIn', false, "The password provided to sign-in is not valid. Please try again.");
			return false;
		}

		require_once __DIR__ . DIRECTORY_SEPARATOR . "DCurl.php";

		self::$curl = new DCurl(self::$dibDomain);
		self::$curl->thisClassIsInDibFramework = false;
		self::$curl->caCertPemFilePath = __DIR__ . DIRECTORY_SEPARATOR . 'cacert.pem';

		DIB::$USER['unique_id'] = rand(0, 9999999);

		$apiToken = 'X3T6_gTnEM12^w$6MIL2Rd2s';
		$debug = false;

		$result = self::$curl->dibLogin($un, $pw, $debug, $apiToken);
        
		if($result !== true) {
			$curlMsg = (extension_loaded('curl')) ? '' : ', that your PHP CURL extension is enabled';

			if(strpos($result, 'of the target site') !== false)
				$result = "Please check the Dropinbase sign-in credentials provided.";

			$response[] = self::getResponse('SignIn', false, "Authentication failed at Dropinbase online portal.<br>Please check your credentials{$curlMsg}, and that the Dropinbase server at " . self::$dibDomain . ' is not blocked by firewalls before trying again.<br>Remote response:<br>' . $result);
			return false;
		}

		$response[] = self::getResponse('SignIn', true, "Authentication successfull.");

		return true;
	}

    
	/**
	* Check if Apache and PHP have required modules & extensions
	* 
	* @return mixed TRUE on success, or response array on failure
	*/
	public static function checkRequiredPhpApacheModules(&$response) {

		// Check if Apache / Nginx is running

		if (strpos($_SERVER['SERVER_SOFTWARE'], 'Apache') !== false)
			$server = 'Apache';
		elseif (function_exists('apache_get_modules'))
			$server = 'Apache';
		else
			$server = 'Nginx';
		
		$apache = array();
		$php = array();
        $phpOpt = array();
		$apacheOpt = array();
		
		if($server === 'Apache') {
			if(function_exists('apache_get_modules')) {

				$apacheMods = apache_get_modules();

				if (!in_array('mod_rewrite', $apacheMods)) 
					$apache[] = '<b>mod_rewrite</b> (required - enable URL rewriting in .htaccess)';

				if (!in_array('mod_mime', $apacheMods)) 
					$apacheOpt[] = '<b>mod_mime</b> (recommended - proper MIME types in .htaccess)';

				if (!in_array('mod_headers', $apacheMods)) 
					$apacheOpt[] = '<b>mod_headers</b> (recommended - added security layer in .htaccess)';

				if (!in_array('mod_expires',$apacheMods)) 
					$apacheOpt[] = '<b>mod_expires</b> (optional - automatic expiry of cache files, set in .htaccess)';

				if (!in_array('mod_deflate', $apacheMods)) 
					$apacheOpt[] = '<b>mod_deflate</b> (optional - additional file compression for performance)';

			} else {
				$msg = "It seems that the PHP 'apache_get_modules()' function is not registered which prevents the installer from validating required Apache modules.
							<br>If your installation does not function properly, please check that the following Apache modules are installed:
							<br><b>mod_rewrite, mod_mime, mod_headers</b>";
	  			$response[] = self::getResponse('PHP', false, $msg);
			}
		} else {
			$msg = 'Detected Nginx as webserver. PHP cannot directly check for URL rewriting (required), MIME type definitions, and header manipulations.<br>Nginx should be configured with the equivalent of the Apache .htaccess file.';
			$response[] = self::getResponse('PHP', false, $msg);
		}
        
	  	if (!extension_loaded('pdo_mysql'))
            $php[] = '<b>pdo_mysql</b> (required - at present the Dropinbase database needs to be in MariaDb/MySql/Aurora. Client databases can reside in other systems (MariaDb, MySql, Aurora, PostgreSQL, SQLite, SQL Server)';
	  	if (!extension_loaded('curl'))
            $phpOpt[] = '<b>curl</b> (required by Unit Tester which is still experimental)';
	  	if (!extension_loaded('mbstring'))
	   		$php[] = '<b>mbstring</b> (required to ensure UTF-8 encoding is utilized)';
	  	if (!extension_loaded('openssl'))
	   		$phpOpt[] = '<b>openssl</b> (required if two-factor authentication using Google Authenticator is used, and on older PHP versions)';
	  	
	  	if(!empty($apache)) {
	  		$apacheStr = implode('<br>', $apache);
	  		$response[] = self::getResponse('Apache Modules', true, $apacheStr);
        }

		if(!empty($apacheOpt)) {
			$apacheStr = implode('<br>', $apacheOpt);
			$response[] = self::getResponse('Apache Modules', false, $apacheStr);
	  }
        
        if(!empty($php)) {
	  		$phpStr = implode('<br>', $php);
	  		$response[] = self::getResponse('PHP Extensions', true, $phpStr);
        }

        if(!empty($phpOpt)) {
            $phpStr = implode('<br>', $phpOpt);
            $response[] = self::getResponse('PHP Extensions', false, $phpStr);
        }
        
        return TRUE;
    }
    
    /**
	* Check PHP Version
	* 
	* @return mixed TRUE on success, or response array on failure
	*/
	private static function phpVer(&$response) {
   
	  	if (phpversion() < '5.3.3') {
	  		$response[] = self::getResponse('PHP', true, 'PHP version 5.3.3 or above is required. Your version: ' . phpversion());
	  		return FALSE;
	  	}
	  	
	  	return TRUE; 
    }   
    
    private static function createConn($connPath, $dbIndex, $dbType, $host, $port, $database, $schema, $username, $password, &$response) {

		$dropinArr = array (
			'mysql' => 'dibMySqlPdo',
			'pgsql' => 'dibPgSqlPdo',
			'sqlite' => 'dibSqlitePdo',
			'mssql' => 'dibMsSqlPdo'
		);

		$dbDropin = (isset($dropinArr[$dbType])) ? $dropinArr[$dbType] : 'dibMySqlPdo';

		$foreignKeyConstraints = ($dbType === 'sqlite') ? true : false;

		if($dbType === 'mysql')
            $charset = 'utf8mb4';
        elseif($dbType === 'pgsql')
            $charset = 'utf8';
        else
            $charset = 'UTF-8'; // sqlite, mssql

        $tmpArray = array(
            'database'=>$database,
            'dbType'=>$dbType,
            'charset'=>$charset,
            'username'=>$username,
            'password'=>$password,
            'host'=>$host,
            'port'=>$port,
            'emulatePrepare'=>true,
            'dbDropin'=>$dbDropin,
            'systemDropin'=>true,
        );

        DIB::$DATABASES[$dbIndex] = $tmpArray;

        if($dbType === 'pgsql')
            DIB::$DATABASES[$dbIndex]['schema'] = $schema;
        elseif($dbType === 'mysql')
            DIB::$DATABASES[$dbIndex]['collation'] = 'utf8mb4_unicode_520_ci';
        elseif($dbType === 'sqlite') {
            DIB::$DATABASES[$dbIndex]['foreignKeyConstraints'] = $foreignKeyConstraints;
            unset(DIB::$DATABASES[$dbIndex]['username'], DIB::$DATABASES[$dbIndex]['password'], DIB::$DATABASES[$dbIndex]['port']);
        }

		require_once self::$dibPath . 'dropins' . DIRECTORY_SEPARATOR . 'dibAdmin' . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'DConfigs.php';

		$d = New DConfigs();
		$result = $d->writeConnFile();

		if ($result !== true) {
			$response[] = self::getResponse('Database Connection', false, $result);
	  		return FALSE;
		}

		return TRUE;
	}

    private static function updateIndexFile(&$response) {

		$path = self::$basePath.'index.php';

		if(!is_writable($path)){
			$response[] = self::getResponse('Index.php file', false, "The installer cannot update the $path file due to file permissions. Please alter the file manually by commenting the 'require ./installer/index.php' line out, to run Dropinbase instead of the Installer.");
	  		return FALSE;
		}
        
		// Read the content of the file into a string
		$fileContents = @file_get_contents($path);

		// Check if the file was successfully read
		if ($fileContents === false) {
			$response[] = self::getResponse('Index.php file', false, "The installer cannot read to the $path file. Please alter the file manually by commenting the 'require ./installer/index.php' line out, to run Dropinbase instead of the Installer.");
	  		return FALSE;
		}
		
		// Comment require ./installer/index.php
		$i = strpos($fileContents, '$INSTALLER=TRUE; require ');
		if($i !== false) {
			$updatedContents = str_replace('$INSTALLER=TRUE; require ', '// $INSTALLER=TRUE; require ', $fileContents);
		} else {
			$updatedContents = str_replace('require \'./installer/index.php\'; die();', '// require \'./installer/index.php\'; die();', $fileContents);
		}
		
		$result = @file_put_contents($path, $updatedContents);

        if ($result === false) {
			$response[] = self::getResponse('Index.php file', false, "The installer cannot update the $path file due to file permissions. Please alter the file manually by commenting the 'require ./installer/index.php' line out, to run Dropinbase instead of the Installer.");
	  		return FALSE;
		}

        // installer.js has code to redirect the user to /login
        return TRUE;
    }

	public static function downloadDib($params) {
		$dest = self::$basePath.'runtime' . DIRECTORY_SEPARATOR . 'tmp';

		$result = self::checkFiles($response);

		if($result !== true) {
			DIB::$ACTION = $response[0];
			return false;
		}

		$result = self::createPath($dest);
		if($result !== true) {
			file_put_contents($dest . DIRECTORY_SEPARATOR . 'install_progress.dtxt', 'error');
			DIB::$ACTION = self::getResponse('File Permissions', true, "The webserver lacks permissions to create child folders of the /runtime folder.<br>Please amend using the chmod and chown commands, and try again.");
			return FALSE;
		}
		
		// Log user in
		$result = self::signUserIn($response, $params);
		if($result !== true) {
			DIB::$ACTION = $response[0];
			return false;
		}

		// Get name of download file
		$url = "/dropins/serve/Installer/downloadDib?containerName=appReq&itemEventId=CF637F07040B4A56A51B456536A0DCF7-dib";
        $postData = array();

		$result = self::$curl->request('POST', $url, $postData, 'json');

        if($result === false || empty($result)) {
            $msg = (extension_loaded('curl')) ? 'Could not access the Dropinbase server. Please check your PHP error logs for more info, and that you have access to the internet.' : 'The Dropinbase API request failed. Please ensure that the PHP CURL extension is enabled, and try again.';
			DIB::$ACTION = self::getResponse('Download DIB', false, $msg);
	  		return FALSE;
        }

        $data = json_decode($result, true);

        // Will get a validResult / invalidResult style response

        if(empty($data) || !is_array($data) || !array_key_exists('success', $data) || !array_key_exists('message', $data) ) {
            $msg = "Could not JSON decode data returned from the Dropinbase API endpoint\r\nResult from remote server: $result";
			DIB::$ACTION = self::getResponse('Download DIB', false, $msg);
			return FALSE;
        }

        if($data['success'] !== true) {
            $msg = "The Dropinbase API could not process the request. Error returned from remote server:<br><br>" . $data['message'];
			DIB::$ACTION = self::getResponse('Download DIB', false, $msg);
			return FALSE;
        }

		$url = base64_decode($data['records']['url']);
		
		// $url =  'https://assets.dropinbase.com/dropinbase20241111.zip';

		$progressFile = $dest . DIRECTORY_SEPARATOR . 'install_progress.dtxt';
		if (file_exists($progressFile)) @unlink($progressFile);
		file_put_contents($progressFile, 0);

		$dest .= DIRECTORY_SEPARATOR . 'dropinbase.zip';

		if (file_exists($dest)) @unlink($dest);

		// Open the file for writing
		$fp = fopen($dest, 'w+');

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_FILE, $fp);  // Save the file to the opened destination
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);  // Follow any redirects if necessary
		curl_setopt($ch, CURLOPT_NOPROGRESS, false);  // Allow progress tracking
		curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function ($resource, $download_size, $downloaded, $upload_size, $uploaded) use ($progressFile) {
			if ($download_size > 0) {
				// Calculate and save the progress percentage
				$progress = round(($downloaded / $download_size) * 100);
				file_put_contents($progressFile, $progress);
			}
		});
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification (use with caution in production)
		curl_setopt($ch, CURLOPT_TIMEOUT, 300);  // Set a timeout in case the server takes too long to respond
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');  // Set a user agent string to avoid being blocked by certain servers

		// Execute the cURL request
		curl_exec($ch);

		// Check for cURL errors
		if(curl_errno($ch)) {
			$error_msg = curl_error($ch);
			file_put_contents($progressFile, 'error');  // Save any errors to the progress file
			DIB::$ACTION = self::getResponse('download', false, 'Could not download the Dropinbase framework from ' . $url . '<br>Error reported: ' . $error_msg);
			return false;
		}

		curl_close($ch);
		fclose($fp);

		// When download is complete, set progress to 100%
		//file_put_contents($progressFile, 100);
		DIB::$ACTION = self::getResponse('download', true, "Download of '$dest' complete");
		
	}

	public static function downloadDibProgress($params) {

		$dest = self::$basePath.'runtime' . DIRECTORY_SEPARATOR . 'tmp';
		$progressFile = $dest . DIRECTORY_SEPARATOR . 'install_progress.dtxt';

		if($params['progressRequestNo'] == 0) {
			if (file_exists($progressFile)) @unlink($progressFile);
		}

		if(!file_exists($dest)) {
			$result = self::createPath($dest);
			if($result !== true) {
				file_put_contents($dest . DIRECTORY_SEPARATOR . 'install_progress.dtxt', 'error');
				DIB::$ACTION = self::getResponse('File Permissions', true, "The webserver lacks permissions to create child folders of the /runtime folder.<br>Please amend using the chmod and chown commands, and try again.");
				return false;
			}
		}

		$progress = 0;

		if (file_exists($progressFile)) {
			$progress = file_get_contents($progressFile);

			if($progress == (string)(int)$progress && (int)$progress < 100) {
				$msg = 'Downloading Framework: ' . $progress . '%';
				DIB::$ACTION = self::getResponse('download', false, $msg);

			}

		} else {
			file_put_contents($progressFile, 0);
			$msg = 'Downloading Framework: 0%';
			DIB::$ACTION = self::getResponse('download', false, $msg);
		}

		if($progress == 'error') {
			DIB::$ACTION = self::getResponse('download', true, 'stop queue');
			
			return false;
		}

		$dibFile = $dest . DIRECTORY_SEPARATOR . 'dropinbase.zip';

		if(empty(self::$dibPath)) {
			self::$dibPath = self::$basePath . 'dropinbase' . DIRECTORY_SEPARATOR;
		}
 
		if($progress == 100) {

			// check that the intended file was downloaded and not a HTML error page
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$mimeType = finfo_file($finfo, $dibFile);
			finfo_close($finfo);
	
			if ($mimeType !== 'application/zip') {
				file_put_contents($progressFile, 'error');
				DIB::$ACTION = self::getResponse('download', true, 'Could not download the Dropinbase framework.<br>Please try again, or contact Dropinbase support at support@dropinbase.com');
				return false;
			}

			if(!file_exists(self::$dibPath)) {
				$result = self::createPath(self::$dibPath);
				if($result !== true) {
					file_put_contents($progressFile, 'error');
					DIB::$ACTION = self::getResponse('File Permissions', true, "The webserver lacks permissions to create the framework folder: '" . self::$dibPath . "' folder.<br>Please amend using the chmod and chown commands.<br>Alternatively manually unzip '$dibFile' here,<br> assign the necessary permissions, and then Skip this installation step.");
					return false;
				}

			} elseif(!is_writable(self::$dibPath)) {
				file_put_contents($progressFile, 'error');
				DIB::$ACTION = self::getResponse('File Permissions', true, "The webserver lacks permissions to create files in the '" . self::$dibPath . "' folder.<br>Please amend using the chmod and chown commands.<br>Alternatively manually unzip '$dibFile' here,<br> assign the necessary permissions, and then Skip this installation step.");
				return false;
			}

			/*
			if(file_exists(self::$dibPath)) {
				$result = self::delDir(self::$dibPath);

				if($result !== true) {
					file_put_contents($progressFile, 'error');
					DIB::$ACTION = self::getResponse('File Permissions', true, 'Could not remove existing Dropinbase folder: ' . self::$dibPath);
					return false;
				}
			}
			*/

			file_put_contents($progressFile, 'unzip');

			DIB::$ACTION = self::getResponse('unzip', false, "Installing files to " . self::$dibPath);
			return false;

		} elseif($progress == 'unzip') {
			
			file_put_contents($progressFile, 'unzipping');

			set_time_limit(10 * 60);

			$result = self::unzipDir($dibFile, self::$dibPath);

			if($result !== true) {
				file_put_contents($progressFile, 'error');
				DIB::$ACTION = self::getResponse('unzip', true, "Could not unzip '$dibFile' to '" . self::$dibPath . "'<br>The webserver user requires permissions to write to this folder for installation purposes.");
				return false;
			}

			file_put_contents($progressFile, 'next step');

			DIB::$ACTION = self::getResponse('unzip', false, "Setting permissions...");
			return false;

		} elseif($progress == 'unzipping') {

			DIB::$ACTION = self::getResponse('unzip', false, "Installing files to " . self::$dibPath);
			return false;

		} elseif($progress == 'next step') {

			DIB::$ACTION = self::getResponse('get perms', true, 'Framework installation to ' . self::$dibPath . ' successfull.<br><br>Please continue with the next step.');
			if (file_exists($progressFile)) @unlink($progressFile);
			return false;

		} else {
			DIB::$ACTION = self::getResponse('none', false, 'Downloading... ' . $progress . '%');
			return false;
		}

		return true;
	}

	public static function getComposerVersion() {
		if (strtolower(substr(php_uname(), 0, 7)) === "windows") {
			$composerVersion = shell_exec('composer --version 2>&1');
			if (empty($composerVersion) || stripos($composerVersion, "Composer version") === false) {
				$composerVersion = null;
			} else {
				$composerVersion = $composerVersion ? trim($composerVersion) : null;
			}

		} else {
			$composerVersion = shell_exec('composer --version 2>/dev/null');
			$composerVersion = $composerVersion ? trim($composerVersion) : null;
		}

		return $composerVersion;
	}

	private static function createScriptWindows($params) {

		$dibAngularVersion = self::$dibAngularVersion;
		$dibNodeVersion = self::$dibNodeVersion;
		$dibNodeMajorVersion = (int)explode('.', $dibNodeVersion)[0];
		$dibNodeMinorVersion = (int)explode('.', $dibNodeVersion)[1];

		if(empty(self::$dibPath) || !file_exists(self::$dibPath . 'DibApp.php')) {
			DIB::$ACTION = self::getResponse('configureDib', true, "The Dropinbase framework has not been installed yet. First execute that step and try again.");
			return false;
		}

		$basePath = realpath(self::$basePath);
		$dibPath = realpath(self::$dibPath);
		$angularPath = $dibPath . '\dropins\setNgxMaterial\angular';

		if(!file_exists($angularPath)) {
			DIB::$ACTION = self::getResponse('configureDib', false, "First install the complete Dropinbase framework and try again<br>($angularPath is missing).");
			return false;
		}
		
		chdir($basePath);

		// Detect installed Composer version
		$composerVersion = self::getComposerVersion();
		$composerFoundPath = self::findComposerOnWindows();
		if(!empty($composerFoundPath)) {
			$composerFoundPath = str_replace("\\", "\\\\", dirname(realpath($composerFoundPath)));
		}
		$composerPath = (!empty($params['composerFolder'])) ? $params['composerFolder'] : $composerFoundPath;
		
		if(empty($composerVersion)) {

			if(empty($composerPath)) {
				DIB::$ACTION = self::getResponse('configureDib', false, "Please specify a valid installation path for Composer, and try again.");
				return false;
			}

			$result = self::createPath($composerPath);
			if($result === false) {
				DIB::$ACTION = self::getResponse('configureDib', false, "Could not create path for Composer installation: $composerPath. Please amend and try again.");
				return false;
			}
		}

		// Detect installed Node.js version

		chdir($dibPath . '/dropins/setNgxMaterial/angular');

		$nodeVersion = self::checkCommand('node -v');
		$nodeMajorVersion = (int)explode('.', $nodeVersion)[0];
		$nodeMinorVersion = (int)explode('.', $nodeVersion)[1];

		// echo "=====================================================================";
		// echo $nodeMajorVersion;
		// echo $nodeMinorVersion;

		if($nodeVersion) {
			$nodeVersion = trim(trim($nodeVersion, 'v'));
			$nodeMajorVersion = (int)explode('.', $nodeVersion)[0];
			$nodeMinorVersion = (int)explode('.', $nodeVersion)[1];
		} else 
			$nodeVersion = null;

		// Detect installed Angular CLI version

		$angularVersion = self::checkCommand('ng version');

		if($angularVersion === null) {
			$angularVersion = self::checkCommand('npx ng version');
		}

		preg_match('/Angular CLI\:\s*(\d+\.\d+\.\d+)/', $angularVersion, $matches);
		$angularVersion = isset($matches[1]) ? $matches[1] : null;
		$angularVersion = $angularVersion ? trim($angularVersion) : null;


//$composerVersion =  null;
//$nodeVersion = null;
//$angularVersion = null;

		$installing = '';
		$installed = '';

		$nodeJsCheckSumx86 = self::$nodeJsCheckSumx86;
		$nodeJsCheckSumx64 = self::$nodeJsCheckSumx64;

		// PowerShell script template with error handling and Invoke-WebRequest fallback
		$psScript = <<< PS
		# Application checksums (https://nodejs.org/en/blog/release/v20.9.0)
		\$nodeJsCheckSumx86 = "$nodeJsCheckSumx86" 
		\$nodeJsCheckSumx64 = "$nodeJsCheckSumx64"
		
		# Check if script is running as administrator
		\$currentPrincipal = New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
		if (-not \$currentPrincipal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
			Write-Host "Administrator rights required. Please close and then run Powershell as Administrator before executing the script." -ForegroundColor Red
			exit 1
		}

		Write-Host "Starting dependency installation..."

		function StopIfFailed {
			param (
				[string] \$CommandName = '',
				[string] \$StopOnError = 'yes'
			)
			# `LASTEXITCODE` of 0 or null = success
			# Non-zero = failure
			if (\$LASTEXITCODE -eq \$null -or \$LASTEXITCODE -eq 0) {
				Write-Host "Command '\$CommandName' succeeded." -ForegroundColor Green
			}
			else {
				# The command failed
				if (\$StopOnError -eq 'yes') {
					Write-Host "`r`nCommand '\$CommandName' failed with exit code \$LASTEXITCODE. Script will stop executing." -ForegroundColor Red
					exit \$LASTEXITCODE
				}
			}
		}

		# A function to download files by trying three different methods
		function DownloadFile {
			param (
				[string] \$url,
				[string] \$output
			)
			
			Write-Host "Downloading \$url..." -ForegroundColor Blue
			
			if (Get-Command Invoke-WebRequest -ErrorAction SilentlyContinue) {
				try {
					Invoke-WebRequest -Uri "\$url" -OutFile "\$output"
					StopIfFailed -CommandName "Invoke-WebRequest" -StopOnError "no"
				} catch {
					Write-Host "Invoke-WebRequest failed. Trying WebClient..." -ForegroundColor Yellow
					UseWebClient "\$url" "\$output"
				}
			
			} else {
				Write-Host "Invoke-WebRequest not available. Trying WebClient..." -ForegroundColor Yellow
				UseWebClient "\$url" "\$output"
			}
		}

		# Use WebClient to download files
		function UseWebClient {
			param (
				[string] \$url,
				[string] \$output
			)

			try {
				\$webClient = New-Object System.Net.WebClient
				\$webClient.DownloadFile("\$url", "\$output")
				StopIfFailed -CommandName "webClient.DownloadFile" -StopOnError "no"
			} catch {
				Write-Host "WebClient failed. Trying Start-BitsTransfer..." -ForegroundColor Yellow
				UseStartBitsTransfer "\$url" "\$output"
			}
		}

		# Use Start-BitsTransfer to download files
		function UseStartBitsTransfer {
			param (
				[string] \$url,
				[string] \$output
			)

			try {
				Start-BitsTransfer -Source "\$url" -Destination "\$output"
				StopIfFailed -CommandName "Start-BitsTransfer" -StopOnError "no"
			} catch {
				Write-Host "\r\nDownload of \$url failed with all 3 methods. Please download and install manually. Exiting script." -ForegroundColor Red
				exit 1
			}
		}
		
		
		# Function to calculate the file checksum
		function CheckFileHashSHA256 {

			param (
				[string] \$filePath,
				[string] \$validHash,
				[string] \$appName
			)

			if (\$validHash -ne (Get-FileHash -Algorithm SHA256 \$filePath).Hash) {
				Write-Host "Checksum validation failed for downloaded \$appName! Aborting installation." -ForegroundColor Red
				Remove-Item \$filePath -Force
				Exit 1
			}
		}

PS;

		// Add Node.js installation if not installed or version is incorrect (with error handling)

// *** TODO: detect if nvm installed - then rather do nvm install... and nvm use... 
// ALSO the help must advise the use of nvm before running installation script.

		if (!$nodeVersion || ($nodeMajorVersion != $dibNodeMajorVersion) || ($nodeMinorVersion < $dibNodeMinorVersion)) {
			$installing .= "Node.js $dibNodeVersion<br>";
			$psScript .= <<< PS
		
		try {
			if (-not (Get-Command msiexec.exe -ErrorAction SilentlyContinue)) {
				Write-Host "msiexec.exe not found. Please ensure it's available in your system's PATH variable." -ForegroundColor Red
				exit 1
			}

			# Detect if the system is 32-bit or 64-bit
			if (\$env:PROCESSOR_ARCHITECTURE -eq "x86") {
				\$nodeUrl = "https://nodejs.org/dist/v$dibNodeVersion/node-v$dibNodeVersion-x86.msi"
				\$installerFile = "node-v$dibNodeVersion-x86.msi"
				\$nodeJsCheckSum = \$nodeJsCheckSumx86
			} else {
				\$nodeUrl = "https://nodejs.org/dist/v$dibNodeVersion/node-v$dibNodeVersion-x64.msi"
				\$installerFile = "node-v$dibNodeVersion-x64.msi"
				\$nodeJsCheckSum = \$nodeJsCheckSumx64
			}

			# Download Node.js installer
			Write-Host "Downloading Node.js version $dibNodeVersion for \$env:PROCESSOR_ARCHITECTURE..."
			DownloadFile -url \$nodeUrl -output \$installerFile
			StopIfFailed -CommandName "Download Node.js installer"

			# Perfom checksum
			CheckFileHashSHA256 -filePath \$installerFile -validHash \$nodeJsCheckSum -appName "Node.js installer"
			StopIfFailed -CommandName "Check NodeJs Checksum of \$installerFile"

			# Install Node.js silently
			Write-Host "Installing Node.js..."
			Start-Process -FilePath msiexec.exe -ArgumentList '/i', \$installerFile, '/quiet', '/norestart' -Wait
			# & msiexec.exe /i \$installerFile /quiet /norestart
			StopIfFailed -CommandName "Execution of \$installerFile"

			# Detect Node.js installation directory from the registry
			try {
				Write-Host "Trying to detect Node.js installation directory from the registry..."
				\$nodeInstallDir = (Get-ItemProperty -Path "HKLM:\SOFTWARE\Node.js" -Name "InstallPath" -ErrorAction silentlycontinue).InstallPath
			} catch {
				try {
					\$nodeInstallDir = (Get-ItemProperty -Path "HKLM:\SOFTWARE\WOW6432Node\Node.js" -Name "InstallPath" -ErrorAction silentlycontinue).InstallPath
				} catch {
					Write-Host "Failed to detect Node.js installation directory from the registry. Moving to next checks..." -ForegroundColor Red
					exit 1
				}
			}

			# Remove the installer
			Remove-Item \$installerFile -ErrorAction Stop
			

			Write-Host "Node.js $dibNodeVersion installed successfully."

		} catch {
			Write-Host "Node.js $dibNodeVersion installation failed." -ForegroundColor Red
			exit 1
		}
		PS;

		} else {
			$msg = ($nodeVersion != $dibNodeVersion) ? "`n`n***NOTE: Version $dibNodeVersion is recommended by Angular, but your existing version should work fine. If it does not, please manually install $dibNodeVersion, delete the /dropins/setNgxMaterial/angular/node_modules folder and run npm install here. Restart to ensure the watcher uses the new version before testing.`n`n" : '';
			$psScript .= "\r\n\$nodeMessage = 'Node.js $nodeVersion is already installed. $msg'";
			$psScript .= "\r\nWrite-Host \$nodeMessage -ForegroundColor Red \r\n";
			$installed .= 'Node.js ' . $nodeVersion . '<br>';
		}

		// Add Composer installation if not installed (with error handling)
		if (!$composerVersion) {
			$installing .= 'Composer<br>';
			$psScript .= <<< PS

		try {
			# Check if PHP is installed
			if (Get-Command php -ErrorAction SilentlyContinue) {
				Write-Host ""
			} else {
				Write-Host "PHP cannot be run from the commandline, or php.exe is not in PATH. Please check your PHP installation, and ensure it is globally available by adding it to the PATH environment setting." -ForegroundColor Red
				exit 1
			}

			# Download Composer installer
			Write-Host "Downloading Composer and signature..."

			DownloadFile -url "https://getcomposer.org/installer" -output composer-setup.php
			StopIfFailed -CommandName "Download Composer installer"

			DownloadFile -url "https://composer.github.io/installer.sig" -output ComposerInstaller.sig
			StopIfFailed -CommandName "Download Composer Signature"

			# Calculate SHA384 checksum of the downloaded installer
			\$calculatedHash = Get-FileHash -Path composer-setup.php -Algorithm SHA384 | Select-Object -ExpandProperty Hash
			\$expectedHash = Get-Content ComposerInstaller.sig

			# Validate the checksum
			if (\$calculatedHash -ne \$expectedHash) {
				Write-Host "Composer installer checksum validation failed! Aborting installation." -ForegroundColor Red
				Remove-Item composer-setup.php -Force
				Exit 1
			}

			# Run the installer
			& php composer-setup.php --install-dir="$composerPath"
			StopIfFailed -CommandName "Execution of php composer-setup.php"

			Remove-Item composer-setup.php -ErrorAction Stop
			

			\$existingPath = [Environment]::GetEnvironmentVariable("Path", "Machine")
			if (\$existingPath -notlike "*$composerPath*") {
				[Environment]::SetEnvironmentVariable("Path", "\$existingPath;$composerPath", "Machine")
				\$env:Path = "\$env:Path;$composerPath"
			}
			Write-Host "Composer installed successfully."

		} catch {
			Write-Host "\r\nComposer installation failed." -ForegroundColor Red
			exit 1
		}
PS;
		} else {
			$psScript .= "\r\nWrite-Host 'Composer is already installed.'\r\n";
			$installed .= 'Composer<br>';
		}
		
		// Run composer install and npm install
		$psScript .= <<< PS

		# Navigate to the PHP project directory and run Composer install
		Set-Location "$basePath"

		Write-Host "Executing composer install..."
		try {
			php "$composerPath\\composer.phar" install
			if ((\$LASTEXITCODE -ne \$null) -and (\$LASTEXITCODE -ne 0)) {
				Write-Host "Composer installation failed using PHP. Let's try executing 'composer install' directly..." -ForegroundColor Red
				composer install
			}
			StopIfFailed -CommandName "composer install"

		} catch {
			Write-Host "Composer installation failed using PHP. Let's try executing 'composer install' directly..." -ForegroundColor Red
			composer install
			StopIfFailed -CommandName "composer install"
		}

		# Navigate to the Angular project directory and install angular
		Set-Location "$angularPath"
		Write-Host "Installing Angular ..."

		# Remove node_modules and package-lock.json if they exist
		if (Test-Path "node_modules") {
			Write-Host "Removing node_modules directory..."
			try {
				Remove-Item -Recurse -Force "node_modules" -ErrorAction Stop
				StopIfFailed -CommandName "Remove node_modules directory"

			} catch {
				Write-Host "Failed to remove node_modules." -ForegroundColor Red
				exit 1
			}
		}
		
		if (Test-Path "package-lock.json") {
			Write-Host "Removing package-lock.json file..."
			try {
				Remove-Item -Force "package-lock.json" -ErrorAction Stop
				StopIfFailed -CommandName "Remove package-lock.json"

			} catch {
				Write-Host "Failed to remove package-lock.json." -ForegroundColor Red
				exit 1
			}
		}
PS;

// The following failed on some machines, so using a simpler approach above: 
// Start-Process -FilePath \$nodePath -ArgumentList "install", '/quiet' -NoNewWindow -Wait 

		// Add Angular CLI installation if not installed or version is incorrect (with error handling) npm i @angular/cli@19.2.7   OR   npm install -g @angular/cli@19.2.7
		if (!$angularVersion || $angularVersion !== $dibAngularVersion) {
			$installing .= "Angular CLI $dibAngularVersion<br>";
			$psScript .= <<< PS

		Write-Host "Installing Angular CLI $dibAngularVersion..."
		try {
			# Get-ChildItem -Path "C:\Program Files" -Directory
			\$nodeDir = Get-ChildItem -Path "C:\\Program Files" -Directory -Filter "nodejs"

			\$nodePath = Resolve-Path -Path (\$nodeDir.FullName + "\\npm.cmd")

			& \$nodePath i @angular/cli@$dibAngularVersion
			StopIfFailed -CommandName "Install Angular CLI $dibAngularVersion"
			Write-Host "Angular CLI $dibAngularVersion installed successfully."

		} catch {
			Write-Host "Angular CLI CLI $dibAngularVersion installation failed." -ForegroundColor Red
			exit 1
		}
		PS;

// The following failed on some machines, so using a simpler approach above: 
// Start-Process -FilePath \$nodePath -ArgumentList "install -g @angular/cli@$dibAngularVersion", '/quiet' -NoNewWindow -Wait


		} else {
			$installed .= "Angular CLI $dibAngularVersion<br>";
			$psScript .= "\r\nWrite-Host 'Angular CLI $dibAngularVersion is already installed.'\r\n";
		}


		// Run "npm install" in the Angular project directory
		$psScript .= <<< PS
		try {
			
			& \$nodePath install

			StopIfFailed -CommandName "npm install"

		} catch {
			Write-Host "npm install failed." -ForegroundColor Red
			exit 1
		}
		PS;


		$psScript .= "\r\nWrite-Host 'Dependencies installed successfully.'\r\n";

		$psScript .= "\r\nWrite-Host 'Dropinbase Installation complete.' -ForegroundColor Green\r\n";

		// Save the PowerShell script to a file
		$path = self::$basePath . 'runtime' . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'configuredib.ps1';

		file_put_contents($path, $psScript);

		$installing .= 'Composer 3d-party dependencies<br>Angular node_modules<br>';

		$installStr = '<div id="copyBtn"></div><br><br<b>Running the command above will install the following:</b><br>' . $installing . '<br><b>The following are already installed:</b><br>' . $installed;

		DIB::$ACTION = self::getResponse('configureDib', true, "A Windows PowerShell script was generated for this installation.<br>Please review the script, open a Terminal Window in Powershell and run the following command:<br><br><pre class='execScript' id='script'>powershell -ExecutionPolicy Bypass -File $path</pre>$installStr");
		return TRUE;

		/*
		echo "\nDetected Versions:\n";
		echo "Node.js: " . ($nodeVersion ?: 'Not Installed') . "\n";
		echo "Composer: " . ($composerVersion ?: 'Not Installed') . "\n";
		echo "Angular CLI: " . ($angularVersion ?: 'Not Installed') . "\n";
		*/

	}

	// Create bash script
	private static function createScriptLinux($params) {
		
		$writePerms = array(
			'configs folder (this should be read-only on production)' => 'BASE/configs', 
			'runtime folder' => 'BASE/runtime', 
			'dropins folders (repeat for each dropin that has containers that need to be generated - make readonly on production)' => 'BASE/dropins/main/dibCode', 
			'Angular compile folder 1' => 'SYSTEM/dropins/setNgxMaterial/angular/ngtmp', 
			'user files folder' => 'USERFOLDER'
		);

		$dibAngularVersion = self::$dibAngularVersion;
		$dibNodeVersion = self::$dibNodeVersion;
		$dibNodeMajorVersion = (int)explode('.', $dibNodeVersion)[0];
		$dibNodeMinorVersion = (int)explode('.', $dibNodeVersion)[1];

		if(empty(self::$dibPath) || !file_exists(self::$dibPath . 'DibApp.php')) {
			DIB::$ACTION = self::getResponse('configureDib', true, "The Dropinbase framework has not been installed yet. First execute that step and try again.");
			return false;
		}

		$basePath = realpath(self::$basePath);
		$dibPath = realpath(self::$dibPath);
		$angularPath = $dibPath . '/dropins/setNgxMaterial/angular';

		if(!file_exists($angularPath)) {
			DIB::$ACTION = self::getResponse('configureDib', false, 'First install the Dropinbase framework and try again.');
			return false;
		}
		
		chdir($basePath);

		// Detect installed Composer version
		$composerVersion = self::getComposerVersion();

		chdir($dibPath . '/dropins/setNgxMaterial/angular');

		// Detect installed Node.js version
		$nodeVersion = self::checkCommand('node -v');
		if($nodeVersion) {
			$nodeVersion = trim(trim($nodeVersion, 'v'));
			$nodeMajorVersion = (int)explode('.', $nodeVersion)[0];
			$nodeMinorVersion = (int)explode('.', $nodeVersion)[1];
		} else 
			$nodeVersion = null;

		// Detect installed Angular CLI version

		//$angularVersion = shell_exec('ng version 2>/dev/null | grep -oP "(?<=CLI: )[^ ]+"');
		$angularVersion = self::checkCommand('ng version');

		if($angularVersion === null) {
			$angularVersion = self::checkCommand('npx ng version');
		}

		preg_match('/Angular CLI\:\s*(\d+\.\d+\.\d+)/', $angularVersion, $matches);
		$angularVersion = isset($matches[1]) ? $matches[1] : null;
		$angularVersion = $angularVersion ? trim($angularVersion) : null;

		// Get $owner and $group - set rights to all new folders/files with these users.
		// Also get $webUser (user running apache) - install npm and angular with this user (note on systems with multiple websites, the $owner/$group may differ from $webUser)

		if(empty($params['owner']) || empty($params['group'])) {
			DIB::$ACTION = self::getResponse('configureDib', false, "First specify a valid file/folder 'owner' and 'group' and try again.");
			return false;
		}

		if(empty($params['webUser'])) {
			DIB::$ACTION = self::getResponse('configureDib', false, "First specify the user under which the web-server runs, and try again.");
			return false;
		}

		$regex = '/^[a-z][-a-z0-9_]{0,36}[a-z0-9]$/'; // valid Linux user name

		if(preg_match($regex, $params['webUser']) !== 1) {
			DIB::$ACTION = self::getResponse('configureDib', false, "The web-server user name does not seem valid. Please try again.");
			return false;
		}

		if(preg_match($regex, $params['owner']) !== 1) {
			DIB::$ACTION = self::getResponse('configureDib', false, "The 'owner' user name does not seem valid. Please try again.");
			return false;
		}

		if(preg_match($regex, $params['group']) !== 1) {
			DIB::$ACTION = self::getResponse('configureDib', false, "The 'group' name does not seem valid. Please try again.");
			return false;
		}

		$owner = $params['owner'];
		$group = $params['group'];
		$webUser = $params['webUser'];

		// Detect Linux distribution from /etc/os-release
		$osReleaseFile = '/etc/os-release';
		$linuxDistro = 'unknown';
		if (file_exists($osReleaseFile)) {
			$osReleaseContent = file_get_contents($osReleaseFile);
			if (preg_match('/^ID=(\w+)/m', $osReleaseContent, $matches)) {
				$linuxDistro = $matches[1];
			}
		}

		// Generate the bash script based on the detected Linux distribution
		$installing = '';
		$installed = '';

		// Note, linux apt/cnf/yum does checksum verification of packages 

		$bashScript = <<< BASH
		#!/bin/bash
		# Detected Linux Distribution: $linuxDistro

		# User must have sudo rights to run this script:
		if [ "$(id -u)" -ne 0 ]; then
			echo "This script must be run by a user with privileged permissions." >&2
			exit 1
		fi

		# Break on any error
		set -e

		# Function to check if a command exists
		command_exists() {
			command -v "$1" >/dev/null 2>&1
		}

		# Define Linux owner and group names to use for folder and file permissions
		OWNER=$owner
		GROUP=$group

		# Define user that must install node(npm) and Angular - in order to have rights
		WEBUSER=$webUser

		#  Define permissions for read-only files and folders
		FOLDERPERMS=755
		FILEPERMS=644
		
		# Define permissions for writeable files and folders
		WRITEFOLDERPERMS=777
		WRITEFILEPERMS=776

		cd $dibPath/dropins/setNgxMaterial/angular

		BASH;

		$downgradeNode = '';

		// Add Node.js installation commands if the required version is not installed
		if (!$nodeVersion || $nodeMajorVersion !== $dibNodeMajorVersion || $nodeMinorVersion < $dibNodeMinorVersion) {
			$installing .= "Node.js $dibNodeVersion<br>";

			switch ($linuxDistro) {
				
				case 'centos':
				case 'rhel':
					if(!!$nodeVersion && ($nodeMajorVersion > $dibNodeMajorVersion || ($nodeMajorVersion == $dibNodeMajorVersion && $nodeMinorVersion > $dibNodeMinorVersion))) {
						$installNode = "sudo yum downgrade -y nodejs-$dibNodeVersion-1nodesource";
					} else
						$installNode = "yum install -y nodejs-$dibNodeVersion-1nodesource";

					$bashScript .= <<< BASH

		if ! command_exists curl; then
			echo "Installing curl..."
			yum install -y curl
		fi

		# Install Node.js for CentOS/RHEL
		echo "Downloading Node.js $dibNodeVersion..."
		curl -fsSL https://rpm.nodesource.com/setup_$dibNodeMajorVersion.x | bash -

		echo "Installing Node.js $dibNodeVersion..."
		$installNode

		BASH;
				break;

				case 'fedora':
					if(!!$nodeVersion && ($nodeMajorVersion > $dibNodeMajorVersion || ($nodeMajorVersion == $dibNodeMajorVersion && $nodeMinorVersion > $dibNodeMinorVersion))) {
						$installNode = "dnf downgrade -y nodejs-$dibNodeVersion-1nodesource";
					} else
						$installNode = "dnf install -y nodejs-$dibNodeVersion-1nodesource";

					$bashScript .= <<< BASH

		if ! command_exists curl; then
			echo "Installing curl..."
			dnf install -y curl
		fi

		# Install Node.js for Fedora
		echo "Downloading Node.js $dibNodeVersion..."
		curl -fsSL https://rpm.nodesource.com/setup_$dibNodeMajorVersion.x | bash -

		echo "Installing Node.js $dibNodeVersion..."
		$installNode

		BASH;
				break;

				default: // ubuntu / debian / etc.
					if(!!$nodeVersion && ($nodeMajorVersion > $dibNodeMajorVersion || ($nodeMajorVersion == $dibNodeMajorVersion && $nodeMinorVersion > $dibNodeMinorVersion))) {
						$downgradeNode = '--allow-downgrades';
					}

					$bashScript .= <<< BASH
					# Update system packages 
					echo "Updating system packages..."
					apt update -y && apt upgrade -y
			
					if ! command_exists curl; then
						echo "Installing curl..."
						apt install -y curl
					fi
			
					# Update and install Node.js for Ubuntu/Debian
					echo "Downloading Node.js $dibNodeVersion..."
					curl -fsSL https://deb.nodesource.com/setup_$dibNodeMajorVersion.x | bash -
			
			
					echo "Installing Node.js $dibNodeVersion..."
					echo "nodeVersion: $nodeVersion. $nodeMajorVersion > $dibNodeMajorVersion || ($nodeMajorVersion == $dibNodeMajorVersion && $nodeMinorVersion > $dibNodeMinorVersion)";

					apt install -y $downgradeNode nodejs=$dibNodeVersion-1nodesource1
			
					BASH;
					break;

			}

		} else {
			$installed .= "Node.js $nodeVersion<br>";
			$msg = ($nodeVersion != $dibNodeVersion) ? "\r\n\r\n***NOTE: Version $dibNodeVersion is recommended by Angular, but your existing version should work fine. If it does not, please manually install $dibNodeVersion, delete the /dropins/setNgxMaterial/angular/node_modules folder and run npm install here. Restart to ensure the watcher uses the new version, before testing.\r\n" : '';
			$bashScript .= "\r\necho 'Node.js $nodeVersion is already installed. $msg'\n";
		}

/*
		// NPM configuration
		$bashScript .= <<< BASH

		echo "Set Node.js global install folder"

		# Set npm global installation directory for $webUser user
		sudo -u \$WEBUSER npm config set prefix /var/www/.npm-global

		# Add npm global directory to PATH for \$WEBUSER user
		if ! sudo -u \$WEBUSER grep -q "export PATH=/var/www/.npm-global/bin:\$PATH" /var/www/.bashrc; then
			echo "Adding npm global bin directory to PATH for $webUser user..."
			sudo -u \$WEBUSER bash -c 'echo "export PATH=/var/www/.npm-global/bin:\$PATH" >> /var/www/.bashrc'
			sudo -u \$WEBUSER bash -c 'source /var/www/.bashrc'
		fi

		BASH;
*/
		$bashScript .= "\n\n# Install Composer\necho 'Install Composer'\n";

		// Add Composer installation commands if Composer is not installed
		if ($composerVersion === null) {
			$installing .= 'Composer<br>';

			switch ($linuxDistro) {
				case 'ubuntu':
				case 'debian':
					$bashScript .= "apt install -y composer\n";
					break;

				case 'centos':
				case 'rhel':
					$bashScript .= "yum install -y composer\n";
					break;

				case 'fedora':
					$bashScript .= "dnf install -y composer\n";
					break;
			}
		} else {
			$installed .= 'Composer<br>';
			$bashScript .= "echo 'Composer is already installed.'\n";
		}

		// node_modules installation
		$bashScript .= <<< BASH
		# Install node_modules
		cd $angularPath

		# Remove node_modules and package-lock.json if they exist
		if [ -d "node_modules" ]; then
			echo "Removing existing node_modules directory..."
			rm -rf node_modules
		fi

		if [ -f "package-lock.json" ]; then
			echo "Removing existing package-lock.json..."
			rm package-lock.json
		fi
		BASH;

		if ($angularVersion !== $dibAngularVersion) {
			$installing .= "Angular CLI $dibAngularVersion<br>";
			$bashScript .= "npm i @angular/cli@$dibAngularVersion\n"; // npm install -g @angular/cli@$dibAngularVersion\n    OR   npm i @angular/cli@$dibAngularVersion
		} else {
			$installed .= "Angular CLI $dibAngularVersion<br>";
			$bashScript .= "echo 'Angular CLI $dibAngularVersion is already installed.'\n";
		}

		// Run "npm install" in the Angular project directory
		$bashScript .= <<< BASH
		
		echo "Install node_modules"

		npm install

		echo "Install Angular CLI"
		BASH;
		

		// Add project-specific commands for Composer installation
		$bashScript .= <<< BASH
		cd $basePath

		echo "Install Composer Libraries"
		composer install --no-dev --optimize-autoloader

		echo "Dependencies installed"

		echo "Configure permissions"
		
		# Configure general folder and file permissions

		chown \$OWNER:\$GROUP -R $basePath
		find $basePath -type d -exec chmod \$FOLDERPERMS {} +
		find $basePath -type f -exec chmod \$FILEPERMS {} +
		
		# Ensure ngc is executable
		chmod +x ./dropinbase/dropins/setNgxMaterial/angular/node_modules/.bin/ngc

		# Set writable folders and file permissions
		BASH;

		$bashScript .= "\n\n";
		
		foreach($writePerms as $key => $path) {
			if($path === 'USERFOLDER') {
				if(!empty(DIB::$USERSPATH))
					$path = DIB::$USERSPATH;
				else
					continue;
			}

			$path = str_replace('SYSTEM', $dibPath, $path);
			$path = str_replace('BASE', $basePath, $path);
			$path = str_replace('//', '/', $path);
			$path = str_replace('\/', '/', $path);

			$bashScript .= "# $key\n";

			if(!file_exists($path))
				$bashScript .= "mkdir -p $path\n";

			$bashScript .= "find $path -type d -exec chmod \$WRITEFOLDERPERMS {} +\n";
			$bashScript .= "find $path -type f -exec chmod \$WRITEFILEPERMS {} +\n";

			if($path !== 'configs')
				$bashScript .= "chmod g+s $path\n";

			$bashScript .= "\n";
		}

		$bashScript .= "\necho 'Dropinbase Installation complete'";

		//$bashScript .= "\necho 'Restarting Apache' \n systemctl restart apache2\n";

		//$str = '<pre>' . $bashScript . '</pre>';

		// Save the PowerShell script to a file
		$path = self::$basePath . 'runtime' . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'configuredib.sh';
		
		$bashScript = str_replace("\r\n", "\n", $bashScript);
		file_put_contents($path, $bashScript);

		$installing .= 'Composer 3d-party dependencies<br>Angular node_modules<br>Setting folder&file permissions<br>';
		$installStr = '<div id="copyBtn"></div><br><br><b>Running the command above will install the following:</b><br>' . $installing . '<br><b>The following are already installed:</b><br>' . $installed;
		DIB::$ACTION = self::getResponse('configureDib', true, "A Linux bash script was generated for this installation.<br>Please review, and then execute the following in Linux as a privileged user:<br><br><b id='script'>chmod +x $path; sh $path</b>$installStr");
		return TRUE;

		file_put_contents('install_dependencies.sh', $bashScript);

		// Make the bash script executable
		chmod('install_dependencies.sh', 0755);

		return $str;
	}

	public static function getOwnerGroupWebUser() {
		if (strtolower(substr(php_uname(), 0, 7)) === "windows")
			return array(null, null, null);

		if(function_exists('fileowner') && function_exists('posix_getpwuid')) {
			// Get the owner and group names
			$ownerId = fileowner(__FILE__);
			$groupId = filegroup(__FILE__);

			// Convert to names
			$owner = posix_getpwuid($ownerId)['name'];
			$group = posix_getgrgid($groupId)['name'];

			
			if (isset($_SERVER['USER'])) {
				$webUser = $_SERVER['USER'];
			} else {
				$uid = getmyuid();
				$userInfo = posix_getpwuid($uid);
				$webUser = $userInfo['name'];
			}

		} else {
			$owner = 'www-data';
			$group = 'www-data';
			$webUser = 'www-data';
		}

		return array($owner, $group, $webUser);
	}

	/**
     * Recursively creates parts of a directory path that may not yet exist in the file system
     * @param string $path - full path to a file (not a folder)
     * @return boolean TRUE on success. FALSE on error
     */
    public static function createPath($path) {
        try {
            if(empty($path)) 
                return FALSE;

            if(substr($path, -1) == DIRECTORY_SEPARATOR) // Avoid issue on Linux
                $path = substr($path, 0, -1);
            if (is_dir($path))
                return true;

            $prev_path = dirname($path);
            $return = self::createPath($prev_path);

            if ($return && is_writable($prev_path))
                // check again if path still does not exist in case of concurrent users
                return (!is_dir($path)) ? mkdir($path, 0770) : true;
            else 
                return false;
        } catch (Exception $e) {
            return false;
        }
    }


	/**
	 * Checks if a command can be run successfully on the system.
	 * Returns the command's output on success, or null on failure.
	 */
	private static function checkCommand($command) {
		// Capture the return code in $return_var and output in $output.
		$output = [];
		$return_var = 1; // set default to non-zero

		// On Windows, you might prefer "where node" but usually running "node -v" is enough.

		if (strtolower(substr(php_uname(), 0, 7)) === "windows") {
			$str = ' 2>&1';
		} else {
			$str = ' 2>/dev/null';
		}

		exec($command . $str, $output, $return_var);

		// If $return_var is 0, the command executed successfully.
		if ($return_var === 0) {
			// Return the full output as a string.
			$output = implode("\n", $output);
			$output = ltrim($output, 'v');
			return $output;
		}

		// If the command failed (return_var != 0), return null
		return null;
	}

	private static function verifyInstallParts($params, &$response) {
		$success = array();

		$dir = self::$basePath . 'dropinbase' . DIRECTORY_SEPARATOR . 'DibApp.php';
		if(!file_exists($dir)) {
			$success['dropinbase'] = array(false, 'The Dropinbase framework files do not exist.<br><b>Cannot conduct further checks</b>.');
			return $success;
		} else {
			$success['dropinbase'] = array(true, 'The Dropinbase framework files exist.');
		}

		$result = self::checkFilesAndDbConn($response, $params, TRUE);
		if($result === false) {
			$success['conn'] = array(false, $response[0]['notes']);
		} else {
			// Last convenience check: dropins/main folder
			$path = self::$basePath  . 'dropins' . DIRECTORY_SEPARATOR . 'main';
			if(!file_exists($path)) {
				$success['conn'] = array(false, 'Folder structure', true, "Database connection is successful. Note, the '$path' folder does not exist - it is not required, but helpful if you're new to Dropinbase.");
				return FALSE;
			}

			$success['conn'] = array(true, 'Dropinbase client files exist, and database connection is successful.');
		}

		// Check composer and vendor folder
		
		if(empty(DIB::$VENDORPATH))
			$dir = self::$basePath . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
		else
			$dir = DIB::$VENDORPATH . 'autoload.php';

		$vendorPathExists = file_exists($dir);

		$composerOutput = self::checkCommand('composer --version'); // 2>nul

		if (empty($composerOutput)) {
			$str = ($vendorPathExists) ? 'Composer not found, but vendor folder exists.' : 'Composer not found and vendor folder missing.';
			$success['composer'] = array(true, $str);
		} else {
			if ($vendorPathExists) {
				$success['composer'] = array(true, 'Composer installed and vendor folder exists.');
			} else {
				$success['composer'] = array(false, "Composer installed, but required vendor folder missing. See Help for more info.");
			}
		}

		// Check Angular CLI and node_modules

		$dir = self::$basePath . 'dropinbase' . DIRECTORY_SEPARATOR . 'dropins' . DIRECTORY_SEPARATOR . 'setNgxMaterial' . DIRECTORY_SEPARATOR . 'angular' . DIRECTORY_SEPARATOR . 'node_modules';
		if(!file_exists($dir)) {
			$success['node_modules'] = array(false, 'Folder /dropinbase/dropins/setNgxMaterial/angular/node_modules not found.<br><b>Cannot conduct further checks</b>.');
			return $success;
		} else {
			$success['node_modules'] = array(true, 'Folder /dropinbase/dropins/setNgxMaterial/angular/node_modules exists.');
		}

		$dir = self::$basePath . 'dropinbase' . DIRECTORY_SEPARATOR . 'dropins' . DIRECTORY_SEPARATOR . 'setNgxMaterial' . DIRECTORY_SEPARATOR . 'angular' . DIRECTORY_SEPARATOR . 'node_modules' . DIRECTORY_SEPARATOR . '@angular';
		if(!file_exists($dir)) {
			$success['angular'] = array(false, 'Folder /dropinbase/dropins/setNgxMaterial/angular/node_modules/@angular not found.');

		} else {
			
			$dir = self::$basePath . 'dropinbase' . DIRECTORY_SEPARATOR . 'dropins' . DIRECTORY_SEPARATOR . 'setNgxMaterial' . DIRECTORY_SEPARATOR . 'angular' . DIRECTORY_SEPARATOR . 'node_modules' . DIRECTORY_SEPARATOR . '.bin' . DIRECTORY_SEPARATOR . 'ngc';
			if(!file_exists($dir)) {
			
				// See if npx can find ngc
				$npxVersion = self::checkCommand('npx ngc --version');
				if(empty($npxVersion)) {
					$success['node_modules'] = array(false, "Folder /dropinbase/dropins/setNgxMaterial/angular/node_modules/@angular exists, but Angular compiler is not working using 'ngc' or 'npx ngc'.");
				} else {
					$success['node_modules'] = array(true, 'Folder /dropinbase/dropins/setNgxMaterial/angular/node_modules/@angular exists, and Angular compiler (ngc) functional.');
				}
			} else {
				$success['node_modules'] = array(true, 'Folder /dropinbase/dropins/setNgxMaterial/angular/node_modules/@angular exists, and Angular compiler (ngc) functional.');
			}
		}

		// Check Node.js
		$dir = self::$basePath . 'dropinbase' . DIRECTORY_SEPARATOR . 'dropins' . DIRECTORY_SEPARATOR . 'setNgxMaterial' . DIRECTORY_SEPARATOR . 'angular';

		if(!file_exists($dir)) {
			$success['angular'] = array(false, 'DIB Framework folder /dropinbase/dropins/setNgxMaterial/angular not found. <br><b>Cannot conduct further checks</b>.');
			return $success;
		}

		chdir($dir);
		$nodeOutput = self::checkCommand('node -v');

		if ($nodeOutput !== null) {
			$nodeOutputMajorVersion = (int)explode('.', $nodeOutput)[0];
			$nodeOutputMinorVersion = (int)explode('.', $nodeOutput)[1];

			$dibNodeMajorVersion = (int)explode('.', self::$dibNodeVersion)[0];
			$dibNodeMinorVersion = (int)explode('.', self::$dibNodeVersion)[1];

			if (($nodeOutputMajorVersion != $dibNodeMajorVersion) || ($nodeOutputMinorVersion < $dibNodeMinorVersion))  {
				$success['nodeversion'] = array(false, "Node.js version $nodeOutput installed (it may not work with Angular CLI " . self::$dibAngularVersion . "). Node.js version " . self::$dibNodeVersion . " recommended.");
			} else {
				$success['nodeversion'] = array(true, "Node.js version $nodeOutput installed.");
			}
			
		} else {
			$success['nodeversion'] = array(false, "Node.js not installed. Version " . self::$dibNodeVersion . " recommended.");
		}
		
		// Check npm
		$npmOutput = self::checkCommand('npm -v');
		if ($npmOutput !== null) {
			//$success['npm'] = array(true, "NPM installed.");
			$a=1; // skip - can be confusing if wrong version no
		} else {
			$success['npm'] = array(false, "NPM (which comes with Node.js) not installed or not accessible.");
		}

		// Check Angular CLI
		$ngVersion = self::checkCommand('ng version');

		if($ngVersion === null) {
			$ngVersion = self::checkCommand('npx ng version');
		}

// *** TODO: detect if nvm installed - then rather do nvm install... and nvm use...
// ALSO the help must advise the use of nvm before running installation script.


// *** TODO: check for Major and Minor version ... 

		if ($ngVersion !== null) {

			preg_match('/Angular CLI\:\s*(\d+\.\d+\.\d+)/', $ngVersion, $matches);
			$ngVersion = isset($matches[1]) ? $matches[1] : null;
			$ngVersion = $ngVersion ? trim($ngVersion) : null;

			if($ngVersion === null) {
				$success['ngversion'] = array(false, "Angular CLI not installed. Version " . self::$dibAngularVersion . " recommended.");
			
			} else {
				$ngVersionMajorVersion = (int)explode('.', $ngVersion)[0];
				$ngVersionMinorVersion = (int)explode('.', $ngVersion)[1];

				$dibNgMajorVersion = (int)explode('.', self::$dibAngularVersion)[0];
				$dibNgMinorVersion = (int)explode('.', self::$dibAngularVersion)[1];

				if (($ngVersionMajorVersion == $dibNgMajorVersion) && ($ngVersionMinorVersion == $dibNgMinorVersion)) {
					$success['ngversion'] = array(true, "Angular CLI version $ngVersion installed.");
				} else
					$success['ngversion'] = array(false, "Angular CLI version $ngVersion installed. Version " . self::$dibAngularVersion . " required.");
			}
			
		} else {
			$success['ngversion'] = array(false, "Angular CLI not (globally) installed. Version " . self::$dibAngularVersion . " recommended.");
		}

		return $success;
	}

	public static function verifyInstall($params) {

		$result = self::verifyInstallParts($params, $response);

		$msg = '<br><table class="dibTable" >';
		$error = false;

		foreach ($result as $key => $r) {
			$msg .= '<tr><td>';
			if(!!$r[0]) {
				$msg .= "<img src='/resources/correctTick.png' alt='OK' style='width: 24px; height: 24px; vertical-align: middle;' />";
			} else {
				$error = true;
				$msg .= "<img src='/resources/redCross.png' alt='Error' style='width: 24px; height: 24px; vertical-align: middle;' />";
			}

			$msg .= '</td><td>' . $r[1];
			$msg .= '</td></tr>';
		}

		$msg .= '</table>';

		if($error) {
			$msg = "<br><span style='color:red'>Errors found. Please redo affected steps or see 'Manual Installation Help' above.</span><br>$msg";
		}

		$showFile = true;

		DIB::$ACTION = array('msg'=>$msg, 'showFile'=>$showFile);

		return TRUE;
	}


	/**
     * unzipDir file. Will create $saveDir if it does not exist, but won't empty it if it exists.
     * @param $pathToZipFile full path to zip file
	 * @param $targetPath folder where to save contents of zip file
	 * @return mixed boolean TRUE on success, string with err msg on failure
     */
	private static function unzipDir($pathToZipFile, $targetPath) {
		try {
			if(!file_exists($targetPath)) {
				$result = self::createPath($targetPath);
				if($result !== true) return $result;
			}
			
			$zip = new ZipArchive;
			$res = $zip->open($pathToZipFile);
			if ($res === TRUE) {
				$zip->extractTo($targetPath);
				$zip->close();
			} else
				return 'Could not open Zip file';
			
			return TRUE;
			
		} catch(Exception $e) {
            return "Unzip error: ".$e->getMessage();
		}
	}

	/**
     * Recursively delete a folder and all contents in subfolders etc
	 * USE WITH GREAT CAUTION!
     *
     * @param string $dir parent folder where files and subfolders reside
     * @param string $ext eg php or txt or empty 
     * @param int $fileAgeSeconds only delete files modified/created more than $fileAgeSeconds ago
     * @return boolean false if any one file could not be deleted, else true
     *
     */
	public static function delDir($dir, $ext='', $fileAgeSeconds=0) {
        if(!is_dir($dir)) return true;

	    $fp = @opendir($dir);

	    if ($fp !== false) {

	        while (false !== ($f = readdir($fp))) {

                if ($f == '.' || $f == '..') continue;
                
	            $file = realpath($dir) . DIRECTORY_SEPARATOR . $f;
                $ageDel = ($fileAgeSeconds == 0 || filemtime($file) <= time() - $fileAgeSeconds);

	            if (is_dir($file) && !is_link($file)) {
	                $r = self::delDir($file, $ext, $fileAgeSeconds);
                    if($r === false) return false;

                } elseif(!empty($ext) && $ext !== '*') {
                    if(pathinfo($file, PATHINFO_EXTENSION) == $ext && $ageDel) {
                        $r = @unlink($file);
                        if($r === false) return false;
                    }

                } elseif ($ageDel) {
                    $r = @unlink($file);
                    clearstatcache(true);
                    if($r === false) return false;
                }
	        }
    	    closedir($fp);
			
            if(empty(array_diff(scandir($dir), array('.', '..')))) {
	            $r = @rmdir($dir);
				if($r === false) return false;
			}

            clearstatcache(true);
	    }
        return true;
	}

	public static function findComposerOnWindows() {
		//
		// 1) Check if Composer is even callable by running "composer --version"
		//
		$versionOutput = shell_exec('composer --version 2>nul');
		if (empty($versionOutput)) {
			// If this returns nothing, "composer" isn't recognized on PATH
			// or otherwise not callable. Return null.
			return null;
		}

		//
		// 2) Attempt "where composer"
		//
		$whereOutput = shell_exec('where composer 2>nul');
		$foundPath = self::parseOutputForFirstLine($whereOutput);
		if ($foundPath) {
			return $foundPath;
		}

		//
		// 3) Attempt the cmd.exe "for" trick
		//
		//    cmd /c "for %I in (composer) do @echo %~$PATH:I"
		//
		$cmdOutput = shell_exec('cmd /c "for %I in (composer) do @echo %~$PATH:I"');
		$foundPath = self::parseOutputForFirstLine($cmdOutput);
		if ($foundPath) {
			return $foundPath;
		}

		//
		// 4) Attempt via PowerShell "Get-Command composer"
		//
		//    powershell.exe -NoProfile -Command "Get-Command composer | Select-Object -ExpandProperty Definition"
		//
		$psCommand = 'powershell.exe -NoProfile -Command "Get-Command composer | Select-Object -ExpandProperty Definition"';
		$psOutput = shell_exec($psCommand . ' 2>nul');
		$foundPath = self::parseOutputForFirstLine($psOutput);
		if ($foundPath) {
			return $foundPath;
		}

		//
		// 5) If we still don’t have anything, return null
		//
		return null;
	}

	/**
	 * Helper function: Takes the raw string from shell_exec()
	 * and returns the first non-empty line or null if none.
	 */
	private static function parseOutputForFirstLine(?string $rawOutput) {
		if (!$rawOutput) {
			return null;
		}

		// Split into lines, trim each line, filter out empty
		$lines = array_map('trim', explode("\n", $rawOutput));
		$lines = array_filter($lines);

		// Return the first line if it exists
		return $lines ? reset($lines) : null;
	}

}