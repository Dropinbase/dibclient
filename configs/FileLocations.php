<?php

 self::$fileLocations = array(
 	// Path to PHP's error log file - used to display in dibDebug
 	'phpErrorLog' => 'c:/wamp/logs/php_error.log',
 		 
 	// Path to web server's error log file - used to display in dibDebug
 	'webErrorLog' => 'c:/wamp/logs/apache_error.log',
 		
 	// Path to PHP executable file used with asynchronous threads in Windows. If not listed or empty, an attempt will be made to find it.
 	'phpExecutablePathWindows' => '',
 	
 	// Path to PHP executable file used with asynchronous threads in Linux. If not listed or empty, an attempt will be made to find it.
 	'phpExecutablePathLinux' => '' 
 );