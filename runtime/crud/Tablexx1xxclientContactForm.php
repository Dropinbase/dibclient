<?php 
    Class Tablexx1xxclientContactForm { 
    public $count = 0;
    public $newPkValue = null; // for INSERTS it will contain lastInsertId
    public $pkeys = 'id';
    public $fieldType = array("id"=>PDO::PARAM_INT, "resigned"=>PDO::PARAM_STR, "client_id"=>PDO::PARAM_INT, "updated"=>PDO::PARAM_STR, "first_name"=>PDO::PARAM_STR, "notes"=>PDO::PARAM_STR, "last_name"=>PDO::PARAM_STR, "position"=>PDO::PARAM_STR, "email"=>PDO::PARAM_STR, "phone_w"=>PDO::PARAM_STR, "mobile"=>PDO::PARAM_STR);
    public $storeType = array("id"=>'none', "resigned"=>'none', "client_id"=>'dropdown', "updated"=>'none', "first_name"=>'none', "notes"=>'none', "last_name"=>'none', "position"=>'none', "email"=>'none', "phone_w"=>'none', "mobile"=>'none');
    public $sqlFields = array();
    public $fkeyDisplay = array(
                 'client_id'=>"`client1001`.`name`", 
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
            	$str .= '`client_contact`.`' . $field . '` = :pk' . $i . ' AND ';
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
        if(strpos($activeFilter, 'clientForm_clientContactForm') === 0) {
            $crit = "`client_contact`.`client_id` = :submitItemAlias_parent_client_pk"; 
            if (array_key_exists('submitItemAlias_parent_client_pk',$filterParams)) {
                $params[':submitItemAlias_parent_client_pk'] = $filterParams['submitItemAlias_parent_client_pk'];
                // Remove from array so that Related Records' parseFilterArray can add any other params, e.g. if Related Records and activeFilter both apply
                // unset($filterParams["submitItemAlias_parent_client_pk"]);
            } else {
                $value = EvalCriteria::evalParam(':submitItemAlias_parent_client_pk', $filterParams);
                if(is_array($value) || $value === ':submitItemAlias_parent_client_pk')
                    // ***TODO LogERROR!
                    //return array('error',"Error! The filter parameter 'submitItemAlias_parent_client_pk' for filter '' on clientContactForm is missing from submitted values.");
                    $crit = '1 = 2'; // We're returning no records since if eg submitCheckedItems is used and there are no checked records then this error will occur.
                else 
                    $params[':submitItemAlias_parent_client_pk'] = $value;
            }
            $criteria .= " AND ($crit) ";
        }
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
        $pkList = "`client_contact`.`id`";
        $criteria = '';
        $order ='';
        if ($option === 'next')  { 
            $criteria = "WHERE `id` > :pk1";
            $order = "ORDER BY `client_contact`.`id`";
        } elseif ($option === 'prev') {
            $order = "ORDER BY `id` DESC";
            $criteria = "WHERE `id` < :pk1";
        } elseif ($option === 'last') {
            $order = "ORDER BY `client_contact`.`id` DESC";
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
            $order = "ORDER BY `client_contact`.`id` ASC";
            $params = array();
            $fieldType = array();
        }
        // activeFilter                
        if(!empty($activeFilter)){  
            $crit = ''; 
            if($activeFilter === 'clientForm_clientContactForm') {  
                $crit = "`client_contact`.`client_id` = :submitItemAlias_parent_client_pk"; 
                if (array_key_exists('submitItemAlias_parent_client_pk',$filterParams)) {
                    $params[':submitItemAlias_parent_client_pk'] = $filterParams['submitItemAlias_parent_client_pk'];
                    // Remove from array so that Related Records' parseFilterArray can add any other params, e.g. if Related Records and activeFilter both apply
                    // unset($filterParams['submitItemAlias_parent_client_pk']);
                } else {
                    $value = EvalCriteria::evalParam(':submitItemAlias_parent_client_pk', $filterParams);
                    if(is_array($value))
                        //return array('error',"Error! The filter parameter 'submitItemAlias_parent_client_pk' for filter 'clientContactForm' on clientContactForm is missing from submitted values.");
                        $crit = ' 1=2 '; // We're returning no records since if eg submitCheckedItems is used and there are no checked records then this error will occur.
                    else 
                        $params[':submitItemAlias_parent_client_pk'] = $value;
                }
            } else
                return  array('error',"Error! The named active filter could not be found in the crud class. Please contact the System Administrator.");
            $criteria = ($criteria!=='') ? $criteria . " AND ($crit) " : " WHERE $crit";
        }
        // Template: sql statement for MySql to fetch records for the Toolbar on Forms. Used in eg CrudPdoTemplate.php.
$sql = "SELECT $pkList FROM `client_contact` $criteria $order LIMIT 1";
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
        // activeFilter                
        if(!empty($activeFilter)){  
            $crit = ''; 
            if($activeFilter === 'clientForm_clientContactForm') {  
                $crit = "`client_contact`.`client_id` = :submitItemAlias_parent_client_pk"; 
                if (array_key_exists('submitItemAlias_parent_client_pk',$filterParams)) {
                    $params[':submitItemAlias_parent_client_pk'] = $filterParams['submitItemAlias_parent_client_pk'];
                    // Remove from array so that Related Records' parseFilterArray can add any other params, e.g. if Related Records and activeFilter both apply
                    // unset($filterParams['submitItemAlias_parent_client_pk']);
                } else {
                    $value = EvalCriteria::evalParam(':submitItemAlias_parent_client_pk', $filterParams);
                    if(is_array($value))
                        //return array('error',"Error! The filter parameter 'submitItemAlias_parent_client_pk' for filter 'clientContactForm' on clientContactForm is missing from submitted values.");
                        $crit = ' 1=2 '; // We're returning no records since if eg submitCheckedItems is used and there are no checked records then this error will occur.
                    else 
                        $params[':submitItemAlias_parent_client_pk'] = $value;
                }
            } else
                return  array('error',"Error! The named active filter could not be found in the crud class. Please contact the System Administrator.");
            $criteria = ($criteria!=='') ? $criteria . " AND ($crit) " : " WHERE $crit";
        }
        // Template: sql statement for MySql to fetch nth record for the Toolbar on Forms. Used in eg CrudPdoTemplate.php.
$sql = "SELECT `client_contact`.`id` 
        FROM `client_contact`
        $criteria
        ORDER BY `client_contact`.`id` 
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
            	$fieldExpr = "`client_contact`.`$field`";
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
                $criteria = "`client_contact`.`id` = :pk1 ";
                $sql = "SELECT `client_contact`.`id`,`client_contact`.`client_id`,`client_contact`.`first_name`,`client_contact`.`last_name`,`client_contact`.`position`,`client_contact`.`email`,`client_contact`.`phone_w`,`client_contact`.`mobile`,`client_contact`.`resigned`,`client_contact`.`updated`,`client_contact`.`notes` 
                     , `client1001`.`name` AS `client_id_display_value`
                         FROM `client_contact`                  
                     LEFT JOIN `client` `client1001` ON `client_contact`.`client_id` = `client1001`.`id` 
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
                	$sql = "SELECT Count(*) AS totalcount FROM `client_contact`  ";
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
                // activeFilter
                if(!empty($activeFilter)){  
                    $crit = $this->parseFilter($activeFilter, $params, $filterParams); // $params passed by reference to be populated
                    if(is_array($crit)) return $crit;
                    $criteria .= " AND $crit";
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
                     LEFT JOIN `client` `client1001` ON `client_contact`.`client_id` = `client1001`.`id` 
                 ";
                 	} else 
                 		$join = '';
                    if($page === 1 || $countMode==='all'){
	                    $sql = "SELECT Count(*) AS filteredCount FROM `client_contact` $join  $criteria";
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
                        	$orderStr .= '`client_contact`.`' . $record['property'] . '` ' . $direction . ', ';

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
                `client_contact`.`id` AS `Id`, `client_contact`.`first_name` AS `First Name`, `client_contact`.`last_name` AS `Last Name`, `client_contact`.`position` AS `Position`, `client_contact`.`email` AS `Email`, `client_contact`.`phone_w` AS `Phone W`, `client_contact`.`mobile` AS `Mobile`, `client_contact`.`resigned` AS `Resigned`, `client_contact`.`updated` AS `Updated`, `client_contact`.`notes` AS `Notes` 
                     , `client1001`.`name` AS `Client`
            FROM `client_contact` 
                     LEFT JOIN `client` `client1001` ON `client_contact`.`client_id` = `client1001`.`id` 
                 ";
else
    $sql = "SELECT `client_contact`.`id`,`client_contact`.`client_id`,`client_contact`.`first_name`,`client_contact`.`last_name`,`client_contact`.`position`,`client_contact`.`email`,`client_contact`.`phone_w`,`client_contact`.`mobile`,`client_contact`.`resigned`,`client_contact`.`updated`,`client_contact`.`notes` 
                     , `client1001`.`name` AS `client_id_display_value`
            FROM `client_contact` 
                     LEFT JOIN `client` `client1001` ON `client_contact`.`client_id` = `client1001`.`id` 
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
                $blankRecord = array("id"=>null, "client_id"=>null, "first_name"=>null, "last_name"=>null, "email"=>null, "phone_w"=>null, "mobile"=>null, "resigned"=>null, "notes"=>null);
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
            // Check Validation
	        // client_id (integer)
            if(isset($attributes['client_id'])) {            	
	            if(trim((string)($attributes['client_id'])) !== '') {
		            if(!is_int((int)$attributes["client_id"]) || !ctype_digit((string)abs($attributes["client_id"])))
		                return array ('error',"The 'Client (client_id)' field must be an integer value.");
	            } 
	        }  else	            
                return array ('error',"The 'Client (client_id)' field is required. Please provide a value.");
	        // first_name (plain text)
            if(isset($attributes['first_name'])) {            	
	            if(trim((string)($attributes['first_name'])) !== '') {
	            	if(strlen($attributes["first_name"]) > 35)
		                return array ('error',"The 'First Name (first_name)' field cannot contain more than 35 characters");
	            } 
	        }  else	            
                return array ('error',"The 'First Name (first_name)' field is required. Please provide a value.");
	        // last_name (plain text)
            if(isset($attributes['last_name'])) {            	
	            if(trim((string)($attributes['last_name'])) !== '') {
	            	if(strlen($attributes["last_name"]) > 35)
		                return array ('error',"The 'Last Name (last_name)' field cannot contain more than 35 characters");
	            } 
	        }  else	            
                return array ('error',"The 'Last Name (last_name)' field is required. Please provide a value.");
	        // position (plain text)
            if(isset($attributes['position'])) {            	
	            if(trim((string)($attributes['position'])) !== '') {
	            	if(strlen($attributes["position"]) > 50)
		                return array ('error',"The 'Position (position)' field cannot contain more than 50 characters");
	            } 
	        }             
	        // email (email)
            if(isset($attributes['email'])) {            	
	            if(trim((string)($attributes['email'])) !== '') {
	            	if(strlen($attributes["email"]) > 150)
		                return array ('error',"The 'Email (email)' field cannot contain more than 150 characters");
		            if(!filter_var($attributes["email"], FILTER_VALIDATE_EMAIL))
		                return array ('error',"The 'Email (email)' field must be a valid email address.");
	            } 
	        }             
	        // phone_w (plain text)
            if(isset($attributes['phone_w'])) {            	
	            if(trim((string)($attributes['phone_w'])) !== '') {
	            	if(strlen($attributes["phone_w"]) > 35)
		                return array ('error',"The 'Phone W (phone_w)' field cannot contain more than 35 characters");
	            } 
	        }             
	        // mobile (plain text)
            if(isset($attributes['mobile'])) {            	
	            if(trim((string)($attributes['mobile'])) !== '') {
	            	if(strlen($attributes["mobile"]) > 35)
		                return array ('error',"The 'Mobile (mobile)' field cannot contain more than 35 characters");
	            } 
	        }             
	        // resigned (date)
            if(isset($attributes['resigned'])) {            	
	            if(trim((string)($attributes['resigned'])) !== '') {
	        		if(!strtotime($attributes["resigned"]))
		                return array ('error',"The 'Resigned (resigned)' field must be a valid date.");
		            else
		                $attributes["resigned"] = date('Y-m-d', strtotime($attributes["resigned"]));
	            } 
	        }             
	        // updated (datetime)
            if(isset($attributes['updated'])) {            	
	            if(trim((string)($attributes['updated'])) !== '') {
	        		if(!strtotime($attributes["updated"]))
		                return array ('error',"The 'Updated (updated)' field must be a valid date-time.");
		            else
		                $attributes["updated"] = date('Y-m-d H:i:s', strtotime($attributes["updated"]));
	            } 
	        }             
	        // notes (plain text)
            if(isset($attributes['notes'])) {            	
	            if(trim((string)($attributes['notes'])) !== '') {
	            	if(strlen($attributes["notes"]) > 250)
		                return array ('error',"The 'Notes (notes)' field cannot contain more than 250 characters");
	            } 
	        }             
            // All clear - perform the insert...
            $sql = "INSERT INTO `client_contact` (";
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
                    $this->auditInsert("create", $fieldName, null, $newValue, 4594, $recordId);
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
            // client_id (integer)	        
            if(isset($attributes['client_id'])) {            	
	            if(trim((string)($attributes['client_id'])) !== '') {
		            if(!is_int((int)$attributes["client_id"]) || !ctype_digit((string)abs($attributes["client_id"])))
		                return array ('error',"The 'Client (client_id)' field must be an integer value.");
	            } 
	        }             
            // first_name (plain text)	        
            if(isset($attributes['first_name'])) {            	
	            if(trim((string)($attributes['first_name'])) !== '') {
	            	if(strlen($attributes["first_name"]) > 35)
		                return array ('error',"The 'First Name (first_name)' field cannot contain more than 35 characters");
	            } 
	        }             
            // last_name (plain text)	        
            if(isset($attributes['last_name'])) {            	
	            if(trim((string)($attributes['last_name'])) !== '') {
	            	if(strlen($attributes["last_name"]) > 35)
		                return array ('error',"The 'Last Name (last_name)' field cannot contain more than 35 characters");
	            } 
	        }             
            // position (plain text)	        
            if(isset($attributes['position'])) {            	
	            if(trim((string)($attributes['position'])) !== '') {
	            	if(strlen($attributes["position"]) > 50)
		                return array ('error',"The 'Position (position)' field cannot contain more than 50 characters");
	            } 
	        }             
            // email (email)	        
            if(isset($attributes['email'])) {            	
	            if(trim((string)($attributes['email'])) !== '') {
	            	if(strlen($attributes["email"]) > 150)
		                return array ('error',"The 'Email (email)' field cannot contain more than 150 characters");
		            if(!filter_var($attributes["email"], FILTER_VALIDATE_EMAIL))
		                return array ('error',"The 'Email (email)' field must be a valid email address.");
	            } 
	        }             
            // phone_w (plain text)	        
            if(isset($attributes['phone_w'])) {            	
	            if(trim((string)($attributes['phone_w'])) !== '') {
	            	if(strlen($attributes["phone_w"]) > 35)
		                return array ('error',"The 'Phone W (phone_w)' field cannot contain more than 35 characters");
	            } 
	        }             
            // mobile (plain text)	        
            if(isset($attributes['mobile'])) {            	
	            if(trim((string)($attributes['mobile'])) !== '') {
	            	if(strlen($attributes["mobile"]) > 35)
		                return array ('error',"The 'Mobile (mobile)' field cannot contain more than 35 characters");
	            } 
	        }             
            // resigned (date)	        
            if(isset($attributes['resigned'])) {            	
	            if(trim((string)($attributes['resigned'])) !== '') {
	        		if(!strtotime($attributes["resigned"]))
		                return array ('error',"The 'Resigned (resigned)' field must be a valid date.");
		            else
		                $attributes["resigned"] = date('Y-m-d', strtotime($attributes["resigned"]));
	            } 
	        }             
            // updated (datetime)	        
            if(isset($attributes['updated'])) {            	
	            if(trim((string)($attributes['updated'])) !== '') {
	        		if(!strtotime($attributes["updated"]))
		                return array ('error',"The 'Updated (updated)' field must be a valid date-time.");
		            else
		                $attributes["updated"] = date('Y-m-d H:i:s', strtotime($attributes["updated"]));
	            } 
	        }             
            // notes (plain text)	        
            if(isset($attributes['notes'])) {            	
	            if(trim((string)($attributes['notes'])) !== '') {
	            	if(strlen($attributes["notes"]) > 250)
		                return array ('error',"The 'Notes (notes)' field cannot contain more than 250 characters");
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
            $pkCrit = "`client_contact`.`id` = :pk1";                   
            $crit = $pkCrit;
            // Get record's existing (old) values
            $sql = "SELECT `client_contact`.* 
		            FROM `client_contact`
                     LEFT JOIN `client` `client1001` ON `client_contact`.`client_id` = `client1001`.`id` 
		                WHERE $crit";
            $recordOld = $this->getRecordByPk($sql, $pkValues);
            if (count($recordOld) === 0)
                return array('error',"Error! The record to be updated has been deleted or denied by the permission system.");
            $crit .= "";
            // Get field-level criteria if applicable
            $validAttributes = $attributes;
            $sql = 'UPDATE `client_contact` SET ';
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
                        $this->auditInsert("update", $fieldName, $recordOld[$fieldName], $newValue, 4594, $recordId);
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
            $pkCrit = "`client_contact`.`id` = :pk1";            
            $attributes = 'Not yet loaded';
            // Get criteria for old values
             $crit = $pkCrit;
            // Get old values before we delete the record...
            $sql = "SELECT * FROM `client_contact` WHERE $pkCrit";
            $attributes = $this->getRecordByPk($sql, $pkValues);
            if(count($attributes) === 0)
                return TRUE; // Other user deleted this record
            $sql = "DELETE FROM `client_contact` WHERE $crit";
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
                        $this->auditInsert("delete", $fieldName, $oldValue, NULL, 4594, $recordId);
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
            $pkCrit = "`client_contact`.`id` = :pk1";  
            // Fields for duplication (include required fields and exclude expression, file, image & exclusion fields etc.)
            $sql = "SELECT 1 AS to_prohibit_error_if_none, `id`, `client_id`, `first_name`, `last_name`, `position`, `email`, `phone_w`, `mobile`, `resigned`, `updated`, `notes`
                    FROM `client_contact` WHERE $pkCrit";
            //Note - create code handles unique values :-)  
            dibMySqlPdo::setParamsType($fieldType, DIB::$CONTAINERDATA[2]);        
            $record = dibMySqlPdo::execute($sql, DIB::$CONTAINERDATA[2], $params, true);
            if($record === FALSE || count($record) < 1){
				Log::err(count($record) . "SQL error while fetching values to duplicate for clientContactForm.\r\nSQL: $sql\r\nPARAMS:" . json_encode($params) . "\r\nERROR:" . Database::lastErrorAdminMsg());
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
							Log::err("Unusual Sql error... No result returned in SOURCE db when attempting to find original values for a LOOKUP query while duplicating container clientContactForm records. Note, unless this query returns a value, the code will not work.\r\nSQL: " . $value[0] . "\r\n\PARAMS: " . json_encode($args));
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
										Log::err("Sql error, or no result returned when attempting to run a LOOKUP query against database id $targetDatabaseId while duplicating container 'clientContactForm' records using the 'create' directive. Note, unless the create code succeeds, the whole operation will fail:\r\nLAST SQL ERROR:" . Database::lastErrorAdminMsg() . "\r\nPARAMS: " . json_encode($value[4]));
		    							return array('error', "Configuration error found while duplicating records. Please contact the System Administrator.");
		    						}
		    						$result = array('id'=>$result);
		    					} else {
		    						Log::err("Sql error, or no result returned when attempting to run a LOOKUP query against database id $targetDatabaseId while duplicating container 'clientContactForm' records. Note, unless this query returns a value, the code will not work.\r\nSQL: " . $value[1] . "\r\n\PARAMS: " . json_encode($args));
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
					$sql = 'UPDATE client_contact SET `' . $args[0] . "` = :value WHERE $pkCrit";					
					PeffApp::$array['DuplicateRecords']['client_contact*'.$args[0].'*'.$value] = array($args[1], $args[2], $sql, $params);
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
            $attributes['position'] = 0;
            $attributes['updated'] = date('Y-m-d H:i:s', time());
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
             VALUES ('$crudType', $tableId, 9112, 'client_contact', :recordId, :dateTime, :ipAddress, :fieldName, :oldValue, :newValue, :loginId, :username, :unique_record)";
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
    	return array('Id', 'Resigned', 'Client', 'Updated', 'First Name', 'Notes', 'Last Name', 'Position', 'Email', 'Phone W', 'Mobile' );
    }
    public function getSqlParts() {
		return array('model' => 'Table',
					 'containerName' => "Tablexx1xxclientContactForm",
					 'selectFields' => "`client_contact`.`id`,`client_contact`.`client_id`,`client_contact`.`first_name`,`client_contact`.`last_name`,`client_contact`.`position`,`client_contact`.`email`,`client_contact`.`phone_w`,`client_contact`.`mobile`,`client_contact`.`resigned`,`client_contact`.`updated`,`client_contact`.`notes`",
				     'selectSqlFields' => trim("
                 ", ", \r\n"),
				     'selectSqlDisplay' =>  trim("
                 ", ", \r\n"),
				     'selectTableDisplay' => trim("
                     , `client1001`.`name` AS `client_id_display_value`
                 ", ", \r\n"),          
                     'from' => trim("`client_contact`                  
                     LEFT JOIN `client` `client1001` ON `client_contact`.`client_id` = `client1001`.`id` 
                  ", ", \r\n")
        );
	}
} // end Class                
            