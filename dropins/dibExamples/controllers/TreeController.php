<?php

/**
 * This controller is used for CRUD operations for dibTree
 *   
 */
class TreeController extends Controller {
    
    /**
	* Private function used to find parents of child items that match a given query
	* @param undefined $id item Id
	* @param undefined $parentId parent item Id
	* @param undefined $node original node
	* 
	* @return
	*/
    protected function getParent($id, $parentId, $node) {
        if ($parentId === $node)
            return $id;
          
        $sql = "SELECT id, pef_item_id FROM pef_item WHERE id = :id";
        $treeItems = Database::fetch($sql, array(':id'=>$parentId));
        
        if (is_null($treeItems['pef_item_id'])) {
            if ($node === 'root' && isset($treeItems['id']))
                return $treeItems['id'];
            else
                return -999;
        } else
            return $this->getParent($treeItems['id'], $treeItems['pef_item_id'], $node);
    }   
    
    /**
     * Returns json representing child items of a given $node
     * @param string $containerName used by DIB to automatically verify user's permissions (do not give this parameter a default value)
     * @param array $submissionData
     * @param type $node node for which child items must be returned 
     * @param type $query search text entered by user
     */
    function read($containerName, $submissionData = null, $node = null, $query=NULL) {    	
           
        if(empty($node))
            return $this->invalidResult('Invalid request to server. Could not refresh the treeview.');
                
        $params = array();        
        if(trim($query) === '') $query = NULL;

        if ($query) {
            if ($node == (string)(int)$node || $node === 'root') {
                // We need to get a list of id's whose children somewhere down the line have a name like $query...
                // Loop through records that are like query
                $critList = array();
                $sql = "SELECT id, pef_item_id FROM pef_item 
                		WHERE pef_container_id = $containerId AND ^^CONCAT(`name`,id)^^ LIKE :query";
                $treeItems = Database::execute($sql, array(':query' => '%'.$query.'%'));
                
                // Now use a recursive function to find each of these records' parent at position $node
                foreach ($treeItems as $key => $record) {
                    if ($node === $record['id'])
                        continue;
                    elseif (is_null($record['pef_item_id'])) {
                        if($node === 'root')
                            $critList[] = $record['id'];
                        else
                            continue;
                    } else {
                        $id = $this->getParent($record['id'], $record['pef_item_id'], $node);
                        if (array_search($id, $critList)===FALSE)
                            $critList[] = $id;
                    }
                }
                // Got the list - use sql IN operator...
                $criteria = ($critList) ? 'ci.id IN (' . implode(',', $critList) . ')' : 'ci.id IS NULL' ;
            } else
                return $this->invalidResult('Invalid Request');
      
        } elseif ($node === 'root')
            $criteria = "ci.pef_item_id IS NULL";
            
        elseif (is_numeric($node)) {
            $criteria = "ci.pef_item_id = :id";
            $params = array(':id'=>$node);
            
        } else
            return $this->invalidResult('Invalid request');
        
        /* Note the following field names:
        
        id - must be unique (int or text)
        text - display value
        icon - svg 
        leaf - 1 => has no children; 0 => has children (causes the + to show)
        checked - TRUE/FALSE - whether the node is checked when the tree loads, or not
        expanded - TRUE/FALSE - whether the node (with children) is expanded when the tree loads, or not 
        can_have_children - TRUE/FALSE - whether other nodes may be dropped on the current node
        
        */
        
        $sql = "SELECT ci.id, REPLACE(^^CONCAT(ci.`name`, ' (', CAST(ci.id AS CHAR), ')')^^, ' ','') AS `text`, cp.icon, 0 AS  `leaf`, 
                   ci.pef_item_id, count(ci2.id) AS childCounter
                FROM pef_item ci LEFT JOIN pef_component cp ON ci.pef_component_id = cp.id
                  LEFT JOIN pef_container c ON ci.pef_container_id = c.id
                  LEFT JOIN pef_item ci2 ON ci2.pef_item_id = ci.id
                WHERE $criteria AND c.name = 'dibexContainerDropins' 
                GROUP BY ci.id, REPLACE(^^CONCAT(ci.`name`, ' (', CAST(ci.id AS CHAR), ')')^^, ' ',''), cp.icon, ci.pef_item_id, ci.order_no
                ORDER BY ci.order_no";    
       
        $treeItems = Database::execute($sql, $params);
        
        if(Database::count()===0 && $node === 'root') // Handle case of empty tree
            $treeItems = array(0=>array('id'=>'*DIB EMPTY TREE*', 'text'=>'Drop here', 'icon'=>null, 'leaf'=>0));
        else {    
	        foreach ($treeItems as $key => $record) {
	            $treeItems[$key]['leaf'] = ($treeItems[$key]['childCounter'] > 0) ? FALSE : TRUE; 
	            $treeItems[$key]['checked'] = FALSE;
	            $treeItems[$key]['expanded'] = (isset($record['pef_item_id'])) ? FALSE : TRUE; // only expand the first level
	            $treeItems[$key]['can_have_children'] = TRUE;
	        }
        }
        
        return $this->validResult(null, null, null, null, null, $treeItems);
    }
    
    /**
     * Delete a record. 
     * Also see the dibDesignerDeleteItem function in /droinbase/controller/DibTasksController.php 
     * 
     */
    public function delete($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
        if(!isset($submissionData['submitCheckedItem.self']['0']['id']))
        	return $this->invalidResult('First select one or more items and try again.');        	
        	
        $itemId = $submissionData['submitCheckedItem.self']['0']['id'];        
        if(!$itemId || ($itemId !=(string)(int)$itemId)) 
        	return $this->invalidResult('First select one or more items and try again.');
        
        $rst = Database::execute('SELECT id FROM pef_item WHERE pef_item_id = ' . $itemId);
        
        // Handle single item
        if(Database::count() === 0 && !isset($submissionData['submitCheckedItem.self']['1']))
        	return $this->validResult(NULL, "Item $itemId targetted for deletion.", 'notice', 4000);
        
        elseif (Database::count() > 1) {
			if(isset($submissionData['submitCheckedItem.self']['1']))
				return $this->validResult(NULL, "Multiple items, with some child items targetted for deletion.", 'notice', 4000);
			else
        		return $this->validResult(NULL, "Item $itemId with its child items targetted for deletion.", 'notice', 4000);
        
        } else
			return $this->validResult(NULL, "Multiple items selected for deletion", 'notice', 4000);
		
    }

    /**
     * Drop a specific node onto another
     * @param string $containerName used by DIB to automatically verify user's permissions (do not give this parameter a default value)
     * @param string $submissionData
     * @param string $fromContainer origin container
     * @param string $dropPosition 'after'/'before'/'append'
     * @param string $dropNodeId target node id
     * @param string $nodeId id of node being dropped
     * @param string $parentId 'root'/integer
     */
    function drop($containerName, $submissionData=null, $fromContainer, $dropPosition, $dropNodeId, $nodeId, $parentId) {
        
        $dropPosition = ($dropPosition === 'append') ? 'appended to' : 'dropped ' . $dropPosition;
        	
        return $this->validResult(NULL, "Node id '$nodeId' $dropPosition '$dropNodeId'. Parent id of '$dropNodeId' is '$parentId'.");
    }
    
    /**
     * An odd function
     * @param string $containerName
     */
    function oddFunc($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
    	$checked = (isset($submissionData['submitItemAlias.self']['checkSomething'])) ? 'checked' : 'not checked';
        return $this->validResult(NULL, "Check box is $checked. itemEventId '$itemEventId', containerName '$containerName'");  
    }    
	
    
}
