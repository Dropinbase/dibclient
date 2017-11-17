<?php

/*
Demonstrates basic event functionality

NOTES:
Controller class names must always end in Controller.php, eg EventsController.php.
Functions must always use either the validResult() or invalidResult() functions
   of the Controller.php class to return a response to the client, that will be waiting,
   unless the event initiates the Queue with response_type being an integer.
It is therefore recommened to always extend the Controller class.

If there is nothing of value to return to the client, use:
   return $this->validResult(NULL);
*/

class EventsController extends Controller {
	
	public function eventTrigger($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
        // Since we return the name of the event (text passed from the client), we treat it as unsafe and sanitize it before returning the name... 
        $triggerType = preg_replace("/[^A-Za-z0-9 ]/", '', $triggerType);
        return $this->validResult(NULL, "'$triggerType' fired!", 'dialog');        
    }
    
    public function btnHelloWorld_click($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {        
      	// NOTES:
      	// Adding $containerName to the function parameters causes DIB to check that the user's permGroup has rights to the container,
      	//   by checking for the existence of the container name in the /runtime/_Containersxx1xx.php file (asuming a permGroup of x1x)
      	// Excluding containerName or giving it a default value in the function parameters will invalidate permission checking on container level.
      	// The same applies for $itemEventId which causes DIB to check the /runtime/_ItemEventsxx1xx.php file (asuming a permGroup of x1x).
      	
      	
      	// Return an empty actionlist, and a message using style 'dialog' (available styles = success/notice/warning/dialog)
        return $this->validResult(NULL, 'Hello World!', 'dialog');        
    
    } 

    public function textfield_changed($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
        // Note, both textfields call this function...
        // We cleverly use the alias of the textfields to get the value of the one which triggered the action...
        
        // Since hackers can change the automated $itemAlias value, and we're returning it to the client, let's validate it with a whitelist
        if(!in_array($itemAlias, array('Textfield1', 'Textfield2')))
            return $this->invalidResult("Invalid request", 'dialog');
    
        // We use the PeffApp::getSubmitVal() function to return NULL if nothing was submitted
        // Note the abbreviation 'sIA.s' means 'submitItemAlias.self', 
        //    while 'sIA.p' means 'submitItemAlias.parent' and using only 'sIA.' will translate to 'submitItemAlias.'
        // Alternatively we could use: 
        //	  $value = isset($submissionData['submitItemAlias.self'][$itemAlias]) ? $submissionData['submitItemAlias.self'][$itemAlias] : NULL;
        // but this is nicer:
        
        $value = PeffApp::getSubmitVal($submissionData, 'sIA.s', $itemAlias);
        
        if(empty($value))
        	return $this->validResult(null, "Server-side function was called by '$itemAlias'. No value was supplied.", 'dialog');
        	
        // Angular comes with excellent built-in prevention of XSS attacks
        //    but should we want to allow only certain characters, we can validate or santize the string
        
        if(!DValidate::_string($value, ' '))
        	return $this->invalidResult("Invalid value supplied. The textbox may only contain alpha-numeric characters, underscore and spaces", 'dialog');
        	
        // Note, using invalidResult above serves no other purpose but to display a dialog message.
        // In other cases (such as crud events on trees) invalidResult instructs the client to 
        //    perform an alternative action than it normally would with a validResult.
        
        // Return an empty actionlist, and a message using style 'dialog'
        return $this->validResult(null, "Server-side function was called by '$itemAlias' with a value of '$value'", 'dialog'); 
    }   
    
    /***
      The following four functions demonstrate the setting of disabled, visible, style and class configurations
    */
     
	public function btnDisable_click($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {        
         
        ClientFunctions::addMethod($actionList, 
            array('view.Textfield1.disabled'=>TRUE,
                  'view.btnDisable.disabled'=>TRUE
            )
        );
        /***
            NOTES:
            - Targeted items must have aliases! Any item (including layout components) can be targeted.
            - The last parameter, $containerName, is optional and is omitted above. It indicates where searching starts to find the item referenced by its Alias. 
            - If $containerName is not provided, the current container is used. 
              This will only work if $containerName is a parameter in the controller function above (see declaration of btnDisable_click above in this case).
            - If the target container is loaded in a port with an alias (not the default port), then $containerName below should use the following format:
              CONTAINERNAME/PORTALIAS
        ***/

        return $this->validResult($actionList);
        
        // Note the following would also work, though invalidResult is intended for reporting errors in a popup dialog:
        // return $this->invalidResult(null, null, null, $actionList);
    }
    
    public function btnHide_click($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {        
        // Multiple calls to addMethod can also be used, but using a single call is more efficient ...

        // See NOTES in btnDisable_click for more info
        ClientFunctions::addMethod($actionList, array('view.Textfield2.visible'=>FALSE));
        ClientFunctions::addMethod($actionList, array('view.btnHelloWorld.visible'=>FALSE)); 
        
        return $this->validResult($actionList);
    }
    
    public function btnShowEnable_click($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
        // See NOTES in btnDisable_click for more info
        ClientFunctions::addMethod($actionList,
            array('view.Textfield1.disabled'=>FALSE,
                  'view.Textfield2.visible'=>TRUE,
                  'view.btnHelloWorld.visible'=>TRUE,
                  'view.btnDisable.disabled'=>FALSE
            )
        );
        
        return $this->validResult($actionList);
    }
    
    public function btnSetStyleClass_click($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
        
        // Get random values to use 
        $height = rand(15, 50);
        $width = rand(100,200);
        $rnd = rand(0,5);
        $textAlign = array('top','right','bottom','left',''); // Note the last option: ''
        $classes = array('md-primary','md-custom-primary','md-accent','md-warn','md-cornered');
        $color = sprintf("#%02x%02x%02x", rand(0,255), rand(0,255), rand(0,255));
        
        if($rnd === 5) {
        	// Reset to default
        	$style = array();
        	$class = array(''=>false);	
        } else {
        	$style = array('height'=>$height.'px', 'width'=>$width.'px', 'text-align'=>$textAlign[$rnd]);
        	$class = array($classes[$rnd]=>true);
        }
        
        // See NOTES in btnDisable_click for more info
        ClientFunctions::addMethod($actionList,
            array('view.btnHelloWorld.style'=>$style,
                  'view.btnHelloWorld.class'=>$class,
                  'view.btnSetStyleClass.style'=>array('background-color'=>$color)
            )
        );
        
        // More options

        // ClientFunctions::addMethod($actionList, array('view.btnHelloWorld.style'=>array() ));
       	// ClientFunctions::addMethod($actionList, array('view.btnHelloWorld.class'=>array('md-custom-primary' => false))); 
        
        return $this->validResult($actionList);
    }
    
    // ----------------------------------
    
    public function btnNextAction_click($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
        // The addSubmitUrl action is used to set the next action to be called by the client
        // The optional $containerName parameter can be used to indicate where submissionData is collected from
        // The optional $itemId parameter identifies an item with additional submissionData configs (eg submitCheckedItem : 'myTree') to include
        
        // Note the use of the container name and dibuid in the url below that provides values required for permission checking
        ClientFunctions::addSubmitUrl($actionList, "/dropins/dibExamples/Events/textfield_changed/$containerName/dib*4x1jtsqhb?itemAlias=Textfield1");
        return $this->validResult($actionList);
    }
    
    public function btnOpenUrl_click($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
        
        // Open the dibtestTestForm container, wait until it is open and data is loaded (the default behaviour of OpenUrl), 
        //   and append 'xxx' to the item with Alias 'varchar10'
        ClientFunctions::addAction($actionList, 'OpenUrl', array('url'=>'/nav/dibtestTestForm'));
		ClientFunctions::addAction($actionList, 'AppendValue', array('varchar10'=>'xxx'));
        return $this->validResult($actionList);
    }
    
    public function btnSetActiveFilter_click($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
        /* For the Chart subcontainner, the JSON configuration on the client is generated by default as:
            'itemAlias' : 'subChart',
            'activeFilter' : 'dibexEvents_subChart',
      		'containerSettingFilter' : {
      			'dibexEventsExtJs_subChart' : 'submitItemAlias_parent_company1Id',
      			'dibexEventsExtJs_company2' :  'submitItemAlias_parent_company2Id'
      		}
      	   
      	   It is based on records in pef_item and pef_item_filter.
      	   All we need to do is switch the activeFilter config to point to 
      	    one of the listed filter names under containerSettingFilter above or '' (remove filter), 
      	    and then refresh the subcontainer.
        */
        
        // Use the Alias of the item that called the function to select the name of the filter to apply
        switch ($itemAlias) {
        	case 'none' : $filterName = ''; break;
        	case 'activeFilterCompany1' : $filterName = 'dibexEvents_Company1'; break;
        	case 'activeFilterSupervisors' : $filterName = 'dibexEvents_Supervisors'; break;
        	case 'activeFilterOriginal' : $filterName = 'dibexEvents_CompanyConsultantGrid';       	
        }
        
        // See /dropins/setNgMateria/dibGlobals/js/action/setActiveFilter.js
		
		// Prepare parameters for setActiveFilter action
		$params = array(
			'itemAliasPath'=>'dibexEvents.CompanyConsultantGrid', // path to find subcontainer
			'isContainer'=>TRUE, // Whether a container (or item filter) is being set
			'newActiveFilter'=>$filterName // Name of new filter 
		);
		
        ClientFunctions::addAction($actionList, 'setActiveFilter', $params);
        ClientFunctions::addAction($actionList, 'RefreshContainer', array('value'=>'dibexEvents.CompanyConsultantGrid'));
        
        return $this->validResult($actionList);
    }

    public function btnPopupYNCancel_click($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
        // The Yes and No responses can implement one of three action types:
        //   'itemAlias' - point to another (optionally hidden) item by its alias, and always execute the 'click' event
        //   'submitUrl' - execute a server- or client-side action, eg /dropins/dibExamples/Events/btnPopupYNCancel_click
        //   'action' -  or specify an single action to execute (see $actionArray below)
        // The Yes action is required. If the No action is omitted, the button will show but simply close the dialog.
        $actionArray = array('submitUrl'=>'dibGlobals.action.setValue', 
                             'params'=>array('Textfield1'=>"You clicked No on the popup which set Textfield1 and triggered its change event"));
        $params = ClientFunctions::addMsgPopup($actionList, 
            'A popup question', 'Do you want to say hello?', 
            'itemAlias', 'btnHelloWorld', 
            'action', $actionArray);
        return $this->validResult($actionList);
        
    } 

    public function promptQuestion($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
       	// Note, the prompt action is set to call this same event when the user provides a value. 
       	// The value is submitted in $submissionData['submitPromptInput'] - so if this is empty, we know it's the first call
       	// Note prompts also handle the three action types mentioned for Yes/No popups above... 
       	
       	if(empty($submissionData['submitPromptInput'])) {
        	ClientFunctions::addPrompt($actionList, 
                'Math test', "What is 1 + 2?", 
                'itemAlias', 'btnPrompt', 
                true); // Use true to force an answer
        	return $this->validResult($actionList);
        }
        
        $answer = $submissionData['submitPromptInput'];
        
        If(trim($answer) !== '3')
            return $this->invalidResult("I'm afraid you need some tuition!", 'warning', 4000);
        
        // Note, instead of using itemAlias (as in promptQuestion above), we specify an event url to execute btnHelloWorld_click.
        // But since the btnHelloWorld_click function has $itemEventId as a parameter, we are forced to send the dibuid value (if specified), or the pef_item_event.id value for it. 
        // This is for extra security in a scenario where certain item events on a specific container may not be executed by certain permgroups
		// If all permgroups that have access to the specific container's events, may execute any event on it, 
		//   then remove the $itemEventId from the function paramater list, and the coresponding dibuid value below.
		//   To avoid the hasssle, using itemAlias pointing to a hidden button is a simpler solution
        ClientFunctions::addPrompt($actionList, 
            'Math Test', "Well done! And what is 2 + 3? If you cancel you will start the quiz again, else you will be greeted :)...",
            'submitUrl', "/dropins/dibExamples/Events/btnHelloWorld_click/$containerName/dib*kyjzvldx2", 
            false, 
            'itemAlias', 'btnPrompt');
        
        return $this->validResult($actionList);
    }
    
    public function containerEvents($containerName, $containerEventId, $submissionData = null, $triggerType = null, $containerId = null) {
        // Note, all the container events on dibtestCompanyGrid call this function...
        // We use the $triggerType to determine which event called it... and merely append it to the eventNotices field contents.
      
        // Important to sanitize $triggerType before returning its value (since it comes from the client and may contain something malicious...)
        if(!in_array($triggerType, array('rowClick', 'postLink', 'onInit', 'reloadContainer', 'load')))
        	$triggerType = 'unknown!';
        	
        ClientFunctions::addAction($actionList, 'AppendValue', array('dibexEvents.containerEvents'=>"$containerName: $triggerType; "));

        return $this->validResult($actionList); 
    }

    //
    public function mdButton_click($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
        
        list($client_pk) = PeffApp::getSubmitVal($submissionData, 'sIA.s', array('client_pk'));

        return $this->validResult(null, 'hello world', 'dialog');
    }

    // Location: Test Consulant Grid on /nav/dibexEvents
    public function btnHot_click($rowId=null, $rowName=null, $containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {        
        list($id, $name) = PeffApp::getSubmitVal($submissionData, 'sIA.s', array('id', 'name'));
        
        // Handle the 2nd button quickly...
        if($itemAlias === 'btnHotTheSecond')
            return $this->validResult(null, "I'm still here", 'dialog');

        // Handle the first button

        // Note, if the user selected a row in the grid, 
        //    the last selected row's items with aliases are available in $submissionData
        //    This is NOT necessarily the values from the row of the button that was clicked!

        //    So when we work with buttons in rows, we normally rely on values configured in the URL (see below)

        if(empty($id)) {
            // User has not selected a row in the grid
            // Use the id and name values from the event URL: /dropins/dibExamples/Events/btnHot_click/{{row.id}}           
            $id = $rowId;

            // Get the name from the database
            $rst = Database::fetch("SELECT name FROM test_consultant WHERE id = :id", array(':id'=>$id));
            if(empty($rst))
                return $this->invalidResult("Eish... seems this record was deleted.");
            $name = $rst['name'];
            $msg = 'THIS ROW VALUES';
        } else 
            $msg = 'LAST SELECTED ROW VALUES (click refresh btn to clear)';

        // Validate variables
        if($id !== (string)(int)$id)
            return $this->invalidResult('Are you hacking?');
        
        // Purge the name 
        $name = preg_replace("/[^A-Za-z0-9 ]/", '', $name);

        // Return the primary key value of the record in the grid that was clicked
        return $this->validResult(null, "$msg: Hello $name. Your id is '$id'", 'dialog');
    }

}