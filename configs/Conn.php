<?php 

// NOTES: The index of the main dropinbase table must match the DBINDEX value in Dib.php  
//        Using an IP address as host (eg 127.0.0.1 instead of 'localhost') can dramatically increase performance

DIB::$DATABASES = array( // dropinbase, glasschem_dib
	//1 => array('database'=>'dropinba_ngdev', 'dbType'=>'mysql', 'connectionString'=>'mysql:dbname=dropinba_ngdev;host=dropinbase.com;charset=utf8', 'username'=>'dropinba_ngdev',
   //           'password'=>'h1bKPiDgrqV1', 'host'=>'dropinbase.com', 'port'=>'', 'emulatePrepare'=>true, 'charset'=>'utf8', 'dbDropin'=>'dibMySqlPdo', 'systemDropin'=>true),      

   1 => array('database'=>'dropinba_ngdev', 'dbType'=>'mysql', 'connectionString'=>'mysql:dbname=dropinba_ngdev;host=127.0.0.1;charset=utf8', 'charset'=>'utf8',
              'username'=>'root', 'password'=>'123456', 'host'=>'127.0.0.1', 'port'=>'', 'emulatePrepare'=>true,  'dbDropin'=>'dibMySqlPdo', 'systemDropin'=>true),      

   7 => array('database'=>'dib_demo', 'dbType'=>'mysql', 'connectionString'=>'mysql:dbname=dib_demo;host=127.0.0.1;charset=utf8', 'charset'=>'utf8',
              'username'=>'root', 'password'=>'123456', 'host'=>'127.0.0.1', 'port'=>'', 'emulatePrepare'=>true,  'dbDropin'=>'dibMySqlPdo', 'systemDropin'=>true),      
 
   8 => array('database'=>'apros', 'dbType'=>'mysql', 'connectionString'=>'mysql:dbname=apros;host=127.0.0.1;charset=utf8', 'username'=>'root',
              'password'=>'123456', 'host'=>'127.0.0.1', 'port'=>'', 'emulatePrepare'=>true, 'charset'=>'utf8', 'dbDropin'=>'dibMySqlPdo', 'systemDropin'=>true), 
   
   

); 

?>