<?php 
/* 

NOTES: 
      - The index of the main dropinbase table must match the DBINDEX value in Dib.php  
      - Using an IP address as host (eg 127.0.0.1 instead of 'localhost') can increase performance
      - More info about charset and collation:
          https://www.coderedcorp.com/blog/guide-to-mysql-charsets-collations/

*** TODO :
      'odbc','oracle','access','sybase','db2','ingres','maxdb','informix','dbase','firebird','amazon redshift','sap','cockroachdb'

Example connection configurations:
*/

DIB::$DATABASES = [
      // MySql / MariaDb / SkySQL / AuroraDb
      1 => [
            'database'=>'dropinbase',
            'host'=>'127.0.0.1',
            'port'=>'',
            'username'=>'root',
            'password'=>'123456',
            'timeout'=>60,
            'charset'=>'utf8', 
            'dbType'=>'mysql', 
            'dbDropin'=>'dibMySqlPdo', 
            'systemDropin'=>true,
            'emulatePrepare'=>true,
            // 'collation' => 'utf8mb4_unicode_520_ci', // optional. See link to more info above
            // 'timezone' => ''; // optional
            // 'sql_mode' => ''; // optional
      ],

      // SQLServer:
      3 => [
            'database'=>'MyMsSqlDb',
            'host'=>'MyMachine\\SQL2012',
            'port'=>'',
            'username'=>'root',
            'password'=>'123456',
            'charset'=>'utf8', 
            'dbType'=>'mssql', 
            'dbDropin'=>'dibMsSqlPdo', 
            'systemDropin'=>true,
            'emulatePrepare'=>true
      ],

      // Sqllite: (see https://www.connectionstrings.com/sqlite-net-provider/)
      4 => [
            'database'=>'dib.sqlite3',
            'host'=>'C:/DIB/dib.sqlite3',
            'port'=>'',
            'username'=>'',
            'password'=>'',
            'charset'=>'utf8', 
            'dbType'=>'sqlite', 
            'dbDropin'=>'dibSqlLitePdo', 
            'systemDropin'=>true,
            'emulatePrepare'=>true,
            'foreign_key_constraints'=> true,
      ],

      // PostgreSQL:
      5 => [
            'database'=>'dropinbase',
            'host'=>'127.0.0.1',
            'port'=>3317,
            'username'=>'postgres',
            'password'=>'1A2b3C45',
            'charset'=>'utf8', 
            'dbType'=>'mysql', 
            'dbDropin'=>'dibPgSqlPdo', 
            'systemDropin'=>true,
            'emulatePrepare'=>true,
            // 'timezone' => ''; // optional
      ],

];