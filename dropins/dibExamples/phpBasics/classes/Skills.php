<?php

// Declare a simple class to store skills	

// Note its good protocol to name the class the same as the file name
class Skills {

	// The variables declared on class level are called the class properties

	// Private properties are visible only within the class
	private $skill = ''; // the name of the skill
	private $years = 0; // count of years experience in this skill
	private $acceptableSkills = array('gardening', 'maths', 'cooking', 'snorkeling'); // Finite list of possible skills

	// Here is a public variable. It can be read and set by external code instantiating this class.
	public $lastSet = ''; // Date/time this skill was last set

	const maxYears = 60;

	// External code (see class staff below) will call the "setter" and "getter" public functions below 
	// to set and get the values stored in $skill and $years.

	// "setter" function:

	/** 
	*	@param string $skill one of the approved skills
	*   @param int $years count of years experience in this skill
	*	@return mixed TRUE on success, err message on failure
	*/
	public function setSkill($skill, $years){
		if(!in_array($skill, $this->acceptableSkills, TRUE)) 
			return 'This is not an acceptable skill';
		
		if(!is_numeric($years) || $years < 1 || $years > self::maxYears)
			return 'Years must be between 1 and ' . self::maxYears;
		
		$this->skill = $skill;
		$this->years = $years;
		$this->lastSet = Date('Y-m-d H:i:s');
		return TRUE;
	}	

	// "getter" functions (used to get the private values of variables - see Important Notes below):

	public function getSkill() {		
		return $this->skill;
	}

	public function getYears() {
		return $this->years;
	}

}

/* 
    Important Notes:
	We could have declared the $skill and $years properties public and do away with the methods to set and get their values, 
	like with the $lastSet variable.

	This would not be "wrong" but with "getters"/"setters" we can perform logic upon changes or access, like setting the $lastSet value above. 
	We can validate input and return appropriate errors, or modify values.
	It gives us more control over how they are used and a greater ability to prevent misuse.
	Also, we can more easily extend functionality in future without breaking external code that has made use of the properties.

	Above we are forcing developers to use our "setters" to set the skill, which in turn will always set the $lastSet date which is what we want.
	BUT using getters and setters for no good reason is not good design.

	Note, developers will be able to manually change the $lastSet date (since its not protected), but then they should have a good reason to.	
*/
