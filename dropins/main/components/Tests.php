<?php

class Tests {
	
	/**
	* Validates, transforms and returns user message 
	* Called from BasicsController.php
	*
	* @param string $msg user message
	* @return string error message on failure, or processed message on success
	*/
    public static function testMsg($msg) {
    	if(!ctype_alnum(str_replace(' ', '', $msg)))
    		return array('error', "The name may only contain alpha-numeric characters and spaces. Please try again... ");
    		
    	return "Testing test message: '$msg'";
    }
    
    
}