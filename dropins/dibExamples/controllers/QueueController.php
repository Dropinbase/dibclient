<?php

/*
Demonstrates the use of Queues to execute tasks asynchronously.
Queues are useful for long-running scripts...

HOW IT WORKS:
The client is instructed to start polling the server (by calling the QueueController) every asyncInterval seconds for any actions or messages that are waiting.
This instruction is either merged into the UI by secifying the asyncInterval(seconds) in the response_type field of the Item/Container event tables, 
  OR the server can include asyncInterval as part of a validResult response to the client.
  Note, when response_type is used, the client will not wait for the server to send a validResult or invalidResult response.
In the meantime, the server prepares actions and messages and adds them to the Queue. 
Actions/Messages are executed on the client in the order that they were added to the Queue.
The server can at any point change the value of asyncInterval, or set it to 'stop' (which instructs the client to stop polling).
If the client polls retryCount times (default is 10) without receiving any response, the polling will stop automatically.
  The value of retryCount can also be updated at any time.
Queues are stored per loginId, and it is possible to target another loginId - ensure their clients are polling though.

*/

class QueueController extends Controller {

    public function startedWithResponseType($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {        
      	// This function was called by an event where the response_type was set to 1000.
      	
      	// Stop the Queue conditionally      	

        $t = PeffApp::getSubmitVal($submissionData, 'sIA.s', 'text1');
        
      	if($t === 'stop me') 
      		return Queue::addMsg('Title', "Action blocked :)", 'dialog', 0, true, 'stop');
      	
      	// If the server will potentially be busy for a long time it is a good idea to set the PHP timeout...
		  //set_time_limit(600);
		  
		// The client request that initiated the call always waits for a result so let's satisfy its need:
		$this->flushResult(); 
		// the available parameters are the same as invalidResult, so you could do this:
		// $this->flushResult("Please wait, this script can take up to 40 seconds to complete!", 'notice', 5000); 
      	
      	$this->doSomeWork($t);
      	
        // Since the client is no longer waiting for a response, we don't send a validResult or invalidResult
    }
	
	protected function doSomeWork($text) {
		// The client request that initiated the call always waits for a result so let's satisfy its need:
		// Note, this call will always appear after validation tests that use invalidResult to return error messages to the user.
		$this->flushResult(); 

		// Let's give the client something to do...
      	for($i=1; $i<5; $i++) {
			// The Queue class has a native function to send messages
			Queue::addMsg('Title', "Message details... this is no $i/4", 'notice', 1000);
		}
      	
        // Wait a bit for demo purposes
        sleep(3);
        
        // Change the asyncInterval and retryCount
        Queue::updateIntervals(1500, 5);
        
        // Can change asyncInterval while sending messages or adding actions too... 
        // Notice the 5th parameter $replace - it is used to replace any other messages in the Queue with the current one
        Queue::addMsg('Progress', 'Setting text1', 'notice', 1000, true, 1100);
        
      	// Set the value of a field - first strip insecure chars
      	$text = (empty($text)) ? 'a new value...' : preg_replace('/[^\w ]/', '', $text);
      	ClientFunctions::addAction($actionList, 'SetValue', array('text1'=> 'The Queue did this: ' . $text));
      	ClientFunctions::addAction($actionList, 'RefreshContainer', array('value'=>'test_company_grid'));
      	
      	Queue::addMsg('Progress', "We're done", 'dialog');
      	
      	// All done, we send the last actions and set the asyncInterval to 'stop' which will cause the client to stop polling
      	Queue::add($actionList, false, 'stop');
	}
	
    public function promptQuestion($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
        $params = ClientFunctions::addMsgPopup($actionList, 'A popup question', 'Do you want more action?', 'itemalias', 'btnHidden');
        return $this->validResult($actionList);
    } 

    public function handlePrompt($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null, $async = false) {
        // Stop the Queue conditionally
      	$t = PeffApp::getSubmitVal($submissionData, 'sIA.s', 'text1');
        
      	if($t === 'stop me') 
      		return $this->invalidResult ("Action blocked :)");
      	
      	// NOTE the new parameter, $async that was added to the function definition
      	// The repeatUrl action below will cause the client to call handlePrompt again, 
      	// but this time $async will be TRUE, causing the rest of this function to execute...
      	if($async===false) {
			
			// Since we'll intiate the Queue server-side, we need to set a unique id that will be used by the client when requesting actions for this user
       		// The call to validResult will package this value (stored in PeffApp::$queueUid) for return to the client.
			// We also need to include the new queueUid in the url's query parameters
			   
			// Note, PeffApp::randomToken() generates a cryptographically secure random string which is important to prevent hackers predicting the value.
			PeffApp::$queueUid = DIB::$USER['id'] .'_'. PeffApp::randomToken(20);
        	
        	ClientFunctions::repeatUrl($actionList, '?async=TRUE&queueUid=' . PeffApp::$queueUid, $containerName, $triggerType, $itemId, $itemEventId, $itemAlias);	
			
			// Start the polling with asyncInterval set to 1000			
			return $this->validResult($actionList, null, null, null, 1000);
		}
		
		// The client request that initiated the call always waits for a result so let's satisfy its need:
		$this->flushResult();

        // Call our worker function which adds actions to the Queue...
        $this->doSomeWork($t);
    }
    
    public function sendMsg($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null, $async = false) {
		// Prevent public access
		if(DIB::$USER['name'] === 'system_public')
			return  $this->invalidResult('This function is not available for public users');
		
        list($msg, $loginId) = PeffApp::getSubmitVal($submissionData, 'sIA.s', array('queueMsg', 'queueLoginId'));
		
		// The client request that initiated the call always waits for a result so let's satisfy its need:
		$this->flushResult();

        // Change the retryCount - now the queue will stop automatically after receiving 20 empty results
        Queue::updateIntervals(null, 20);
        
        // validate loginId
        if($loginId == (string)(int)$loginId) {
        	$sql = "SELECT l1.first_name as name1, l2.first_name as name2, l2.notes
        			FROM pef_login l1 LEFT JOIN pef_login l2 ON l2.id = :lId2
        			WHERE l1.id = :lId1";
			$rst = Database::fetch($sql, array(':lId1'=>DIB::$USER['id'], ':lId2'=>$loginId), DIB::LOGINDBINDEX);
			
			if(Database::count()===1 && strlen($rst['notes'])>2) {
				// sanitize msg and add it to the user's queue
				$me = $rst['name1'];
				$them = $rst['name2'];
				$msg = (empty($msg)) ? 'nothing' : preg_replace('/[^\w ]/', '', $msg); 
				$queueUid = $rst['notes'];
				Queue::addMsg('Message', "Message sent", 'notice', 4000, true, 1000);
				return Queue::addMsg('Message', "Hello $them! Msg from $me: " . $msg, 'notice', 5000, true, 1000, $queueUid);
			}
		}
        
        return Queue::addMsg('Error', "Sorry, the selected login is not listening, or is invalid.", 'dialog', 0, true, 2000);
    }
    
    public function listen($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
		// Prevent public access
		if(DIB::$USER['name'] === 'system_public')
			return  $this->invalidResult('This function is not available for public users');

		// Store queueUid against Login id (***TODO, handle reset of pef_login field after 20s of no activity)
    	Database::execute("UPDATE pef_login SET notes = :uid WHERE id = " . DIB::$USER['id'], array(':uid'=> PeffApp::$queueUid));
		Queue::addMsg('Messages', 'Listening for messages... The queue will automatically stop after 20s of no activity', 'notice', 5000, true, 1000);
		
		// The client request that initiated the call always waits for a result so let's satisfy its need:
		$this->flushResult();
	}

}