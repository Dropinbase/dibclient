<?php

// Our tools class contains utility functions that can be used throughout our app
// The functions will all be declared "static" so that no instantiation of the class is needed before the functions can be used.

// A class containing general utility functions is often used in multiple other files/classes in a project, 
// and even copied and used in other projects

class Tools {

	// Note, properties that are declared "static" exist only once in memory. 
	// All references to it share this value as opposed to non-static class properties that exist once for each instantiation of the class.

	public static $errMsgCount = 0;
	public $nonStaticErrCount = 0;

	public static function errMsg($msg) {
		echo '<span style="color:red;font-weight: bold;">' . $msg . '</span><br><br>';
		self::$errMsgCount++;

		// Note the following line would cause a compile error. Non static properties cannot be used in static functions
		// $this->$nonStaticErrCount++;
	}

	public static function msg($msg) {
		echo '<span style="color:black">' . $msg . '</span><br><br>';
	}

	/** 
	 * Attempts conversion of string date to Y-m-d format
	 * @param string $date date to convert
	 * @return string $date Y-m-d format of date, or false on error
	 */
	public static function convertDate($date) {		
		// Replace slashes with hyphens
		$date = str_replace(array('\\', '/'), '-', $date); // Note backslashes (\) must be escaped inside strings with another backslash.
		if(strpos($date, '-00') !== FALSE) return FALSE;

		//	Convert the string date into time and return it in format of yyyy-mm-dd
		$date = strtotime($date);
	
		if(!empty($date))
			return date('Y-m-d', $date);	
	
		return FALSE;
	}

	public static function dateDifference($date1, $date2) {
		$d1 = new DateTime($date1);
		$d2 = new DateTime($date2);
		
		$diff = $d2->diff($d1);
		
		return $diff->y; // return difference in years
	}

}