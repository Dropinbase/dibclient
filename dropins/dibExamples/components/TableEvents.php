<?php

/**
 * Processes events caught by table-level crud triggers in CrudController.php and Crud.php
 * Table-level crud events are specified in the pef_table.crud_events field. The following options are available:
 * 	bc(before create), ac(after create), bu(before update), au(after update), bd(before delete), ad(after delete)
 * Eg au,ad,bc
 * 
 * All containers linked to these tables are affected. 
 * Make sure that permfiles are regenerated after changes to the pef_table.crud_events field. The perm files store the trigger to activate crud events.
 * Note, sql queries that update tables without going through the Crud or the CrudController classes will not trigger this class.
 * This class must be positioned in the relevant containers' module dropin's components folder, or the parent folder of the components folder.
 * If a container is not linked to a module dropin, then the /runtime/crud or /runtime folders.
 */
class TableEvents {

    /**
     * Processes any table-level crud event directed to it
     * Note the parameters sent by reference that can be updated
     * 
     * @param string $containerName name of the containerName involved
     * @param string $trigger one of: before create, after create, before update, after update, before delete, after delete
     * @param string $source the record source of the container: table name, or id of pef_sql record involved
     * @param array $attributes record field names and values
     * @param array $primaryKeyData primary key names and values
     * @param int $databaseId id of database involved - useful for calls to Database class
     * @param object $crudClass object pointing to container's crud class
     * @param mixed $crudResult result of crud operation (note, could be array('error', $msg) in case crud operation failed)
     * @param array $submissionData submissionData available on Dropinbase CRUD events
     * 
     * @return mixed TRUE on success, or array('error', $msg) if operation must be cancelled (message is returned to user)
     */
    public static function trigger($containerName, $trigger, $source, &$attributes, &$primaryKeyData, $databaseId, &$crudClass, &$crudResult, $submissionData=null) {
        // The following line can be used to see contents provided:
        
        //Log::w($containerName, $trigger, $source, $attributes, $primaryKeyData, $databaseId, $crudClass, $crudResult, $submissionData);
        
        /* If 'after create', 'after delete' or 'after update' is involved we normally want to handle the case of an error happening before the Table Event trigger was called:
        
        // If an error occured, do nothing
        if (isset($primaryKeyData[0]) && $primaryKeyData[0] === 'error')
        	return true;
        */
        
        // Now let's handle 'before update' and 'before delete', the two events configured on the test_child table
        
        // Check if unique indexes are satisfied:
        $msg = null;
        if($trigger === 'before update') {
        	// Check combination of pkey fields:
        	$sql = "SELECT primkey1 FROM test_child WHERE primkey1=:p1 AND primkey2=:p2 AND NOT (primkey1=:pkey1 AND primkey2=:pkey2) ";
        	$params = array(':p1'=>$attributes['primkey1'],
        					':p2'=>$attributes['primkey2'],
        					':pkey1'=>$primaryKeyData['primkey1'],
                            ':pkey2'=>$primaryKeyData['primkey2']);
                            
            $rst = Database::fetch($sql, $params);
            
        	if(Database::count()>0) {
        		$p1 = $attributes['primkey1'];
        		$p2 = $attributes['primkey2'];
				$msg = "The combination of primary key values '$p1' and '$p2' is already in use. Please try again.";
			}
        
        } elseif ($trigger === 'before delete') {
			$msg = "Caught a deletion and blocking it...";
			// Note the if statement below handles the cancelation of the crud action 
		}
        
        // Returning an array where the first element is 'error' cancels the operation and sends the message to the user...
        // Note that cancelling 'after' events will not cancel the crud operation, except 'after readone' and 'after readmany'...
        if ($msg)
        	return array('error', "This is the custom Table-level error message...<br>$msg");
        
        // If all is well, return true so that crud operations can proceed.
        return true;

        // *** Note that the following means can also be used to send messages or actions to the client:
        // *** THE CODE BELOW DOES NOT EXECUTE DUE TO THE return true LINE ABOVE ... 
        
        // By setting PeffApp::clientMsg we set (and override) any other pending system message that may have been configured
        PeffApp::setClientMsg("Container Events triggered to change values in the 'name' field", 'notice', 4000);
        
        // We can also send client actions by adding them to the PeffApp::$clientActions array...
        ClientFunctions::addAction(PeffApp::$clientActions, 'OpenUrl', array('url'=>'/nav/dibDashboard')); 

    }
}


