<?php

/**
 * Tutorial examples of common Dropinbase server-side functions
 *   
 */
class PhpController extends Controller {
    // NOTE, Controllers should always extend the basic Controller class in order to
    //   send a result back to the client via the validResult or invalidResult functions
    //   See below for more details...    
    
    // Loading classes
    public function loadClasses($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
		
		// Classes in the /components folder of the current dropin are loaded 
		// automatically using the framework's autoloader' as needed (note, require_once is used)
		// Ie, if the event url is /dropins/dibExamples/ANYCONTROLLERHERE then
		// the classes in /dropins/dibExamples/components need not be required explicitly.
		$msgToSendToClient = Tests::testMsg('To infinity and beyond');
		
        // Classes in the Dropinbase core, eg the Database class, are also loaded automatically using the autoloader
        // The file paths are stored in the /runtime/Components.php file, which is created automatically if it is not found.
		$rst = Database::execute("SELECT id, name FROM test_company LIMIT 20", null, 'dibtestCompanyGrid');
		
		// Classes in other dropins can be loaded with the PeffApp::load function
		PeffApp::load('dibExcel', 'DibExcelExport.php', 'components');
		$e = New DibExcelExport();
		
		// PeffApp::load can be used to merely get the physical path to a file in a dropin folder (without loading it)
		//   by setting the 4th parameter to FALSE
		$templateFile = PeffApp::load('dibExcel', 'ExcelExportTemplate.xlsx', 'templates', FALSE);
		if($templateFile === FALSE)
			return $this->invalidResult("Could not load the default /dropins/dibExcel/templates/ExcelExportTemplate.xlsx template file. Please restore it and try again.");
            
		$filters = array('submitHeaderFilter');
		$data = array('name'=>'>b', 'id'=>'<=40');
		
		$e->exportContainer('dibtestCompanyGrid', $templateFile, $data, $filters, 'A1', 5000, TRUE, TRUE);
		
		// The PeffApp::loadPath function can be used when a Dropinbase path/url is present
		// The following example's 2nd parameter avoids loading the file, and the 3d parameter states there is no function name in the url
		$result = PeffApp::loadPath("/dropins/dibCsv/CsvProcessor", FALSE, FALSE);
		if($result[0]==='error')
			return $this->invalidResult("Could not find the /dropins/dibCsv/CsvProcessor class. Please restore it and try again.");

		$msgToSendToClient .= ".\r\n" . ' The class name is: ' . basename($result[0]);
		
		// This particular event's response_type = 1000, which set client Queue requests in motion to handle to above DibExcelExport Queue actions.
		// The client is not waiting for a response from the server, but we do need to stop the client from polling the server for Queue actions.
		// Note: The various event response types are:
		//      'actions' - the client waits for a (in)validResult (see the databaseFunctions function below as an example)
		//      'redirect' - the client will accept headers to redirect to another url, or receive a file
		//      an integer value or 'stop' - initializes or stops the Queue - more about this later (see below)
		
		// Add the message to the Queue, and stop the client Queue from issuing further requests
		return Queue::addMsg('Message', $msgToSendToClient, 'dialog', 0, false, 'stop');
	
    }
    
    // Database functions
    public function databaseFunctions($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
		// Instead of implementing a full ORM (Object Relational Mapping) class by default, 
		// Dropinbase provides Crud classes for tables and queries (see crudFunctions below), 
		// and otherwise allows the execution of any SQL statement.
		// Please see /nav/dibDocs?id=97&text=SQL%20Overview for more info 
		// Additional help for the Database class is available on the same link 
		
		// *** Error handling
		// If the SQL fails due to a system or syntax error (eg database server is down or table does not exist etc)
		//   then $rst would be FALSE and Database::count() would be 0. An error will be logged in /runtime/logs/error.log.
		//   Database::lastErrorAdminMsg() will contain technical details of the error.
		//   If the the error originates in core DIB classes such as the Crud class, 
		//   Database::lastErrorUserMsg() will often contain a useful message for display to the user.
		// If it succeeds but there are no records then $rst would be an empty array, and Database::count() would still be 0.
		// Note that certain database engines do not reliably return the count of affected records. Use !empty($rst) or count($rst)<1 instead...
		
		// WARNING. In case of an error, the Database class returns FALSE but does not cause the system to stop executing PHP code. 
		// Developers must handle potential errors.
		
		// *** Referential integrity (foreign key constraint) errors 
		// Sql statements that fail because of referential integrity on relationships will return FALSE, but will not be logged in error.log
		// The details of the error can however be retrieved by using the Database::lastErrorAdminMsg() and Database::lastErrorUserMsg() functions.		
		// If any INSERT, UPDATE or DELETE statement could fail because of referential integrity, 
		// either run another query prior to it to ensure conditions are met that will not cause referential integrity issues, OR
		// test the result of the database call and handle it.
		
		
		// *** Fetch one record from the dropinbase database
		
		$rst = Database::fetch("SELECT 1 + 2 AS answer, min(name) as minName, max(id) as MaxId
								FROM test_company");		
		if($rst === FALSE)
			// It is a good idea to point the user to the System Administrator, as the error.log will contain further details.
			return $this->invalidResult("System error! Please contact the System Administrator for more info.");
        elseif(count($rst)<1)
			return $this->invalidResult("Could not find any records in the test_company table. Please populate it and try again.");
        
        // Get a value
		$str = "1 + 2 = " . $rst['answer'] . "\r\n";
		$str .= "Min name : " . $rst['minName'] . "\r\n";
		
		// *** Fetch multiple records from the dropinbase database, use parameters, and loop through the records
		
		// WARNING! To avoid SQL injection, always use PDO parameters (eg :minName below) instead of 
		//   adding text that originates from users directly to the SQL string
		
		$params = array(':minName'=>$rst['minName']);
		$rst = Database::execute('SELECT id, name FROM test_company WHERE name > :minName AND id < 20', $params);
		
		$str .= 'Id values: ';
		foreach($rst as $key=>$record) {
			if(strlen($record['name']) > 7)
				$str .= $record['id'] . (isset($rst[$key+1]) ? ', ' : '');
		}
		
		// *** Another example of using parameters (still using the dropinbase database)
		// (AGAIN - ALWAYS use parameters with user data)
		
		$params = array(':notes'=>'testing', 
						':website'=>'http://www.example.com', 
						':id'=>100);
		$result = Database::execute("UPDATE test_company SET notes = :notes, website = :website WHERE id = :id", $params);
		
		// *** Using another database

		// The third parameter can be the name of a container, 
		//   OR the index of a database in pef_database (which must correspond to the index in /config/Conn.php)
		// The use of a container name is preferred:
		//   1. to ensure your solution can be deployed and distributed without conflict.
		//   2. only users with permissions to open the container will be allowed to run the query. 
		//      FALSE will be returned on a permission error, an error will be logged in /runtime/logs/error.log, and
		//      Database::lastErrorAdminMsg() will contain details, while Database::lastErrorUserMsg() is useful for display to the user.
		
		// For the sake of demonstration we are using the database containing the dibtestCompanyGrid table
		
        $rst = Database::fetch("SELECT min(chinese_name) as minName FROM test_company", null, 'dibtestCompanyGrid');
        if($rst === FALSE)
			return $this->invalidResult(Database::lastErrorUserMsg());
		if(empty($rst['minName']))
			return $this->invalidResult("Ooops there are no records with chinese names :(");

        $str .= "\r\nChinese name 1: " . $rst['minName'];
        
        // Note, the following specification of $databaseId is unneccessary - 
        //    without the 3d parameter the sql executes against the dropinbase database.
        // Since we dont have enough info about tables in other databases on your system, 
        //    we provide this merely as an example of what your code may look like:
       
        $databaseId = DIB::DBINDEX;
        $rst = Database::fetch("SELECT min(chinese_name) as minName FROM test_company", null, $databaseId);
        $str .= "\r\nChinese name 2: " . $rst['minName'];
		
		
		// Using Database::create and Database::update. 	

		// *** Note that field names containing spaces or other non-alphanumeric characters (except underscore(_)) could cause bugs. 

		$newName = uniqid('NewCo ', true);
		$params = array('name'=>$newName, 'chinese_name'=>'T达拉斯', 'notes'=>'xxx');
		// Again, the third parameter can be a database index or a container name
		$pkeyValue = Database::create('test_company', $params, 'dibtestCompanyGrid'); 


		$params = array('chinese_name'=>'123 T达拉斯', 'notes'=>'yyy');
		// The 4th parameter is a SQL WHERE clause
		$result = Database::update('test_company', $params, 'dibtestCompanyGrid', "name = '$newName'");

		// To avoid SQL injection, it is best to use PDO parameters in criteria, ie :name, but then we must include `name` in $params too
		$params = array('name'=>$newName, 'chinese_name'=>'123 T达拉斯', 'notes'=>'yyy');
		$result = Database::update('test_company', $params, 'dibtestCompanyGrid', 'name = :name');

		// Prefixing the key with # indicates that the value is not a constant, but a SQL expression (which can contain PDO parameters)
		// Prefixing the key with ! indicates that no attempt must be made to update this field, ie the parameter must not be included in the update. 
		//    The parameter will be used for criteria or expressions and is probably not a field name. The value in this case will always be treated as a constant.

		$suffix = 'some user value';
		$newName2 = uniqid('NewCo2 ', true);
		$params = array(
			'name'=>$newName2,
			'#notes'=>"CONCAT(notes, ' *--', name, '--*')", // note the # prefix to indicate the value is not a constant
			'#chinese_name'=>'CONCAT(chinese_name, :suffix)', // note the # prefix and the use of :suffix
			'!suffix'=>$suffix, // note the ! prefix to exclude the parameter from any update operation
			'id'=>$pkeyValue
		);

		// AUDIT TRAILS

		// The (optional) last parameter is used to specify the level of AUDIT TRAILING to apply (by default no audit trailing is done)		
		//    detail - individual records for old and new values of each field that has changed is stored in the audit_trail for the given container
		//    summary - one record is used to store old and new values of each field that has changed
		//    basic - only the primary key value(s) is stored 
		// NOTE: when activating audit trailing, a container name must be used to indicate the database index (third parameter). 
	    $result = Database::update('test_company', $params, 'dibtestCompanyGrid', 'id = :id', 'basic');

		// NOTE, multiple records can be affected by Database::update and Database::delete actions - values changed in all will be captured in the audit trail

		// Let's delete that record (or any range of records defined by criteria)
		// Note we opt to store a single record in the audit trail for demo purposes
		$result = Database::delete('test_company', array(':name'=>$newName2), 'dibtestCompanyGrid', 'name=:name', 'summary');

		// Since we have the primary key value we can also simply do this:
		$result = Database::delete('test_company', array('id'=>$pkeyValue), 'dibtestCompanyGrid');		

        // *** Fetching records in a different PDO Style
        // See http://php.net/manual/en/pdostatement.fetch.php
        $rst = Database::execute("SELECT id FROM test_company WHERE id < 5", null, 'dibtestCompanyGrid', PDO::FETCH_OBJ);
        $str .= "\r\nMore Id's: ";
        foreach($rst as $key=>$record)
			$str .= $record->id . (isset($rst[$key+1]) ? ', ' : '');
		// Note, if there are no records, $rst contain an empty array and the foreach would merely be skipped.
		
		// *** Executing other SQL statements that do not start with the word 'SELECT'
		
		/* 
		  Note, if the first 6 characters of the SQL statement is 'SELECT' (case-insensitive), OR a $style is specified, then 
          records are returned using the $style format (by default a 3-dim associative array). 
          If these characters are 'INSERT' (case-insensitive) then the new primary key value is returned.
          Else the query is merely executed and TRUE or FALSE is returned
		*/
		
		// Use these statements to experiment with Database::count() (see below)
		$result = Database::execute("UPDATE test_company SET Notes = 'testing' WHERE id < 4");
		$result = Database::execute("DELETE FROM test_company WHERE id > 9000000");
		$str .= "\r\nRecords affected by the last statement: " . Database::count();

		// In many database engines, eg. MySQL, the Database::count() method will return the number of 
		//  affected records of the last SELECT, INSERT, UPDATE and DELETE statement
		// WARNING. According to the PDO documentation, this function does however not work reliably for SELECT statements of all database engines.
		//   See http://php.net/manual/en/pdostatement.rowcount.php
		// (DIB uses "SELECT @@Rowcount" for Sql Server SELECT statements to set Database::count() correctly).
		
		// It is therefore recommended to use one of the following tests when fetching records...
		// if(empty($rst)) ... OR
		// if(count($rst)<1) ...
		
		
		
		// *** Executing data dictionary queries 
		// Since these type of queries do not start with the word 'select', but do return records, 
		//   we specify a $style to instruct the Database class to return the records
		// Note, the DIB::$DATABASES array contains the array specified in Conn.php 
		
		if(DIB::$DATABASES[DIB::DBINDEX]['dbType'] === 'mysql')
			$result = Database::fetch("SHOW TABLE STATUS LIKE 'test_company'", array(), 'dibtestCompanyGrid', PDO::FETCH_ASSOC);
		
		$str .= "\r\ntest_company meta data: ";
		foreach($result as $key=>$value)
			$str .= $key . '=>' . $value . ', ';
		
		$str = rtrim($str, ', ');

		// *** Working with transactions 
		// Transactions provide a convenient way to ensure that a batch of sql statements all succeed 
		//   before any changes are committed to the database, otherwise they are all rolled back.
		// The Dropinbase functions that import tables to create containers use transactions to ensure 
		//   all records that constitute containers are successfully inserted before committing them.
		
		// Begin a transaction
		Database::transactionBegin(); // Note, this function accepts a database id or container name as parameter. DIB::DBINDEX is used as default.
		
		// Here we can execute multiple sql statements - if any of them return FALSE we rollback the transactions ...
		$result = Database::execute("UPDATE test_company SET Notes = 'testing' WHERE id=2");
		
		if($result === FALSE) {
			Database::transactionRollback();
			return $this->invalidResult("Could not update the test_company table.");
		}
		
		// etc... 
		// etc...
		// If all is well, commit the transactions
		Database::transactionCommit();

		// NOTE: especially for long running SQL scripts, or high volumes of concurrent users, using transactions could cause table/record locking issues.
		// You may therefore need to write code to ensure only one user runs the queries at any given moment... 
		
		
		// *** Using more advanced PDO features of prepared statements  
		// In a scenario where we transfer potentially thousands of records from one database to another,
		//   we would be executing the same queries multiple times, but with different parameter values.
		// In such cases the Database::stmt function can be used to return a prepared statement 
		// Note, by default the Database class always uses prepared statements (even for a query executed only once)
		
		$params = array(':id'=>5);
		// The last paramenter below is set to FALSE in order to prevent the return of records (ie merely prepare a statement).
		$rst = Database::execute("SELECT id, name FROM test_company WHERE id < :id", $params, DIB::DBINDEX, null, TRUE, FALSE);
		$str .= "\r\nRecords affected by the last statement: " . Database::count(); // Note, for some database engines, Database::count() does not work...
		
		// Get the statement
		$fetchStmt = Database::stmt(); // Note, this function accepts a database id or container name as parameter. DIB::DBINDEX is used as default.
		
		// Loop through the records 
		while ($row = $fetchStmt->fetch(PDO::FETCH_NUM)) {
			// Here we could use another prepared statement to INSERT records one by one, using the ->bindValue method of the statement.
			// As an example, see the TransferRecords function in /dropinbase/components/database/PDatabaseTools.php (still in Beta)
		}
		
		// Also note that the Crud class has functions to query records in batches/pages
		// See the crud function below... 
		
		// Write the data collected in $str to a file 
		$path = DIB::$RUNTIMEPATH . 'tmp' . DIRECTORY_SEPARATOR . 'Testing Database Functions.txt';
		file_put_contents($path, $str);
		
		// If the event's reponse_type is 'actions' (which is the default), we must always send a response to the client API 
		//   that has been waiting (note only the first parameter is required below);
		// See the function comments for validResult and invalidResult in /dropinbase/system/Controller.php for more details
		return $this->validResult(NULL, "The result file, 'Testing Database Functions.txt', has been created in the /runtime/tmp folder.", 'dialog');
    }
    
    // Crud functions
    public function crudFunctions($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
		/**
		 * Provides CRUD functionality based on ContainerName (for containers) or ItemId (for e.g. dropdownlists). 
		 * It makes use of the same crud code that is generated specifically for each container within the various /crud folders.
		 * The files are generated automatically if they do not exist.
			This implies:
				1. container specific validation is done on Create and Update
				2. container and item specific permissions apply on all actions
				3. grid header filters and subcontainer/activefilters are available
				4. audit trailing (if specified in pef_table) applies
				5. records can be retrieved as batches/pages
		*
		* NOTE, if an error occurs on any of the Crud functions, 
		*       the result = array('error', "Useful error message to display to the user.");
	 	*/
		
		// select ($containerName, $phpFilter=null, $phpFilterParams=array(), $sort=null, $pageSize=1000000, $page=1, $readType='gridlist',
		//		   $gridFilter=null, $activeFilter=null, $activeFilterParams=array(), $node=null, $countMode='all')
		
		// Get all records from testCompanyGrid where id < 10, and sort results on first_name descending, and then on last_name ascending
		// Note we use table name aliases in criteria (eg test_company.xxx) since other tables in the WHERE clause may have fields with the same names
		list($records, $count) = Crud::select('dibtestCompanyGrid', 'test_company.id < 10', array(), 'name=>DESC;chinese_name=>');
		
		if($records === 'error')
			return $this->invalidResult ("Crud::select error: $count");
			
		// Loop through them
		$str = "There are $count records:\r\n";
		foreach ($records as $key=>$record) {
			$str .= $key . ":\r\n";
			foreach($record as $field=>$value)
				$str .= "'$field' = $value\r\n";		
		}
		
		// Filter records using parameters, sort results on first_name, fetch only the first 5 records (of the 1st page), and use item captions as field names
		$params = array(':id' => 20, ':name' => '%at%');
		$result = Crud::select('dibtestCompanyGrid', 'test_company.id < :id AND test_company.name LIKE :name', $params, 'name=>ASC', 5, 1, 'exportlist');
		
		// Note using $page and $pageSize, a loop can be constructed to process batches of records
		// This is especially useful when potentially thousands of records can cause a server to run out of memory 
		
		// Retrieve the first 1000 records where the id is between 5 and 100, and the name field contains 'at', using the grid filter syntax
		// Note, table name prefixes must not be added to fields
		$params = array('id' => '>=5&<=100', 'name' => "*at*");
		$result = Crud::select('dibtestCompanyGrid', null, null, null, 1000, 1, 'gridlist', $params);
		
			
		// fetch ($containerName, $pkValues)
		
		// Fetch a specific record by primary key value
		$result = Crud::fetch('dibtestCompanyGrid', array('id'=>5));
		
		
		// fetchDefaults($containerName, $createParams = null, $submissionData=array()
		
		// Fetch the crud defaults applicable to fields in a specific container, set the name field to 'new company name'
		$result = Crud::fetchDefaults('dibtestCompanyGrid', array('name'=>'new company name'));
		if(isset($result[0]) && $result[0]==='error')
			return $this->invalidResult ("Crud::fetchDefaults error: " . $result[1]);
		
		
		// update($containerName, $pkValues, $values)
		
		// Update values in a record identified by primary key values 
		// Note you must include values for all required fields
		$result = Crud::update('dibtestCompanyGrid', array('id'=>5), array('notes'=>'hello', 'chinese_name'=>'new name'));
		if(isset($result[0]) && $result[0]==='error')
			return $this->invalidResult ("Crud::update error: " . $result[1]);
		
		
		// create ($containerName, $newValues)
		
		// Create a new record (note $result will contain an array of primary key values)
		$newRecord = array('name'=>uniqid(), 'chinese_name'=>'hmmm...');
		$result = Crud::create('dibtestCompanyGrid', $newRecord);
		if(isset($result[0]) && $result[0]==='error')
			return $this->invalidResult ("Crud::create error: " .$result[1]);
		
		
		// delete($containerName, $pkValues)
		
		// Delete a record identified by primarykey values in $pkValues
		$pkValues = $result;
		$result = Crud::delete('dibtestCompanyGrid', $pkValues);
		if(isset($result[0]) && $result[0]==='error')
			return $this->invalidResult ("Crud::delete error: " .$result[1]);
			
			
		// getPrimaryKeys($containerName)
		
		// Returns an array of primary key names used in the table the container is based on
		$result = Crud::getPrimaryKeys('dibtestCompanyGrid');
		
		
		// getFieldTypes($containerName)
		
		// Returns an array of the container item's field names and types
		$result = Crud::getFieldTypes('dibtestCompanyGrid');
		
		
		// getCaptions($containerName) 
		
		// Returns an array of the container item's field captions and types
		$result = Crud::getCaptions('dibtestCompanyGrid');
		
		
		// function getSqlFields($containerName)
		
		// Returns an array of the container sql field names and expressions
		$result = Crud::getSqlFields('dibtestCompanyGrid');
		
		
		// getSqlParts($containerName)
		
		// Returns an array of sql clauses (eg select, from, where, group by) used to query the database. Also returns the crud class name.
	    $result = Crud::getSqlParts('dibtestCompanyGrid');
	    
	    
	    // duplicate($containerName, $pkValues, $setValues=array(), $targetDatabaseId=null) 
	    
	    // Creates a duplicate of a record - will only work where primary key is auto-increment or supplied in $setValues
	    // Duplicate record with primary key value of 5, and set the name to a random string. 
	    $result = Crud::duplicate('dibtestCompanyGrid', array('id'=>5), array('name'=> uniqid(), 'notes'=>'duplicating id=5'));
    	if(isset($result[0]) && $result[0]==='error')
			return $this->invalidResult ("Crud::duplicate error: " .$result[1]);
		
		// Return a response to the client (note only the first parameter is required)
		return $this->validResult(NULL, "Crud function testing completed", 'dialog');
		
    }
    
    // getGridCritAndParams
    public function gridCriteriaAndParams($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
		
	/*  It is often necessary to process the data a user has filtered on a grid, eg to export it to a file.
	    The grid may be in a subcontainer with a filter linked to a parent container, or 
	    the user may have filtered records on the grid, or selected records, 
	    or all three cases may apply simultanously - limiting the records you need to work with.
	    To obtain the records, the DibFunctions:::getGridCritAndParams function can be used
			
	*/
	
		// The function extracts the specified $filters from $submissionData entries and builds a SQL criteria string
		
		// Specify $filters to look at:
		$filters = array(
			'submitCheckedItem.self', // Records the user selected/checked in the grid
			'submitHeaderFilter.self', // Records the user filtered using the grid's header filters
			'submitActiveFilter.self' // The subcontainer's filter (this entry is normally not applicable)
		);
        
        // Note, the $criteria, $params, $table, and $fieldTypes variables are returned by reference - no need to initialize them first
        $result = DibFunctions::getGridCritAndParams('dibtestCompanyGrid', $submissionData, $filters, $criteria, $params, $table, $fieldTypes);
       
        if($result !== true) {
			Log::err('Could not get criteria for grid. Error details: ' . $result);
        	return $this->invalidResult('Could not determine criteria. Please contact the System Administrator.');
		}
		
        // Get records  
        list($records, $filteredCount) = Crud::select($containerName, $criteria, $params);
        
        // Process records... 
        // Just post variables to /runtime/Debug.txt
        //Log::w($records, $filteredCount, $criteria, $params);
        
        // Return a response to the client (note only the first parameter is required)
        $msg = ($filteredCount == 1) ?  '1 record was involved' : $filteredCount . ' records were involved';
        return $this->validResult(NULL, $msg);
    }    

    // Post data, and returning files
    public function sendFile($containerName, $itemEventId, $submissionData=null) {
    /*	
	---	Returning files:
		The default response type for item and container events is 'actions' which causes the client to always
		wait for a json response. When the response type is 'redirect' the client will not expect a response,
		and headers can be sent to the client for other purposes, such as returning files for download.
		
	--- submissionData
		With 'redirect', submissionData cannot be posted... data needs to be sent in the URL.
		Ensure therefore that values that are included in submissionData do not contain data that 
		would cause the length of the URL to through a client-side exception. 
		Use the "dibIgnore_" prefix to item names to exclude items from being included where needed.
	*/
	
		$sendText = (!isset($submissionData['submitItemAlias.self']['sendText'])) ? 'Empty :)' : $submissionData['submitItemAlias.self']['sendText'];
		
		// Let's put $sendText in a PDF file and return it to the user
		
		// Since online users can add links to very large images etc, we strip html tags and use only the first 1000 characters.
		$sendText = strip_tags($sendText);
		$sendText = substr($sendText, 0, 1000);
		
		if(trim($sendText)==='') $sendText = '<h2>We may have stripped that...</h2>';
		
		// Replace line feeds with <br>
		$sendText = str_replace("\n", '<br>', $sendText);
		
		// Add HTML headers etc
		$sendText = '
		<html>
		<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /></head>
		<body>' .
		$sendText
		. '</body>
		</html>
		';	

		// First load DPdf 
		PeffApp::load('dibPdf', 'DPdf.php', 'components');		
		
		DPdf::convertHtml($sendText, true, 'myPdf.pdf');
		
		/* 
		// ALTERNATIVE ... store the HTML in a file and export it to the client...
		
		$now = DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''));
		$fileName = uniqid('test_', TRUE) . '_' . $now->format("YmdHisu") . 'txt';
		$filePath = DIB::$RUNTIMEPATH . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . $fileName;
	    
		file_put_contents($filePath, $sendText);
		
		// The exportFileToClient function sends the appropriate headers for us
		DibFunctions::exportFileToClient($filePath, $containerName.'.txt'); 
		
		// Delete the temp file
	    unlink($filePath);
	    */
    }
	
	 //
	 public function buildDocxXslx($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
        
		list($sendText, $msg) = PeffApp::getSubmitVal($submissionData, 'sIA.s', array('sendText','msg'));
		
		// Note $itemAlias contains the type of template being merged : docx / xlsx

		$tmplFile = DIB::$DROPINPATHDEV . 'dibExamples' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $itemAlias . 'Tmpl.' . $itemAlias;
		$resultFile = DIB::$DROPINPATHDEV . 'dibExamples' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'test.' . $itemAlias;

		$params = array('profile_id'=>2, 'name'=>'John', 'path'=>DIB::$FILESPATH . 'icons' . DIRECTORY_SEPARATOR);

		$el = new Eleutheria();
		
		$el->mergeTmpl($itemAlias, $tmplFile, $resultFile, TRUE, FALSE, $params);

		/**
		 * mergeTmpl($InputFormat, $TemplateFile, $OutputFile='', $OutputToBrowser=false, $OutputToMemory=false, $Parameters=array(), $cacheKey='', $configs='')	
		 *
		 * @params $InputFormat - One of 'html','text','htmlbody','textbody','docx','xlsx'
		 * @params $TemplateFile - If 'htmlbody' or 'textbody', then template content, else physical path to template file
		 * @params $OutputFile = '' - (Optional) Physical path to where result must be stored
		 * @params $OutputToBrowser = FALSE - Whether result must be sent to browser
		 * @params $OutputToMemory = FALSE - Whether result is kept in public class variables docHeader, docBody and docFooter after function exits
		 * @params $Parameters = array() - (Optional) Global merge parameters
		 * @params $cacheKey = '' - (Optional) Stores result in cache using $cacheKey
		 * @params $configs = '' - (Optional) Comma delimited string eg 'cj,bj,js'. Options: 'ca' (compress javaccript) 'cj' (compress json), 
		 *							'ph' (purify html with HtmlPurifier), 'bh' (beautify html), 'bj' beautify js with JsBeautifier
		*/
		
		// NOTE: We could set $OutputToBrowser to FALSE above, and then use the function below to specify a specific name for our download
		//DibFunctions::exportFileToClient($resultFile, 'testFile.' . $itemAlias);
	}
	
	//
    public function buildPDF($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
        
		$tmplFile = DIB::$DROPINPATHDEV . 'dibExamples' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'pdfTmpl.html';

		$params = array('profile_id'=>2, 'name'=>'John');

		$el = new Eleutheria();
		// Export to memory so that we can get hold of the content
		$el->mergeTmpl('html', $tmplFile, '', FALSE, TRUE, $params);
		
		$content = $el->docBody[0];

		// First load DPdf 
		PeffApp::load('dibPdf', 'DPdf.php', 'components');
		
		$result = DPdf::convertHtml($content, true, 'myPdf.pdf');
		/**
		 * public static function convertHtml($html, $exportToClient, $file)
		 * 
		 * Converts HTML to a PDF and saves the file to the supplied filename location, or sends the file to the client.
		 * 
		 * Input:
		 *    $html - The html to convert
		 *    $exportToclient - whether resulting pdf file must be streamed to the client or not 
		 *    $file - The physical location to save the PDF, OR (if $exportToClient=True) the name of the file to send to the client
		 * 
		 * Output:
		 *    true/false - If conversion was successful or not
		 */

		 if($result === FALSE)
		 	echo "Conversion to PDF failed, please contact the System Administrator for more info (error.log)";

	}
	
    // Asynchronous execution
    public function asyncExecution($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
	/*
		Most server scripts execute within the time limit imposed by PHP's settings, while preparing a response for the client.
		Normally scripts that potentially take more time are handled using one of the following strategies:
		1. Increase both the client and server timeouts (normally not a good solution)
		2. Send a response to stop the client from waiting. 
		   Execute the actual script asynchronously (and impose an acceptable timelimit) - when its done notify the client to take further action.
		
		Below the second method is used to run a very basic script asynchronously

		NOTE: An even simpler method is available with Dropinibase: Queues...  see QueueController for more info...
	*/
		
		if(!isset($submissionData['submitItemAlias.self']['msg']))
			return $this->invalidResult("First specify a message and try again.");
		
		$msg = $submissionData['submitItemAlias.self']['msg'];
		
		// Since $msg will be echoed to the user, it is important to validate the contents to avoid possible xss attacks
		if(!ctype_alnum(str_replace(' ', '', $msg)))
			return $this->invalidResult("Aikona! The message may only contain alphanumeric characters and spaces. Please amend and try again.");
		
		// Prepare variables to send to the asynchronous script
		$args = array(
	        'msg'=>$msg,
	        'another_var'=>'here I am'
        );
        
        // Since we'll initiate the Queue server-side, we need to set a unique id that will be used by the client when requesting actions for this user
        // Note PeffApp::randomToken() generates a cryptographically secure random string which is important to prevent hackers predicting the value.
		// The call to DibFunctions::async below will send this value to the asynchronous script to use in any Queue actions.
        // The call to validResult at the end of this function will package this value for return the client.
        PeffApp::$queueUid = DIB::$USER['id'] .'_'. PeffApp::randomToken(20);
        
        // Get the path to the script
        $file = PeffApp::load('dibExamples', 'AsyncTest.php', 'components', FALSE);
        
        // Execute the script asyncronously, using a timeout of 5 minutes (a timeout value of 0 = infinite). 
        // Any errors will be logged in /runtime/logs in a .log file with a random name, prefixed with 'AsyncTest_'
        // The script will run within a Try Catch, so exceptions can be thrown ( eg throw new Exception("Error msg goes here..."); )
        // (the random name prohibits file-locking behaviour of PHP's shell_exec function with concurrent users)
        
        $result = DibFunctions::async($file, $args, (60 * 5), 'AsyncTest_');
        
        // On success $result will be the processId of the running script. On failure it will be FALSE. 
        if($result === FALSE)
        	return $this->invalidResult('System error! Please contact the System Administrator.');
        
        // Send a response to stop the client from waiting, and to start the Queue to poll for messages
        return $this->validResult(
        	TRUE, 
            "Cooking up something good ... In the meantime you may continue using the system for other tasks.",             
            'notice', 4000, 1000
        );
	
    }
    
    // Escaping functions
    public function escaping($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
	/*
		When building responses using text that in any way originated from users, it is important to escape it properly to avoid css attacks.
		(Note Sql injection is avoided by always using PDO parameters as explained above)
		
		The following are places where user data is displayed
		1. Container Components (eg grid/form fields)
		2. Trees
		3. dropdown lists 
		4. (In)ValidResult messages		
		5. msgPopup
		6. Prompt
		7. setValue
		8. ItemHandler		
		
		The first 3 cases are handled by Angular's ng-bind and ng-bind-html directives that escapes data 
		
		The remaining cases must preferably be escaped serverside if there is any possibility that the content may be insecure.
	*/
    }
    
    // Other functions ***TODO...
    public function other($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
		PeffApp::createPath();
		PeffApp::getRealIpAddr();
		Log::err("mmm");
	
    }

   

}