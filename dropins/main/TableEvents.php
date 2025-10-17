<?php

/**
 * Processes events caught by global table crud triggers in CrudController.php and Crud.php
 */
class TableEvents {

    /**
     * Manages/relays any crud event directed to it
     * Note the parameters sent by reference that can be updated
     * 
     * @param string $containerName name of the containerName involved
     * @param string $trigger one of: before create, after create, before update, after update, before delete, after delete
     * @param string $source the record source of the container: table name, or id of pef_sql record involved
     * @param array $attributes record field names and values
     * @param array $primaryKeyData primary key names and values
     * @param int $databaseId id of database involved - useful for calls to the Database class
     * @param object $crudClass object pointing to container's crud class
     * @param mixed $crudResult result of crud operation (note, could be array('error', $msg) in case crud operation failed)
     * @param array $clientData client data submitted from browser
     * @return mixed TRUE on success, or array('error', $msg) if operation must be cancelled (message is returned to user)
     */
    public static function trigger($containerName, $trigger, $source, &$attributes, &$primaryKeyData, $databaseId, &$crudClass, &$crudResult, $clientData) {
        
        // If an error occured, do nothing - the CrudController takes care of it
        if (isset($primaryKeyData[0]) && $primaryKeyData[0] === 'error')
            return TRUE;

        /* Here you can add any "catch-all" code */

		
        // Code to pull in table event PHP classes in the 'tableEvents' subfolder, which have the actual event code in them for the current table
        if (isset($crudResult['Id'])) 
            $attributes['Id'] = $crudResult['Id'];
        
        $path = realpath(dirname(__FILE__)).DIRECTORY_SEPARATOR.'tableEvents'.DIRECTORY_SEPARATOR;
        if (file_exists($path.$source.'.php')) {
            include_once $path.'table.php';
            include_once $path.$source.'.php';

            // convert eg. after update -> afterUpdate
            $trigger = str_replace(' ', '', ucwords($trigger)); 
            $trigger[0] = strtolower($trigger[0]);

            if (class_exists($source)) {
                $sourceObject = new $source();
                if (method_exists($sourceObject, $trigger)) {
                    return $sourceObject->$trigger($containerName, $attributes, $clientData, $primaryKeyData);
                }
            }
        }
        return true;
    }

}


