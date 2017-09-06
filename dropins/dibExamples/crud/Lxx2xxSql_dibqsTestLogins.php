<?php
/* Obtain dropdown list data from pef_sql statements */
class Lxx2xxSql_dibqsTestLogins {    
    public $displayErrorsInBrowser = FALSE;
	public $count = 0;
	protected $dbh;
    //***TODO for security - Need to know display_field's type - its the only field that's going to be filtered on...
    function __construct() {
        $dbClassPath = (DIB::$DATABASES[DIB::$ITEMLISTDATA[3]]['systemDropin']) ? DIB::$SYSTEMPATH.'dropins'.DIRECTORY_SEPARATOR : DIB::$DROPINPATHDEV;
        require_once $dbClassPath.'setData/dibMySqlPdo'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'dibMySqlPdo.php';
    }
    /**
	* Returns an array of (id, display_value) pairs generated by the sql query linked to a specific $containerItemId
	* @param int $containerItemId 
	* @param int $page
	* @param int $page_size
	* @param string $query used with SQL LIKE to filter values. If of the form p__x it is assumed that x is the primary key value of a specific record's value to return
	* @param string $activeFilter name of the filter to apply
	* @param array $filterParams associative array of possible parameter values (normally the submissionData array)
	* @param string $phpFilter SQL Where clause that may contain PDO params - if present it overrides where_clause and activeFilter
	* @param array $phpFilterParams associative array of PDO parameter values for $phpFilter
	* @param string $pageNoFromValue - NOT ACTIVE
	* @param boolean $showUsedOnly return only values used in the database
	* 
	* @return array of (id, display_value) pairs
	*/
    public function getList($containerItemId, $page=1, $page_size, $query=null, $activeFilter=null, $filterParams=null, $phpFilter='', $phpFilterParams=array(), $pageNoFromValue=null, $showUsedOnly = false) {
    	//***TODO Hack which hopefully will not be needed in > ExtJs 6 anymore... (first time an empty dropdown is clicked then start=-25 and page=0)
    	if(!$page || $page<=0) $page = 1;
        if(!$page_size || $page_size<0)
            return array('error','Invalid request. Please contact the System Administrator');
        //* A bound dropdown can only store one value... this value will reference a single primary key field, or a single field of a composite primary key 
        $order_by = '';
        $criteria = '';
        $params = array();
        $group_by = '';
        $permCrit = '';
        // Set sql parts                  
        $totalSql = "SELECT Count(*) AS totalcount FROM pef_login ";             
        $sql = "    id AS `id` ,  username AS id_display_value ";
        $from_clause = "pef_login";
        $display_field = "username";
        $pkey = "id";        
        $order_by = ($phpFilter==='') ? " ORDER BY username" : ''; 
        $criteria = "username like 'test%'";  
        // Process user filter
        if($query) {
            $query = urldecode($query);
            if(substr($query,0,3)==='p__') {
                // ***TODO p__undefined should not be sent by client, since pkey value could be = 'undefined'. Rather use p__*dib__undef*
                $params = ($query === 'p__null' || $query === 'p__undefined') ? array(":f1" => NULL) : array(":f1" => substr($query,3));
                $criteria = " $pkey = :f1";
                if($permCrit !== '') { // If where_clause had any pdo parameters that could be for permission purposes, include the criteria 
					$criteria .= ' AND (' . $permCrit . ')';
					$params = array_merge($params, $permParams);
				}
            } else {
                $params[":f1"] = $query."%";
                if($criteria === '')
                    $criteria = $display_field . ' LIKE :f1';
                else
                    $criteria = "($criteria) AND ($display_field LIKE :f1)";
            }
        }
        // *** NOTE: if phpFilter present it overrides where_clause and activeFilter
        if(!empty($phpFilter)) {
        	$criteria = $phpFilter; 
        	$params = $phpFilterParams;
        }
        /*if($pageNoFromValue) {
        	// Determine page no of pkey value. First need to find corresponding display value
        	$rst = dibMySqlPdo::execute("SELECT $display_field AS dib__Display FROM $from_clause WHERE $pkey = :pkey", 1, array(':pkey'=>$pageNoFromValue), TRUE);
            if($rst === FALSE)
                return array('error', "Error! Could not read dropdown data information. Please contact the System Administrator");
			if($criteria) 
				$criteria = " WHERE $criteria AND $display_field <= :dib__Display $order_by";
			else 
				$criteria = " WHERE $display_field <= :dib__Display $order_by";
			$params[':dib__Display'] = $rst['dib__Display'];
		} else*/ if($criteria) $criteria = ' WHERE ' . $criteria;
        //if($pageNoFromValue) return array(array(), ceil($filteredCount / $page_size)); 
            // Template: MySql - Get SQL for paging purposes for database engines that support the LIMIT keyword. Used in eg CrudPdoTemplate.php.
    if($page === 1)
        $limit = ' LIMIT ' . $page_size;
    else
        $limit = ' LIMIT ' . ($page_size * ($page - 1)) .  ', ' . $page_size;    
$sql = "SELECT $sql FROM $from_clause $criteria $group_by $order_by $limit";
        // Get the data
        $records = dibMySqlPdo::execute($sql, 1, $params);
        // Get $totalCount
		if($group_by === '') 
    		$filterCountRst = dibMySqlPdo::execute($totalSql . $criteria, DIB::$ITEMLISTDATA[3], $params, TRUE);	            
        else 
            $filterCountRst = dibMySqlPdo::execute("SELECT FOUND_ROWS() AS totalcount", DIB::$ITEMLISTDATA[3], array(), true);
		if($filterCountRst === FALSE)
            return array('error', "Error! Could not read dropdown data information. Please contact the System Administrator");
        $filteredCount = intval($filterCountRst["totalcount"]);			
 //-----------------        
        // Add hard-coded rows if any (to first page only). Semicolon delimitted list of values where every two values forms a id and display_value pair)
        if($records === FALSE)
            return array('error', "Error! Could not read dropdown data information. Please contact the System Administrator");
        return array($records, $filteredCount);
    }
}
?>