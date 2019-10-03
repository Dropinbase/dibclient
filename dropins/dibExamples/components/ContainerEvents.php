<?php
/*
Demonstrates Container Events

NOTES: 
1. Function parameters vary according to trigger (event) as follows:
	before readone,  before readmany: 
    	$containerName, &$criteriaParams, $trigger, $gridFilter, &$criteria
    
    after readone,  after readmany: 
    	$containerName, $attributes, $trigger, $filterParams
    	
    before update: 
		$containerName, &$attraaibutes, $trigger, $recordOld, $submissionData
	
	after update: 
		$containerName, $attributes, $trigger, $recordOld, $validAttributes, $submissionData
		
   	get defaults, before create, after create, before delete, after delete:
   		$containerName, &$attributes, $trigger, $submissionData

2. Note that parameters such as $attributes and $validAttributes can be received by reference (eg &$attributes) which is useful on the 'before' events. Values can be manipulated before storing them.

3. To cancel execution of any further code in the calling Crud class: 
   return array('cancel', $message);
   where $message will be displayed to the user.
   To change audit trail info being captured for a specific container, set $attributes on 'after create' or $validAttributes on 'after update'

4. Note, delete the corresponding Table Crud class with any change in pef_container_event, pef_container, pef_item etc. that may affect it. This will cause the
   Crud class code to be regenerated.
*/

class ContainerEvents extends Controller {

    public function beforeDelete($containerName, $attributes, $trigger) {
        // Send a message with NodeJs
        if(!empty(DIB::$NODEJSHOST))
        	NodeJs::msgHeader('Crud Event', "NodeJs msg: '$trigger' event triggered.", 4000, 'notice');        	
        
        // We cancel the event by sending an array of the form array('cancel', $userMessage)        
        return array('cancel', "'$trigger' event triggered. For demo purposes this event is being cancelled.");
    }
    
    public function afterReadMany($containerName, &$attributes, $trigger, $filterParams) {        
        // NOTE, since we configured $attributes to be sent by reference,
        // we can (conditionally) modify the contents of $attributes, which would then change the values that are sent to the client      
        if(isset($attributes[3]))
        	$attributes[0]['name'] = "Container Events classified this info, if >3 records ;-)";
        
        // We'll include a notice for the user. 
        // By setting PeffApp::clientMsg we set (and override) any other message that may have been configured using validResult(...)
        PeffApp::setClientMsg("Container Events triggered to change values in the 'name' field", 'notice', 4000);
        
        // We can also send client actions by adding them to the PeffApp::$clientActions array...
        ClientFunctions::addAction(PeffApp::$clientActions, 'AppendValue', array('dibexEvents.containerEvents'=>"$containerName: $trigger (fired from Container Crud Events); "));
 
    } 
    
    public function getDefaults($containerName, &$attributes, $trigger) {
        return array('cancel', "'$trigger' event triggered. Crud defaults will not be set...");
    } 
    
    public function afterUpdate($containerName, &$attributes, $trigger, $recordOld, &$validAttributes) {  
    	// NOTE, if this were a beforeUpdate event, we could (conditionally) modify the contents of $attributes (passed by reference),
    	//       which would then change the values of the record being saved 	
        return array('cancel', "'$trigger' event triggered. Note the record has already been stored; cancelling this event has no effect other than preventing audit trail info to be stored.");
    }

}