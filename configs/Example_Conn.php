<?php 
/* Example connection configurations:

MySql Pdo:
         array('database'=>'dropinbase', 'host'=>'127.0.0.1', 'connectionString'=>'mysql:dbname=dropinbase;host=127.0.0.1;charset=utf8', 'username'=>'root',
               'password'=>'123456', 'port'=>'', 'emulatePrepare'=>true, 'charset'=>'utf8', 'dbType'=>'mysql', 'dbDropin'=>'dibMySqlPdo', 'systemDropin'=>true),
MsSql Pdo:
         array('database'=>'myMsSqlDb', 'host'=>'MyMachine\\SQL2008', 'connectionString'=>'sqlsrv:Server=MyMachine\\SQL2008;Database=MyMsSqlDb', 'username'=>'testuser',
               'password'=>'test', 'port'=>'', 'emulatePrepare'=>true, 'charset'=>'utf8', 'dbType'=>'mssql', 'dbDropin'=>'dibMsSqlPdo', 'systemDropin'=>true),
               
Sqllite: (see https://www.connectionstrings.com/sqlite-net-provider/)
         array('database'=>'pef.sqlite3', 'host'=>'D:/DIB/pef.sqlite3', 'connectionString'=>'sqlite:D:/DIB/pef.sqlite3', 'username'=>'',
               'password'=>'', 'port'=>'', 'emulatePrepare'=>true, 'charset'=>'utf8', 'dbType'=>'sqlite', 'dbDropin'=>'dibSqlLitePdo', 'systemDropin'=>true),

*** TODO :
'postgresql','oracle','access','interbase','db2','ingres','maxdb','sybase','dbase','firebird','pervasivesql','sap'

*/

// NOTES: The index of the main dropinbase table must match the DBINDEX value in Dib.php  
//        Using an IP address as host (eg 127.0.0.1 instead of 'localhost') can dramatically increase performance

DIB::$DATABASES = array(
    1 => array('database'=>'dropinbase', 'dbType'=>'mysql', 'connectionString'=>'mysql:dbname=dropinbase;host=127.0.0.1;charset=utf8', 'username'=>'root',
                'password'=>'123456', 'host'=>'127.0.0.1', 'port'=>'', 'emulatePrepare'=>true, 'charset'=>'utf8', 'dbDropin'=>'dibMySqlPdo', 'systemDropin'=>true), 
    8 => array('database'=>'dib_modules', 'dbType'=>'mysql', 'connectionString'=>'mysql:dbname=dib_modules;host=127.0.0.1;charset=utf8', 'username'=>'root',
                'password'=>'123456', 'host'=>'127.0.0.1', 'port'=>'', 'emulatePrepare'=>true, 'charset'=>'utf8', 'dbDropin'=>'dibMySqlPdo', 'systemDropin'=>true)     
); 

?>
