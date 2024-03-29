<?php

 self::$fileLocations = array(

	// *** NOTE: use forward-slashes (/) to delimit folders in paths since back-slashes are used to escape characters in PHP

 	// Path to PHP's error log file - used to display in dibDebug (leave blank on production, or if not desired)
 	'phpErrorLog' => 'C:/wamp64/logs/php_error.log',
	
 	// Path to web server's error log file - used to display in dibDebug (leave blank on production, or if not desired)
 	'webErrorLog' => 'C:/wamp64/logs/apache_error.log',
 	
 	// Path to PHP executable file used with asynchronous threads in Windows. If not listed or empty, an attempt will be made to find it.
 	'phpExecutablePathWindows' => '',
 	
 	// Path to PHP executable file used with asynchronous threads in Linux. If not listed or empty, an attempt will be made to find it.
	'phpExecutablePathLinux' => '', 
	
	// (Windows only) full path to PHP Code editor application (adds ability to open files from Designer)
	'phpCodeEditor' => "C:/Users/___MY___WINDOWS___USER___/AppData/Local/Programs/Microsoft VS Code/Code.exe" // OR 'C:/Program Files (x86)/Microsoft VS Code/Code.exe'
 );