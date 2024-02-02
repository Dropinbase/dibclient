<?php 

/**
* Specify additional "add-on" functions for Eleutheria
*/

Class EleutheriaFunctions { 
	
	// List of Eleutheria function names defined in this class
    public $functionList = array('test');
	
	/**
	* Demo function. $parts[0]=count. $parts[1]=string. Repeats the string, count times. Then adds all values in the current merge record as a semicolon-delimitted list. 
	* @param array $parts function parameter values
	* @param int $partsCount the count of function parameter values (ie count($parts))
	* @param array $mergeRecord associative array of values in the current merge record
	* @param object $etClass a pointer to the Eleutheria class providing access to its public variables and functions.
	* @return string
	*/
    public function test($parts, $partsCount, &$mergeRecord, $etClass) {
        // Demo function
        if ($partsCount < 2)
            return array("The 'test' function requires at least two parameters (count, string).");
        
        if ($parts[0] != (string)(int)$parts[0] || $parts[0]<1)
            return array("The 'test' function's first parameter must be an integer > 0");
        
        $str = $etClass->inputFormat . ' - ';
        
        for($i=0; $i<(int)$parts[0]; $i++)
            $str .= $parts[1];
        
        foreach($mergeRecord as $key=>$value)
            if(!is_array($value)) $str .= ';'.$value;
        
        return $str;
    }

}

?>