<?php

// NOTES: The index of the main dropinbase table must match the DBINDEX value in Dib.php
//        Using an IP address as host (eg 127.0.0.1 instead of 'localhost') can dramatically increase performance

DIB::$DATABASES = array(
1 => array(
        'database' => 'dropinbase',
        'dbType' => 'mysql',
        'connectionString' => 'mysql:dbname=dropinbase;host=127.0.0.1;port=3307;charset=utf8mb4',
        'charset' => 'utf8mb4',
        'username' => 'root',
        'password' => '',
        'host' => '127.0.0.1',
        'port' => 3307,               
        'emulatePrepare' => true,
        'dbDropin' => 'dibMySqlPdo',
        'systemDropin' => true
    )
);