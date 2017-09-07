<?php 

// NOTES: The index of the main dropinbase table must match the DBINDEX value in Dib.php  
//        Using an IP address as host (eg 127.0.0.1 instead of 'localhost') can dramatically increase performance

DIB::$DATABASES = array(

   1 => array('database'=>'dropinbase', 'dbType'=>'mysql', 'connectionString'=>'mysql:dbname=dropinbase;host=127.0.0.1;charset=utf8', 'charset'=>'utf8',
              'username'=>'root', 'password'=>'123456', 'host'=>'127.0.0.1', 'port'=>'', 'emulatePrepare'=>true,  'dbDropin'=>'dibMySqlPdo', 'systemDropin'=>true),      
  // 3 => array('database'=>'dib_demo', 'dbType'=>'mysql', 'connectionString'=>'mysql:dbname=dib_demo;host=127.0.0.1;charset=utf8', 'charset'=>'utf8',
  //            'username'=>'root', 'password'=>'123456', 'host'=>'127.0.0.1', 'port'=>'', 'emulatePrepare'=>true,  'dbDropin'=>'dibMySqlPdo', 'systemDropin'=>true), 
     

);

