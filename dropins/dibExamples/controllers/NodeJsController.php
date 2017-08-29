<?php
/*
Demonstrates basic NodeJs functionality

NOTES:
Controller class names must always end in Controller.php, eg EventsController.php.
Functions must always use either the validResult() or invalidResult() functions
   of the Controller.php class to return a response to the client (that will be waiting). 
It is therefore recommened to always extend the Controller class.
If no value must be returned, use:
   return $this->validResult(NULL);
*/

class NodeJsController extends Controller {

    public function btnMsgAction_click($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
        // Create a loop to demonstrate NodeJs' ability to send asynchroneous messages and actions
        
        // Fetch all companies in the test_company table. 
        $rst = Database::execute("SELECT name FROM test_company");
        if(Database::count()===0)
        	return $this->invalidResult("There are no records in the test_company table. First add some and try again.");
        	
        foreach ($rst as $key=>$record) {
        	// Send a notice to the user of what's happening
        	// Note, by default NodeJs sends actions/messages to the current user (identified by DIB::$USER['id']).
        	// To target a different user, add their login id as the 5th parameter below.
			NodeJs::msgHeader("Adding company", "Adding '" . $record['name'] . " to the list and waiting 2 seconds...", 1900, 'notice');
			
			// Reset the actionList to remove previous actions added;
			$actionList = array();
			
			// If this is not the last record, add a linefeed
			if(isset($rst[$key+1])) $record['name'] .= "\r\n";
			
			// Prepare the action
			ClientFunctions::addAction($actionList, 'AppendValue', array('companyList' => $record['name']));
			
			// Send it with NodeJs. The 2nd parameter identifies the container to start searching for Alias values.
			NodeJs::action($actionList, 'dibexEvents');
			
			// If there happens to be more than 5 records, then quit.
			// NOTE we could add a LIMIT clause to the Sql statement above, but we want to keep this code compatible with other RDBMS's that
			// do not use LIMIT (eg MsSql). Later on we will explain how to add directives for translation between sql languages...
			if($key > 5) break;
			
			// Wait 2 seconds
			sleep (2);
		}
		
		// Send something to the client-side framework that is waiting for a response (to avoid exceptions)
		return $this->validResult(NULL); 
    }
    
    public function promptQuestion($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
        // prompt($title, $text, $actionType, $action, $forceInput=FALSE, $cancelActionType=NULL, $cancelAction=NULL, $regexRules='', $errorMsg='', $loginId=null)
        // 4. TODO - also not working ?
        NodeJs::prompt('Math Test', 'What is 1 + 2? If you cancel you will be greeted :)...', 
        							      'url', "/dropins/dibExamples/NodeJs/promptAnswer/$containerName/first", FALSE, 'itemAlias', 'btnHelloWorld');
        
        return $this->validResult(NULL);
    }
    
    public function promptAnswer($containerName, $questionOrder=null, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
        
        if(!isset($submissionData['submitPromptInput']))
            return $this->invalidResult('Invalid answer');
        
        $answer = $submissionData['submitPromptInput'];

        If($questionOrder === 'first') {
        	if (trim($answer) != '3')
            	NodeJs::msgHeader('Math Test', "I'm afraid you need some tution!", 5000, 'warning');
	        else {
					NodeJs::prompt('Math Test', 'Well done! And what is 10 - 1?', 
        							      			  'url', "/dropins/dibExamples/NodeJs/promptAnswer/$containerName/last", FALSE);
        		} 

        } elseif (trim($answer) !== '9')
            return $this->invalidResult("Eish... try again!", 5000, 'warning');
        else
        	return $this->validResult(NULL, 'Passed!', 'success');
        
        return $this->validResult(NULL); // in case NodeJs returned something above (always return validResult/invalidResult to waiting client...)
    }

    public function regAlias($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
        $userAlias = PeffApp::getSubmitVal($submissionData, 'sIA.s', 'userAlias');
        if(empty($userAlias) || strlen($userAlias)>15 || !ctype_alnum($userAlias))
        	return $this->invalidResult("Please try again. The Alias must be an alpha-numeric string (no spaces) and no longer than 15 characters.");

        // Store with the secure token in a temporary text file
        $path = DIB::$RUNTIMEPATH . 'tmp' . DIRECTORY_SEPARATOR . 'NodeJs_Users.txt';

        // read/write - create if it doesn't exist
        $fh = fopen($path, 'c+'); 
        
        // Let's lock this file to prevent concurrent users overwriting one another's changes
        flock($fh, LOCK_EX); 
        
        $size = filesize($path);

        if($size > 0) {
            $file = fread($fh, $size);                    
            $users = json_decode($file, TRUE);
            
            // We use '@dibTime' to store the last time the file was accessed
            // If later than 5 mins ago then empty the file
            if(time() - $users['@dibTime'] > (5*60))
                $users = array('@dibTime' => time());
        
         } else
            $users = array('@dibTime' => time());
        
        // Add this user
        if(!isset($users[$userAlias]))
            $users[$userAlias] = DIB::$USER['secure_id'];

        // Empty the file
        ftruncate($fh, 0);
        rewind($fh);

        // Write file, and release the lock
        fwrite($fh, json_encode($users));
        fflush($fh);
        flock($fh, LOCK_UN);
        fclose($fh);
        
        return $this->validResult(null, "Alias registered successfully", 'notice', 3000);
    }

    public function btnSend_click($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
        // We use the PeffApp::getSubmitVal() function to return NULL if nothing was submitted
        // Note the abbreviation 'sIA.s' means 'submitItemAlias.self', 
        //    while 'sIA.p' means 'submitItemAlias.parent' and only 'sIA.' will translate to 'submitItemAlias.'
        // Alternatively we could use: 
        //	  $msg = isset($submissionData['submitItemAlias.self']['msg']) ? $submissionData['submitItemAlias.self']['msg'] : NULL;
        // but this is nicer:
        
        list($msg, $recipientAlias) = PeffApp::getSubmitVal($submissionData, 'sIA.s', array('msg', 'recipientAlias'));
        
        // Validate recipient string
        if(empty($recipientAlias) || strlen($recipientAlias)>15 || !ctype_alnum($recipientAlias))
        	return $this->invalidResult("Please try again. The Recipient Alias must be an alpha-numeric string (no spaces) and no longer than 15 characters.");

        // Validate message
        if(empty($msg) || strlen($msg)>150  || !ctype_alnum(str_replace(' ', '', $msg)))
            return $this->invalidResult("Please provide a message containing only alpha-numeric characters and spaces, no longer than 150 characters.");
        
        // Get recipient's NodeJsUserId 
        $path = DIB::$RUNTIMEPATH . 'tmp' . DIRECTORY_SEPARATOR . 'NodeJs_Users.txt';

        if(!file_exists($path))
             return $this->invalidResult("Sorry, the recipient has not been registered. Please try again.");

        // read - create if it doesn't exist
        $fh = fopen($path, 'r'); 
        // Get shared lock
        flock($fh, LOCK_SH); 
        
        $size = filesize($path);
        $file = fread($fh, $size);
        $users = json_decode($file, TRUE);
        flock($fh, LOCK_UN);
        fclose($fh); 
  
        if(!isset($users[$recipientAlias]))
            return $this->invalidResult("Sorry, the recipient has not been registered. Please try again.");

        // Send the message with NodeJs
        NodeJs::msgHeader("Hello", $msg, 5000, 'notice', $users[$recipientAlias]); 

        // Inform the sender
        return $this->validResult(NULL, 'Message sent to recipient: ' . $recipientAlias, 'notice', 2500);
    }

}