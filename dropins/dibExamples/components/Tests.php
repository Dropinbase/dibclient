<?php
/*
Demonstrate Container Events

*/
            
class Tests {
	
	
	/**
	*
	* Validates user message and then includes in a string which is returned
	* Called from eg PhpController.php's loadClass function 
	*
	* @param string $msg user message
	* 
	* @return string error message on failure, or processed message on success
	*/
    public static function testMsg($msg) {    
    	if(!ctype_alnum(str_replace(' ', '', $msg)))
    		return "The message may only contain alpha-numeric characters and spaces. Change the code and try again... ";
    		
    	return "Testing test message: '$msg'";
    }
    
    
}