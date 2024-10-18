<?php

/* NOTE: Depending on what is uncommented below, this file determines which of the following three options should run: */

// 1. To view installed PHP extensions, uncomment the following line
// phpinfo(); die();

$DIR = __DIR__;

// 2. Run installer
 require './installer/index.php'; die();

// 3. Run Dropinbase
require './dropinbase/index.php';

/* -- TIPS --
- The Dropinbase framework can be moved to any folder accessible by the webserver. Just delete /configs/Dib.php and adjust the path above.
- A common multi-tenant setup is to use symbolic links to point to the same /dropinbase folder. This can also be done for the vendor folder.
- If the folder varies between environments, use for eg. getenv('Dropinbase_Path') to obtain it.
*/