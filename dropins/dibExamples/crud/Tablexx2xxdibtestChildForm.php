<?php 
    Class Tablexx2xxdibtestChildForm { 
    public $count = 0;
    public $newPkValue = null; // for INSERTS it will contain lastInsertId
    public $pkeys = 'primkey1,primkey2';
    public $fieldType = array("primkey1"=>PDO::PARAM_STR, "primkey2"=>PDO::PARAM_STR, "pef_test_id"=>PDO::PARAM_INT, "date_fld"=>PDO::PARAM_STR, "unique1"=>PDO::PARAM_STR, "unique2"=>PDO::PARAM_STR, "notes"=>PDO::PARAM_STR);
    public $storeType = array("primkey1"=>'none', "primkey2"=>'none', "pef_test_id"=>'dropdown', "date_fld"=>'none', "unique1"=>'none', "unique2"=>'none', "notes"=>'none');
    public $sqlFields = array();
    public $fkeyDisplay = array(
            'pef_test_id'=>"^^CONCAT(`test1001`.`varchar10_required`, '-', CAST(`test1001`.`has_default` AS CHAR))^^", 
            );
    protected $filterArray = null;
    protected $now = null;
    protected $ipAddress = null;
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
            	$str .= '`test_child`.`' . $field . '` = :pk' . $i . ' AND ';
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
    public function getToolbarInfo($pkValues, $activeFilter, $filterParams, $getFirstOnly=FALSE) {        
        $params = array();   
        $criteria = '';
        if(!empty($activeFilter)) {
            $criteria = $this->parseFilter($activeFilter, $params, $filterParams);
            if(isset($criteria[0]) && $criteria[0]==='error')
                return $criteria;
            $criteria .= " AND ($criteria)";
            $fromClause = "`test_child`
                LEFT JOIN `test` `test1001` ON `test_child`.`pef_test_id` = `test1001`.`id` 
                ";
        } else 
            $fromClause = "`test_child`";
        if($criteria !== '') $criteria = 'WHERE ' . substr($criteria, 4);
        if($getFirstOnly) { // Used after deletes to navigate to first record
            $sql = "SELECT primkey1,primkey2 FROM $fromClause $criteria ORDER BY primkey1,primkey2 limit 1";
            $rst = dibMySqlPdo::execute($sql, DIB::$CONTAINERDATA[2], $params, true);
            if($rst === FALSE)
                return array('error', 'Could not get first record. Please contact the System Administrator. (#0).');
            return $rst;
        }
        // First get total count        
        $sql = "SELECT count(*) as `total` FROM $fromClause $criteria";
        $rst = dibMySqlPdo::execute($sql, DIB::$CONTAINERDATA[2], $params, true);
        if(dibMySqlPdo::count() === 0)
            return array('error', 'Could not set form navigation counts. Please contact the System Administrator. (#1).');
        $totalCount = $rst['total'];
        // Add pkeys to $params
        $fieldType = array();
        if (!array_key_exists("primkey1", $pkValues))
            return array ('error',"The primary key field names specified in the request are invalid.");        
        $params[":pk1"] = $pkValues["primkey1"];
        $fieldType[":pk1"] = PDO::PARAM_STR;    
        if (!array_key_exists("primkey2", $pkValues))
            return array ('error',"The primary key field names specified in the request are invalid.");        
        $params[":pk2"] = $pkValues["primkey2"];
        $fieldType[":pk2"] = PDO::PARAM_STR;    
        // Get current, first and last 
        if($totalCount>1) {
            $pkCriteria = "(`primkey1` = :pk1 AND `primkey2` = :pk2)";
            $rankCriteria = " OR dib__rank=1 OR dib__rank=$totalCount";
            $sql = "SELECT `dib__rank`, primkey1,primkey2, $pkCriteria AS dib__IsCurrent FROM (
                SELECT @rownum:=@rownum+1 AS `dib__rank`, `test_child`.`primkey1`,`test_child`.`primkey2` 
                FROM $fromClause, 
                  (SELECT @rownum:=0) r 
                $criteria
                ORDER BY `test_child`.`primkey1`,`test_child`.`primkey2`) dibt1
                WHERE $pkCriteria $rankCriteria";
            $currentRst = dibMySqlPdo::execute($sql, DIB::$CONTAINERDATA[2], $params, false);
            if(dibMySqlPdo::count() === 0)
                return array('error', 'Could not set form navigation counts. Please contact the System Administrator (#2).');
            $countRec = count($currentRst);
            if($countRec >= 3) {                
                list($first, $current, $last) = $currentRst;
                $currentNo = (int)$current['dib__rank']; 
                unset($last['dib__rank']);
                unset($first['dib__rank']);
            } elseif($countRec > 1) { // at least 2 records (can happen if eg first and last are same record, or either is same as current)
                list($first, $current) = $currentRst;
                $currentNo = ($first['dib__IsCurrent']) ? (int)$first['dib__rank'] : (int)$current['dib__rank'];
                unset($first['dib__rank']);
                unset($current['dib__rank']);
                $last = $current;
            } else { // 1 record
                $currentNo = (int)$currentRst[0]['dib__rank']; 
                unset($currentRst[0]['dib__rank']);
                $first = $currentRst[0];
                $last = $first;
            }
            unset($last['dib__IsCurrent']);
            unset($first['dib__IsCurrent']);
            // Now get prev, next
            $rankCriteria = '';
            $pkCriteria = '0';
            if($currentNo - 1 < 1) {
                if($currentNo + 1 > $totalCount)                
                    $getNos = 'none';
                else {
                    $rankCriteria = ' OR dib__rank=' . ($currentNo + 1);
                    $getNos = 'next';
                }
            } elseif($currentNo + 1 > $totalCount) {           
                $getNos = 'prev';
                $rankCriteria = ' OR dib__rank=' . ($currentNo - 1);
            } else {
                $rankCriteria = ' OR dib__rank=' . ($currentNo - 1) . ' OR dib__rank=' . ($currentNo + 1);
                $getNos = 'both';
            }
            if($getNos !== 'none') {
            $sql = "SELECT `dib__rank`, primkey1,primkey2, $pkCriteria AS dib__IsCurrent FROM (
                SELECT @rownum:=@rownum+1 AS `dib__rank`, `test_child`.`primkey1`,`test_child`.`primkey2` 
                FROM $fromClause, 
                  (SELECT @rownum:=0) r 
                $criteria
                ORDER BY `test_child`.`primkey1`,`test_child`.`primkey2`) dibt1
                WHERE $pkCriteria $rankCriteria";
                $prevNextRst = dibMySqlPdo::execute($sql, DIB::$CONTAINERDATA[2], $params, false);
                if(dibMySqlPdo::count() === 0)
                    return array('error', '#2 Could not set form navigation counts. Please contact the System Administrator (#3).');
                $countRec = count($prevNextRst);
                if($getNos === 'prev') {
                    unset($prevNextRst[0]['dib__rank']);
                    $prev = $prevNextRst[0];
                    $next = null;
                } elseif($getNos === 'next') {
                    unset($prevNextRst[0]['dib__rank']);
                    $next = $prevNextRst[0];
                    $prev = null;
                } else {
                    list($prev, $next) = $prevNextRst;
                    unset($prev['dib__rank']);
                    unset($next['dib__rank']);                   
                }
                unset($prev['dib__IsCurrent']);
                unset($next['dib__IsCurrent']);
            } else {
                $prev = null;
                $next = null;
            }
        } else { // no records
            $first = null;
            $currentNo = 1;
            $last = null;
            $prev = null;
            $next = null;
        }        
        return array(
            'next' => $next,
            'prev' => $prev,
            'first' => $first,
            'last' => $last,
            'current' => array('current'=>$currentNo),
            'total' => array('total'=>$totalCount)
        );       
    }
    /**
     * Fetches the primary key values of the nth record
     */
    public function getToolbarRecord($position, $activeFilter, $filterParams) {
        $position = (int)$position;
        if(!$position || $position < 0)
            return array('error', 'Position must be a positive integer');
        if($position < 1) $position = 1;
        $params = array();   
        $criteria = '';
        if(!empty($activeFilter)) {
            $criteria = $this->parseFilter($activeFilter, $params, $filterParams);
            if(isset($criteria[0]) && $criteria[0]==='error')
                return $criteria;
            $criteria .= " AND ($criteria)";
            $fromClause = "`test_child`
                LEFT JOIN `test` `test1001` ON `test_child`.`pef_test_id` = `test1001`.`id` 
                ";
        } else 
            $fromClause = "`test_child`";
        if($criteria !== '') $criteria = 'WHERE ' . substr($criteria, 4);
        // Template: SQL statement for MySql to fetch nth record for the Toolbar on Forms. Used in eg CrudPdoTemplate.php.
$sql = "SELECT `test_child`.`primkey1`,`test_child`.`primkey2` 
        FROM $fromClause
        $criteria
        ORDER BY `test_child`.`primkey1`,`test_child`.`primkey2` 
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
            	$fieldExpr = "`test_child`.`$field`";
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
                    $stringValue = trim(substr($stringValue, 0, strlen($stringValue) - 1));
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
                //not like
                elseif (strtolower(substr($stringValue, 0, 7)) === "<>like ") {
                    $fieldCrit .= "$fieldExpr NOT LIKE :f" . $i;
                    $params[':f'.$i] = str_replace('*', '%', substr($stringValue, 7)); //note, this allows user to put * or _ inside $stringValue... which is okay...
                    $fieldType[':f'.$i] = $this->fieldType[$field];                   
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
                elseif (strtolower(substr($stringValue, 0, 5)) === "like ") {
                    $fieldCrit .= "$fieldExpr LIKE :f" . $i;
                    $params[':f'.$i] = str_replace('*', '%', substr($stringValue, 5)); //note, this allows user to put * or _ inside $stringValue... which is okay...
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
                if (!array_key_exists("primkey1", $filterParams))
                    return array ('error',"The primary key fields specified in the request are invalid.");
                $params[":pk1"] = $filterParams["primkey1"];
                $fieldType[":pk1"] = PDO::PARAM_STR;
                if (!array_key_exists("primkey2", $filterParams))
                    return array ('error',"The primary key fields specified in the request are invalid.");
                $params[":pk2"] = $filterParams["primkey2"];
                $fieldType[":pk2"] = PDO::PARAM_STR;
                $criteria = "`test_child`.`primkey1` = :pk1 AND `test_child`.`primkey2` = :pk2 ";
                $sql = "SELECT `test_child`.`primkey1`,`test_child`.`primkey2`,`test_child`.`pef_test_id`,`test_child`.`unique1`,`test_child`.`unique2`,`test_child`.`date_fld`,`test_child`.`notes` 
                , ^^CONCAT(`test1001`.`varchar10_required`, '-', CAST(`test1001`.`has_default` AS CHAR))^^ AS `pef_test_id_display_value`
                         FROM `test_child`                  
                LEFT JOIN `test` `test1001` ON `test_child`.`pef_test_id` = `test1001`.`id` 
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
                	$sql = "SELECT Count(*) AS totalcount FROM `test_child`  ";
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
                LEFT JOIN `test` `test1001` ON `test_child`.`pef_test_id` = `test1001`.`id` 
            ";
                 	} else 
                 		$join = '';
                    if($page === 1 || $countMode==='all'){
	                    $sql = "SELECT Count(*) AS filteredCount FROM `test_child` $join  $criteria";
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
                        	$orderStr .= '`test_child`.`' . $record['property'] . '` ' . $direction . ', ';
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
                `test_child`.`primkey1` AS `Primkey1`, `test_child`.`primkey2` AS `Primkey2`, `test_child`.`unique1` AS `Unique1`, `test_child`.`unique2` AS `Unique2`, `test_child`.`date_fld` AS `Date Fld`, `test_child`.`notes` AS `Notes` 
                , ^^CONCAT(`test1001`.`varchar10_required`, '-', CAST(`test1001`.`has_default` AS CHAR))^^ AS `Test`
            FROM `test_child` 
                LEFT JOIN `test` `test1001` ON `test_child`.`pef_test_id` = `test1001`.`id` 
                 ";
else
    $sql = "SELECT `test_child`.`primkey1`,`test_child`.`primkey2`,`test_child`.`pef_test_id`,`test_child`.`unique1`,`test_child`.`unique2`,`test_child`.`date_fld`,`test_child`.`notes` 
                , ^^CONCAT(`test1001`.`varchar10_required`, '-', CAST(`test1001`.`has_default` AS CHAR))^^ AS `pef_test_id_display_value`
            FROM `test_child` 
                LEFT JOIN `test` `test1001` ON `test_child`.`pef_test_id` = `test1001`.`id` 
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
                $blankRecord = array("primkey1"=>null, "primkey2"=>null, "pef_test_id"=>null, "date_fld"=>null, "unique1"=>null, "unique2"=>null, "notes"=>null);
                $blankRecord = $this->getDefaults($blankRecord, $filterParams);
                if(isset($blankRecord[0]) && $blankRecord[0]==='error')
                	return array('error', $blankRecord[1]);
                // Find offset in $attributes where pkey values from $actionData are in $attributes:
                $actionData = json_decode(urldecode($actionData), true);
                $found = FALSE;
                $k=0;
                if($actionData) {
                	if(!array_key_exists('primkey1,primkey2', $actionData)) {
						Log::err('To use inline adding, the primary key must be included in submitted fields.');
	                	return array('error','Configuration error. Please contact the System Administrator.');
	                }
                    foreach($attributes as $k => $r) {
                        if($r['primkey1,primkey2'] === $actionData['primkey1,primkey2']) {
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
                	if(!array_key_exists('primkey1,primkey2', $actionData)) {
						Log::err('To use inline adding, the primary key must be included in submitted fields.');
	                	return array('error','Configuration error. Please contact the System Administrator.');
	                }
                    foreach($attributes as $k => $r) {
                        if($r['primkey1,primkey2'] === $actionData['primkey1,primkey2']) {
                            $found = TRUE;
                            break;
                        }
                    }
                    if ($found === TRUE) {
                        // Set primary key values to NULL
                        $r['primkey1,primkey2'] = NULL;
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
    public function create(&$attributes, $makeUniqueValues=FALSE, $targetDatabaseId=NULL) {        
        // User can only provide values for fields they have rights to AND where ci.crud_include<>0. 
    	// The rest must get default values - prevent user from updating them, even if provided...
    	// So if (crud_include=0 OR user lacks permissions (and is not relatedrecordsitem)) AND there is a default, then set the default
    	//    else if no default AND field is required, then give an error msg 
    	//    else if field is not required, unset it.
        if(!$targetDatabaseId) $targetDatabaseId = DIB::$CONTAINERDATA[2];
        try { 
            // Check Validation
	        // primkey1 (plain text)
            if(isset($attributes['primkey1'])) {            	
	            if(trim((string)($attributes['primkey1'])) !== '') {
	            } 
	        }  else	            
                return array ('error',"The 'Primkey1 (primkey1)' field is required. Please provide a value.");
	        // primkey2 (plain text)
            if(isset($attributes['primkey2'])) {            	
	            if(trim((string)($attributes['primkey2'])) !== '') {
	            } 
	        }  else	            
                return array ('error',"The 'Primkey2 (primkey2)' field is required. Please provide a value.");
	        // pef_test_id (integer)
            if(isset($attributes['pef_test_id'])) {            	
	            if(trim((string)($attributes['pef_test_id'])) !== '') {
		            if(!is_int((int)$attributes["pef_test_id"]) || !ctype_digit((string)abs($attributes["pef_test_id"])))
		                return array ('error',"The 'Test (pef_test_id)' field must be an integer value.");
	            } 
	        }  else	            
                return array ('error',"The 'Test (pef_test_id)' field is required. Please provide a value.");
	        // unique1 (plain text)
            if(isset($attributes['unique1'])) {            	
	            if(trim((string)($attributes['unique1'])) !== '') {
	            } 
	        }             
	        // unique2 (plain text)
            if(isset($attributes['unique2'])) {            	
	            if(trim((string)($attributes['unique2'])) !== '') {
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
	        // notes (plain text)
            if(isset($attributes['notes'])) {            	
	            if(trim((string)($attributes['notes'])) !== '') {
	            } 
	        }             
            //Check Unique Values for table option 23305
            $criteria = '1=1';
            if(!array_key_exists('unique1', $attributes)) {
                Log::err("Unique value validation failed. Ensure that values for all fields that are involved in checking unique index of pef_table_option.id  are submitted to the server (ie they exist as (hidden) fields in container id 7151), or have defaults in pef_field.");
                return array('error',"Could not perform unique value validation. Please contact the System Administrator.");
            }
            $criteria .= " AND `unique1` = :fk1";
            if(!array_key_exists('unique2', $attributes)) {
                Log::err("Unique value validation failed. Ensure that values for all fields that are involved in checking unique index of pef_table_option.id  are submitted to the server (ie they exist as (hidden) fields in container id 7151), or have defaults in pef_field.");
                return array('error',"Could not perform unique value validation. Please contact the System Administrator.");
            }
            $criteria .= " AND `unique2` = :fk2";
            $sql = "SELECT `test_child`.`primkey1`,`test_child`.`primkey2` AS pkv FROM `test_child` WHERE $criteria";
            $paramsU = array(":fk1" => $attributes["unique1"], ":fk2" => $attributes["unique2"]);
            $rst = dibMySqlPdo::execute($sql, $targetDatabaseId, $paramsU, true);
            if ($rst === FALSE) {
				Log::err("Unique value validation failed. Ensure that values for all fields that are involved in checking unique index of pef_table_option.id 23305 are submitted to the server (ie they exist as fields in container id 7151)");
                return array('error',"Could not perform unique value validation. Please contact the System Administrator.");
            }
            if(dibMySqlPdo::count() > 0) {
                if($makeUniqueValues)
                    // Force unique values - for combinations, only enforce on first 
                    $attributes['unique1'] = SyncFunctions::cleanName($attributes['unique1'],'test_child', '');
                elseif(count($paramsU) > 1)
                    return array('error',"Add record cancelled. The combination of values in 'unique1,unique2' needs to be unique. The record identified by '" . $rst['pkv'] . "' already contains these values.");
                else
                    return array('error',"Add record cancelled. The value in 'unique1,unique2' needs to be unique. The record identified by '" . $rst['pkv'] . "' already contains this value.");
            }
            // All clear - perform the insert...
            $sql = "INSERT INTO `test_child` (";
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
            $pkValues["primkey1"] = $attributes["primkey1"];
            $pkValues["primkey2"] = $attributes["primkey2"];
            // Add pkvalues to $attributes 
            $attributes = array_merge($attributes, $pkValues);             
            $crit = TRUE;
            if ($crit===TRUE) {
                // Insert audit trail record - first set unique_record
                $this->unique_record = 1;
                $this->now = date('Y-m-d H:i:s', time());
                $this->ipAddress = PeffApp::getRealIpAddr();
                // Get pk values
                $recordId='';
                $recordId .= "primkey1=" . $attributes["primkey1"] . ", ";
                $recordId .= "primkey2=" . $attributes["primkey2"];
                foreach ($attributes AS $fieldName => $newValue)
                    $this->auditInsert("create", $fieldName, null, $newValue, 1438, $recordId);
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
        // Updates must occur only for fields that users have rights to AND where ci.crud_include<>0. The rest must retain old values - prevent user from updating them...
        try {    
            // Check Validation - note the PeffApp::jsonDecode() function in the CrudController already ensures $attributes contain no arrays
            // primkey1 (plain text)	        
            if(isset($attributes['primkey1'])) {            	
	            if(trim((string)($attributes['primkey1'])) !== '') {
	            } 
	        }             
            // primkey2 (plain text)	        
            if(isset($attributes['primkey2'])) {            	
	            if(trim((string)($attributes['primkey2'])) !== '') {
	            } 
	        }             
            // pef_test_id (integer)	        
            if(isset($attributes['pef_test_id'])) {            	
	            if(trim((string)($attributes['pef_test_id'])) !== '') {
		            if(!is_int((int)$attributes["pef_test_id"]) || !ctype_digit((string)abs($attributes["pef_test_id"])))
		                return array ('error',"The 'Test (pef_test_id)' field must be an integer value.");
	            } 
	        }             
            // unique1 (plain text)	        
            if(isset($attributes['unique1'])) {            	
	            if(trim((string)($attributes['unique1'])) !== '') {
	            } 
	        }             
            // unique2 (plain text)	        
            if(isset($attributes['unique2'])) {            	
	            if(trim((string)($attributes['unique2'])) !== '') {
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
            // notes (plain text)	        
            if(isset($attributes['notes'])) {            	
	            if(trim((string)($attributes['notes'])) !== '') {
	            } 
	        }             
            // Check if values in $pkValues are indeed pk's and of the right type
            $params = array();
            $fieldType = array();
            if (!array_key_exists("primkey1", $pkValues))
                return array ('error',"The primary key fields specified in the request are invalid.");            
            $params[":pk1"] = $pkValues["primkey1"];
            $fieldType[":pk1"] = PDO::PARAM_STR;            
            if (!array_key_exists("primkey2", $pkValues))
                return array ('error',"The primary key fields specified in the request are invalid.");            
            $params[":pk2"] = $pkValues["primkey2"];
            $fieldType[":pk2"] = PDO::PARAM_STR;            
            $pkCrit = "`test_child`.`primkey1` = :pk1 AND `test_child`.`primkey2` = :pk2";                   
            // Check Unique Values for table option unique_fld(23305)
            $criteria = '1=1 ';           
            if(!array_key_exists('unique1', $attributes)) {
                $uRst = Database::fetch("SELECT `unique1` FROM `test_child` WHERE $pkCrit", $params);
                $attributes['unique1'] = $uRst['unique1'];
            }
            $criteria .= " AND `unique1` = :fk1 ";
            if(!array_key_exists('unique2', $attributes)) {
                $uRst = Database::fetch("SELECT `unique2` FROM `test_child` WHERE $pkCrit", $params);
                $attributes['unique2'] = $uRst['unique2'];
            }
            $criteria .= " AND `unique2` = :fk2 ";
            $criteria .= "AND `primkey1` <> :pk1   AND `primkey2` <> :pk2";
            $sql = "SELECT `test_child`.`primkey1`,`test_child`.`primkey2` AS pkv FROM `test_child` WHERE $criteria";
            $paramsU = array(":fk1" => $attributes["unique1"], ":fk2" => $attributes["unique2"]);
            $rst = dibMySqlPdo::execute($sql, DIB::$CONTAINERDATA[2], $paramsU + $params, true);
            if ($rst === FALSE)
                return array('error',"Could not perform unique value validation. Please contact the System Administrator.");
            if( dibMySqlPdo::count() > 0) {
                if(count($paramsU) > 1)
                    return array('error',"Update record cancelled. The combination of values in 'unique1 , unique2' needs to be unique. Another record already contains the same combination of values.");
                else
                    return array('error',"Update record cancelled. The value in 'unique1,unique2' needs to be unique. Another record already contains the same value.");
            }            
            $crit = $pkCrit;
            // Get record's existing (old) values
            $sql = "SELECT `test_child`.* 
		            FROM `test_child`
                LEFT JOIN `test` `test1001` ON `test_child`.`pef_test_id` = `test1001`.`id` 
		                WHERE $crit";
            $recordOld = $this->getRecordByPk($sql, $pkValues);
            if (count($recordOld) === 0)
                return array('error',"Error! The record to be updated has been deleted or denied by the permission system.");
            $crit .= "";
            // Get field-level criteria if applicable
            $validAttributes = $attributes;
            $sql = 'UPDATE `test_child` SET ';
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
                $this->now = date('Y-m-d H:i:s', time());
                $this->ipAddress = PeffApp::getRealIpAddr();
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
                        $this->auditInsert("update", $fieldName, $recordOld[$fieldName], $newValue, 1438, $recordId);
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
        return array('error',"Sorry, the permission system restricts you from deleting records from this table.");
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
            if (!array_key_exists("primkey1", $pkValues))
                return array ('error',"The primary key fields specified in the request are invalid.");
            $params[":pk1"] = $pkValues["primkey1"];
            $fieldType[":pk1"] = PDO::PARAM_STR;
            if (!array_key_exists("primkey2", $pkValues))
                return array ('error',"The primary key fields specified in the request are invalid.");
            $params[":pk2"] = $pkValues["primkey2"];
            $fieldType[":pk2"] = PDO::PARAM_STR;
            //`id` = :pk0
            $pkCrit = "`test_child`.`primkey1` = :pk1 AND `test_child`.`primkey2` = :pk2";  
            // Fields for duplication (include required fields and exclude expression, file, image & exclusion fields etc.)
            $sql = "SELECT 1 AS to_prohibit_error_if_none, `primkey1`, `primkey2`, `pef_test_id`, `unique1`, `unique2`, `date_fld`, `notes`
                    FROM `test_child` WHERE $pkCrit";
            //Note - create code handles unique values :-)  
            dibMySqlPdo::setParamsType($fieldType, DIB::$CONTAINERDATA[2]);        
            $record = dibMySqlPdo::execute($sql, DIB::$CONTAINERDATA[2], $params, true);
            if($record === FALSE || count($record) < 1){
				Log::err(count($record) . "SQL error while fetching values to duplicate for dibtestChildForm.\r\nSQL: $sql\r\nPARAMS:" . json_encode($params) . "\r\nERROR:" . Database::lastErrorAdminMsg());
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
							Log::err("Unusual Sql error... No result returned in SOURCE db when attempting to find original values for a LOOKUP query while duplicating container dibtestChildForm records. Note, unless this query returns a value, the code will not work.\r\nSQL: " . $value[0] . "\r\n\PARAMS: " . json_encode($args));
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
										Log::err("Sql error, or no result returned when attempting to run a LOOKUP query against database id $targetDatabaseId while duplicating container 'dibtestChildForm' records using the 'create' directive. Note, unless the create code succeeds, the whole operation will fail:\r\nLAST SQL ERROR:" . Database::lastErrorAdminMsg() . "\r\nPARAMS: " . json_encode($value[4]));
		    							return array('error', "Configuration error found while duplicating records. Please contact the System Administrator.");
		    						}
		    						$result = array('id'=>$result);
		    					} else {
		    						Log::err("Sql error, or no result returned when attempting to run a LOOKUP query against database id $targetDatabaseId while duplicating container 'dibtestChildForm' records. Note, unless this query returns a value, the code will not work.\r\nSQL: " . $value[1] . "\r\n\PARAMS: " . json_encode($args));
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
					$sql = 'UPDATE test_child SET `' . $args[0] . "` = :value WHERE $pkCrit";					
					PeffApp::$array['DuplicateRecords']['test_child*'.$args[0].'*'.$value] = array($args[1], $args[2], $sql, $params);
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
        if (!array_key_exists("primkey1", $pkValues))
            return array ('error',"The primary key fields specified in the request are invalid.");
        $params[":pk1"] = $pkValues["primkey1"];
        $fieldType[":pk1"] = PDO::PARAM_STR;
        if (!array_key_exists("primkey2", $pkValues))
            return array ('error',"The primary key fields specified in the request are invalid.");
        $params[":pk2"] = $pkValues["primkey2"];
        $fieldType[":pk2"] = PDO::PARAM_STR;
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
     * @var string $crudType create/read/update/delete
     * @var string $fieldName name of field
     * @var array $oldValue string containing old values
     * @var array $newValue string containing new values
     * @var integer $tableId table_id
     * @var string $tableName name of table
     * @var integer $recordId primary key value
     */
    protected function auditInsert($crudType, $fieldName, $oldValue, $newValue, $tableId, $recordId) {
        $sql = "INSERT INTO `pef_audit_trail` 
             (action, pef_table_id, pef_container_id, table_name, record_id, date_time, ip_address, field_name, old_value, new_value, pef_login_id, username, unique_record) 
             VALUES ('$crudType', $tableId, 7151, 'test_child', :recordId, :dateTime, :ipAddress, :fieldName, :oldValue, :newValue, :loginId, :username, :unique_record)";
        Database::execute($sql, array(':dateTime'=>$this->now, ':fieldName'=> $fieldName, ':recordId'=>$recordId, 
        	':oldValue'=>$oldValue, ':newValue'=>$newValue, ':username'=>DIB::$USER['username'], ':unique_record'=>$this->unique_record, 
        	':ipAddress'=>$this->ipAddress, ':loginId'=>DIB::$USER['id']),
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
    	return array('Primkey1', 'Primkey2', 'Test', 'Date Fld', 'Unique1', 'Unique2', 'Notes' );
    }
    public function getSqlParts() {
		return array('model' => 'Table',
					 'containerName' => "Tablexx2xxdibtestChildForm",
					 'selectFields' => "`test_child`.`primkey1`,`test_child`.`primkey2`,`test_child`.`pef_test_id`,`test_child`.`unique1`,`test_child`.`unique2`,`test_child`.`date_fld`,`test_child`.`notes`",
				     'selectSqlFields' => trim("
            ", ", \r\n"),
				     'selectSqlDisplay' =>  trim("
            ", ", \r\n"),
				     'selectTableDisplay' => trim("
                , ^^CONCAT(`test1001`.`varchar10_required`, '-', CAST(`test1001`.`has_default` AS CHAR))^^ AS `pef_test_id_display_value`
            ", ", \r\n"),          
                     'from' => trim("`test_child`                  
                LEFT JOIN `test` `test1001` ON `test_child`.`pef_test_id` = `test1001`.`id` 
             ", ", \r\n")
        );
	}
} // end Class                
            