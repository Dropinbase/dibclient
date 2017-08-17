<?php

class RunController extends Controller {
	/**
	* Basic Hellow World function... NOTE the security warning below (see ***)	
	*/
	function helloWorld($containerName, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null){
		// *** WARNING : note that itemEventId has been removed from the parameter list above to allow calls from any button on this container.
		NodeJs::msgHeader('Items', "Hello world from NodeJs!", 5000, 'success');
        return $this->validResult(NULL, 'Hello world from json response to action.','dialog');
	}
	
	function testAsync($containerName, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null){
		//tests passing a variable to the testasync.php script, and basic dib functionality in running async envornment
		//change throwException to true to ask testasync to throw an exception.
		//the thrown exception should give a nodejs popup and log the actual error to Async.log(default log if one is not given)
		DibFunctions::async('testasync.php', array('throwException'=>false));
		return $this->validResult(NULL);
	}	
	
	function testpopupreply($containerName, $containerEventId, $submissionData = null, $triggerType = null, $containerId = null){
        if(!isset($submissionData['submitPromptInput']))
            return $this->invalidResult('Invalid answer');
        
        $answer = $submissionData['submitPromptInput'];
        NodeJs::popupd($answer);
        return $this->validResult(NULL);
	}
	
	function testClick($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null){
		
		return $this->validResult(NULL);	
	}
			
	function setValues($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null){
		if ($itemAlias === 'resetDisabled') {//DONE
	    	// Enables all items that were disabled by test functions below
			//$executeList['parentCompanyId'] = array(array('exec'=>'setDisabled','args'=>array(FALSE)));
	        //$actionList[] = ClientFunctions::itemMethods($executeList);
	        ClientFunctions::addMethod($actionList, 'parentCompanyId', array('setDisabled'=>FALSE));
            
	        //$executeList['parent.globalSearchContainer'] = array(array('exec'=>'setDisabled','args'=>array(FALSE)));
	        //$actionList[] = ClientFunctions::itemMethods($executeList);
	        ClientFunctions::addMethod($actionList,'parent.globalSearchcontainer',array('setDisabled'=>FALSE));
            
	        //$executeList['testCompanyForm.subConsultants.mobile'] = array(array('exec'=>'setDisabled','args'=>array(FALSE)));
	        //$actionList[] = ClientFunctions::itemMethods($executeList);
	         ClientFunctions::addMethod($actionList,'testCompanyForm.subConsultants.mobile',array('setDisabled'=>FALSE));
             
	        //$executeList['testForm.varchar10'] = array(array('exec'=>'setDisabled','args'=>array(FALSE)));
	        //$actionList[] = ClientFunctions::itemMethods($executeList);
	        ClientFunctions::addMethod($actionList,'testForm.varchar10',array('setDisabled'=>FALSE));
            
	        // Using Json response to execute the actions
	        return $this->validResult($actionList, 'Items enabled!');
		
		} elseif ($itemAlias === 'refreshSelf') {//DONE
	    	// self - references current container
			//$itemValues['value'] = 'self';
        	//$actionList[] = ClientFunctions::action('RefreshContainer', $itemValues);
            ClientFunctions::addAction($actionList, 'RefreshContainer', array('value' => 'self'));
	        NodeJs::msgHeader('test',"Refreshing 'self'.", 3000);
	        
	        // Using Json response to execute the actions
	        return $this->validResult($actionList);
		
		} elseif ($itemAlias === 'refreshParent') {
	    	// parent - references parent container (button is on child container: testCompanyForm.subConsultants ... Tools menu)
			//$itemValues['value'] = 'parent';
        	//$actionList[] = ClientFunctions::action('RefreshContainer', $itemValues);
            ClientFunctions::addAction($actionList, 'RefreshContainer', array('value' => 'parent'));
	        NodeJs::msgHeader('test',"Refreshing 'parent'.", 3000);
	         
	        // Using Json response to execute the actions
	        return $this->validResult($actionList);
		
		} elseif($itemAlias === 'setChineseName') {//DONE
			// ALIAS - references item on current container			
	        //$itemValues['chinese_name'] = '**';
	        //$actionList[] = ClientFunctions::action('appendToField', $itemValues);
	        ClientFunctions::addAction($actionList,'appendToField', array('chinese_name' =>'*xx*' ));
            
	        //$executeList['parentCompanyId'] = array(array('exec'=>'setDisabled','args'=>array(TRUE)));
	        //$actionList[] = ClientFunctions::itemMethods($executeList);
	        ClientFunctions::addMethod($actionList, 'parentCompanyId', array('setDisabled'=>TRUE));
            
	        // Using NodeJs to execute the actions:
	        NodeJs::msgHeader('test',"Record's primary key value: " . $submissionData['submitItemAlias.self']['companyId'], 3000);
	        NodeJs::action($actionList, $containerName);
	        
	        // Return something valid to stop client waiting for response and causing exception
	        return $this->validResult(NULL);
	    
	    } elseif ($itemAlias === 'setParentChineseName') {
	    	// parent.ALIAS - references item on parent container (button is on child container: testCompanyForm.subConsultants ... Tools menu)
			//$itemValues['parent.chinese_name'] = '!!';
	        //$actionList[] = ClientFunctions::action('appendToField', $itemValues);
	        ClientFunctions::addAction($actionList,'appendToField', array('parent.chinese_name' =>'!!' ));
            
	        //$executeList['parent.parentCompanyId'] = array(array('exec'=>'setDisabled','args'=>array(TRUE)));
	        //$actionList[] = ClientFunctions::itemMethods($executeList);
	        ClientFunctions::addMethod($actionList, 'parent.parentCompanyId', array('setDisabled'=>TRUE));
	        // Using Json response to execute the actions
	        return $this->validResult($actionList, "Parent's primary key value: " . $submissionData['submitItemAlias.parent']['companyId']);
		
		} elseif($itemAlias === 'setGlobalSearchContainer') {//DONE
	    	// parent.ALIAS - references item on parent container (button is on testCompanyForm... Actions/References menu)
			//$itemValues['parent.globalSearchContainer'] = 'testCompanyGrid';
		    //$actionList[] = ClientFunctions::action('setValue', $itemValues);
	        ClientFunctions::addAction($actionList,'setValue', array('parent.globalSearchContainer' =>'testCompanyGrid' ));
            
	        //$executeList['parent.globalSearchContainer'] = array(array('exec'=>'setDisabled','args'=>array(TRUE)));
	        //$actionList[] = ClientFunctions::itemMethods($executeList);
	        ClientFunctions::addMethod($actionList, 'parent.globalSearchContainer', array('setDisabled'=>TRUE));
            
	        // Get value
	        $value = (isset($submissionData['submitItemAlias.parent']['globalSearchContainer'][0])) 
	                 ? $submissionData['submitItemAlias.parent']['globalSearchContainer'][0] : 'not set';
	        // Using Json response to execute the actions
	        return $this->validResult($actionList, "Global Search value: " . $value);
		
		} elseif($itemAlias === 'setContainerChineseName') { //DONE
	    	// CONTAINERNAME.ALIAS - references item on specified container
			//$itemValues['testCompanyForm.chinese_name'] = '$$';
	        //$actionList[] = ClientFunctions::action('appendToField', $itemValues);
	        ClientFunctions::addAction($actionList,'appendToField', array('testCompanyForm.chinese_name' =>'$$' ));
            
	        //$executeList['testCompanyForm.parentCompanyId'] = array(array('exec'=>'setDisabled','args'=>array(TRUE)));
	        //$actionList[] = ClientFunctions::itemMethods($executeList);
	        ClientFunctions::addMethod($actionList, 'testCompanyForm.parentCompanyId', array('setDisabled'=>TRUE));
            
	        // Using Json response to execute the actions
	        $value = $submissionData['submitItemAlias.testCompanyForm']['companyId'];
	        return $this->validResult($actionList, "testCompanyForm's primary key value: " . $value);
		
		} elseif($itemAlias === 'setPopup_varchar10') { //DONE
			// CONTAINERNAME.ALIAS - references item on specified container			
	    	
	    	// NOTE Optional parameter for OpnUrl: &waituntilopen=false - Default is to wait before other actions are performed. This will cause not to wait.
	        ClientFunctions::addAction($actionList,'OpenUrl', array('value' =>'/nav/testForm?primary_id=1&waituntilopen=true' ));
            
			//$itemValues['testForm.varchar10'] = '##';
	        //$actionList[] = ClientFunctions::action('appendToField', $itemValues);
	        ClientFunctions::addAction($actionList,'appendToField', array('testForm.varchar10' =>'##' ));
            
	        //$executeList['testForm.varchar10'] = array(array('exec'=>'setDisabled','args'=>array(TRUE)));
	        //$actionList[] = ClientFunctions::itemMethods($executeList);
	        ClientFunctions::addMethod($actionList, 'testForm.varchar10', array('setDisabled'=>TRUE));
            
	        // Using Json response to execute the actions
	         $value = (isset($submissionData['submitItemAlias.dibBase1']['globalSearchContainer'][0])) 
	                 ? $submissionData['submitItemAlias.dibBase1']['globalSearchContainer'][0] : 'not set';
	        return $this->validResult($actionList, "Global Search value on dibBase1: " . $value);
	        
		} elseif($itemAlias === 'setContainerChildAlias') { //DONE
			// CONTAINERNAME.ALIAS1.ALIAS2 - references item (ALIAS2) on child-container 
			//   where the container component has alias ALIAS1 on the specified container CONTAINERNAME		
	    	
	    	//$itemValues['testCompanyForm.subConsultants.mobile'] = '11';
	        //$actionList[] = ClientFunctions::action('appendToField', $itemValues);
	        ClientFunctions::addAction($actionList,'appendToField', array('testCompanyForm.subConsultants.mobile' =>'11' ));
            
	        //$executeList['testCompanyForm.subConsultants.mobile'] = array(array('exec'=>'setDisabled','args'=>array(TRUE)));
	        //$actionList[] = ClientFunctions::itemMethods($executeList);
	        ClientFunctions::addMethod($actionList, 'testCompanyForm.subConsultants.mobile', array('setDisabled'=>TRUE));
	        // Using Json response to execute the actions
	        $value = $submissionData['submitItemAlias.testCompanyForm.subConsultants']['id'];
	        return $this->validResult($actionList, "testCompanyForm's primary key value: " . $value);
	        
		} elseif($itemAlias === 'testSetClose') { 
			// CONTAINERNAME.ALIAS - testing closing, and setting values etc. on another container from a popup form	
	    	
	    	//$itemValues['value'] = 'self';
        	//$actionList[] = ClientFunctions::action('CloseContainer', $itemValues);
        	ClientFunctions::addAction($actionList,'CloseContainer', array('value' =>'self' ));
            
        	//$itemValues['value'] = '/nav/testCompanyForm?primary_id=1';
	        //$actionList[] = ClientFunctions::action('OpenUrl', $itemValues);
            ClientFunctions::addAction($actionList,'OpenUrl', array('value' =>'/nav/testCompanyForm?primary_id=1' ));
            
	    	//$itemValues['testCompanyForm.chinese_name'] = '^^';
	        //$actionList[] = ClientFunctions::action('appendToField', $itemValues);
	        ClientFunctions::addAction($actionList,'appendToField', array('testCompanyForm.chinese_name' =>'^^' ));
            
	        //$executeList['testCompanyForm.parentCompanyId'] = array(array('exec'=>'setDisabled','args'=>array(TRUE)));
	        //$actionList[] = ClientFunctions::itemMethods($executeList);
            ClientFunctions::addMethod($actionList, 'testCompanyForm.parentCompanyId', array('setDisabled'=>TRUE));
            
	        // Using Json response to execute the actions
	        $value = $submissionData['submitItemAlias.self']['id'];
	        $value2 = (isset($submissionData['submitItemAlias.testCompanyForm']['companyId'])) ? $submissionData['submitItemAlias.testCompanyForm']['companyId'] : '';
	        return $this->validResult($actionList, "Popup form's id: " . $value . ". testCompanyForm's id: " . $value2);
	        
		}
	    
	}

    public function testHotReload($containerName) { 
        ClientFunctions::addActionNg($actionList,'HotReloadContainer', array( 'containerName' => $containerName));
        NodeJs::action($actionList,$containerName);
        return $this->validResult($actionList);
    }
    
    public function testContainerEvent($containerName, $containerEventId, $submissionData = null, $triggerType = null, $containerId = null) {
        $triggerType = urldecode($triggerType);
        NodeJs::msgHeader('Items', "containerEventId: $containerEventId triggerd on '$triggerType' on container: $containerName ($containerId)", 5000, 'success');
        // Always return a result to PeffApp
        return $this->validResult(NULL);
    }
    
    // Basic function call examples
    function basicActionsAndMethods($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
        
        // The following are examples of calling ExtJs' api methods. addMethod can be used to set ExtJs configs too (see Set Active Filter button)
       
        ClientFunctions::addMethod($actionList, 'subEmployees', array('bringToFront' => TRUE));
        // In the following line, note the extra 'parent' infront of the dropdownlookup's alias to reference the internal hbox. 
        // (To reference the extjs dropdown only (inside the dropdownlookup's internal hbox), use 'parentCompanyId')
        // Also note the "'extra'" quotes around text, and that multiple methods can be executed on the same item:
        ClientFunctions::addMethod($actionList, 'parentparentCompanyId', array('setFieldLabel' => "'New label'", 'setDisabled' => TRUE)); 

 		/*
        // Alternative way of accomplishing the same:
        $executeList['subEmployees'] = array(array('exec'=>'bringToFront','args'=>array(TRUE)));  
        $executeList['parentparentCompanyId'] = array(
        										    array('exec'=>'setDisabled','args'=>array(TRUE)),
        										    array('exec'=>'setFieldLabel','args'=>array("'New label'"))
        										);  
        $actionList[] = ClientFunctions::itemMethods($executeList);
        */
        
        ClientFunctions::addAction($actionList, 'setValue', array('website' => 'www.dropinbase'));
        // Note multiple fields can be targetted with the same action:
        ClientFunctions::addAction($actionList, 'appendToField', array('chinese_name' => '**', 'website' => '.com'));
        
        /*
        // Alternative way of accomplishing the same:
		$itemValues['website'] = 'www.dropinbase.com';
		$actionList[] = ClientFunctions::action('setValue', $itemValues);
		$itemValues2['chinese_name'] = '**';
		$itemValues2['website'] = '.com';
		$actionList[] = ClientFunctions::action('appendToField', $itemValues2);
		*/
		
        //$actionList['nextActionItem'] = 114;
        //$actionList['itemEventId'] = $itemEventId;
        
        // Set next action to call
        $actionList['submitUrl'] = '/dropins/dibExamples/run/helloWorld';        
        $actionList['responseType']='actions';
        
        return $this->validResult($actionList);
    }
    
    /**
	* Testing submission of values from grid fields	when use selects value on parent_company_contact_id dropdown
	*/
	function gridDropdownSelect($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null)
	{
		$params = json_encode($submissionData);
        return $this->validResult(NULL, $params,'dialog');
	}
	
    
    function button4_1FilterNone($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
        // Remove the active filters...
        $executeList['subChart'] = array(
            array('exec'=>'activeFilter',
                  'args'=>array(''))
        );
        $executeList['subTestGoogleMap'] = array(
            array('exec'=>'activeFilter',
                  'args'=>array(''))
        );
        $actionList[] = ClientFunctions::itemMethods($executeList);  
        
        ClientFunctions::addAction($actionList,'RefreshContainer', array('value' =>'subChart' ));
        ClientFunctions::addAction($actionList,'RefreshContainer', array('value' =>'subTestGoogleMap' ));
        
        return $this->validResult($actionList);
    }
    
    function button4_2FilterUsers($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
        /* For the Google Map subcontainner, the Js code looks like this by default:
            'itemAlias' : 'subTestGoogleMap',
            'activeFilter' : 'testCompanyForm_subTestGoogleMap',
      		'containerSettingFilter' : {
      			'testCompanyForm_subTestGoogleMap' : "submitItemAlias_parent_companyId",
      			'testCompanyParentUsers2' : "submitItemAlias_parent_parentCompanyId"
      		}
      	   
      	   All we need to do is switch the activeFilter config to point to
      	   one of the listed filter names or '' (remove filter), and then refresh the subcontainer
        */
        $executeList['subTestGoogleMap'] = array(
                array('exec'=>'activeFilter',
                      'args'=>array("testCompanyForm_subTestGoogleMap"))
        );
        $executeList['subChart'] = array(
                array('exec'=>'activeFilter',
                      'args'=>array("testCompanyForm_subChart"))
        ); 
         
        $actionList[] = ClientFunctions::itemMethods($executeList);
        
        ClientFunctions::addAction($actionList,'RefreshContainer', array('value' =>'subChart' ));
        ClientFunctions::addAction($actionList,'RefreshContainer', array('value' =>'subTestGoogleMap' ));
        
        return $this->validResult($actionList);
    }
    
    function button4_3FilterParentUsers($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
        /* For the Google Map subcontainner, the Js code looks like this by default:
            'itemAlias' : 'subTestGoogleMap',
            'activeFilter' : 'testCompanyForm_subTestGoogleMap',
      		'containerSettingFilter' : {
      			'testCompanyForm_subTestGoogleMap' : "submitItemAlias_parent_companyId",
      			'testCompanyParentUsers2' : "submitItemAlias_parent_parentCompanyId"
      		}
      	   
      	   All we need to do is switch the activeFilter config to point to 
      	   one of the listed filter names or '' (remove filter), and then refresh the subcontainer
        */
        $executeList['subTestGoogleMap'] = array(
        		array('exec'=>'activeFilter',
              		  'args'=>array("testCompanyParentUsers2"))
        );
        $executeList['subChart'] = array(
                array('exec'=>'activeFilter',
                      'args'=>array("testCompanyParentUsers"))
        ); 
        
        $actionList[] = ClientFunctions::itemMethods($executeList);
        
        ClientFunctions::addAction($actionList,'RefreshContainer', array('value' =>'subChart' ));
        ClientFunctions::addAction($actionList,'RefreshContainer', array('value' =>'subTestGoogleMap' ));
        
        return $this->validResult($actionList);       
    }    
    
    function button5_1SetPieChart($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
        $sql = "UPDATE dib_highchart_setting s INNER JOIN dib_highchart_default d ON s.dib_highchart_default_id = d.id INNER JOIN pef_container c ON s.pef_container_id = c.id
                SET s.value = 'pie'
                WHERE c.name = :containerName AND d.name = 'chart.type'";
        Database::execute($sql, array(':containerName'=>'testCompanyChart'));

        //$itemValues['value'] = 'subChart';
        //$actionList[] = ClientFunctions::action('RefreshContainer', $itemValues); 
        ClientFunctions::addAction($actionList,'RefreshContainer', array('value' =>'subChart'));
        return $this->validResult($actionList);
    }
    
    function button5_2SetMixedChart($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
        $sql = "UPDATE dib_highchart_setting s INNER JOIN dib_highchart_default d ON s.dib_highchart_default_id = d.id INNER JOIN pef_container c ON s.pef_container_id = c.id
                SET s.value = 'areaspline'
                WHERE c.name = :containerName AND d.name = 'chart.type'";
        Database::execute($sql, array(':containerName'=>'testCompanyChart'));
        
        //$itemValues['value'] = 'subChart';
        //$actionList[] = ClientFunctions::action('RefreshContainer', $itemValues);
        ClientFunctions::addAction($actionList,'RefreshContainer', array('value' =>'subChart'));
           
        return $this->validResult($actionList);        
    }
    
    function testSubmissionData($containerName, $itemEventId, $submissionData=null, $triggerType=null, $itemId=null, $itemAlias=null) {
        
        $s = $this->getSubmitValues($submissionData);
        $str='';
        $prevArrayType = '';
        // Build string
        foreach($s AS $key => $v) {
        	if(is_null($v)) 
        		$v = 'NULL';
        	elseif(is_string($v))
        		$v = $v; // do nothing (avoid ints and strings being seen as bools)
        	elseif(is_bool($v))
        		$v = ($v) ? 'TRUE' : 'FALSE';
        		
            //Detect if base key changed... find delimiter...
            $arrayType = substr($key,0, strpos($key,'_')+4);
            if($arrayType !==$prevArrayType)
                // Add extra space
                $str .= "\r\n";
                
            $prevArrayType = $arrayType;
            $str .= $key . ' = ' . $v . "\r\n";
        }
        
       // $itemValues['submissionDataText'] = $str;
       // $actionList[] = ClientFunctions::action('appendToField', $itemValues);
        ClientFunctions::addAction($actionList,'appendToField', array('submissionDataText' =>$str ));

        return $this->validResult($actionList);
    }
    
    public function testGetValue($args) {
        $greeting = 'Hello ' . $args['testvar3__name'];
        return array('lower'=>1000, 'upper'=>4000, 'greeting'=>$greeting);
    }
    
    public function yesNoPopUp($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
        $rst = Database::fetch("SELECT pie.id FROM pef_item_event pie INNER JOIN pef_item i ON pie.pef_item_id = i.id WHERE i.name='buttonAppendTextNodeJs'");
        $eventId = $rst['id'];
        NodeJs::msgPopup('Test Popup', "Click Yes to execute hidden button action, and No to execute 'hello world'.",
                         'itemAlias','hiddenButton','action',"/dropins/dibExamples/Run/helloWorld/$containerName/$eventId",true);
           
        return $this->validResult(NULL);
    }
    
    public function hiddenButton($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
        
        NodeJs::msgHeader('hidden button',"Hidden button's event was fired",5000,'success');
           
        return $this->validResult(NULL);
    }
    
    public function promptQuestion($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
        // prompt($title, $text, $actionType, $action, $forceInput=FALSE, $cancelActionType=NULL, $cancelAction=NULL, $regexRules='', $errorMsg='', $loginId=null)
        NodeJs::prompt('Math Test',"What is 1 + 2?",'itemAlias','hiddenPromptButton',TRUE);
        return $this->validResult(NULL);
    }
    
    public function promptAnswer($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
        
        if(!isset($submissionData['submitPromptInput']))
            return $this->invalidResult('Invalid answer');
        
        $answer = $submissionData['submitPromptInput'];

        If(trim($answer) !== '3')
            NodeJs::msgHeader('hidden button',"I'm afraid you need some tution!",5000,'warning');
        else
            NodeJs::prompt('Math Test',"Well done! And what is 2 + 3? If you cancel you will start the quiz again :)...",
                           'action',"/dropins/dibExamples/Run/helloWorld/$containerName",FALSE,'itemAlias','promptQuestion');
         
        return $this->validResult(NULL);
    }

    public function docx_click($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
    	 $e = new Eleutheria();
    	 $Parameters = array("aaa"=>'YIP A DIP', 'bbb'=>'ooopey');
    	 $e->mergeTmpl('docx', "C:\\temp\\test.docx", "C:\\temp\\testout.docx", false, false, $Parameters);
         return $this->validResult(NULL);
    }
    
    function fixDocs($containerName, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null){
		$sql = "SELECT id, name, renamed_to FROM pef_component 
				WHERE pef_material_dropin_id = 62 AND name<>renamed_to AND renamed_to is not null and renamed_to<>''";
		$rst = Database::execute ($sql);
		
		foreach($rst as $key=>$r) {
			
			$path = DIB::$SYSTEMPATH . 'dropins' . DIRECTORY_SEPARATOR . 'setNgMaterial' .DIRECTORY_SEPARATOR . 'dibGlobals' .DIRECTORY_SEPARATOR. 'templates' .DIRECTORY_SEPARATOR. 'includes' .DIRECTORY_SEPARATOR. 'components' .DIRECTORY_SEPARATOR;
			if(file_exists($path . $r['name'] . '.et.html')) {
				$result =  rename($path . $r['name'] . '.et.html', $path . $r['renamed_to'] . '.et.html');
				if($result !== TRUE)
					Log::w('ERROR: ' . $r['name'] . ' to ' . $r['renamed_to']);
				else
					Log::w('SUCCESS: ' . $r['name'] . ' to ' . $r['renamed_to']);
			}
			
			$files = glob($path . '*.*');
			foreach($files as $file) {
				$c = file_get_contents($file);
				if(strpos($c, "'" . $r['name'] . "'") !== false)
					Log::w("---- ".basename($file));
			}
			
			$path = dirname($path) . DIRECTORY_SEPARATOR . 'sections' . DIRECTORY_SEPARATOR;
			$files = glob($path . '*.*');
			foreach($files as $file) {
				$c = file_get_contents($file);
				if(strpos($c, "'" . $r['name'] . "'") !== false)
					Log::w("---- ".basename($file));
			}
			
		}
		
		return $this->validResult(NULL, 'done!');
		
		/*$sql = "SELECT id, name, `body` as b FROM pef_docs WHERE `body` like '%~nav(%'";
		$rst = Database::execute ($sql);
		
		foreach($rst as $key=>$r) {
			
			$i = strpos($r['b'], '~nav(');
			$body = $r['b'];
			
			while($i !== false) {
				$i = $i + strlen('~nav(');
				$j = strpos($body, ')~', $i);
				$str = substr($body, $i, $j - $i);
				
				$p = explode(',', $str);
				
				if(isset($p[0]) && isset($p[1]) && ( trim($p[0]) === trim($p[1]) )) {
					$str2 = trim($p[0]);
					//Log::w ($r['name'], $str, $str2);
					
				} elseif(isset($p[1])) {
					$str2 = trim($p[1]) . ',' . trim($p[0]); // . (isset($p[2]) ? ','.trim($p[2]) : '');
					//Log::w ($r['name'], $str, $str2);
				} else 
					$str2 = trim($str);
				
				$body = substr($body, 0, $i) . $str2 . substr($body, $j);
				
				//if($key < 2) {
				//	Log::w($str, $str2, $body);
				//}
				
				$i = strpos($body, '~nav(', $j + 1);
			}
		//	Database::execute("UPDATE pef_docs SET `body` = :body WHERE id = " . $r['id'], array(':body'=>$body));
		}
		
		return $this->validresult(null, $key);*/
		
	}

    //
    public function mdButton_click($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
        return $this->validResult(NULL, 'You clicked me', 'dialog');
        
    }

}