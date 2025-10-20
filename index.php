<?php

require_once './configs/SecurityHeaders.php';

/* NOTE: Depending on what is uncommented below, this file determines which of the following three options should run: */

// 1. To view installed PHP extensions, uncomment the following line
// phpinfo(); die();

$DIR = __DIR__;

// 2. Run installer (uncomment to run installer)
$INSTALLER=TRUE; require './installer/index.php'; die();

// 3. Run Dropinbase
// Get vendor path from environment variable, default to './vendor/' if not set
$vendorPath = getenv('VENDOR_PATH') ?: './vendor/';
// Ensure trailing slash
$vendorPath = rtrim($vendorPath, '/') . '/';

$dibIndexPath = $vendorPath . 'dropinbase/dropinbase/index.php';
if (!file_exists($dibIndexPath)) {
    die("Dropinbase not found at: " . $dibIndexPath);
}
require $dibIndexPath;

/* -- TIPS --
- The Dropinbase framework can be moved to any folder accessible by the webserver. Just delete /configs/Dib.php and adjust the path above, or hardcode DIB::$SYSTEMPATH in /configs/DibTmpl.php.
- If the folder varies between environments, use environment variables to obtain the path, eg. getenv('Dropinbase_Path').
- A common multi-tenant setup is to reuse the same /dropinbase and /vendor folders. Either use symbolic links, or set DIB::$SYSTEMPATH and DIB::$VENDORPATH in DibTmpl.php.
*/