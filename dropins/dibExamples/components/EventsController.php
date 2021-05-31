<?php

class EventsController  {

    //
    public function myTest($containerName, &$attributes, $trigger, $submissionData) {
        
        // NOTE, we can (conditionally) modify the contents of any parameter above sent by reference (eg &$attributes),
        // which would then affect subsequent operations;
        if(isset($attributes[3]))
           $attributes[0]['name'] = 'Container event overriding this name';
        // We'll include a notice for the user. 
        // By setting PeffApp::$clientMsg we override any other message that may have been configured using validResult(...)
        PeffApp::setClientMsg("Container Events triggered to change values in the 'name' field", 'notice', 4000);
        // We can also add any other client actions 
        ClientFunctions::addAction(PeffApp::$clientActions, 'ReloadContainer', array('value'=>'self'));
		// If an error occurs, return array with error message which will cancel any further code execution and display the message to the user.;
		//return array('cancel', 'Oops, something went wrong. Please contact the System Administrator');
		return TRUE;

    }

}