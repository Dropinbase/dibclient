<?php

/* NOTES: 
   - The index of the main dropinbase table must match the DBINDEX value in Dib.php
   - Depending on your setup, using an IP address as host (eg 127.0.0.1 instead of 'localhost') can increase performance
   - This file is regenerated each time connection details are updated using the /nav/dibConfigs UI in Dropinbase
   - View the Example_Conn.php file for examples of how to connect to different database servers
   - By default the MySQL dropinbase tables use the 'utf8mb4_unicode_520_ci' collation. 
     Ensure your MySQL server's my.ini file is configured accordingly, or change the collation for the dropinbase tables to match your own settings (HeidiSQL has a useful bulk tool).
   - More info about charset and collation:
        https://www.coderedcorp.com/blog/guide-to-mysql-charsets-collations/
*/

DIB::$DATABASES = array(
    1 => array(
        'database'=>'dropinbase',
        'username'=>'dibuser',
        'password'=>'dibpassword',
        'host'=>'db',
        'port'=>3306,
        'charset'=>'utf8mb4',
		'collation' => 'utf8mb4_unicode_520_ci',
        'connectionStringExtra'=>'',
        'dbType'=>'mysql',
        'emulatePrepare'=>true,
        'dbDropin'=>'dibMySqlPdo',
        'systemDropin'=>true
    )
);