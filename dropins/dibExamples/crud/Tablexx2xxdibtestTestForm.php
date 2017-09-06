<?php 
    Class Tablexx2xxdibtestTestForm { 
    public $count = 0;
    public $newPkValue = null; // for INSERTS it will contain lastInsertId
    public $pkeys = 'id';
    public $fieldType = array("id"=>PDO::PARAM_INT, "smallint_fld"=>PDO::PARAM_INT, "email"=>PDO::PARAM_STR, "varchar10_required"=>PDO::PARAM_STR, "int_fld"=>PDO::PARAM_INT, "url"=>PDO::PARAM_STR, "has_default"=>PDO::PARAM_STR, "bigint_fld"=>PDO::PARAM_INT, "longitude"=>PDO::PARAM_STR, "time_stamp"=>PDO::PARAM_STR, "float_fld"=>PDO::PARAM_STR, "lattitude"=>PDO::PARAM_STR, "unique_fld"=>PDO::PARAM_STR, "double_fld"=>PDO::PARAM_STR, "file_fld"=>PDO::PARAM_STR, "nvarchar80"=>PDO::PARAM_STR, "decimal_fld"=>PDO::PARAM_STR, "image_fld"=>PDO::PARAM_STR, "text_fld"=>PDO::PARAM_STR, "date_fld"=>PDO::PARAM_STR, "document_fld"=>PDO::PARAM_STR, "tinytext_fld"=>PDO::PARAM_STR, "time_fld"=>PDO::PARAM_STR, "expression_fld"=>PDO::PARAM_STR, "mediumtext_fld"=>PDO::PARAM_STR, "datetime_fld"=>PDO::PARAM_STR, "notes"=>PDO::PARAM_STR, "longtext_fld"=>PDO::PARAM_STR, "year_fld"=>PDO::PARAM_INT, "test_company_id"=>PDO::PARAM_INT, "bit_fld"=>PDO::PARAM_STR, "enum_fld"=>PDO::PARAM_STR, "test_company2_id"=>PDO::PARAM_INT, "tinyint_fld"=>PDO::PARAM_BOOL, "set_fld"=>PDO::PARAM_STR, "dibuid"=>PDO::PARAM_STR);
    public $storeType = array("id"=>'none', "smallint_fld"=>'none', "email"=>'none', "varchar10_required"=>'none', "int_fld"=>'none', "url"=>'none', "has_default"=>'none', "bigint_fld"=>'none', "longitude"=>'none', "time_stamp"=>'none', "float_fld"=>'none', "lattitude"=>'none', "unique_fld"=>'none', "double_fld"=>'none', "file_fld"=>'none', "nvarchar80"=>'none', "decimal_fld"=>'none', "image_fld"=>'none', "text_fld"=>'none', "date_fld"=>'none', "document_fld"=>'none', "tinytext_fld"=>'none', "time_fld"=>'none', "expression_fld"=>'none', "mediumtext_fld"=>'none', "datetime_fld"=>'none', "notes"=>'none', "longtext_fld"=>'none', "year_fld"=>'none', "test_company_id"=>'dropdown', "bit_fld"=>'none', "enum_fld"=>'none', "test_company2_id"=>'dropdown', "tinyint_fld"=>'none', "set_fld"=>'none', "dibuid"=>'none');
    public $sqlFields = array();
    public $fkeyDisplay = array(
                 'test_company_id'=>"^^CONCAT(`test_company1001`.`name`, ' (' , CAST(`test_company1001`.`id` AS CHAR), ')')^^", 'test_company2_id'=>"^^CONCAT(`test_company1002`.`name`, ' (' , CAST(`test_company1002`.`id` AS CHAR), ')')^^", 
                 );
    protected $filterArray = null;
    function __construct() {
        $dbClassPath = (DIB::$DATABASES[DIB::$CONTAINERDATA[2]]['systemDropin']) ? DIB::$SYSTEMPATH.'dropins'.DIRECTORY_SEPARATOR : DIB::$DROPINPATHDEV;
        require_once $dbClassPath.'setData/dibMySqlPdo'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'dibMySqlPdo.php';
        set_time_limit(180);
    }
    /**
     * parses array $filterParams and converts to a SQL WHERE clause string and PDO parameters
     */
    private function parseFilterArray($filterParams=array(), &$params=array(), &$fieldType=array()) {
        if (count($filterParams) === 0) 
            return '';
        $i = 0;
        $str = '';
        foreach($filterParams as $record) {
            $field = $record['property'];
            $value = $record['value'];
            if(!array_key_exists($field, $this->fieldType))
                return array('error',"An unknown fieldname was used in filter criteria. Please contact the System Administrator.");
            if(array_key_exists($field, $this->sqlFields))
            	$str .= $this->sqlFields[$field] . ' = :pk' . $i . ' AND ';
			else
            	$str .= '`test`.`' . $field . '` = :pk' . $i . ' AND ';
            $params[':pk' . $i] = $value;
            $fieldType[':pk' . $i] = $this->fieldType[$field];
            $i++;
        }
        return substr ($str, 0, strlen($str) - 4);
    }
    /**
	 * Returns sql criteria string and related pdo parameters and parameter types, given a filter name
	 * @param string $activeFilter name of filter
	 * @param array $params empty array to be populated with pdo parameter values
	 * @param array $filterParams associative array of activeFilter parameter values, e.g. array('notes'=>'%aaa%', 'id'=>5)
	 * 
	 * @return string - sql criteria string, and $params via referencing
	 */ 
    public function parseFilter($activeFilter, &$params=array(), &$filterParams=array()) {
		$criteria = ''; 
        if($criteria==='') return  array('error',"Error! The named active filter could not be found in the crud class. Please contact the System Administrator.");
        return substr($criteria, 4);
	}
    /**
     * Fetches the primary key values of the next, previous, etc. records for use with the Form's toolbar
     */
    public function getToolbarInfo($pkValues, $option, $activeFilter, $filterParams) {
        $params = array();
        $fieldType = array();
        if (!array_key_exists("id", $pkValues))
            return array ('error',"The primary key fields specified in the request are invalid.");        
         if ($pkValues["id"] != (string)(float)$pkValues["id"])
            return array ('error',"Error! The primary key field values specified in the request are invalid.");
        $params[":pk1"] = $pkValues["id"];
        $fieldType[":pk1"] = PDO::PARAM_INT;    
        $pkList = "`test`.`id`";
        $criteria = '';
        $order ='';
        if ($option === 'next')  { 
            $criteria = "WHERE `id` > :pk1";
            $order = "ORDER BY `test`.`id`";
        } elseif ($option === 'prev') {
            $order = "ORDER BY `id` DESC";
            $criteria = "WHERE `id` < :pk1";
        } elseif ($option === 'last') {
            $order = "ORDER BY `test`.`id` DESC";
            $params = array();
            $fieldType = array();
        } elseif ($option === 'total') {
            $pkList = 'Count(*) as `total`';
            $params = array();
            $fieldType = array();
        } elseif ($option === 'current') {
            $pkList = 'Count(*) as `current`';
            $criteria = "WHERE `id` <= :pk1";
        } elseif ($option ==='first') {
            $order = "ORDER BY `test`.`id` ASC";
            $params = array();
            $fieldType = array();
        }
        // Template: sql statement for MySql to fetch records for the Toolbar on Forms. Used in eg CrudPdoTemplate.php.
$sql = "SELECT $pkList FROM `test` $criteria $order LIMIT 1";
        dibMySqlPdo::setParamsType($fieldType, DIB::$CONTAINERDATA[2]);       
        $rst = dibMySqlPdo::execute($sql, DIB::$CONTAINERDATA[2], $params, true);
        if(dibMySqlPdo::count() > 0)
            return $rst;
        else
            return null;
    }
    /**
     * Fetches the primary key values of the nth record
     */
    public function getToolbarRecord($position, $activeFilter, $filterParams) {
        $position = (int)$position;
        if(!$position || $position < 0)
            return array('error', 'Position must be a positive integer');
        if($position < 1) $position = 1;
		$criteria = '';
        $params = array();
        // Template: sql statement for MySql to fetch nth record for the Toolbar on Forms. Used in eg CrudPdoTemplate.php.
$sql = "SELECT `test`.`id` 
        FROM `test`
        $criteria
        ORDER BY `test`.`id` 
        LIMIT " . ($position - 1) . ', 1'; 
        $rst = dibMySqlPdo::execute($sql, DIB::$CONTAINERDATA[2], $params, true);
        if(dibMySqlPdo::count() > 0)
            return $rst;
        else
            return null;
    }
    /**
     * parses $gridFilter and returns a SQL WHERE clause string, and PDO parameters (passed by reference)
     */
    public function parseGridFilter($gridFilter, &$params=array(), &$fieldType=array(), &$usedSqlField=FALSE) {
        //Eg [{"property":"name","value":"g & >e"},{"property":"notes","value":"< z &  w"}]
        $allCrit = '';
        $i = 0;
        foreach($gridFilter as $row) {
        	if(!isset($row['property']) || !isset($row['value']))
        		return array('error',"Filter criteria was not submitted in correct format. Please contact the System Administrator.");
        	$field=$row['property'];
        	$value=$row['value'];
            if(!array_key_exists($field, $this->fieldType))
                return array('error',"An unknown fieldname was used in filter criteria. Please contact the System Administrator.");
            // Handle sql expression fields
            if(array_key_exists($field, $this->sqlFields)) {
            	$fieldExpr = $this->sqlFields[$field];
            	$usedSqlField = TRUE; // Indication that SELECT Count(*) for filteredCount must use all table joins in FROM clause
            } else
            	$fieldExpr = "`test`.`$field`";
            $subparts = explode ('&', str_replace('|', '|&', $value));
            $fieldCrit = '';
            foreach($subparts as $stringValue)  {
                $stringValue = trim($stringValue);
                if($stringValue === '')
                	return array('error', "The filter criteria syntax is incorrect. Please try again.");        	
                if ($fieldCrit !== '')
                    $fieldCrit .= $conjunction; //$conjunction is found in prev. loop
                if (substr($stringValue, -1) === '|') {
                    $conjunction = ' OR ';
                    $stringValue = substr($stringValue, 0, strlen($stringValue) - 1);
                } else
                    $conjunction = ' AND ';
                $intValue = trim($stringValue, '=!>< ');
                if ($intValue === '') 
                	return array('error', "The filter criteria syntax is incorrect. Please try again.");
        		//dropdowns
        	    if($this->storeType[$field] === 'dropdown') {
					$fieldCrit .= "$fieldExpr = :f" . $i;
                    $params[':f'.$i] = $intValue;
                    $fieldType[':f'.$i] = $this->fieldType[$field];
				}
				//is null
				elseif (strtolower(substr($stringValue, 0, 4)) === "null") {
                    $fieldCrit .= "$fieldExpr IS NULL";                
                }
                //is not null
                elseif (strtolower(substr($stringValue, 0, 6)) === "<>null") {
                    $fieldCrit .= "$fieldExpr IS NOT NULL";                
                }
                //is empty
                elseif (strtolower(substr($stringValue, 0, 5)) === "empty") {
                    $fieldCrit .= "$fieldExpr = ''";                
                }
                //is not empty
                elseif (strtolower(substr($stringValue, 0, 7)) === "<>empty") {
                    $fieldCrit .= "$fieldExpr <> ''";                    
                }
                //equal to
                elseif (substr($stringValue, 0, 1) === "=") {
                    $fieldCrit .= "$fieldExpr = :f" . $i;
                    $params[':f'.$i] = $intValue;
                    $fieldType[':f'.$i] = $this->fieldType[$field];
                }
                //not equal to
                elseif (substr($stringValue, 0, 2) === "<>") {
                    $fieldCrit .= "$fieldExpr != :f" . $i;
                    $params[':f'.$i] = $intValue;
                    $fieldType[':f'.$i] = $this->fieldType[$field];
                }
                //greater and equal
                elseif (substr($stringValue, 0, 2) === ">=") {
                    $fieldCrit .= "$fieldExpr >= :f" . $i;
                    $params[':f'.$i] = $intValue;
                    $fieldType[':f'.$i] = $this->fieldType[$field];
                }
                //greater than
                elseif (substr($stringValue, 0, 1) === ">") {
                    $fieldCrit .= "$fieldExpr > :f" . $i;
                    $params[':f'.$i] = $intValue;
                    $fieldType[':f'.$i] = $this->fieldType[$field];
                }
                //smaller and equal
                elseif (substr($stringValue, 0, 2) === "<=") {
                    $fieldCrit .= "$fieldExpr <= :f" . $i;
                    $params[':f'.$i] = $intValue;
                }
                //smaller than
                elseif (substr($stringValue, 0, 1) === "<") {
                    $fieldCrit .= "$fieldExpr < :f" . $i;
                    $params[':f'.$i] = $intValue;
                    $fieldType[':f'.$i] = $this->fieldType[$field];
                }
                //like
                elseif (strtolower(substr(ltrim($stringValue), 0, 5)) === "like ") {
                    $fieldCrit .= "$fieldExpr LIKE :f" . $i;
                    $params[':f'.$i] = str_replace('*', '%', substr(ltrim($stringValue), 5)); //note, this allows user to put * or _ inside $stringValue... which is okay...
                    $fieldType[':f'.$i] = $this->fieldType[$field];                   
                }
                //anything else - use LIKE
                else {
                    $fieldCrit .= "$fieldExpr LIKE :f" . $i;
                    $params[':f'.$i] = str_replace('*', '%', $stringValue).'%'; //note, this allows user to put % or _ inside $stringValue... which is okay...
                    $fieldType[':f'.$i] = $this->fieldType[$field];
                }
                $i++;
            }              
            if ($fieldCrit !== '')
                $allCrit .= '(' . $fieldCrit . ') AND ';
        }
        // Remove last ' AND '
        return substr($allCrit, 0, -4);
    }
    /**
     * Fetch records, returning only one page at a time
     * @param int $page page number to return
     * @param int $page_size count of records on each page
     * @param array $order sorting order, eg array(array('property'=>$field, 'direction'=>'ASC'), array('property'=>$field2, 'direction'=>'DESC'));
     * @param string $readType 'gridlist' = use table field names, 'exportlist' = use captions from pef_item fields
     * @param array $gridFilter grid header filter values, eg array(array('property'=>$field, 'value'=>$value), array('property'=>$field2, 'value'=>$value2));
     * @param string $activeFilter name of the filter to apply
     * @param array $filterParams activeFilter parameter values, eg array('notes'=>'%aaa%', 'id'=>5). If $order === '*readByPk*' then primary key values eg array('id'=>5)
     * @param string $phpFilter extra criteria to add (eg 'item_id = :id'). $phpFilterParams handles parameter values
     * @param array phpFilterParams associative array with parameter values for $phpFilter
     * @param array $group TODO grouping functionality
     * @param string $node fetch tree child nodes of $node
     * @param string $action ExtJs - inline grid adding
     * @param array $actionData ExtJs - inline grid adding
     * @param string $countMode TODO - all=do total count and filtered count on all pages, first=do counts on first page only     
     * @return array array($attributes, $filteredCount, $totalCount, $summaryData)   OR   array('error', $errMsg) on failure
     */
    public function read ($page=1, $page_size=40, $order=array(), $readType='gridlist', $gridFilter=array(), $activeFilter=null, $filterParams=array(), $phpFilter=null, $phpFilterParams=array(), $group=null, $node=null, $action=null, $actionData=null, $countMode='all') {
        if(!$page || !$page_size || $page<0 || $page_size<0)
            return array('error','Invalid request. Please contact the System Administrator');
        try {
            if ($order === '*readByPk*') {
                //Request for record by primary key values
                $params = array();
                if (!array_key_exists("id", $filterParams))
                    return array ('error',"The primary key fields specified in the request are invalid.");
                 if ($filterParams["id"] != (string)(float)$filterParams["id"])
                    return array ('error',"Error! The primary key field values specified in the request are invalid.");
                $params[":pk1"] = $filterParams["id"];
                $fieldType[":pk1"] = PDO::PARAM_INT;
                $criteria = "`test`.`id` = :pk1 ";
                $sql = "SELECT `test`.`id`,`test`.`varchar10_required`,`test`.`has_default`,`test`.`time_stamp`,`test`.`unique_fld`,`test`.`nvarchar80`,`test`.`text_fld`,`test`.`tinytext_fld`,`test`.`mediumtext_fld`,`test`.`longtext_fld`,`test`.`bit_fld`,`test`.`tinyint_fld`,`test`.`smallint_fld`,`test`.`int_fld`,`test`.`bigint_fld`,`test`.`float_fld`,`test`.`double_fld`,`test`.`decimal_fld`,`test`.`date_fld`,`test`.`time_fld`,`test`.`datetime_fld`,`test`.`year_fld`,`test`.`enum_fld`,`test`.`set_fld`,`test`.`email`,`test`.`url`,`test`.`longitude`,`test`.`lattitude`,`test`.`file_fld`,`test`.`image_fld`,`test`.`document_fld`,`test`.`expression_fld`,`test`.`notes`,`test`.`test_company_id`,`test`.`test_company2_id`,`test`.`dibuid` 
                     , ^^CONCAT(`test_company1001`.`name`, ' (' , CAST(`test_company1001`.`id` AS CHAR), ')')^^ AS `test_company_id_display_value`, ^^CONCAT(`test_company1002`.`name`, ' (' , CAST(`test_company1002`.`id` AS CHAR), ')')^^ AS `test_company2_id_display_value`
                         FROM `test`                  
                     LEFT JOIN `test_company` `test_company1001` ON `test`.`test_company_id` = `test_company1001`.`id` 
                     LEFT JOIN `test_company` `test_company1002` ON `test`.`test_company2_id` = `test_company1002`.`id` 
                         WHERE $criteria";
                dibMySqlPdo::setParamsType($fieldType, DIB::$CONTAINERDATA[2]);
                $attributes = dibMySqlPdo::execute($sql, DIB::$CONTAINERDATA[2], $params, true); // not making it false since sometimes joins of pef_sql dropins return multiple records...
                if($attributes === FALSE || count($attributes) < 1) 
                    return array('error',"Error! Could not find the requested record. Please refresh the record to determine if another user perhaps deleted it, otherwise contact the System Administrator.");
                $filteredCount = 1;
                $totalCount = 1;
            } else { // ---------------------------------- Fetch many records based on filter ---------------------------------
                // Permission filter
                $fieldType = array();
                $criteria = "";
				$params = array();
                // Get total count of records user has permissions to                
                if($page === 1 || $countMode==='all'){
                	$sql = "SELECT Count(*) AS totalcount FROM `test`  ";
	                $totalCountRst = dibMySqlPdo::execute($sql, DIB::$CONTAINERDATA[2], $params, true);
	                $totalCount = $totalCountRst["totalcount"];
	                $totalCountRst = null;
                } else
                	$totalCount = null;
                // php generated / developer filter
                if($phpFilter) { 
                    if (!empty($phpFilterParams) && !is_array($phpFilterParams)) {
						Log::err("The \$phpFilterParams parameter is not an array.");
						return array('error', "The data cannot be retrieved. Please contact the System Administrator.");
					} else                    	
                    	$params = $phpFilterParams;
                    $criteria .= " AND ($phpFilter) ";
                }                
                // Related Records Filter ***TODO see note in CrudController... this must be cleaned up!
                if (isset($filterParams[0])) {
                    $crit = $this->parseFilterArray($filterParams, $params, $fieldType); // $params passed by reference to be populated
                    if(is_array($crit))
                        return $crit; #error occured; $criteria contains error message.
                    else
                        $criteria .= " AND ($crit) ";
                } 
                // user grid filters
                $userFilter = '';
                if ($gridFilter) { 
                    $userFilter = $this->parseGridFilter($gridFilter, $params, $fieldType, $usedSqlField); // $params passed by reference to be populated                
                    if(is_array($userFilter))
                        return $userFilter; //error occured; $crit contains error message.
                } else
                	$usedSqlField = false;
                $filteredCount = $totalCount;
                if($userFilter) $criteria .= " AND ($userFilter) ";
                if ($criteria !== '') {
                    $criteria = ' WHERE ' . substr($criteria, 4);
                    if($usedSqlField) {
						$join = "                 
                     LEFT JOIN `test_company` `test_company1001` ON `test`.`test_company_id` = `test_company1001`.`id` 
                     LEFT JOIN `test_company` `test_company1002` ON `test`.`test_company2_id` = `test_company1002`.`id` 
                 ";
                 	} else 
                 		$join = '';
                    if($page === 1 || $countMode==='all'){
	                    $sql = "SELECT Count(*) AS filteredCount FROM `test` $join  $criteria";
	                    dibMySqlPdo::setParamsType($fieldType, DIB::$CONTAINERDATA[2]);                          
	                    $itemCountRst = dibMySqlPdo::execute($sql, DIB::$CONTAINERDATA[2], $params, true);
	                    $filteredCount = $itemCountRst['filteredCount']; //NOTE postgres returns lowercase fieldnames irrespective of how you specified them!
	                    $itemCountRst = null;
					}
                }
                $orderStr = '';
                if($order) {
                    $orderStr = " ORDER BY ";
                    foreach($order as $key => $record) {
                        if(!isset($record['property']) || !array_key_exists($record['property'], $this->fieldType)) 
                            return array('error', "An invalid fieldname was used in order criteria. Please contact the System Administrator.");
                        if(isset($record['direction'])) {
                            if (!in_array($record['direction'], array('ASC', 'DESC', '')))
                                return array('error', "An invalid sort direction was used in order criteria. Please contact the System Administrator.");
                            else
                                $direction = $record['direction'];
                        } else
                            $direction = '';
                        if(array_key_exists($record['property'], $this->sqlFields))
                        	$orderStr .= $this->sqlFields[$record['property']] . ' ' . $direction . ', ';
                        elseif(array_key_exists($record['property'], $this->fkeyDisplay))
                        	$orderStr .= $this->fkeyDisplay[$record['property']] . ' ' . $direction . ', ';
                        else 
                        	$orderStr .= '`test`.`' . $record['property'] . '` ' . $direction . ', ';
                    }
                    $orderStr = substr($orderStr, 0, strlen($orderStr) - 2);
                }                
                // Fetch records - handle only specific columns that may be viewed by this permgroup
                // Set SQL statement
                    // Template: MySql - Get SQL for paging purposes for database engines that support the LIMIT keyword. Used in eg CrudPdoTemplate.php.
    if($page === 1)
        $limit = ' LIMIT ' . $page_size;
    else
        $limit = ' LIMIT ' . ($page_size * ($page - 1)) .  ', ' . $page_size;    
                // Template: main SQL statement for MySQL to fetch many records limited by paging. Used in eg CrudPdoTemplate.php.
if($readType === 'exportlist')
    $sql = "SELECT 
                `test`.`id` AS `Id`, `test`.`varchar10_required` AS `Varchar10 Required`, `test`.`has_default` AS `Has Default`, `test`.`time_stamp` AS `Time Stamp`, `test`.`unique_fld` AS `Unique Fld`, `test`.`nvarchar80` AS `Nvarchar80`, `test`.`text_fld` AS `Text Fld`, `test`.`tinytext_fld` AS `Tinytext Fld`, `test`.`mediumtext_fld` AS `Mediumtext Fld`, `test`.`longtext_fld` AS `Longtext Fld`, `test`.`bit_fld` AS `Bit Fld`, `test`.`tinyint_fld` AS `Tinyint Fld`, `test`.`smallint_fld` AS `Smallint Fld`, `test`.`int_fld` AS `Int Fld`, `test`.`bigint_fld` AS `Bigint Fld`, `test`.`float_fld` AS `Float Fld`, `test`.`double_fld` AS `Double Fld`, `test`.`decimal_fld` AS `Decimal Fld`, `test`.`date_fld` AS `Date Fld`, `test`.`time_fld` AS `Time Fld`, `test`.`datetime_fld` AS `Datetime Fld`, `test`.`year_fld` AS `Year Fld`, `test`.`enum_fld` AS `Enum Fld`, `test`.`set_fld` AS `Set Fld`, `test`.`email` AS `Email`, `test`.`url` AS `Url`, `test`.`longitude` AS `Longitude`, `test`.`lattitude` AS `Lattitude`, `test`.`file_fld` AS `File Fld`, `test`.`image_fld` AS `Image Fld`, `test`.`document_fld` AS `Document Fld`, `test`.`expression_fld` AS `Expression Fld`, `test`.`notes` AS `Notes`, `test`.`dibuid` AS `Dibuid` 
                     , ^^CONCAT(`test_company1001`.`name`, ' (' , CAST(`test_company1001`.`id` AS CHAR), ')')^^ AS `Test Company`, ^^CONCAT(`test_company1002`.`name`, ' (' , CAST(`test_company1002`.`id` AS CHAR), ')')^^ AS `Test Company2`
            FROM `test` 
                     LEFT JOIN `test_company` `test_company1001` ON `test`.`test_company_id` = `test_company1001`.`id` 
                     LEFT JOIN `test_company` `test_company1002` ON `test`.`test_company2_id` = `test_company1002`.`id` 
                 ";
else
    $sql = "SELECT `test`.`id`,`test`.`varchar10_required`,`test`.`has_default`,`test`.`time_stamp`,`test`.`unique_fld`,`test`.`nvarchar80`,`test`.`text_fld`,`test`.`tinytext_fld`,`test`.`mediumtext_fld`,`test`.`longtext_fld`,`test`.`bit_fld`,`test`.`tinyint_fld`,`test`.`smallint_fld`,`test`.`int_fld`,`test`.`bigint_fld`,`test`.`float_fld`,`test`.`double_fld`,`test`.`decimal_fld`,`test`.`date_fld`,`test`.`time_fld`,`test`.`datetime_fld`,`test`.`year_fld`,`test`.`enum_fld`,`test`.`set_fld`,`test`.`email`,`test`.`url`,`test`.`longitude`,`test`.`lattitude`,`test`.`file_fld`,`test`.`image_fld`,`test`.`document_fld`,`test`.`expression_fld`,`test`.`notes`,`test`.`test_company_id`,`test`.`test_company2_id`,`test`.`dibuid` 
                     , ^^CONCAT(`test_company1001`.`name`, ' (' , CAST(`test_company1001`.`id` AS CHAR), ')')^^ AS `test_company_id_display_value`, ^^CONCAT(`test_company1002`.`name`, ' (' , CAST(`test_company1002`.`id` AS CHAR), ')')^^ AS `test_company2_id_display_value`
            FROM `test` 
                     LEFT JOIN `test_company` `test_company1001` ON `test`.`test_company_id` = `test_company1001`.`id` 
                     LEFT JOIN `test_company` `test_company1002` ON `test`.`test_company2_id` = `test_company1002`.`id` 
                 ";   
$sql .= $criteria . $orderStr . $limit;               
                dibMySqlPdo::setParamsType($fieldType, DIB::$CONTAINERDATA[2]);
                $attributes = dibMySqlPdo::execute($sql, DIB::$CONTAINERDATA[2], $params, false);
                if($attributes === FALSE) {
                	if($gridFilter){
                		foreach($params as $key=>$p)	
                			$criteria = str_replace($key, "'$p'", $criteria);
                		return array('error',"Error! Could not read table information. Verify the filter applied or else please contact the System Administrator.\r\n$criteria");
                    } else
                    	return array('error',"Error! Could not read table information. Please contact the System Administrator.");
                }
            // Get values where dropdowns are based on queries based on other db's...
            }
            if ($action === 'add') {
                // Inline adding in the grid - add a blank row
                // NOTE!: If the keys are not a continuous numeric sequence starting from 0, all keys are encoded as strings, and specified explicitly for each key-value pair.
                $blankRecord = array("id"=>null, "varchar10_required"=>null, "unique_fld"=>null, "nvarchar80"=>null, "text_fld"=>null, "tinytext_fld"=>null, "mediumtext_fld"=>null, "longtext_fld"=>null, "bit_fld"=>null, "tinyint_fld"=>null, "smallint_fld"=>null, "int_fld"=>null, "bigint_fld"=>null, "float_fld"=>null, "double_fld"=>null, "decimal_fld"=>null, "date_fld"=>null, "time_fld"=>null, "datetime_fld"=>null, "year_fld"=>null, "enum_fld"=>null, "set_fld"=>null, "email"=>null, "url"=>null, "longitude"=>null, "lattitude"=>null, "file_fld"=>null, "image_fld"=>null, "document_fld"=>null, "expression_fld"=>null, "notes"=>null, "test_company_id"=>null, "test_company2_id"=>null, "dibuid"=>null);
                $blankRecord = $this->getDefaults($blankRecord, $filterParams);
                if(isset($blankRecord[0]) && $blankRecord[0]==='error')
                	return array('error', $blankRecord[1]);
                // Find offset in $attributes where pkey values from $actionData are in $attributes:
                $actionData = json_decode(urldecode($actionData), true);
                $found = FALSE;
                $k=0;
                if($actionData) {
                	if(!array_key_exists('id', $actionData)) {
						Log::err('To use inline adding, the primary key must be included in submitted fields.');
	                	return array('error','Configuration error. Please contact the System Administrator.');
	                }
                    foreach($attributes as $k => $r) {
                        if($r['id'] === $actionData['id']) {
                            $found = TRUE;
                            break;
                        }
                    }
                }
                if ($found === TRUE) // Insert the $blankRecord array at this offset
                    array_splice($attributes, $k+1, 0, $blankRecord);
                else // Insert the $blankRecord array at position 0
                    array_unshift($attributes, $blankRecord);
                $filteredCount++;
            } elseif ($action === 'addreuse') {
                // Inline adding in the grid - add a copy of the previous row
                // NOTE!: If the keys are not a continuous numeric sequence starting from 0, all keys are encoded as strings, and specified explicitly for each key-value pair.
                // Find offset where pkey values from $actionData are in $attributes:
                $actionData = json_decode(urldecode($actionData), true);
                $found = FALSE;
                if($actionData) {
                	if(!array_key_exists('id', $actionData)) {
						Log::err('To use inline adding, the primary key must be included in submitted fields.');
	                	return array('error','Configuration error. Please contact the System Administrator.');
	                }
                    foreach($attributes as $k => $r) {
                        if($r['id'] === $actionData['id']) {
                            $found = TRUE;
                            break;
                        }
                    }
                    if ($found === TRUE) {
                        // Set primary key values to NULL
                        $r['id'] = NULL;
                        // Insert the $blankRecord array at this offset
                        array_splice($attributes, $k+1, 0, array(0=>$r));
                        $filteredCount++;
                    }
                }                
            }
            return array($attributes, $filteredCount, $totalCount, array());
        } catch (Exception $e) {
            return array('error',"Error! Could not read table information. Please contact the System Administrator");
        }
    }    
     /**
     * Inserts a record
     * 
     * @param array $attributes
     * @param boolean $makeUniqueValues - if True, then values will be made unique (if not) using SyncFunctions::cleanName function.
     * @param int $targetDatabaseId - if specified, record is created in the target database (must have the same table structure).
     * @return type
     */
    public function create($attributes, $makeUniqueValues=FALSE, $targetDatabaseId=NULL) {        
        // User can only provide values for fields they have rights to AND where ci.exclude_crud=0. 
    	// The rest must get default values - prevent user from updating them, even if provided...
    	// So if (exclude_crud=1 OR user lacks permissions (and is not relatedrecordsitem)) AND there is a default, then set the default
    	//    else if no default AND field is required, then give an error msg 
    	//    else if field is not required, unset it.
        if(!$targetDatabaseId) $targetDatabaseId = DIB::$CONTAINERDATA[2];
        try { 
            // Add defaults where permissions or exclude_crud restricts user to provide values 
             if(isset($attributes["id"])) unset($attributes["id"]);
             if(isset($attributes["dibuid"])) unset($attributes["dibuid"]);
             unset($attributes["dibuid"]);      
            // Check Validation
	        // varchar10_required (plain text)
            if(isset($attributes['varchar10_required'])) {            	
	            if(trim((string)($attributes['varchar10_required'])) !== '') {
	            	if(strlen($attributes["varchar10_required"]) > 10)
		                return array ('error',"The 'Varchar10 Required (varchar10_required)' field cannot contain more than 10 characters");
	            } 
	        }  else	            
                return array ('error',"The 'Varchar10 Required (varchar10_required)' field is required. Please provide a value.");
	        // has_default (plain text)
            if(isset($attributes['has_default'])) {            	
	            if(trim((string)($attributes['has_default'])) !== '') {
	            	if(strlen($attributes["has_default"]) > 80)
		                return array ('error',"The 'Has Default (has_default)' field cannot contain more than 80 characters");
	            } 
	        }  else	            
                return array ('error',"The 'Has Default (has_default)' field is required. Please provide a value.");
	        // time_stamp (datetime)
            if(isset($attributes['time_stamp'])) {            	
	            if(trim((string)($attributes['time_stamp'])) !== '') {
	        		if(!strtotime($attributes["time_stamp"]))
		                return array ('error',"The 'Time Stamp (time_stamp)' field must be a valid date-time.");
		            else
		                $attributes["time_stamp"] = date('Y-m-d H:i:s', strtotime($attributes["time_stamp"]));
	            } 
	        }  else	            
                return array ('error',"The 'Time Stamp (time_stamp)' field is required. Please provide a value.");
	        // unique_fld (plain text)
            if(isset($attributes['unique_fld'])) {            	
	            if(trim((string)($attributes['unique_fld'])) !== '') {
	            	if(strlen($attributes["unique_fld"]) > 50)
		                return array ('error',"The 'Unique Fld (unique_fld)' field cannot contain more than 50 characters");
	            } 
	        }             
	        // nvarchar80 (plain text)
            if(isset($attributes['nvarchar80'])) {            	
	            if(trim((string)($attributes['nvarchar80'])) !== '') {
	            	if(strlen($attributes["nvarchar80"]) > 80)
		                return array ('error',"The 'Nvarchar80 (nvarchar80)' field cannot contain more than 80 characters");
	            } 
	        }             
	        // text_fld (plain text)
            if(isset($attributes['text_fld'])) {            	
	            if(trim((string)($attributes['text_fld'])) !== '') {
	            } 
	        }             
	        // tinytext_fld (plain text)
            if(isset($attributes['tinytext_fld'])) {            	
	            if(trim((string)($attributes['tinytext_fld'])) !== '') {
	            } 
	        }             
	        // mediumtext_fld (plain text)
            if(isset($attributes['mediumtext_fld'])) {            	
	            if(trim((string)($attributes['mediumtext_fld'])) !== '') {
	            } 
	        }             
	        // longtext_fld (plain text)
            if(isset($attributes['longtext_fld'])) {            	
	            if(trim((string)($attributes['longtext_fld'])) !== '') {
	            } 
	        }             
	        // bit_fld (plain text)
            if(isset($attributes['bit_fld'])) {            	
	            if(trim((string)($attributes['bit_fld'])) !== '') {
	            	if(strlen($attributes["bit_fld"]) > 2)
		                return array ('error',"The 'Bit Fld (bit_fld)' field cannot contain more than 2 characters");
	            } 
	        }             
	        // tinyint_fld (boolean)
        	$attributes['tinyint_fld'] = (!isset($attributes['tinyint_fld']) || !in_array($attributes['tinyint_fld'], array(1, True, '1', 'True'), TRUE)) ? 0 : 1;            	
	        // smallint_fld (integer)
            if(isset($attributes['smallint_fld'])) {            	
	            if(trim((string)($attributes['smallint_fld'])) !== '') {
		            if(!is_int((int)$attributes["smallint_fld"]) || !ctype_digit((string)abs($attributes["smallint_fld"])))
		                return array ('error',"The 'Smallint Fld (smallint_fld)' field must be an integer value.");
	            } 
	        }             
	        // int_fld (integer)
            if(isset($attributes['int_fld'])) {            	
	            if(trim((string)($attributes['int_fld'])) !== '') {
		            if(!is_int((int)$attributes["int_fld"]) || !ctype_digit((string)abs($attributes["int_fld"])))
		                return array ('error',"The 'Int Fld (int_fld)' field must be an integer value.");
	            } 
	        }             
	        // bigint_fld (integer)
            if(isset($attributes['bigint_fld'])) {            	
	            if(trim((string)($attributes['bigint_fld'])) !== '') {
		            if(!is_int((int)$attributes["bigint_fld"]) || !ctype_digit((string)abs($attributes["bigint_fld"])))
		                return array ('error',"The 'Bigint Fld (bigint_fld)' field must be an integer value.");
	            } 
	        }             
	        // float_fld (decimal)
            if(isset($attributes['float_fld'])) {            	
	            if(trim((string)($attributes['float_fld'])) !== '') {
					if(!is_numeric($attributes["float_fld"]))
						return array ('error',"The 'Float Fld (float_fld)' field must be a valid integer or decimal value.");
			    	$d = (string)(double)$attributes["float_fld"];
			    	if(is_infinite($d) || is_nan($d))
	                	return array ('error',"The 'Float Fld (float_fld)' field must be a valid integer or decimal value.");
	            } 
	        }             
	        // double_fld (decimal)
            if(isset($attributes['double_fld'])) {            	
	            if(trim((string)($attributes['double_fld'])) !== '') {
					if(!is_numeric($attributes["double_fld"]))
						return array ('error',"The 'Double Fld (double_fld)' field must be a valid integer or decimal value.");
			    	$d = (string)(double)$attributes["double_fld"];
			    	if(is_infinite($d) || is_nan($d))
	                	return array ('error',"The 'Double Fld (double_fld)' field must be a valid integer or decimal value.");
	            } 
	        }             
	        // decimal_fld (decimal)
            if(isset($attributes['decimal_fld'])) {            	
	            if(trim((string)($attributes['decimal_fld'])) !== '') {
					if(!is_numeric($attributes["decimal_fld"]))
						return array ('error',"The 'Decimal Fld (decimal_fld)' field must be a valid integer or decimal value.");
			    	$d = (string)(double)$attributes["decimal_fld"];
			    	if(is_infinite($d) || is_nan($d))
	                	return array ('error',"The 'Decimal Fld (decimal_fld)' field must be a valid integer or decimal value.");
	            } 
	        }             
	        // date_fld (date)
            if(isset($attributes['date_fld'])) {            	
	            if(trim((string)($attributes['date_fld'])) !== '') {
	        		if(!strtotime($attributes["date_fld"]))
		                return array ('error',"The 'Date Fld (date_fld)' field must be a valid date.");
		            else
		                $attributes["date_fld"] = date('Y-m-d', strtotime($attributes["date_fld"]));
	            } 
	        }             
	        // time_fld (plain text)
            if(isset($attributes['time_fld'])) {            	
	            if(trim((string)($attributes['time_fld'])) !== '') {
	            	if(strlen($attributes["time_fld"]) > 7)
		                return array ('error',"The 'Time Fld (time_fld)' field cannot contain more than 7 characters");
	            } 
	        }             
	        // datetime_fld (datetime)
            if(isset($attributes['datetime_fld'])) {            	
	            if(trim((string)($attributes['datetime_fld'])) !== '') {
	        		if(!strtotime($attributes["datetime_fld"]))
		                return array ('error',"The 'Datetime Fld (datetime_fld)' field must be a valid date-time.");
		            else
		                $attributes["datetime_fld"] = date('Y-m-d H:i:s', strtotime($attributes["datetime_fld"]));
	            } 
	        }             
	        // year_fld (integer)
            if(isset($attributes['year_fld'])) {            	
	            if(trim((string)($attributes['year_fld'])) !== '') {
		            if(!is_int((int)$attributes["year_fld"]) || !ctype_digit((string)abs($attributes["year_fld"])))
		                return array ('error',"The 'Year Fld (year_fld)' field must be an integer value.");
	            } 
	        }             
	        // enum_fld (plain text)
            if(isset($attributes['enum_fld'])) {            	
	            if(trim((string)($attributes['enum_fld'])) !== '') {
	            	if(strlen($attributes["enum_fld"]) > 255)
		                return array ('error',"The 'Enum Fld (enum_fld)' field cannot contain more than 255 characters");
	            } 
	        }             
	        // set_fld (plain text)
            if(isset($attributes['set_fld'])) {            	
	            if(trim((string)($attributes['set_fld'])) !== '') {
	            	if(strlen($attributes["set_fld"]) > 255)
		                return array ('error',"The 'Set Fld (set_fld)' field cannot contain more than 255 characters");
	            } 
	        }             
	        // email (plain text)
            if(isset($attributes['email'])) {            	
	            if(trim((string)($attributes['email'])) !== '') {
	            	if(strlen($attributes["email"]) > 120)
		                return array ('error',"The 'Email (email)' field cannot contain more than 120 characters");
	            } 
	        }             
	        // url (plain text)
            if(isset($attributes['url'])) {            	
	            if(trim((string)($attributes['url'])) !== '') {
	            	if(strlen($attributes["url"]) > 200)
		                return array ('error',"The 'Url (url)' field cannot contain more than 200 characters");
	            } 
	        }             
	        // longitude (plain text)
            if(isset($attributes['longitude'])) {            	
	            if(trim((string)($attributes['longitude'])) !== '') {
	            	if(strlen($attributes["longitude"]) > 200)
		                return array ('error',"The 'Longitude (longitude)' field cannot contain more than 200 characters");
	            } 
	        }             
	        // lattitude (plain text)
            if(isset($attributes['lattitude'])) {            	
	            if(trim((string)($attributes['lattitude'])) !== '') {
	            	if(strlen($attributes["lattitude"]) > 200)
		                return array ('error',"The 'Lattitude (lattitude)' field cannot contain more than 200 characters");
	            } 
	        }             
	        // file_fld (plain text)
            if(isset($attributes['file_fld'])) {            	
	            if(trim((string)($attributes['file_fld'])) !== '') {
	            	if(strlen($attributes["file_fld"]) > 200)
		                return array ('error',"The 'File Fld (file_fld)' field cannot contain more than 200 characters");
	            } 
	        }             
	        // image_fld (plain text)
            if(isset($attributes['image_fld'])) {            	
	            if(trim((string)($attributes['image_fld'])) !== '') {
	            	if(strlen($attributes["image_fld"]) > 200)
		                return array ('error',"The 'Image Fld (image_fld)' field cannot contain more than 200 characters");
	            } 
	        }             
	        // document_fld (plain text)
            if(isset($attributes['document_fld'])) {            	
	            if(trim((string)($attributes['document_fld'])) !== '') {
	            	if(strlen($attributes["document_fld"]) > 200)
		                return array ('error',"The 'Document Fld (document_fld)' field cannot contain more than 200 characters");
	            } 
	        }             
	        // expression_fld (plain text)
            if(isset($attributes['expression_fld'])) {            	
	            if(trim((string)($attributes['expression_fld'])) !== '') {
	            	if(strlen($attributes["expression_fld"]) > 200)
		                return array ('error',"The 'Expression Fld (expression_fld)' field cannot contain more than 200 characters");
	            } 
	        }             
	        // notes (plain text)
            if(isset($attributes['notes'])) {            	
	            if(trim((string)($attributes['notes'])) !== '') {
	            	if(strlen($attributes["notes"]) > 255)
		                return array ('error',"The 'Notes (notes)' field cannot contain more than 255 characters");
	            } 
	        }             
	        // test_company_id (integer)
            if(isset($attributes['test_company_id'])) {            	
	            if(trim((string)($attributes['test_company_id'])) !== '') {
		            if(!is_int((int)$attributes["test_company_id"]) || !ctype_digit((string)abs($attributes["test_company_id"])))
		                return array ('error',"The 'Test Company (test_company_id)' field must be an integer value.");
	            } 
	        }             
	        // test_company2_id (integer)
            if(isset($attributes['test_company2_id'])) {            	
	            if(trim((string)($attributes['test_company2_id'])) !== '') {
		            if(!is_int((int)$attributes["test_company2_id"]) || !ctype_digit((string)abs($attributes["test_company2_id"])))
		                return array ('error',"The 'Test Company2 (test_company2_id)' field must be an integer value.");
	            } 
	        }             
	        // dibuid (plain text)
            if(isset($attributes['dibuid'])) {            	
	            if(trim((string)($attributes['dibuid'])) !== '') {
	            	if(strlen($attributes["dibuid"]) > 30)
		                return array ('error',"The 'Dibuid (dibuid)' field cannot contain more than 30 characters");
	            } 
	        }             
            //Check Unique Values for unique_fld
            if(!array_key_exists('unique_fld', $attributes)) $attributes['unique_fld'] = null;
            $criteria ="`unique_fld` = :fk1 ";
            $sql = "SELECT `test`.`id` AS pkv FROM `test` WHERE $criteria";
            $paramsU = array(":fk1" => $attributes["unique_fld"]);
            $rst = dibMySqlPdo::execute($sql, $targetDatabaseId, $paramsU, true);
            if ($rst === FALSE) {
				Log::err("Unique value validation failed. Ensure that values for all fields that are involved in checking unique index of pef_table_option.id 23300 are submitted to the server (ie they exist as fields in container id 8580)");
                return array('error',"Could not perform unique value validation. Please contact the System Administrator.");
            }
            if(dibMySqlPdo::count() > 0) {
                if($makeUniqueValues)
                    // Force unique values - for combinations, only enforce on first 
                    $attributes['unique_fld'] = SyncFunctions::cleanName($attributes['unique_fld'],'test', '');
                elseif(count($paramsU) > 1)
                    return array('error',"Add record cancelled. The combination of values in 'unique_fld' needs to be unique. The record identified by '" . $rst['pkv'] . "' already contains these values.");
                else
                    return array('error',"Add record cancelled. The value in 'unique_fld' needs to be unique. The record identified by '" . $rst['pkv'] . "' already contains this value.");
            }
            // All clear - perform the insert...
            $sql = "INSERT INTO `test` (";
            $fieldList = '';
            $valueList = '';
            $fieldType = array();
            $i=0;
            foreach ($attributes AS $key => $value) {
                if(array_key_exists($key, $this->storeType) && $this->storeType[$key] !== 'dibsqli') {
                    $fieldList .= "`$key`, ";
                    $valueList .= ":f$i, ";
                    $params[':f'.$i] = $value;
                    $fieldType[':f'.$i] = $this->fieldType[$key];
                    $i++;
                }
            }            
            $fieldList = substr($fieldList, 0, strlen($fieldList) - 2);
            $valueList = substr($valueList, 0, strlen($valueList) - 2);
            $sql .= $fieldList . ") VALUES (" . $valueList . ")";
            dibMySqlPdo::setParamsType($fieldType, $targetDatabaseId);       
            $value = dibMySqlPdo::execute($sql, $targetDatabaseId, $params);           
            if ($value === FALSE || dibMySqlPdo::count() === 0) {
                if($value === FALSE && Database::lastErrorUserMsg())
                    return array('error', Database::lastErrorUserMsg());
                else
                    return array('error',"Error! The system could not create the record. Please contact the System Administrator.");
            }
            // Return array of pkvalues
            $pkValues["id"] = $value;
            // Add pkvalues to $attributes 
            $attributes = array_merge($attributes, $pkValues);             
            $crit = TRUE;
            if ($crit===TRUE) {
                // Insert audit trail record - first set unique_record
                $this->unique_record = 1;
                // Get pk values
                $recordId='';
                $recordId = $value;
                foreach ($attributes AS $fieldName => $newValue)
                    $this->auditInsert("create", $fieldName, null, $newValue, 1437, $recordId);
            } elseif(is_array($crit)) return $crit;
            return $pkValues; // contains pk value of new record
        } catch (Exception $e) {            
            return array ('error', "Error! The create request is not valid. Please contact the System Administator.");            
        }
    }
     /**
     * Update a record
     * 
     * @param $pkValues
     * @param $attributes
     * @return array $pkValues  OR  array('error', $errMsg) on failure
     */
    public function update($pkValues, $attributes) {
        // Updates must occur only for fields that users have rights to AND where ci.exclude_crud=0. The rest must retain old values - prevent user from updating them...
        try {    
            // Check Validation - note the PeffApp::jsonDecode() function in the CrudController already ensures $attributes contain no arrays
            // id (integer)	        
            if(isset($attributes['id'])) {            	
	            if(trim((string)($attributes['id'])) !== '') {
		            if(!is_int((int)$attributes["id"]) || !ctype_digit((string)abs($attributes["id"])))
		                return array ('error',"The 'Id (id)' field must be an integer value.");
	            } 
	        }             
            // varchar10_required (plain text)	        
            if(isset($attributes['varchar10_required'])) {            	
	            if(trim((string)($attributes['varchar10_required'])) !== '') {
	            	if(strlen($attributes["varchar10_required"]) > 10)
		                return array ('error',"The 'Varchar10 Required (varchar10_required)' field cannot contain more than 10 characters");
	            } 
	        }             
            // has_default (plain text)	        
            if(isset($attributes['has_default'])) {            	
	            if(trim((string)($attributes['has_default'])) !== '') {
	            	if(strlen($attributes["has_default"]) > 80)
		                return array ('error',"The 'Has Default (has_default)' field cannot contain more than 80 characters");
	            } 
	        }             
            // time_stamp (datetime)	        
            if(isset($attributes['time_stamp'])) {            	
	            if(trim((string)($attributes['time_stamp'])) !== '') {
	        		if(!strtotime($attributes["time_stamp"]))
		                return array ('error',"The 'Time Stamp (time_stamp)' field must be a valid date-time.");
		            else
		                $attributes["time_stamp"] = date('Y-m-d H:i:s', strtotime($attributes["time_stamp"]));
	            } 
	        }             
            // unique_fld (plain text)	        
            if(isset($attributes['unique_fld'])) {            	
	            if(trim((string)($attributes['unique_fld'])) !== '') {
	            	if(strlen($attributes["unique_fld"]) > 50)
		                return array ('error',"The 'Unique Fld (unique_fld)' field cannot contain more than 50 characters");
	            } 
	        }             
            // nvarchar80 (plain text)	        
            if(isset($attributes['nvarchar80'])) {            	
	            if(trim((string)($attributes['nvarchar80'])) !== '') {
	            	if(strlen($attributes["nvarchar80"]) > 80)
		                return array ('error',"The 'Nvarchar80 (nvarchar80)' field cannot contain more than 80 characters");
	            } 
	        }             
            // text_fld (plain text)	        
            if(isset($attributes['text_fld'])) {            	
	            if(trim((string)($attributes['text_fld'])) !== '') {
	            } 
	        }             
            // tinytext_fld (plain text)	        
            if(isset($attributes['tinytext_fld'])) {            	
	            if(trim((string)($attributes['tinytext_fld'])) !== '') {
	            } 
	        }             
            // mediumtext_fld (plain text)	        
            if(isset($attributes['mediumtext_fld'])) {            	
	            if(trim((string)($attributes['mediumtext_fld'])) !== '') {
	            } 
	        }             
            // longtext_fld (plain text)	        
            if(isset($attributes['longtext_fld'])) {            	
	            if(trim((string)($attributes['longtext_fld'])) !== '') {
	            } 
	        }             
            // bit_fld (plain text)	        
            if(isset($attributes['bit_fld'])) {            	
	            if(trim((string)($attributes['bit_fld'])) !== '') {
	            	if(strlen($attributes["bit_fld"]) > 2)
		                return array ('error',"The 'Bit Fld (bit_fld)' field cannot contain more than 2 characters");
	            } 
	        }             
            // tinyint_fld (boolean)	        
        	$attributes["tinyint_fld"] = (in_array($attributes["tinyint_fld"], array(1, True, '1', 'True'), TRUE)) ? 1 : 0;            	
            // smallint_fld (integer)	        
            if(isset($attributes['smallint_fld'])) {            	
	            if(trim((string)($attributes['smallint_fld'])) !== '') {
		            if(!is_int((int)$attributes["smallint_fld"]) || !ctype_digit((string)abs($attributes["smallint_fld"])))
		                return array ('error',"The 'Smallint Fld (smallint_fld)' field must be an integer value.");
	            } 
	        }             
            // int_fld (integer)	        
            if(isset($attributes['int_fld'])) {            	
	            if(trim((string)($attributes['int_fld'])) !== '') {
		            if(!is_int((int)$attributes["int_fld"]) || !ctype_digit((string)abs($attributes["int_fld"])))
		                return array ('error',"The 'Int Fld (int_fld)' field must be an integer value.");
	            } 
	        }             
            // bigint_fld (integer)	        
            if(isset($attributes['bigint_fld'])) {            	
	            if(trim((string)($attributes['bigint_fld'])) !== '') {
		            if(!is_int((int)$attributes["bigint_fld"]) || !ctype_digit((string)abs($attributes["bigint_fld"])))
		                return array ('error',"The 'Bigint Fld (bigint_fld)' field must be an integer value.");
	            } 
	        }             
            // float_fld (decimal)	        
            if(isset($attributes['float_fld'])) {            	
	            if(trim((string)($attributes['float_fld'])) !== '') {
					if(!is_numeric($attributes["float_fld"]))
						return array ('error',"The 'Float Fld (float_fld)' field must be a valid integer or decimal value.");
			    	$d = (string)(double)$attributes["float_fld"];
			    	if(is_infinite($d) || is_nan($d))
	                	return array ('error',"The 'Float Fld (float_fld)' field must be a valid integer or decimal value.");
	            } 
	        }             
            // double_fld (decimal)	        
            if(isset($attributes['double_fld'])) {            	
	            if(trim((string)($attributes['double_fld'])) !== '') {
					if(!is_numeric($attributes["double_fld"]))
						return array ('error',"The 'Double Fld (double_fld)' field must be a valid integer or decimal value.");
			    	$d = (string)(double)$attributes["double_fld"];
			    	if(is_infinite($d) || is_nan($d))
	                	return array ('error',"The 'Double Fld (double_fld)' field must be a valid integer or decimal value.");
	            } 
	        }             
            // decimal_fld (decimal)	        
            if(isset($attributes['decimal_fld'])) {            	
	            if(trim((string)($attributes['decimal_fld'])) !== '') {
					if(!is_numeric($attributes["decimal_fld"]))
						return array ('error',"The 'Decimal Fld (decimal_fld)' field must be a valid integer or decimal value.");
			    	$d = (string)(double)$attributes["decimal_fld"];
			    	if(is_infinite($d) || is_nan($d))
	                	return array ('error',"The 'Decimal Fld (decimal_fld)' field must be a valid integer or decimal value.");
	            } 
	        }             
            // date_fld (date)	        
            if(isset($attributes['date_fld'])) {            	
	            if(trim((string)($attributes['date_fld'])) !== '') {
	        		if(!strtotime($attributes["date_fld"]))
		                return array ('error',"The 'Date Fld (date_fld)' field must be a valid date.");
		            else
		                $attributes["date_fld"] = date('Y-m-d', strtotime($attributes["date_fld"]));
	            } 
	        }             
            // time_fld (plain text)	        
            if(isset($attributes['time_fld'])) {            	
	            if(trim((string)($attributes['time_fld'])) !== '') {
	            	if(strlen($attributes["time_fld"]) > 7)
		                return array ('error',"The 'Time Fld (time_fld)' field cannot contain more than 7 characters");
	            } 
	        }             
            // datetime_fld (datetime)	        
            if(isset($attributes['datetime_fld'])) {            	
	            if(trim((string)($attributes['datetime_fld'])) !== '') {
	        		if(!strtotime($attributes["datetime_fld"]))
		                return array ('error',"The 'Datetime Fld (datetime_fld)' field must be a valid date-time.");
		            else
		                $attributes["datetime_fld"] = date('Y-m-d H:i:s', strtotime($attributes["datetime_fld"]));
	            } 
	        }             
            // year_fld (integer)	        
            if(isset($attributes['year_fld'])) {            	
	            if(trim((string)($attributes['year_fld'])) !== '') {
		            if(!is_int((int)$attributes["year_fld"]) || !ctype_digit((string)abs($attributes["year_fld"])))
		                return array ('error',"The 'Year Fld (year_fld)' field must be an integer value.");
	            } 
	        }             
            // enum_fld (plain text)	        
            if(isset($attributes['enum_fld'])) {            	
	            if(trim((string)($attributes['enum_fld'])) !== '') {
	            	if(strlen($attributes["enum_fld"]) > 255)
		                return array ('error',"The 'Enum Fld (enum_fld)' field cannot contain more than 255 characters");
	            } 
	        }             
            // set_fld (plain text)	        
            if(isset($attributes['set_fld'])) {            	
	            if(trim((string)($attributes['set_fld'])) !== '') {
	            	if(strlen($attributes["set_fld"]) > 255)
		                return array ('error',"The 'Set Fld (set_fld)' field cannot contain more than 255 characters");
	            } 
	        }             
            // email (plain text)	        
            if(isset($attributes['email'])) {            	
	            if(trim((string)($attributes['email'])) !== '') {
	            	if(strlen($attributes["email"]) > 120)
		                return array ('error',"The 'Email (email)' field cannot contain more than 120 characters");
	            } 
	        }             
            // url (plain text)	        
            if(isset($attributes['url'])) {            	
	            if(trim((string)($attributes['url'])) !== '') {
	            	if(strlen($attributes["url"]) > 200)
		                return array ('error',"The 'Url (url)' field cannot contain more than 200 characters");
	            } 
	        }             
            // longitude (plain text)	        
            if(isset($attributes['longitude'])) {            	
	            if(trim((string)($attributes['longitude'])) !== '') {
	            	if(strlen($attributes["longitude"]) > 200)
		                return array ('error',"The 'Longitude (longitude)' field cannot contain more than 200 characters");
	            } 
	        }             
            // lattitude (plain text)	        
            if(isset($attributes['lattitude'])) {            	
	            if(trim((string)($attributes['lattitude'])) !== '') {
	            	if(strlen($attributes["lattitude"]) > 200)
		                return array ('error',"The 'Lattitude (lattitude)' field cannot contain more than 200 characters");
	            } 
	        }             
            // file_fld (plain text)	        
            if(isset($attributes['file_fld'])) {            	
	            if(trim((string)($attributes['file_fld'])) !== '') {
	            	if(strlen($attributes["file_fld"]) > 200)
		                return array ('error',"The 'File Fld (file_fld)' field cannot contain more than 200 characters");
	            } 
	        }             
            // image_fld (plain text)	        
            if(isset($attributes['image_fld'])) {            	
	            if(trim((string)($attributes['image_fld'])) !== '') {
	            	if(strlen($attributes["image_fld"]) > 200)
		                return array ('error',"The 'Image Fld (image_fld)' field cannot contain more than 200 characters");
	            } 
	        }             
            // document_fld (plain text)	        
            if(isset($attributes['document_fld'])) {            	
	            if(trim((string)($attributes['document_fld'])) !== '') {
	            	if(strlen($attributes["document_fld"]) > 200)
		                return array ('error',"The 'Document Fld (document_fld)' field cannot contain more than 200 characters");
	            } 
	        }             
            // expression_fld (plain text)	        
            if(isset($attributes['expression_fld'])) {            	
	            if(trim((string)($attributes['expression_fld'])) !== '') {
	            	if(strlen($attributes["expression_fld"]) > 200)
		                return array ('error',"The 'Expression Fld (expression_fld)' field cannot contain more than 200 characters");
	            } 
	        }             
            // notes (plain text)	        
            if(isset($attributes['notes'])) {            	
	            if(trim((string)($attributes['notes'])) !== '') {
	            	if(strlen($attributes["notes"]) > 255)
		                return array ('error',"The 'Notes (notes)' field cannot contain more than 255 characters");
	            } 
	        }             
            // test_company_id (integer)	        
            if(isset($attributes['test_company_id'])) {            	
	            if(trim((string)($attributes['test_company_id'])) !== '') {
		            if(!is_int((int)$attributes["test_company_id"]) || !ctype_digit((string)abs($attributes["test_company_id"])))
		                return array ('error',"The 'Test Company (test_company_id)' field must be an integer value.");
	            } 
	        }             
            // test_company2_id (integer)	        
            if(isset($attributes['test_company2_id'])) {            	
	            if(trim((string)($attributes['test_company2_id'])) !== '') {
		            if(!is_int((int)$attributes["test_company2_id"]) || !ctype_digit((string)abs($attributes["test_company2_id"])))
		                return array ('error',"The 'Test Company2 (test_company2_id)' field must be an integer value.");
	            } 
	        }             
            // dibuid (plain text)	        
            if(isset($attributes['dibuid'])) {            	
	            if(trim((string)($attributes['dibuid'])) !== '') {
	            	if(strlen($attributes["dibuid"]) > 30)
		                return array ('error',"The 'Dibuid (dibuid)' field cannot contain more than 30 characters");
	            } 
	        }             
            // Check if values in $pkValues are indeed pk's and of the right type
            $params = array();
            $fieldType = array();
            if (!array_key_exists("id", $pkValues))
                return array ('error',"The primary key fields specified in the request are invalid.");            
             if ($pkValues["id"] != (string)(float)$pkValues["id"])
                return array ('error',"Error! The primary key field values specified in the request are invalid.");
            $params[":pk1"] = $pkValues["id"];
            $fieldType[":pk1"] = PDO::PARAM_INT;            
            // `id` = :pk0    
            $pkCrit = "`test`.`id` = :pk1";                   
            $crit = $pkCrit;
            // Get record's existing (old) values
            $sql = "SELECT `test`.* 
		            FROM `test`
                     LEFT JOIN `test_company` `test_company1001` ON `test`.`test_company_id` = `test_company1001`.`id` 
                     LEFT JOIN `test_company` `test_company1002` ON `test`.`test_company2_id` = `test_company1002`.`id` 
		                WHERE $crit";
            $recordOld = $this->getRecordByPk($sql, $pkValues);
            if (count($recordOld) === 0)
                return array('error',"Error! The record to be updated has been deleted or denied by the permission system.");
            $crit .= "";
            // Get field-level criteria if applicable
            $validAttributes = $attributes;
            $sql = 'UPDATE `test` SET ';
            $i=0;
            foreach ($validAttributes AS $key => $value) {
                if(!array_key_exists($key, $recordOld) || $value == $recordOld[$key] || !array_key_exists($key, $this->fieldType) || (array_key_exists($key, $this->storeType) && $this->storeType[$key] === 'dibsqli')) // NOTE using == and not === since PDO returns int's as str!
                    unset($validAttributes[$key]); // for auditing purposes
                else {
                    // Only update the fields that have changed values
                    $sql .= "`$key`=:f$i, ";
                    $params[':f'.$i] = $value;
                    $fieldType[':f'.$i] = $this->fieldType[$key];
                    $i++;
                }
            }
            $sql = rtrim($sql, ', ') . " WHERE $crit";
            if (count($validAttributes) === 0) // Nothing to update
                return array('notice',"No changes were made to existing database values."); 
	            // Do the actual update for the main table fields
	            dibMySqlPdo::setParamsType($fieldType, DIB::$CONTAINERDATA[2]);
            	$success = dibMySqlPdo::execute($sql, DIB::$CONTAINERDATA[2], $params);
            	$mainUpdated = dibMySqlPdo::count();
            	if($success === FALSE)
            		return array('error', Database::lastErrorUserMsg());
                elseif($mainUpdated === 0) {
					$ind = strpos($crit, " AND (");
					if($ind === FALSE) $ind = -5;
                	return array('error',"Permissions failure on existing(old) values. Only records satisfying the following condition(s) can be changed: " . substr($crit, $ind + 5));
            	}
            $parentFieldChanged = false;
            $crit = TRUE;
            if ($crit===TRUE) {
                // Insert audit trail record - first set unique_record
                $this->unique_record = 1;               
                if (count($pkValues) > 1) {
                    $recordId = '';
                    foreach ($pkValues as $k => $v)
                        $recordId .= "$k=$v, ";
                    $recordId = substr($recordId, 0, strlen($recordId) - 2);
                } else {
                    foreach ($pkValues as $k => $v)
                        $recordId = $v;
                }
                foreach ($validAttributes AS $fieldName => $newValue) {
                    if ($newValue !== $recordOld[$fieldName])
                        $this->auditInsert("update", $fieldName, $recordOld[$fieldName], $newValue, 1437, $recordId);
                }
            } elseif (is_array($crit)) return $crit;
        } catch (Exception $e) {            
            return array ('error', "Error! The update request is not valid. Please contact the System Administator.");
        }
        return $pkValues;
    }
     /**
     * Deletes one record.
     * 
     * @param array $pkValues
     * @return boolean success of delete
     */
    public function delete($pkValues) {
        try {      
            // Check if values in $pkValues are indeed pk's and of the right type.
            $params = array();
            $fieldType = array();
            if (!array_key_exists("id", $pkValues))
                return array ('error',"The primary key fields specified in the request are invalid.");
             if ($pkValues["id"] != (string)(float)$pkValues["id"])
                return array ('error',"Error! The primary key field values specified in the request are invalid.");
            $params[":pk1"] = $pkValues["id"];
            $fieldType[":pk1"] = PDO::PARAM_INT;
            $pkCrit = "`test`.`id` = :pk1";            
            $attributes = 'Not yet loaded';
            // Get criteria for old values
             $crit = $pkCrit;
            // Get old values before we delete the record...
            $sql = "SELECT * FROM `test` WHERE $pkCrit";
            $attributes = $this->getRecordByPk($sql, $pkValues);
            if(count($attributes) === 0)
                return TRUE; // Other user deleted this record
            $sql = "DELETE FROM `test` WHERE $crit";
            dibMySqlPdo::setParamsType($fieldType, DIB::$CONTAINERDATA[2]);       
            $result = dibMySqlPdo::execute($sql, DIB::$CONTAINERDATA[2], $params);
            if ($result === FALSE || dibMySqlPdo::count() === 0) {
                if($result === FALSE && Database::lastErrorUserMsg())
                    return array('error',Database::lastErrorUserMsg());
                else
                    return array('error',"Permissions failure on existing(old) values. Only records satisfying the following condition(s) can be deleted: " . substr($crit, strpos($crit, " AND (") + 5));
            }
            if (dibMySqlPdo::count() > 0) {
                $crit = TRUE;
                if ($crit===TRUE) {
                    // Insert audit trail record - first set unique_record
                    $this->unique_record = 1;                    
                    if (count($pkValues) > 1) {
                        $recordId = '';
                        foreach ($pkValues as $k => $v)
                            $recordId .= "$k=$v, ";
                        $recordId = substr($recordId, 0, strlen($recordId) - 2);
                    } else {
                        foreach ($pkValues as $k => $v)
                            $recordId = $v;
                    }
                    foreach ($attributes AS $fieldName => $oldValue) 
                        $this->auditInsert("delete", $fieldName, $oldValue, NULL, 1437, $recordId);
                } elseif (is_array($crit)) return $crit;
                return true;
            }
        }  catch (Exception $e) {        
			return array('error',"A system error occured while deleting the record. Please contact the System Administrator.");
		}
        // ***TODO if user deletes many records, and he has no permissions on some of them, he shouldn't get 10x permission messages?
    }
    /**
     * Creates a duplicate of a record - will only work is the primary key is an auto-increment or supplied in $setValues.
     * 
     * @param array $pkValues - associative array of primary key field names & values
     * @param array $setValues - associative array of values that should be set in new record, overwriting original record's values
     * @param int $targetDatabaseId - if specified, the record is created in a foreign database table which must have exactly the same structure
     * @return type
     */
    public function duplicate($pkValues, $setValues=array(), $targetDatabaseId=null) {        
        // Currently creation occurs through create function, so ALL fields and records are included where the user has CREATE rights AND
        // and audit trail is maintained. All creation errors are returned as FALSE.
        try {
            // Check if values in $id's are indeed pk's and of the right type. 
            $params = array();
            $fieldType = array();
            if (!array_key_exists("id", $pkValues))
                return array ('error',"The primary key fields specified in the request are invalid.");
             if ($pkValues["id"] != (string)(float)$pkValues["id"])
                return array ('error',"Error! The primary key field values specified in the request are invalid.");
            $params[":pk1"] = $pkValues["id"];
            $fieldType[":pk1"] = PDO::PARAM_INT;
            //`id` = :pk0
            $pkCrit = "`test`.`id` = :pk1";  
            // Fields for duplication (include required fields and exclude expression, file, image & exclusion fields etc.)
            $sql = "SELECT 1 AS to_prohibit_error_if_none, `id`, `varchar10_required`, `has_default`, `time_stamp`, `unique_fld`, `nvarchar80`, `text_fld`, `tinytext_fld`, `mediumtext_fld`, `longtext_fld`, `bit_fld`, `tinyint_fld`, `smallint_fld`, `int_fld`, `bigint_fld`, `float_fld`, `double_fld`, `decimal_fld`, `date_fld`, `time_fld`, `datetime_fld`, `year_fld`, `enum_fld`, `set_fld`, `email`, `url`, `longitude`, `lattitude`, `file_fld`, `image_fld`, `document_fld`, `expression_fld`, `notes`, `test_company_id`, `test_company2_id`
                    FROM `test` WHERE $pkCrit";
            //Note - create code handles unique values :-)  
            dibMySqlPdo::setParamsType($fieldType, DIB::$CONTAINERDATA[2]);        
            $record = dibMySqlPdo::execute($sql, DIB::$CONTAINERDATA[2], $params, true);
            if($record === FALSE || count($record) < 1){
				Log::err(count($record) . "SQL error while fetching values to duplicate for dibtestTestForm.\r\nSQL: $sql\r\nPARAMS:" . json_encode($params) . "\r\nERROR:" . Database::lastErrorAdminMsg());
				return array('error',"Error! Could not read record to duplicate. Please contact the System Administrator.");
			}
            $delayArray = array(); // Temp store for values that must be memorised in order to update this table during DDuplicateRecords's final actions
            foreach($setValues as $field => $value) {
                if (array_key_exists($field, $record)) {                	
					if(!is_array($value)) // contant value
						$record[$field] = $value;
					elseif(!is_null($record[$field])) { 
						// Set values for special fields, such as foreign key references (eg pef_item.pef_field) to records that may have different pkey values
						// lookup value using SQL statements
						// eg SELECT f.name as fname, t.name FROM pef_field f INNER JOIN pef_table t ON f.pef_table_id = t.id WHERE f.id = :value   	
						$args = (strpos($value[0], ':value') !== FALSE) ? array(':value'=>$record[$field]) : array();
						$result = dibMySqlPdo::execute($value[0], DIB::$CONTAINERDATA[2], $args, true);
						if($result === FALSE || count($result) < 1) {
							Log::err("Unusual Sql error... No result returned in SOURCE db when attempting to find original values for a LOOKUP query while duplicating container dibtestTestForm records. Note, unless this query returns a value, the code will not work.\r\nSQL: " . $value[0] . "\r\n\PARAMS: " . json_encode($args));
    						return array('error', "Configuration error found while duplicating records. Please contact the System Administrator.");
						}
						// Convert result to params
						$args = array();
						foreach($result AS $k=>$v)
							$args[':'.$k] = $v;
						// Check if a delayed update must occur
						if($value[1]==='delay') {
							$delayArray[] = array($field, $value[2], $args);
							$record[$field] = null; // ***TODO allow SQL statement incase field value is required
						} else {
							// Run 2nd query, eg SELECT id FROM pef_field f INNER JOIN pef_table t ON f.pef_table_id = t.id WHERE f.name=:fname and t.name=:name
							$result = Database::fetch($value[1], $args, $targetDatabaseId);
							if($result === FALSE || Database::count() === 0) {
								// Check if 'create' is required
								if(isset($value[2]) && $value[2]==='create') {
									$result = Crud::duplicate($value[3], array('id'=>$record[$field]), $value[4], $targetDatabaseId);
									if(isset($result[0]) && $result[0]==='error') {	
										Log::err("Sql error, or no result returned when attempting to run a LOOKUP query against database id $targetDatabaseId while duplicating container 'dibtestTestForm' records using the 'create' directive. Note, unless the create code succeeds, the whole operation will fail:\r\nLAST SQL ERROR:" . Database::lastErrorAdminMsg() . "\r\nPARAMS: " . json_encode($value[4]));
		    							return array('error', "Configuration error found while duplicating records. Please contact the System Administrator.");
		    						}
		    						$result = array('id'=>$result);
		    					} else {
		    						Log::err("Sql error, or no result returned when attempting to run a LOOKUP query against database id $targetDatabaseId while duplicating container 'dibtestTestForm' records. Note, unless this query returns a value, the code will not work.\r\nSQL: " . $value[1] . "\r\n\PARAMS: " . json_encode($args));
	    							return array('error', "Configuration error found while duplicating records. Please contact the System Administrator.");
	    						}
							}							
							$record[$field] = array_pop($result);
						}
					}
                }
            }
            $result = $this->create($record, true, $targetDatabaseId); // This will force unique values, and handle audit trail etc.
            if (isset($result[0]) && $result[0]==='error')
                return $result;
            elseif ($delayArray) {
				$value = array_pop($result);
				$params[':pk1'] = $value;				
				foreach($delayArray as $args) {// store table name, field name, pkey value, sql statements x 2 and sql params x 2
					$sql = 'UPDATE test SET `' . $args[0] . "` = :value WHERE $pkCrit";					
					PeffApp::$array['DuplicateRecords']['test*'.$args[0].'*'.$value] = array($args[1], $args[2], $sql, $params);
				}
				return $value;
			} else
                return array_pop($result);  //returns the pk value of new record or FALSE on error;
        } catch (Exception $e) {
            return array('error',"Error! Could not create duplicate of record. Please contact the System Administrator");
        }
    }
     /**
     * Drop a specific node on-to another
     * @param string $dropPosition 'after'/'before'/'append'
     * @param integer $dropNodeId
     * @param type $nodeId
     * @param string $parentId 'root'/integer
     */
    function dropNode($dropPosition, $dropNodeId, $nodeId, $parentId) {
        return FALSE;
    }
    /**
     * Returns default values of a record as defined in pef_item.
     * 
     * @return array
     */
    public function getDefaults($createParams=null, $submissionData=array()) {
        try {
            $attributes=array();
             $attributes['id'] = 0;
            $attributes['has_default'] = 'waarde';
            // url has fields that must be populated in new record - just add to defaults...
            if ($createParams && is_array($createParams))  // *** TODO Get display_fields for dropdowns values using crud classes for lists...               
                $attributes = array_merge($attributes, $createParams);
            return $attributes;
        }  catch (Exception $e) {
            return array ('error',"Error! Could not load defaults. Please contact the System Administator.");
        }
    }
    /**
     * Returns field-level criteria for field values that were changed in an Update operation.
     * 
     * @param array $validAttributes - associative array of new fields (only updatable fields)
     * @param array $oldValues - associative array of old values
     * @return type
     */
    private function getFieldChangeCriteria(&$validAttributes, &$oldValues) {        
        return FALSE;
    }
    /**
     * Returns the existing values of a certain record - accepts primary key used in parameters in sql.
     * 
     * @param string $sql
     * @return array
     */
    private function getRecordByPk($sql, $pkValues) {
        // Note: apply plain text escaping to everything marked 'plain text' in validation_type
        if (!array_key_exists("id", $pkValues))
            return array ('error',"The primary key fields specified in the request are invalid.");
         if ($pkValues["id"] != (string)(float)$pkValues["id"])
                return array ('error',"Error! The primary key field values specified in the request are invalid.");
        $params[":pk1"] = $pkValues["id"];
        $fieldType[":pk1"] = PDO::PARAM_INT;
        dibMySqlPdo::setParamsType($fieldType, DIB::$CONTAINERDATA[2]);
        $rst = dibMySqlPdo::execute($sql, DIB::$CONTAINERDATA[2], $params, true);
        if ($rst === FALSE)
            return array();
        else
            return $rst;
    }
     /**
     * Adds the actual record to pef_audit_trail
     * 
     * @var string $crudType - create/read/update/delete
     * @var string $fieldName - name of field
     * @var array $oldValue - string containing old values
     * @var array $newValue - string containing new values
     * @var integer $tableId - table_id
     * @var string $tableName - name of table
     * @var integer $recordId - primary key value
     */
    protected function auditInsert($crudType, $fieldName, $oldValue, $newValue, $tableId, $recordId) {
        $sql = "INSERT INTO `pef_audit_trail` 
             (action, pef_table_id, pef_container_id, table_name, record_id, date_time, ip_address, field_name, old_value, new_value, pef_login_id, username, unique_record) 
             VALUES ('$crudType', $tableId, 8580, 'test', :recordId, :dateTime, :ipAddress, :fieldName, :oldValue, :newValue, :loginId, :username, :unique_record)";
        Database::execute($sql, array(':dateTime'=>date('Y-m-d H:i:s', time()), ':fieldName'=> $fieldName, ':recordId'=>$recordId, 
        	':oldValue'=>$oldValue, ':newValue'=> $newValue, ':username'=>DIB::$USER['username'], ':unique_record'=>$this->unique_record, 
        	':ipAddress'=>$_SERVER['REMOTE_ADDR'], ':loginId'=>DIB::$USER['id']),
        	DIB::DBINDEX
        );
        $this->unique_record = 0;
    }
    /**
     * Strips the attributes from any columns the user may not update
     * @param $validColumns
     * @param $attributes
     * @return string
     */
    private function removeSecuredColumns($validColumns, &$attributes) {
        // *!* ***TODO the if statement below can be part of the template creation...        
        if ($validColumns === "*")
            $validAttributes = $attributes;
        else {
            $validAttributes = explode(",", $validColumns);
            $validAttributes = array_flip($validAttributes);
            $validAttributes = array_intersect_key($attributes, $validAttributes);
        }
        return $validAttributes;
    } 
    public function getCaptions() {
    	return array('Id', 'Smallint Fld', 'Email', 'Varchar10 Required', 'Int Fld', 'Url', 'Has Default', 'Bigint Fld', 'Longitude', 'Time Stamp', 'Float Fld', 'Lattitude', 'Unique Fld', 'Double Fld', 'File Fld', 'Nvarchar80', 'Decimal Fld', 'Image Fld', 'Text Fld', 'Date Fld', 'Document Fld', 'Tinytext Fld', 'Time Fld', 'Expression Fld', 'Mediumtext Fld', 'Datetime Fld', 'Notes', 'Longtext Fld', 'Year Fld', 'Test Company', 'Bit Fld', 'Enum Fld', 'Test Company2', 'Tinyint Fld', 'Set Fld', 'Dibuid' );
    }
    public function getSqlParts() {
		return array('model' => 'Table',
					 'containerName' => "Tablexx2xxdibtestTestForm",
					 'selectFields' => "`test`.`id`,`test`.`varchar10_required`,`test`.`has_default`,`test`.`time_stamp`,`test`.`unique_fld`,`test`.`nvarchar80`,`test`.`text_fld`,`test`.`tinytext_fld`,`test`.`mediumtext_fld`,`test`.`longtext_fld`,`test`.`bit_fld`,`test`.`tinyint_fld`,`test`.`smallint_fld`,`test`.`int_fld`,`test`.`bigint_fld`,`test`.`float_fld`,`test`.`double_fld`,`test`.`decimal_fld`,`test`.`date_fld`,`test`.`time_fld`,`test`.`datetime_fld`,`test`.`year_fld`,`test`.`enum_fld`,`test`.`set_fld`,`test`.`email`,`test`.`url`,`test`.`longitude`,`test`.`lattitude`,`test`.`file_fld`,`test`.`image_fld`,`test`.`document_fld`,`test`.`expression_fld`,`test`.`notes`,`test`.`test_company_id`,`test`.`test_company2_id`,`test`.`dibuid`",
				     'selectSqlFields' => trim("
                 ", ", \r\n"),
				     'selectSqlDisplay' =>  trim("
                 ", ", \r\n"),
				     'selectTableDisplay' => trim("
                     , ^^CONCAT(`test_company1001`.`name`, ' (' , CAST(`test_company1001`.`id` AS CHAR), ')')^^ AS `test_company_id_display_value`, ^^CONCAT(`test_company1002`.`name`, ' (' , CAST(`test_company1002`.`id` AS CHAR), ')')^^ AS `test_company2_id_display_value`
                 ", ", \r\n"),          
                     'from' => trim("`test`                  
                     LEFT JOIN `test_company` `test_company1001` ON `test`.`test_company_id` = `test_company1001`.`id` 
                     LEFT JOIN `test_company` `test_company1002` ON `test`.`test_company2_id` = `test_company1002`.`id` 
                  ", ", \r\n")
        );
	}
} // end Class                
            