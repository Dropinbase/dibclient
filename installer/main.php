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
	
	if (!empty(DIB::$ACTION)) {
		echo json_encode(array('success'=>FALSE, 'messages'=>DIB::$ACTION, 'override' => Install::$showOverrideButton));

	} else {
		// All good, echo success	
		echo json_encode(array('success'=>TRUE, 'messages'=>null, 'override' => Install::$showOverrideButton));
	}
	
}



class Install {
	public static $basePath = '';
	public static $dibPath = '';
	public static $query = null;
	public static $showOverrideButton = FALSE;
	private static $curl = null;

	private static $dibDomain = 'https://dibdev.cdn.co.za';
	
	public static function init($path) {
		self::$basePath = $path . DIRECTORY_SEPARATOR;

		if(!empty(DIB::$SYSTEMPATH))
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
    	$perfect = array('database', 'dbType', 'username', 'password', 'host', 'port', 'emulatePrepare', 'charset', 'dbDropin', 'systemDropin');
		
		$missing = array();
		foreach ($perfect as $key) {
			if(!array_key_exists($key, $c))
				$missing[]=$key;
		}
		
		if(!empty($missing))
			return implode (', ', $missing);
		return '';
	}
	
    private static function checkFilesAndDbConn(&$response, $params) {
		$apache = array();
		$php = array();
		
		// Check for required files and folders
        $result = self::checkFiles($response);
		if($result !== true) return false;

        list($host, $port, $database, $username, $password) = array(
            $params['host'], $params['port'], $params['database'], $params['username'], $params['password'], 
        );
        
        if(empty($params['port']) || $params['port'] != (string)(int)$params['port']) {
			$response[] = self::getResponse('Database Connection', false, "The 'port' attribute must be an integer (for MySQL it is normally 3306, and for MariaDb, 3307). Please amend and try again.");
			return FALSE;
		}

        if(empty($params['host']) || empty($params['database']) || empty($params['username'])) {
			$response[] = self::getResponse('Database Connection', false, "The following values are all required: host, database, username, port. Please amend and try again.");
			return FALSE;
		}
		
	  	// Load Conn.php and try to connect to db's
		$connPath = (empty(DIB::$SECRETSPATH)) ? self::$basePath  . 'secrets' . DIRECTORY_SEPARATOR . 'Conn.php' : DIB::$SECRETSPATH . 'Conn.php';

        if(!file_exists($connPath)) {
        	if(!is_writable(dirname($connPath))) {
				$response[] = self::getResponse('File Permissions', true, "The '$connPath' file does not exist, and the webserver does not have permissions to create the file. Either provide the necessary permissions or create the Conn.php file manually (see /secrets/Example_Conn.php).");
				return FALSE;
			}
			// Create the file
			self::createConn($connPath, 1, $host, $port, $database, $username, $password, $response);
		
		}
		
		require $connPath;

        $dbIndex = array_keys(DIB::$DATABASES)[0];

        if(count(DIB::$DATABASES) < 2) {
			self::createConn($connPath, $dbIndex, $host, $port, $database, $username, $password, $response);
            require $connPath;

            $dbIndex = array_keys(DIB::$DATABASES)[0];
        }
		
		// Test the mysql server connection

		$c = DIB::$DATABASES[$dbIndex];
		$missing = self::checkConnAttributes($c);
		if($missing !== '') {
			$response[] = self::getResponse('Database Connection', true, "The entry in the database connection file (/secrets/Conn.php) is missing the following attributes:<br><b>$missing<b><br>Please amend, or delete the Conn.php file so that it can be created again.");
			return FALSE;
		}
		
		// dropinbase database must be mysql
		if($c['dbType'] !== 'mysql') {
			$response[] = self::getResponse('Database Connection', true, "The dropinbase database must (at present) be hosted in MySQL / Mariadb. If it is, change the dbType field to 'mysql' for the first entry of the database connection file (/secrets/Conn.php).");
			return FALSE;
		}

		// construct general conn string and test basic query
		$connStr = "mysql:host=$host;port=$port;charset=utf8mb4";
		Db::setConn($connStr, $username, $password);
		
		$result = Db::execute("SELECT 1 as A");
		if($result === FALSE || Db::count()<1) {
			$response[] = self::getResponse('Database Connection', false, 'Could not connect to the MySQL (compatible) server, using the<br>host, port, username and password provided in entry 1 of the database connection file (/secrets/Conn.php).<br>Database error: ' . Db::lastErrorAdminMsg());
			return FALSE;
		}

		// Test connection to dropinbase db
		$connStr = "mysql:dbname=$database;host=$host;port=$port;charset=utf8mb4";
		Db::setConn($connStr, $username, $password);
		$result = Db::execute("SELECT `content_type` FROM pef_content_type");
		if(Db::count()>1)
			return TRUE;
		
		// Create the database
        $connStr = "mysql:host=$host;port=$port;charset=utf8mb4";
		return self::createDb($connStr, $username, $password, $database, $dbIndex, $response);
	}

	private static function checkFiles(&$response) {
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
		
        if(!file_exists($configsPath . DIRECTORY_SEPARATOR . 'Dib.php') && !is_writable($configsPath)) {
            $response[] = self::getResponse('File Permissions', true, "The webserver lacks permissions to create the Dib.php file in the '$configsPath' folder. Please amend using the chmod and chown commands. Once it is created, you can reset the folder permissions to read-only.");
            return FALSE;
        }
        
        $filesPath = self::$basePath  . 'files';
        if(!file_exists($filesPath)) {
            $response[] = self::getResponse('Folder structure', true, "The '$filesPath' folder does not exist. folder with its required files does not exist. Download the .zip file from Github and recreate the dropinbase client folder structure. Alternatively, copy this folder (and other missing files) from another DIB installation.");
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
	
	private static function createDb($connStr, $username, $password, $databaseName, $dbIndex, &$response) {
		// Create the database 
		Db::setConn($connStr, $username, $password);
		$sql = "CREATE DATABASE IF NOT EXISTS `$databaseName` DEFAULT CHARACTER SET = 'utf8mb4' DEFAULT COLLATE 'utf8mb4_unicode_520_ci';";
		$result = Db::execute($sql);
		if($result === FALSE) {
	    	$response[] = self::getResponse('Database Connection', false, "Could not create the Dropinbase database ('$databaseName') using the following connection properties:<br>$connStr, $username, $password.<br>Please check the connection properties in entry $dbIndex of the database connection file (/secrets/Conn.php). The following SQL failed:<br> " . $sql . '<br> Database error: ' . Db::lastErrorAdminMsg());
			return $response;
		}
		
		// Get sql file contents
		$sql = file_get_contents(self::$dibPath . 'installer' . DIRECTORY_SEPARATOR . 'dropinbase.sql');
		$sql = str_ireplace("CREATE TABLE IF NOT EXISTS `pef_activity_log`", "USE `$databaseName`;\r\n\r\n CREATE TABLE IF NOT EXISTS `pef_activity_log`", $sql);

		// Temporary variable, used to store current query
		$templine = '';

		// Loop through each line
		$lines = explode("\n", $sql);
		$sql = "";
		foreach ($lines as $line){
			// Skip it if it's a comment
			if ($line=='' || substr($line, 0, 2) == '--')
			    continue;

			// Add this line to the current segment
			$templine .= $line;
			// If it has a semicolon at the end, it's the end of the query
			if (substr(trim($line), -1, 1) == ';'){
			    // Perform the query
			    $result = Db::execute($templine);

			    if($result === FALSE) {
			    	$response[] = self::getResponse('Database Connection', false, "Could not create the Dropinbase tables. Please check the MySQL user permissions and the connection properties (/secrets/Conn.php).<br>The following SQL failed: " . $templine . '. Database error: ' . Db::lastErrorAdminMsg());
					return $response;
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
    
    private static function createConn($path, $dbIndex, $host, $port, $database, $username, $password, &$response) {

        if(empty($host) || empty($port) || empty($database) || empty($username)) {
            $response[] = self::getResponse('Database Connection', false, "The following fields are required: host, port, database, username (and password if set for the user)");
	  		return FALSE;
        }
    	 
    	$result = @file_put_contents($path,
"<?php

/* NOTES: 
   - The index of the main dropinbase table must match the DBINDEX value in Dib.php
   - Depending on your setup, using an IP address as host (eg 127.0.0.1 instead of 'localhost') can increase performance
   - This file is regenerated each time connection details are updated using the /nav/dibConfigs UI in Dropinbase
   - View the Example_Conn.php file for examples of how to connect to different database servers
   - By default the MySQL dropinbase tables use the 'utf8mb4_unicode_520_ci' collation. 
     Ensure your MySQL server's my.ini file is configured accordingly, or change the collation for the dropinbase tables to match your own settings (HeidiSQL has a useful bulk tool).
   - More info about charset and collation:
        https://www.coderedcorp.com/blog/guide-to-mysql-charsets-collations/
*/

DIB::\$DATABASES = array(
    " . $dbIndex . " => array(
        'database'=>'$database',
        'username'=>'$username',
        'password'=>'$password',
        'host'=>'$host',
        'port'=>$port,
        'charset'=>'utf8mb4',
		'collation' => 'utf8mb4_unicode_520_ci',
        'connectionStringExtra'=>'',
        'dbType'=>'mysql',
        'emulatePrepare'=>true,
        'dbDropin'=>'dibMySqlPdo',
        'systemDropin'=>true
    )
);");

		if ($result === false) {
			$response[] = self::getResponse('Database Connection', false, "Could not create the following database connection file. Please check permissions: '$path'");
	  		return FALSE;
		}
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

			DIB::$ACTION = self::getResponse('unzip', false, "Installing files...");
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

			DIB::$ACTION = self::getResponse('unzip', false, "Installing files...");
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

		$dibNodeVersion = '20.9.0';
		$dibNodeMajorVersion = 20;
		$dibNodeMinorVersion = 9;

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
		$composerPath = (!empty($params['composerFolder'])) ? $params['composerFolder'] : null;
		
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

		$nodeVersion = shell_exec('node -v 2>&1');
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

		$angularVersion = shell_exec('ng version 2>&1');
		preg_match('/Angular CLI\:\s*(\d+\.\d+\.\d+)/', $angularVersion, $matches);
		$angularVersion = isset($matches[1]) ? $matches[1] : null;
		$angularVersion = $angularVersion ? trim($angularVersion) : null;


//$composerVersion =  null;
//$nodeVersion = null;
//$angularVersion = null;

		$installing = '';
		$installed = '';

		// PowerShell script template with error handling and Invoke-WebRequest fallback
		$psScript = <<< PS
		# Application checksums (https://nodejs.org/en/blog/release/v20.9.0)
		\$nodeJsCheckSumx86 = "808d504dfd367b72260b378a5a5ee1812751a43512ab48d70d9d945f22c71af8"
		\$nodeJsCheckSumx64 = "B2DECDFC3DD4BB43965BE46302E1198B1A3A95DA0BE5C7DC7EB221C185A3C5FD"
		
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
			if (-not \$LASTEXITCODE) {
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
			StopIfFailed -CommandName "Remove \$installerFile"

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
			StopIfFailed -CommandName "Remove composer-setup.php"

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
			php $composerPath\\composer.phar install
			StopIfFailed -CommandName "composer install"

		} catch {
			Write-Host "\r\nComposer install failed." -ForegroundColor Red
			exit 1
		}

		# Navigate to the Angular project directory and run 'npn install'
		Set-Location "$angularPath"
		Write-Host "Running npm install..."

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

		try {
			# Get-ChildItem -Path "C:\Program Files" -Directory
			\$nodeDir = Get-ChildItem -Path "C:\Program Files" -Directory -Filter "nodejs"

			\$nodePath = Resolve-Path -Path (\$nodeDir.FullName + "\\npm.cmd")


			Start-Process -FilePath \$nodePath -ArgumentList "install", '/quiet' -Wait
			StopIfFailed -CommandName "npm install"

		} catch {
			Write-Host "npm install failed." -ForegroundColor Red
			exit 1
		}
		
PS;

		// Add Angular CLI installation if not installed or version is incorrect (with error handling)
		if (!$angularVersion || $angularVersion !== '17.3.9') {
			$installing .= 'Angular CLI 17.3.9<br>';
			$psScript .= <<< PS

		Write-Host "Installing Angular CLI 17.3.9..."
		try {
			Start-Process -FilePath \$nodePath -ArgumentList "install -g @angular/cli@17.3.9", '/quiet' -Wait
			StopIfFailed -CommandName "Install Angular CLI"
			Write-Host "Angular CLI 17.3.9 installed successfully."

		} catch {
			Write-Host "Angular CLI installation failed." -ForegroundColor Red
			exit 1
		}
		PS;
		} else {
			$installed .= 'Angular CLI 17.3.9<br>';
			$psScript .= "\r\nWrite-Host 'Angular CLI 17.3.9 is already installed.'\r\n";
		}

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

		$dibNodeVersion = '20.9.0';
		$dibNodeMajorVersion = 20;
		$dibNodeMinorVersion = 9;

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
		$nodeVersion = shell_exec('node -v 2>/dev/null');
		if($nodeVersion) {
			$nodeVersion = trim(trim($nodeVersion, 'v'));
			$nodeMajorVersion = (int)explode('.', $nodeVersion)[0];
			$nodeMinorVersion = (int)explode('.', $nodeVersion)[1];
		} else 
			$nodeVersion = null;

		// Detect installed Angular CLI version

		//$angularVersion = shell_exec('ng version 2>/dev/null | grep -oP "(?<=CLI: )[^ ]+"');
		$angularVersion = shell_exec('ng version 2>/dev/null');
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

		echo "Install node_modules"

		npm install

		echo "Install Angular CLI"

		BASH;

		if ($angularVersion !== '17.3.9') {
			$installing .= 'Angular CLI 17.3.9<br>';
			$bashScript .= "npm install -g @angular/cli@17.3.9\n";
		} else {
			$installed .= 'Angular CLI 17.3.9<br>';
			$bashScript .= "echo 'Angular CLI 17.3.9 is already installed.'\n";
		}

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
}