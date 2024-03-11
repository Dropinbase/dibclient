<?php

/* NOTE: Depending on what is uncommented below, this file determines which of the following three options should run: */

// 1. To view installed PHP extensions, uncomment the following line
// phpinfo(); die();

$DIR = __DIR__;

// 2. Run installer
require './installer.php'; die();

// 3. Run Dropinbase
// Obtain path to the /vendor folder where the Dropinbase framework is installed
$vendorPath = empty(getenv('Dropinbase_Vendor_Path')) ? './vendor/' : getenv('Dropinbase_Vendor_Path');
require $vendorPath . '/dropinbase/dropinbase/index.php';