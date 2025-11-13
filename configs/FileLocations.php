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
    // (Windows only) full path to you PHP Code editor application (adds ability to open files from Designer)
    'phpCodeEditor' => findVSCode() // OR hardcode it, eg. 'C:/Program Files (x86)/Microsoft VS Code/Code.exe'
);

// Attempt to find the PHP executable path for Visual Studio Code editor
function findVSCode(): ?string {
	$override = getenv('VSCODE_PATH');
    if ($override && is_file($override))
        return $override;

    $cli = trim(shell_exec('where.exe code 2>NUL'));
    if ($cli !== '' && is_file($cli))
        return $cli;

	$user = getActiveUsername() ?? 'Public';

    $candidates = [
		(getenv('SystemDrive') ?: 'C:') . '\Users\\' . $user . '\AppData\Local\Programs\Microsoft VS Code\Code.exe',
        getenv('LOCALAPPDATA') . '\Programs\Microsoft VS Code\Code.exe',
        getenv('ProgramFiles') . '\Microsoft VS Code\Code.exe',
        getenv('ProgramFiles(x86)') . '\Microsoft VS Code\Code.exe',
        getenv('LOCALAPPDATA') . '\Microsoft\WindowsApps\Code.exe',
        getenv('LOCALAPPDATA') . '\Programs\Microsoft VS Code Insiders\Code - Insiders.exe',
        getenv('ProgramFiles') . '\Microsoft VS Code Insiders\Code - Insiders.exe',
    ];

    foreach ($candidates as $path) {
        if (is_file($path))
            return $path;
    }

	return null;
}

// Return the username of the *active* console/RDP session
function getActiveUsername(): ?string{
    $out = shell_exec('quser.exe 2>NUL');
    if (!$out)
        return null;

    foreach (preg_split('/\r?\n/', trim($out)) as $line) {
        if (preg_match('/^\s*>\s*(\S+)/', $line, $m)                    // current session
         || preg_match('/^\s*(\S+)\s+\S+\s+\d+\s+Active\b/', $line, $m)) {
            return $m[1];                                               // first column = user
        }
    }
    return null;
}
