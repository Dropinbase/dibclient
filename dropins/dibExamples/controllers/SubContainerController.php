<?php

class SubContainerController extends Controller {	
    
    function testSubmissionData($containerName, $itemEventId, $submissionData=null, $triggerType=null, $itemId=null, $itemAlias=null) {
        
        $s = PeffApp::flattenArray($submissionData);
        $str = '';
        $prevArrayType = '';
        // Build string
        foreach($s AS $key => $v) {
        	if($v === NULL) 
        		$v = 'NULL';
        	elseif(is_string($v))
        		$v = $v; // Do nothing (avoid ints and strings being seen as bools)
        	elseif(is_bool($v))
        		$v = ($v) ? 'TRUE' : 'FALSE';
        		
            // Detect if base key changed... find delimiter...
            $arrayType = substr($key, 0, strpos($key, '_') + 4);
            if($arrayType !== $prevArrayType)
                // Add extra space
                $str .= "\r\n";
                
            $prevArrayType = $arrayType;
            $str .= $key . ' = ' . $v . "\r\n";
        }
        
        if(DIB::$DEFAULTFRAMEWORK === 'setNgMaterial')
            $str = str_replace("\r\n", '; ', $str);

        if($itemAlias === 'btnSubmissionDataGrid')
        	return $this->validResult(NULL, $str, 'dialog');
        
        ClientFunctions::addAction($actionList, 'setValue', array('dibIgnore_SubmitData' => $str));

        return $this->validResult($actionList);
    }
}
?>