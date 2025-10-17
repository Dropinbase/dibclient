<?php

/**
 * Basic Dropinbase server-side functionality
 *   
 */

/* NOTES: 
  - Controller files must always end in the word 'Controller'
  - URLs in DIB that reference controller functions are of the form: 
		/dropins/DROPINNAME/CONTROLLER/FUNCTIONNAME
		eg /dropins/dibExamples/Basics/helloWorld
	or
		/dropins/SET/DROPINNAME/CONTROLLER/FUNCTIONNAME
		eg /dropins/setTest/Demo/Colors/showRed

    where CONTROLLER does not contain the word 'Controller' 
	
  -	The controller class must have the same name as the file (minus the .php extension),
    and must extend the Controller class (see below) to enable access to the validResult/invalidResult/etc. functions.

  - For more info, see the examples in Docs, linked to the controller classes in 
    /vendor/dropinbase/dropinbase/dropins/dibExamples/controllers

*/

class BasicsController extends Controller {
    // NOTE, Controllers normally extend the Controller class in order to
    //   construct a appropriate JSON result using the validResult or invalidResult functions
	//   which can then be returned to the client.
    //   See below for more details...

	// This function can be called by creating an item event on for eg. a button where
	// the Submit Url is /dropins/main/Basics/greeting
    public function greeting($containerName, $itemEventId, $clientData=null, $triggerType=null, $itemAlias=null, $itemId=null) {
        // NOTES:
        // Adding $containerName and $itemEventId to the function parameters causes DIB to check that the user's permGroup has rights to the container and item event,
        //   by checking the details stored in the pef_perm_active table for the user's permGroup (eg x1x).
		//   Note, container events use other parameters, eg. $containerEventId instead of $itemEventId.
        //   See docs for more info...
        // $clientData - values of items specified to be submitted to the server via the item_alias or configs fields.
		// $triggerType - event type, eg click/change/focus/etc
		// $itemAlias - the name of the item
		// $itemId - the id of the item
		// All the parameters are optional though $containerName and $itemEventId/$containerEventId are normally specified.
        
		// Extract values from $clientData array
		list($name) = DibApp::clientData('alias_self', array('name'));

		// Do some validation since we'll inject the name in HTML (see below)
		if(empty($name)) {
			// Events where the response_type is 'actions' must return JSON. 
			// The validResult and invalidResult functions are used to package the response.
			// Use the => operator to separate the title (if any)
			return $this->invalidResult('Error=>First provide a name in the input above, and try again.');
		}

		// HTML encode the $name to prevent hacking since we'll include it in HTML below
		$name = htmlspecialchars($name, ENT_QUOTES | ENT_HTML5, 'UTF-8', FALSE);

		// Classes in the /components folder (and its subfolders) of the current dropin are loaded automatically
		// using the framework's autoloader' as needed (note, require_once is used)
		// In other words, if the event url is /dropins/dibExamples/ANYCONTROLLERHERE then
		// the classes in /dropins/dibExamples/components and /dropins/dibExamples/components/xxx need not be required explicitly.

		// The Tests.php class in the /dropins/dibExamples/components folder will be loaded automatically:
		$msgToSendToClient = Tests::testMsg('To infinity and beyond');

		// The testMsg function will return an array with an error message if the string sent fails validation.
		// Note, style 'dialog' is used by default for messages returned with invalidResult.
		if(is_array($msgToSendToClient))
			return $this->invalidResult($msgToSendToClient[1]);

		
        // Return an empty actionlist (NULL below), and a message using style 'dialog' (available styles = success/notice/warning/dialog)
		// Messages can contain HTML tags
		// The encoding above has ensured that $name contains no possible malicious characters in HTML.
        return $this->validResult(NULL, "Greeting=>Hello <b>$name</b><br><br>$msgToSendToClient", 'dialog');
    }

	
}