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
      // MySql / MariaDb
      // NOTE: index 1 is normally reserved for the main Dropinbase database
      1 => [
            'database'=>'dropinbase', // name of the dropinbase database, eg. shop_dib (then use shop_data for the user tables database)
            'host'=>'127.0.0.1',
            'port'=>3306, // MariaDb often uses 3307
            'username'=>'root',
            'password'=>'12345',
            'timeout'=>60,
            'charset'=>'utf8mb4', 
            'dbType'=>'mysql', 
            'dbDropin'=>'dibMySqlPdo', 
            'systemDropin'=>true,
            'emulatePrepare'=>true,
            'timeout'=>60, // time-limit for SQL queries (in seconds)
            'collation' => 'utf8mb4_unicode_520_ci', // See link to more info above
            // 'timezone' => '', // optional
            // 'sql_mode' => '', // optional
            // 'connectionStringExtra' => '', // optional connection string parameters, eg sslmode=require;aaa=bbb;ccc=ddd (see https://www.connectionstrings.com)
            // 'connectionOptionsExtra' => [ // array of optional connection options, eg [PDO::MYSQL_ATTR_SSL_CA => '/path/to/ca.pem', ... ],
      ],

      // NOTE: index 2 is normally reserved for the Template Database

      // Sqllite: (see https://www.connectionstrings.com/sqlite-net-provider/)
      3 => [
            'database'=>'dib.sqlite3',
            'foreignKeyConstraints'=>true,
            'host'=>'C:/DIB/dib.sqlite3',
            'charset'=>'UTF-8', 
            'dbType'=>'sqlite', 
            'dbDropin'=>'dibSqlLitePdo',
            'systemDropin'=>true,
            'emulatePrepare'=>true,
            'timeout'=>60, // time-limit for SQL queries (in seconds)
            'cache_size'=>-20000, // number of pages (default page size is 1024 bytes, so -20000 = approx 20MB)
            //'extraConnectionCommands' => [], // optional array of extra pragma or other commands to run after connecting - overrides defaults, eg. ['PRAGMA temp_store = FILE', 'PRAGMA synchronous=FULL']
      ],

      // PostgreSQL:
      4 => [
            'database'=>'mydb',
            'schema' => 'myschema', // needs to be specified for connections on which Dropinbase imports tables. If not specified, it can be used to run custom queries against schemas specified in the SQL (or in the postgreSQL search_path option)
            'host'=>'127.0.0.1',
            'port'=>5432,
            'username'=>'postgres',
            'password'=>'1A2b3C45',
            'charset'=>'UTF8', // client_encoding
            'dbType'=>'pgsql', 
            'dbDropin'=>'dibPgSqlPdo', 
            'systemDropin'=>true,
            'emulatePrepare'=>true,
            'timeout'=>60, // time-limit for SQL queries (in seconds)
            // 'timezone' => '', // optional,
            // 'connectionStringExtra' => '', // optional connection string parameters, eg sslmode=require;aaa=bbb;ccc=ddd (see https://www.connectionstrings.com)
            // 'connectionOptionsExtra' => [], // array of optional connection options
      ],

      // SQLServer:
      5 => [
            'database'=>'MyMsSqlDb',
            'host'=>'MyMachine\\SQLSrv',
            'port'=>1433,
            'username'=>'root',
            'password'=>'12345',
            'charset'=>'UTF-8', 
            'dbType'=>'mssql', 
            'dbDropin'=>'dibMsSqlPdo', 
            'systemDropin'=>true,
            'emulatePrepare'=>true,
            'timeout'=>60, // time-limit for SQL queries (in seconds)
            // 'connectionStringExtra' => '', // optional connection string parameters, eg sslmode=require;aaa=bbb;ccc=ddd (see https://www.connectionstrings.com)
            // 'connectionOptionsExtra' => [], // array of optional connection options. 
            // NOTE, the following are set by default: SET QUOTED_IDENTIFIER ON; SET ANSI_NULLS ON;
      ],

      // SkySQL
      6 => [
            'database'=>'my_sky_data',
            'host'=>'123.456.123.10', // SkySQL host
            'port'=>3306, // MariaDb often uses 3307
            'username'=>'root',
            'password'=>'12345',
            'timeout'=>60,
            'charset'=>'utf8mb4', 
            'dbType'=>'mysql', 
            'dbDropin'=>'dibMySqlPdo', 
            'systemDropin'=>true,
            'emulatePrepare'=>true,
            'timeout'=>60, // time-limit for SQL queries (in seconds)
            'collation' => 'utf8mb4_unicode_520_ci', // See link to more info above
            // 'timezone' => ''; // optional
            // 'sql_mode' => ''; // optional
            // 'connectionStringExtra' => '', // optional connection string parameters, eg sslmode=require;aaa=bbb;ccc=ddd (see https://www.connectionstrings.com)
            'connectionOptionsExtra' => [ // array of optional connection options
                  PDO::MYSQL_ATTR_SSL_CA            => '/path/to/skysql_chain.pem', // or null if you disable verify
                //  PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,                  // only if you don’t ship the CA file
            ]
      ],

      // AuroraDb:
      7 => [
            'database'=>'my_data',
            'host'=>'my-aurora-cluster.cluster-123456789012.us-east-1.rds.amazonaws.com', // Aurora MySQL host
            'hostRead'=>'my-aurora-cluster.cluster-ro-123456789012.us-east-1.rds.amazonaws.com', // Aurora MySQL read replica host
            'connectionString' => 'mysql:host=my-aurora-cluster.cluster-123456789012.us-east-1.rds.amazonaws.com;dbname=my_data;charset=utf8mb4',
            'connectionStringRead' => 'mysql:host=my-aurora-cluster.cluster-ro-123456789012.us-east-1.rds.amazonaws.com;dbname=my_data;charset=utf8mb4',
            'port'=>3307, 
            'username'=>'root',
            'password'=>'12345',
            'charset'=>'utf8mb4', 
            'dbType'=>'mysql', 
            'dbDropin'=>'dibMySqlPdo', 
            'systemDropin'=>true,
            'emulatePrepare'=>true,
            'timeout'=>60, // time-limit for SQL queries (in seconds)
            'collation' => 'utf8mb4_unicode_520_ci', // See link to more info above
            // 'timezone' => ''; // optional
            // 'sql_mode' => ''; // optional
            // 'connectionStringExtra' => ''; // optional connection string parameters, eg sslmode=require;aaa=bbb;ccc=ddd (see https://www.connectionstrings.com)
            // 'connectionOptionsExtra' => [ // array ofoptional connection options, eg [PDO::MYSQL_ATTR_SSL_CA => '/path/to/ca.pem', ... ]
      ],

];