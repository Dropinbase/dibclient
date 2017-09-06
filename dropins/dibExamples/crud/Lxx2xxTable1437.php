<?php
class Lxx2xxTable1437 {    
    function __construct() {
        $dbClassPath = (DIB::$DATABASES[DIB::$ITEMLISTDATA[3]]['systemDropin']) ? DIB::$SYSTEMPATH.'dropins'.DIRECTORY_SEPARATOR : DIB::$DROPINPATHDEV;
        require_once $dbClassPath.'setData/dibMySqlPdo'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'dibMySqlPdo.php';
    }
    /**
	* Returns the specified $page of data from a list consisting of primary key - display value pairs
	* @param undefined $containerItemId - the id of the pef_item record for which the list is returned
	* @param undefined $page - the page number
	* @param undefined $page_size - the page size
	* @param undefined $query - optional string used for filtering records: display values must contain this string
	* @param undefined $activeFilter - optional named filter to be applied
	* @param undefined $filterParams - parameters for the named filter
	* @param undefined $pageNoFromValue - if specified, then the page no is returned where this value can be found in the list
	* @param boolean $showUsedOnly whether only distinct used data is showed in filter list.
	* 
	* @return
	*/
    public function getList($containerItemId, $page=1, $page_size=30, $query=null, $activeFilter=null, $filterParams=null, $phpFilter='', $phpFilterParams=array(), $pageNoFromValue=null, $showUsedOnly = false) {
        try {
        	//***TODO ExtJs bugfix - hopefully not needed in ExtJs 6.x anymore... (first time an empty dropdown is clicked then start=-25 and page=0)
        	if(!$page || $page<=0) $page = 1;
            if(!$page_size || $page_size<0)
                return array('error','Invalid request. Please contact the System Administrator');
            // * Note, a bound dropdown can only store one value... this value will reference a single primary key field, or a single field of a composite primary key 
            $criteria = ''; // ***TODO (minor) - note that activeFilter (if present) overrides phpFilter
            $params = array();
            $sql = "`test`.`id` AS id, CONCAT(`test`.`varchar10_required`, '-', CAST(`test`.`has_default` AS CHAR)) AS `id_display_value`";
            $from_clause = "`test`";
            $display_field = "CONCAT(`test`.`varchar10_required`, '-', CAST(`test`.`has_default` AS CHAR))";
            $pkey = "`test`.`id`";
            $order_by = ($phpFilter === '') ? " ORDER BY CONCAT(`test`.`varchar10_required`, '-', CAST(`test`.`has_default` AS CHAR))" : '';
            $totalSql = "SELECT count(*) AS totalcount FROM `test` ";
            // Add sql to show only used values (especially for filter dropdowns) - does not work for mssql...
            if($showUsedOnly) { 
            	$itemTables = array(155266=>"`test_child`.`pef_test_id`");
            	if(isset($itemTables[$containerItemId])) {
            		$foreignParts = explode('.', $itemTables[$containerItemId]);
					$addSql = " INNER JOIN " . $foreignParts[0] . " dib___F ON dib___F." . $foreignParts[1] . " = `test`.`id` ";
					if(stripos(substr($sql, 0, strpos($sql, '`')), 'DISTINCT') === FALSE) $sql = 'DISTINCT ' . $sql;
					$from_clause .= $addSql;
					$totalSql = str_replace('SELECT count(*)', "SELECT count(DISTINCT $pkey)", $totalSql) .  $addSql;
				}
			}
            //***TODO - add a check for when no query could be found matching parameters - display helpful error to Admin user 
            if($query) {
                $query = urldecode($query);
                if(substr($query,0,3)==='p__') {
                    // ***TODO p__undefined should not be sent by client, since pkey value could technically be = 'undefined'. Rather use p__*dib__undef*
                    $params = ($query === 'p__null' || $query === 'p__undefined') ? array(":f1" => NULL) : array(":f1" => substr($query,3));
                    $criteria = " $pkey = :f1";
                } else { 
                    $params[":f1"] = $query.'%';
                    if($criteria === '')
                        $criteria = $display_field . ' LIKE :f1';
                    else
                        $criteria = "($criteria) AND ($display_field LIKE :f1)";
                }
            }
            if($pageNoFromValue) {
            	// Determine page no of pkey value. First need to find corresponding display value
            	$rst = dibMySqlPdo::execute("SELECT CONCAT(`test`.`varchar10_required`, '-', CAST(`test`.`has_default` AS CHAR)) AS dib__Display FROM `test` WHERE `test`.`id` = :pkey", DIB::$ITEMLISTDATA[3], array(':pkey'=>$pageNoFromValue), TRUE);
	            if($rst === FALSE)
	                return array('error', "Error! Could not read dropdown data information. Please contact the System Administrator");
				if($criteria) 
					$criteria = " WHERE $criteria AND CONCAT(`test`.`varchar10_required`, '-', CAST(`test`.`has_default` AS CHAR)) <= :dib__Display $order_by";
				else 
					$criteria = " WHERE CONCAT(`test`.`varchar10_required`, '-', CAST(`test`.`has_default` AS CHAR)) <= :dib__Display $order_by";
				$params[':dib__Display'] = $rst['dib__Display'];
			} 
			// *** NOTE: if phpFilter present it overrides where_clause and activeFilter
	        if(!empty($phpFilter)) {
	        	$criteria = $phpFilter; 
	        	$params = $phpFilterParams;
	        }
			if($criteria) $criteria = ' WHERE ' . $criteria;
            $databaseId = DIB::$ITEMLISTDATA[3];
            // Get Count
            $filterCountRst = dibMySqlPdo::execute($totalSql . $criteria, $databaseId, $params, TRUE);
            if($filterCountRst === FALSE)
                return array('error', "Error! Could not read dropdown data information. Please contact the System Administrator");
            $filteredCount = intval($filterCountRst["totalcount"]);
            if($pageNoFromValue) return array(array(), ceil($filteredCount / $page_size)); 
            $group_by = '';
                // Template: MySql - Get SQL for paging purposes for database engines that support the LIMIT keyword. Used in eg CrudPdoTemplate.php.
    if($page === 1)
        $limit = ' LIMIT ' . $page_size;
    else
        $limit = ' LIMIT ' . ($page_size * ($page - 1)) .  ', ' . $page_size;    
$sql = "SELECT $sql FROM $from_clause $criteria $group_by $order_by $limit";
            $records = dibMySqlPdo::execute($sql, $databaseId, $params);
            if($records === FALSE)
                return array('error', "Error! Could not obtain data for the list. Please contact the System Administrator");
            return array($records, $filteredCount);
        } catch (Exception $e) {        	
			return array('error', "Error! Could not obtain data for the list. Please contact the System Administrator");
        }
    }
}