<?php

/* NOTE: Depending on what is uncommented below, this file determines which of the following three options should run: */

// 1. To view installed PHP extensions, uncomment the following line
// phpinfo(); die();

$DIR = __DIR__;

// 2. Run installer
$INSTALLER=TRUE; require './installer/index.php'; die();

// 3. Run Dropinbase
require './dropinbase/index.php';

/* -- TIPS --
- The Dropinbase framework can be moved to any folder accessible by the webserver. Just delete /configs/Dib.php and adjust the path above.
- If the folder varies between environments, use environment variables to obtain the path, eg. getenv('Dropinbase_Path').
- A common multi-tenant setup is to reuse the same /dropinbase and /vendor folders. Use symbolic links for folders that cannnot be moved via settings Dib.php.
*/