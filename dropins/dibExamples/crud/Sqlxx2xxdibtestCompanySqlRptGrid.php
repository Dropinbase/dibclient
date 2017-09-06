<?php
class Sqlxx2xxdibtestCompanySqlRptGrid {
    public $page = 1;
	public $page_size = 50;
    public $fieldType = array();
    public $sqlFields = array("id"=>"`t`.`id`", "name"=>"`t`.`name`", "chinese_name"=>"`t`.`chinese_name`", "parent_company"=>"`t`.`parent_company_id`", "parent_company_display_value"=>"^^CONCAT(parentC.`name`, ' (' , parentC.`id`, ')')^^", "website"=>"`t`.`website`", "icon"=>"`t`.`icon`");
    public $pkeys = '';
    function __construct() {
        $dbClassPath = (DIB::$DATABASES[DIB::$CONTAINERDATA[2]]['systemDropin']) ? DIB::$SYSTEMPATH.'dropins'.DIRECTORY_SEPARATOR : DIB::$DROPINPATHDEV;
        require_once $dbClassPath.'setData/dibMySqlPdo'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'dibMySqlPdo.php';
        set_time_limit(180);
    }
    /**
     * parses json $gridFilter string and returns a SQL WHERE clause string, and PDO parameters (passed by reference)
     */
    public function parseGridFilter($gridFilter, &$params=array()) {
        //Eg [{"property":"name","value":"g & >e"},{"property":"notes","value":"< z &  w"}]
        $allCrit = "";
        $i = 0;
        foreach($gridFilter as $row) {
        	if(!isset($row['property']) || !isset($row['value']))
        		return array('error',"Filter criteria was not submitted in correct format. Please contact the System Administrator.");
        	$field=$row['property'];
        	$value=$row['value'];
            if(!array_key_exists($field, $this->sqlFields))
                return array('error',"An unknown fieldname was used in filter criteria. Please contact the System Administrator.");
            if(array_key_exists($field.'_display_value', $this->sqlFields))
            	$field = $this->sqlFields[$field.'_display_value'];
            else
            	$field = $this->sqlFields[$field];
            $subparts = explode ('&', str_replace('|', '|&', $value));
            $fieldCrit = "";
            foreach($subparts as $stringValue)  {
                $stringValue = trim($stringValue);
                 if($stringValue === '')
                	return array('error', "The filter criteria syntax is incorrect. Please try again.");
                if ($fieldCrit !== '')
                    $fieldCrit .= $conjunction; // $conjunction is found in prev. loop
                if (substr($stringValue, -1) === "|") {
                    $conjunction = ' OR ';
                    $stringValue = substr($stringValue, 0, strlen($stringValue) - 1);
                } else
                    $conjunction = ' AND ';
                $intValue = trim($stringValue, "=!>< ");
                if ($intValue === '')
                	return array('error', "The filter criteria syntax is incorrect. Please try again.");
                //is null
                if (strtolower(substr($stringValue, 0, 4)) === "null") {
                    $fieldCrit .= "$field IS NULL";                
                }
                //is not null
                elseif (strtolower(substr($stringValue, 0, 6)) === "<>null") {
                    $fieldCrit .= "$field IS NOT NULL";                
                }
                //is empty
                elseif (strtolower(substr($stringValue, 0, 5)) === "empty") {
                    $fieldCrit .= "$field = ''";                
                }
                //is not empty
                elseif (strtolower(substr($stringValue, 0, 7)) === "<>empty") {
                    $fieldCrit .= "$field <> ''";                    
                }
                //equal to
                elseif (substr($stringValue, 0, 1) === "=") {
                    $fieldCrit .= "$field = :f" . $i;
                    $params[":f".$i] = $intValue;
                }
                //not equal to
                elseif (substr($stringValue, 0, 2) === "!=" || substr($stringValue, 0, 2) === "<>") {
                    $fieldCrit .= "$field != :f" . $i;
                    $params[":f".$i] = $intValue;
                }
                //greater and equal
                elseif (substr($stringValue, 0, 2) === ">=") {
                    $fieldCrit .= "$field >= :f" . $i;
                    $params[":f".$i] = $intValue;
                }
                //greater than
                elseif (substr($stringValue, 0, 1) === ">") {
                    $fieldCrit .= "$field > :f" . $i;
                    $params[":f".$i] = $intValue;
                }
                //smaller and equal
                elseif (substr($stringValue, 0, 2) === "<=") {
                    $fieldCrit .= "$field <= :f" . $i;
                    $params[":f".$i] = $intValue;
                }
                //smaller than
                elseif (substr($stringValue, 0, 1) === "<") {
                    $fieldCrit .= "$field < :f" . $i;
                    $params[":f".$i] = $intValue;
                }                
                //like
                elseif (strtolower(substr(ltrim($stringValue), 0, 5)) === "like ") {
                    $fieldCrit .= "$fieldExpr LIKE :f" . $i;
                    $params[':f'.$i] = str_replace('*', '%', substr(ltrim($stringValue), 5)); //note, this allows user to put * or _ inside $stringValue... which is okay...
                }
                //anything else
                else {
                    $fieldCrit .= "$field LIKE :f" . $i;
                    $params[":f".$i] = str_replace('*', '%', $stringValue).'%'; //note, this allows user to put * or _ inside $stringValue... which is okay...
                }
                $i++;
            }              
            if ($fieldCrit !== "")
                $allCrit .= "(" . $fieldCrit . ") AND ";
        }
        // Remove last ' AND '
        return substr($allCrit, 0, -4);
    }  
    public function parseFilter($activeFilter, &$params, &$filterParams) {
        $criteria = ''; 
        if(strpos($activeFilter, 'dibexSubContainers_testCompanySqlRptGrid') === 0) {
            $crit = "t.parent_company_id = :submitItemAlias_parent_parentCompanyId"; 
            if (array_key_exists("submitItemAlias_parent_parentCompanyId",$filterParams)) {
                $params[':submitItemAlias_parent_parentCompanyId'] = $filterParams["submitItemAlias_parent_parentCompanyId"];
                // Remove from array so that Related Records' parseFilterArray can add any other params, e.g. if Related Records and activeFilter both apply
                // unset($filterParams["submitItemAlias_parent_parentCompanyId"]);
            } else {
                $value = EvalCriteria::evalParam(':submitItemAlias_parent_parentCompanyId', $filterParams);
                if(is_array($value))
                    //return array('error',"Error! The filter parameter 'submitItemAlias_parent_parentCompanyId' for filter 'dibtestCompanyConsultantPopup' on dibtestCompanySqlRptGrid is missing from submitted values.");
                    $crit = '1 = 2'; // We're returning no records since if eg submitCheckedItems is used and there are no checked records then this error will occur.
                else 
                    $params[':submitItemAlias_parent_parentCompanyId'] = $value;
            }            
            $criteria .= " AND ($crit) ";
        }
        if($criteria==='') return  array('error',"Error! The active filter could not be found.");
        return substr($criteria, 4);
	}
    public function read($page, $page_size, $order, $gridFilter=null, $filterParams=array(), $activeFilter=null, $phpFilter=null, $phpFilterParams=array(), $countMode='all') {
        try {
            if(!$page || !$page_size || $page<0 || $page_size<0)
                return array('error','Invalid request. Please contact the System Administrator');
            $display_field='';
            $order_by = '';
            $sql = '';
            $params = array();             
            $whereCrit = '';
            $havingCrit = '';
            $userCrit = '';
            $criteria = "";
            // php generated / developer filter
            if($phpFilter) { 
                $params = $phpFilterParams;
                $criteria .= " AND ($phpFilter) ";
            }
            // activeFilter
            if(!empty($activeFilter)){  
                $crit = $this->parseFilter($activeFilter, $params, $filterParams);
                if(is_array($crit)) return $crit;
                $criteria .= " AND $crit";
            }
            if($page === 1 || $countMode==='all'){
            //$path = DIB::$RUNTIMEPATH . 'tmp' . DIRECTORY_SEPARATOR . '$totalCount_7159' . session_id() . '.txt';
            //if($this->page > 1 && file_exists($path)) { // read from cache
	        //} else { // xxxxxx xxxxxx yyy^var^yyyyyy
				$totalCrit = ($criteria) ? ' WHERE ' . ltrim($criteria, ' AND') : '';                
	            $sqlTotal =  "SELECT Count(*) AS Counter FROM test_company t LEFT JOIN test_company parentC ON t.parent_company_id = parentC.id $totalCrit";
	            $totalRst = dibMySqlPdo::execute($sqlTotal, DIB::$CONTAINERDATA[2], $params);
	            if($totalRst === FALSE)
	                return array('error', "Error! Could not read list information. Please contact the System Administrator");
	            $totalCount = $totalRst[0]['Counter']; //NOTE postgres returns lowercase fieldnames irrespective of how you specified them!
	            $totalRst = null;
	            // Store in cache
	            //file_put_contents($path, $totalCount)
			//}
            } else
            	$totalCount = null;
            // user grid filters
            $filteredCount = $totalCount;
            if ($gridFilter) { 
                $crit = $this->parseGridFilter($gridFilter, $params);
                if(is_array($crit))
                    return $crit; // error occured, $userCrit contains error message.
                else
                    $criteria .= " AND ($crit)";
            }
            // Build ORDER BY clause - Order of priority:  order by in user's request, pef_container, pef_sql 
            $orderStr = '';
            if($order) {
                $orderStr = " ORDER BY ";
                foreach($order as $key => $record) {
                    if(!isset($record['property']) || !array_key_exists($record['property'], $this->sqlFields)) 
                        return array('error', "An invalid fieldname was used in order criteria. Please contact the System Administrator.");
                    if(isset($record['direction'])) {
                        if (strtoupper($record['direction']) !== 'ASC' && strtoupper($record['direction']) !== 'DESC')
                            return array('error', "An invalid sort direction was used in order criteria. Please contact the System Administrator.");
                        else
                            $direction = $record['direction'];
                    } else
                        $direction = '';
                    $orderStr .= $this->sqlFields[$record['property']] . ' ' . $direction . ', ';
                }            
                $orderStr = substr($orderStr, 0, strlen($orderStr) - 2);
            }
            if ($criteria) $whereCrit = ' WHERE ' . substr($criteria, 4);
            if ($havingCrit) $havingCrit = ' HAVING ' . substr($havingCrit, 4);
            $this->page = $page;
            $this->page_size = $page_size;
            $this->filterParams = $filterParams;
            // Template: MySql - Handle the pef_sql limit clause for paging purposes for database engines that support the LIMIT keyword. Used in eg SqlPdoTemplate.php.
        if($this->page === 1)
            $limit = ' LIMIT ' . $this->page_size;
        else
            $limit = ' LIMIT ' . ($this->page_size * ($this->page - 1)) .  ', ' . $this->page_size;
            // Template: MySQL - Main Sql statement to fetch many records of a pef_sql query (with no Group By clause) for database engines that recognise LIMIT. Used in eg SqlPdoTemplate.php. 
		$sql = "SELECT `t`.`id`,`t`.`name`,`t`.`chinese_name`,`t`.`parent_company_id` as parent_company,
^^CONCAT(parentC.`name`, ' (' , parentC.`id`, ')')^^ AS `parent_company_display_value` ,`t`.`website`,`t`.`icon` 
        	FROM test_company t LEFT JOIN test_company parentC ON t.parent_company_id = parentC.id 
        	$whereCrit $orderStr $limit"; 
            $records = dibMySqlPdo::execute($sql, DIB::$CONTAINERDATA[2], $params);
            if($records === FALSE)
                return array('error', "Error! Could not obtain data. Please contact the System Administrator");
            if ($gridFilter && ($this->page===1 || $countMode==='all')) {
            //$path = DIB::$RUNTIMEPATH . 'tmp' . DIRECTORY_SEPARATOR . '$filteredCount_7159' . session_id() . '.txt';
            //if($this->page > 1 && file_exists($path)) { // read from cache
	        //} else { // xxxxxx xxxxxx yyy^var^yyyyyy
				$totalCrit = ($criteria) ? ' WHERE ' . ltrim($criteria, ' AND') : '';                
	            $sqlTotal =  "SELECT Count(*) AS Counter FROM test_company t LEFT JOIN test_company parentC ON t.parent_company_id = parentC.id $totalCrit";
	            $totalRst = dibMySqlPdo::execute($sqlTotal, DIB::$CONTAINERDATA[2], $params);
	            if($totalRst === FALSE)
	                return array('error', "Error! Could not read list information. Please contact the System Administrator");
	            $filteredCount = $totalRst[0]['Counter']; //NOTE postgres returns lowercase fieldnames irrespective of how you specified them!
	            $totalRst = null;
	            // Store in cache
	            //file_put_contents($path, $filteredCount)
			//}
            } 
            return array($records, $filteredCount, $totalCount, array());
        } catch (Exception $e) {
			return array('error',"Error! Could not obtain data. Please contact the System Administrator");
        }
    }    
    public function getCaptions() {
    	return array();
    }
    public function getSqlParts() {
		return array('model' => 'Sql',
				     'containerName' => "Sqlxx2xxdibtestCompanySqlRptGrid",
					 'primary_field' => "",
				     'display_field' => "",
				     'select' => "`t`.`id`,`t`.`name`,`t`.`chinese_name`,`t`.`parent_company_id` as parent_company,
^^CONCAT(parentC.`name`, ' (' , parentC.`id`, ')')^^ AS `parent_company_display_value` ,`t`.`website`,`t`.`icon`",
				     'from' =>   "test_company t LEFT JOIN test_company parentC ON t.parent_company_id = parentC.id",
				     'where' =>  "", 
				     'group_by' =>"",
				     'having' =>  "",
				     'order_by' => "",
				     'limit' =>  ""
        );
	}
}
    