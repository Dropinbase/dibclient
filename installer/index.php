<?php

/* 
 * Copyright (C) Dropinbase - All Rights Reserved
 * This code, along with all other code under the root /dropinbase folder, is provided "As Is" and is proprietary and confidential
 * Unauthorized copying or use, of this or any related file, is strictly prohibited
 * Please see the License Agreement at www.dropinbase.com/license for more info
*/
   //phpinfo(); die(); // To view installed PHP extensions etc, uncomment this line

    class Log {
        public static function err($msg) {
            DIB::$RESPONSE[] = array(
                "name"  => 'SignIn',
                "ready" => false,
                "notes" => $msg
            );
        }

        public static function w() {
            $args = func_get_args();
            if(!isset($args[0])) return;

            $data = date("Y-m-d H:i:s") . "]--\r\n";

            foreach ($args AS $key=>$arg)
                $data .= $key . ": \t" . print_r($arg, true) . "\r\n";
            
            $data = substr($data, 0, -2);

            self::err($data);
        }
    }
    
    error_reporting(E_ALL & ~E_STRICT);

    $path = __DIR__;

    // Use existing Dib.php file if it exists, so that we can determine possible existing path to Dropinbase framework
    // Note $DIR is set in /index.php
    if(file_exists($DIR . DIRECTORY_SEPARATOR . 'configs' . DIRECTORY_SEPARATOR . 'Dib.php'))
        include_once $DIR . DIRECTORY_SEPARATOR . 'configs' . DIRECTORY_SEPARATOR . 'Dib.php';
    else
        include_once $path . DIRECTORY_SEPARATOR . 'Dib.php';

    // Handle resource files
    $resources = array(
        '/resources/installer.css',
        '/resources/installer.js',
        '/resources/favicon.ico',
        '/resources/busyTick.png',
        '/resources/pendingTick.png',
        '/resources/logocircle.png',
        '/resources/east.png',
        '/resources/logo_tr.png'
    );

    if (in_array($_SERVER['REQUEST_URI'], $resources)){
        include($path.$_SERVER['REQUEST_URI']);
        return;
    }

    // Handle actions
    $actions = array(
        '/testPhp',
        '/phpModules',
        '/saveTestDb',
        '/updateIndex',
        '/signIn',
        '/downloadDib',
        '/downloadDibProgress',
        '/configureDib',
        '/checkDibInstall',
    );

    include($path.DIRECTORY_SEPARATOR.'main.php');

    if (in_array($_SERVER['REQUEST_URI'], $actions)){

        $action = trim((string)$_SERVER['REQUEST_URI'], '/');

        if($action === 'phpModules') { phpinfo(); die; }

        header("Content-Type: application/json; charset=UTF-8");

        $params = getPostedVars();

        $result = Install::$action($params);
        return;
    }

    // Show View

    // Get Conn.php entry 1 values (if exists)
    list($host, $port, $database, $username, $password, $disabled, $composerFolder, $owner, $group, $webUser) = getFields($DIR);

    if($disabled) {
        $dbNote = "The first entry (loaded in the fields below) is assumed to reference the Dropinbase database. <br>If it does not, <span style=\"color:red\">DO NOT</span> click the button below since this will create the Dropinbase tables within this database.";
        $dbBtn = "Test Connection. Create the DIB database tables if they don't exist";
    } else {
        $dbNote = "Configure the MySQL/MariaDb entry for the Dropinbase database below.";
        $dbBtn = "Test Connection. Create Conn.php file, and DIB database if it doesn't exist";
    }

    include('view.html');
    die();

    // Gets variables in posted data
    function getPostedVars() {
        $requestPayload = file_get_contents('php://input');

        if(empty($requestPayload)) return array();

        $params = json_decode($requestPayload, TRUE);
        if(empty($params)) return array();
		
        return $params;
	}

    function getFields($basePath) {

        $connPath = $basePath  . DIRECTORY_SEPARATOR . 'configs' . DIRECTORY_SEPARATOR . 'Conn.php';
        
        $disabled = '';

        // Get Composer Folder
        if (strtolower(substr(php_uname(), 0, 7)) !== "windows")
            $composerFolder = null;
        else {
            $composerVersion = Install::getComposerVersion();
            $composerFolder = (empty($composerVersion)) ? "C:\\composer" : null;
        }

        // Get Linux users
        list($owner, $group, $webUser) = Install::getOwnerGroupWebUser();
  
        $values = array('localhost', '3306', 'dropinbase', 'root', '', $disabled, $composerFolder, $owner, $group, $webUser);

        if(file_exists($connPath)) {

            require $connPath;

            if(!empty(DIB::$DATABASES)) {
                // Get index of first entry
                $dbIndex = array_keys(DIB::$DATABASES)[0];
                //if(count(DIB::$DATABASES) > 1)
                //    $disabled =  'disabled';

                $e = DIB::$DATABASES[$dbIndex];

                $values = array(
                    (empty($e['host']) ? '' : $e['host']),
                    (empty($e['port']) ? '' : $e['port']),
                    (empty($e['database']) ? '' : $e['database']),
                    (empty($e['username']) ? '' : $e['username']),
                    (empty($e['password']) ? '' : $e['password']),
                    $disabled,
                    $composerFolder,
                    $owner, $group, $webUser
                );

            }
        }

        return $values;
    }
