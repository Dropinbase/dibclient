<?php

// NOTES: The index (below) for the main dropinbase database must match the value of DBINDEX in Dib.php
//        Using an IP address as host (eg 127.0.0.1 instead of 'localhost') can dramatically increase performance

DIB::$DATABASES = array(
1 => array(
        'database' => 'dropinbase',
        'dbType' => 'mysql',
        'connectionString' => 'mysql:dbname=dropinbase;host=127.0.0.1;charset=utf8',
        'charset' => 'utf8',
        'username' => 'root',
        'password' => '123456',
        'host' => '127.0.0.1',
        'port' => null,               
        'emulatePrepare' => true,
        'dbDropin' => 'dibMySqlPdo',
        'systemDropin' => true
    ),
);
