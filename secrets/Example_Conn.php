<?php 
/* 

NOTES: 
      - The index of the main Dropinbase database must match the DBINDEX value in Dib.php  
      - Using an IP address as host (eg 127.0.0.1 instead of 'localhost') can increase performance
      - This file is regenerated each time connection details are updated using the /nav/dibConfigs UI in Dropinbase
      - By default the Dropinbase tables use the 'utf8mb4_unicode_520_ci' collation in MySQL. 
        Ensure your MySQL server's my.ini file is configured accordingly, or change the collation for the Dropinbase tables (HeidiSQL has a useful bulk tool).
      - More info about charset and collation:
          https://www.coderedcorp.com/blog/guide-to-mysql-charsets-collations/

*** TODO :
      'odbc','oracle','access','sybase','db2','ingres','maxdb','informix','dbase','firebird','amazon redshift','sap','cockroachdb'

Example connection configurations:
*/

DIB::$DATABASES = [
      // MySql / MariaDb / SkySQL / AuroraDb
      1 => [
            'database'=>'dropinbase', // name of the dropinbase database, eg. shop_dib (then use shop_data for the user tables database)
            'host'=>'127.0.0.1',
            'port'=>3306, // MariaDb often uses 3307
            'username'=>'root',
            'password'=>'12345',
            'timeout'=>60,
            'charset'=>'utf8', 
            'dbType'=>'mysql', 
            'dbDropin'=>'dibMySqlPdo', 
            'systemDropin'=>true,
            'emulatePrepare'=>true,
            'collation' => 'utf8mb4_unicode_520_ci', // See link to more info above
            // 'timezone' => ''; // optional
            // 'sql_mode' => ''; // optional
            // 'connectionStringExtra' => ''; // optional connection string parameters, eg sslmode=require;aaa=bbb;ccc=ddd (see https://www.connectionstrings.com)
      ],

      // SQLServer:
      3 => [
            'database'=>'MyMsSqlDb',
            'host'=>'MyMachine\\SQLSrv',
            'port'=>1433,
            'username'=>'root',
            'password'=>'12345',
            'charset'=>'utf8', 
            'dbType'=>'mssql', 
            'dbDropin'=>'dibMsSqlPdo', 
            'systemDropin'=>true,
            'emulatePrepare'=>true,
            // 'connectionStringExtra' => ''; // optional connection string parameters, eg sslmode=require;aaa=bbb;ccc=ddd (see https://www.connectionstrings.com)
      ],

      // Sqllite: (see https://www.connectionstrings.com/sqlite-net-provider/)
      4 => [
            'database'=>'dib.sqlite3',
            'foreignKeyConstraints'=>true,
            'host'=>'C:/DIB/dib.sqlite3',
            'charset'=>'utf8', 
            'dbType'=>'sqlite', 
            'dbDropin'=>'dibSqlLitePdo', 
            'systemDropin'=>true,
            'emulatePrepare'=>true,
            // 'connectionStringExtra' => ''; // optional connection string parameters, eg sslmode=require;aaa=bbb;ccc=ddd (see https://www.connectionstrings.com)
      ],

      // PostgreSQL:
      5 => [
            'database'=>'mydb',
            'schema' => 'myschema', // needs to be specified for connections on which Dropinbase imports tables. If not specified, it can be used to run custom queries against schemas specified in the SQL (or in the postgreSQL search_path option)
            'host'=>'127.0.0.1',
            'port'=>5432,
            'username'=>'postgres',
            'password'=>'1A2b3C45',
            'charset'=>'utf8', 
            'dbType'=>'pgsql', 
            'dbDropin'=>'dibPgSqlPdo', 
            'systemDropin'=>true,
            'emulatePrepare'=>true,
            // 'timezone' => ''; // optional,
            // 'connectionStringExtra' => ''; // optional connection string parameters, eg sslmode=require;aaa=bbb;ccc=ddd (see https://www.connectionstrings.com)
      ],

];