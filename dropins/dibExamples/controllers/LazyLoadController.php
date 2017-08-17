<?php

class LazyLoadController extends Controller {
	
	public function loadInFieldset($containerName, $itemEventId, $itemAlias) {
		// Only allow lazy loading of specified tags
		if(!in_array($itemAlias, array('buttonPHPA', 'buttonPHPAAA', 'buttonPHPCCC')))
			return $this->invalidResult ('Invalid request.');
		
		$tag = str_replace('buttonPHP', '', $itemAlias);
		
		// Add lazy load action to $actionList
    	$params = array('tag' => $tag, 'activateTabAlias'=>null, 'removeExistingItems' => 'lazy', 'parentItemAlias'=>'parentFieldset');
    	$actionList[] = ClientFunctions::dropinAction('dibGlobals', 'lazyLoadChildren', null, $params, 'parentFieldset');
		
		// Send $actionList to client api for execution
		return $this->validResult($actionList);
	}
	
	public function loadInColParent($containerName, $itemEventId, $itemAlias) {
		// Only allow lazy loading of specified tags
		if(!in_array($itemAlias, array('buttonPHPFFF', 'buttonPHPDDD', 'buttonPHPD')))
			return $this->invalidResult ('Invalid request.');
		
		$tag = str_replace('buttonPHP', '', $itemAlias);
		
		// Add lazy load action to $actionList
    	$params = array('tag' => $tag, 'activateTabAlias'=>null, 'removeExistingItems' => 'lazy', 'parentItemAlias'=>'colParent');
    	$actionList[] = ClientFunctions::dropinAction('dibGlobals', 'lazyLoadChildren', null, $params, 'colParent');
		
		// Send $actionList to client api for execution
		return $this->validResult($actionList);
	}
}
