<?php

/* NOTES: 
   - The index of the main dropinbase table must match the DBINDEX value in Dib.php
   - Depending on your setup, using an IP address as host (eg 127.0.0.1 instead of 'localhost') can increase performance
   - View the Example_Conn.php file for examples of how to connect to different database servers
*/

DIB::$DATABASES = [
    1 => [
        'database' => 'dropinbase',
        'username' => 'root',
        'password' => '',
        'host' => '127.0.0.1',
        'port' => 3306,
        'emulatePrepare' => true,
        'charset' => 'utf8mb4',
        'dbType' => 'mysql',
        'dbDropin' => 'dibMySqlPdo',
        'systemDropin' => true
    ],



];