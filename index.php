<?php

// To view installed PHP extensions, uncomment the following line
// phpinfo(); die();

$DIR = __DIR__;

// Run installer
require './installer.php'; die();

// Run Dropinbase
$vendorPath = empty(getenv('Dropinbase_Vendor_Path')) ? './vendor/' : getenv('Dropinbase_Vendor_Path');
require $vendorPath . '/dropinbase/dropinbase/index.php';