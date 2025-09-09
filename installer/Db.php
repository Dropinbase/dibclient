<?php

/* 
 * Copyright (C) Dropinbase - All Rights Reserved
 * This code, along with all other code under the root /dropinbase folder, is provided "As Is" and is proprietary and confidential
 * Unauthorized copying or use, of this or any related file, is strictly prohibited
 * Please see the License Agreement at www.dropinbase.com/license for more info
*/

class Db {	

    private static $count = 0;
    private static $dbh = null;
    private static $connectionString = '';
    private static $username = '';
    private static $password = '';
    private static $lastUserMsg = null;
	private static $lastAdminMsg = null;
	private static $forceNewConn = false;
    private static $dbType = 'mysql'; // default dbType
	
    public static function setConn($connectionString, $dbType, $username, $password) {
		self::$username = $username;
		self::$password = $password;
		self::$connectionString = $connectionString;
        self::$dbType = $dbType;
		
		self::$forceNewConn = true;
	}
	
	public static function execute($sql, $params = array(), $firstRecordOnly = FALSE, $style = null, $cacheKey = null) {
        try {
            self::$count = 0;
            
            // Check if connection has already been made
            if(!self::$dbh || self::$forceNewConn) {
                if(self::$dbType == 'mysql')
                    $extraOptions = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'");
                else
                    $extraOptions = array();

            	self::$dbh = new PDO(self::$connectionString, self::$username, self::$password, $extraOptions);

    			self::$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    			self::$forceNewConn = false;
    		}            
            
            
			$stmt = self::$dbh->prepare($sql);

            foreach ($params AS $key => $value) 
                $stmt->bindValue($key, $value);

			if (!$stmt->execute()) {
				self::setErrorMsg("There was an error executing a query on the database server. Please contact the System Administrator or try again.",
				                  "The following query could not be executed against database: " . $sql);
                return FALSE;
			}
        
			// Get count of affected records
            self::$count = $stmt->rowCount();

            if ((strtoupper(substr($sql, 0, 6))==='SELECT' || $style)) {
                if(!$style) $style = PDO::FETCH_ASSOC;  
                
                if($firstRecordOnly) {
                    $rst = $stmt->fetch($style);
                    if($rst === false) $rst = array(); // fetch returns false if no records exist!
                    if ($cacheKey) Cache::set($cacheKey, json_encode($rst));
                    return $rst;
                
                } else {
					if ($cacheKey) {
						$rst = $stmt->fetchAll($style);
						Cache::set($cacheKey, json_encode($rst));
						return $rst;
                    }     
                 
                    return $stmt->fetchAll($style);
                }
            } elseif (strtoupper(substr($sql, 0, 6))==='INSERT')
                return self::$dbh->lastInsertId();
            else
				return TRUE;

		} catch (PDOException $e) {
            $msg = $e->getMessage();            
//Log::w($msg,$sql,json_encode($params),debug_backtrace());
            $i = stripos($msg,"duplicate entry");
            if ($i !== FALSE) {
                $i = strpos($msg, "'", $i + 14);
                $j = strpos($msg, "'", $i + 1);
                $value = substr($msg, $i + 1, $j - ($i + 1));
                self::setErrorMsg("Unique value error! The following value (or combination of values) already exists in a record of the destination table: '$value'", 
                                      $msg);
            } else {
                $i = stripos($msg,'foreign key constraint');

                if ($i !== FALSE) {
                    $i = strpos($msg, ".", $i + 14) + 1;
                    $j = strpos($msg, ",", $i + 1) - 1;
                    $value = substr($msg, $i + 1, $j - ($i + 1));
                    /* Since we call Database class statically, we can't call it within itself...
                    $sql2 = 'SELECT caption FROM pef_table WHERE name = :name AND pef_database_id = ' . $dbIndex;
                    $rst = self::fetch($sql2, array(':name'=>$value));                    
                    $value = (isset($rst['caption'])) ? $rst['caption'] : $value;*/
     
                    if(strtolower(substr($sql,0,6)) === 'update')
                    	self::setErrorMsg("Updating a field that references(is linked to) the '$value' table failed because the value does not exist in the '$value' table.", 
                                          $msg);
                    elseif(strtolower(substr($sql,0,6)) === 'insert') {
						$i = strpos($msg, "REFERENCES ", $i + 14) + 1;
	                    $j = strpos($msg, " (", $i + 8) - 1;
	                    $value = substr($msg, $i + 11, $j - ($i + 11));
						self::setErrorMsg("Adding a record failed because it should relate(link) to the '$value' table, but the value supplied in the linking field does not exist in the '$value' table.", 
                                          $msg);
                    } else
                    	self::setErrorMsg("Deleting a record failed because other records in the '$value' table are related (linked) to it. Either first delete the related records, or remove/change the relation with them.", 
                                          $msg);
                } else {
                    self::setErrorMsg('Database error. Please contact the System Administrator.',$msg);
                }
            }   
			return FALSE;
        }
	}
    
        /**
    * Start a transaction
    */
    public static function transactionBegin() {
        try {    
            // Check if connection has already been made
            if(self::$dbh) {
    			self::$dbh = new PDO(self::$connectionString , 
                                   self::$username, self::$password
                                   );
                
    			self::$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    		}
            self::$dbh->beginTransaction();
        
        } catch (PDOException $e) {
            self::setErrorMsg('Database error. Please contact the System Administrator.', 
                                  'Could not start a transaction on the database server. Technical details: ' . $e->getMessage());
			return FALSE;
        }  
    }
    
    /**
    * Commit a transaction
    */
    public static function transactionCommit() {
     	try {    
            self::$dbh->commit();
        
        } catch (PDOException $e) {
            self::setErrorMsg('Database error. Please contact the System Administrator.', 
                                  'Could not commit a transaction on the database server. Technical details: ' . $e->getMessage());
			return FALSE;
        }
    }
    
    /**
    * Rollback a transaction
    */
    public static function transactionRollback() {
        try {    
            self::$dbh->rollback();
        
        } catch (PDOException $e) {
            self::setErrorMsg('Database error. Please contact the System Administrator.', 
                                  'Could not rollback a transaction on the database server. Technical details: ' . $e->getMessage());
			return FALSE;
        }
    }
    
    public static function stmt() {
	    return $stmt;
    }
    
    public static function count() {
        return self::$count;
    }
    
    public static function setCount($counter) {
		self::$count = $counter;
	}
	
	/**
	* Returns the last error message generated for the administrator
	* 
	* @return string
	*/
    public static function lastErrorAdminMsg() {
	    return self::$lastAdminMsg;
    }
    
    /**
	* Returns the last error message generated for the user
	* 
	* @return string
	*/
    public static function lastErrorUserMsg() {
	    return self::$lastUserMsg;
    }
    
    /**
	* Sets the lastErrorAdminMsg and lastErrorUserMsg messages
	* @param string $userMsg
	* @param string $adminMsg	* 
	*/
    public static function setErrorMsg($userMsg, $adminMsg) {
        self::$lastUserMsg = $userMsg;
	    self::$lastAdminMsg = $adminMsg;
    }
    
}