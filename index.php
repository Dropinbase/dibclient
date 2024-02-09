<?php

// To view installed PHP extensions, uncomment the following line
// phpinfo(); die();

// Example how to enable environment variables for your project:
/*
if (file_exists("configs/.env.php")) {
    require "configs/.env.php";
}
*/

// Set the relevant environment variables to enable memcache
/*
if (!empty(getenv('eb_memcache')) || isset($_REQUEST['testmemcache'])) {
    ini_set('session.save_handler', 'memcached');

    if (isset($_REQUEST['testmemcache'])) {
        ini_set('session.save_path', getenv('ebtest_memcache'));
    } else {
        ini_set('session.save_path', getenv('eb_memcache'));
    }
    
    ini_set('session.lazy_write', 'On');
    ini_set('session.sess_locking', 'Off');
    ini_set('memcached.sess_lock_expire', '0');
    ini_set('memcached.allow_failover', '1');
    ini_set('memcached.chunk_size', '32768');
    ini_set('memcached.sess_lock_retries', '10');
    ini_set('memcached.default_port', '11211');
    ini_set('memcached.hash_function', 'crc32');
    ini_set('memcached.sess_prefix', 'memc.sess.key.');
}
*/

$DIR = __DIR__;

// Run installer
require './installer.php'; die();

// Run Dropinbase
$vendorPath = empty(getenv('Dropinbase_Vendor_Path')) ? './vendor/' : getenv('Dropinbase_Vendor_Path');
require $vendorPath . '/dropinbase/dropinbase/index.php';