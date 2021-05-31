<?php

class Person {
	public $firstName;
	public $lastName;
	private $dob; // the person's date of birth (since we need to force validation we make this private and use "setters" and "getters")

	// The function named "__construct" is special - it is automatically called when an instance of the class is declared
	// We're not using "getters" and "setters" here since we don't need any validation for firstName and lastName (see notes above)

	public function __construct($firstName, $lastName){
		$this->firstName = $firstName;
		$this->lastName = $lastName;

		// NOTE: never do any validation or code that may fail in constructors. There is no clean way of handling errors that occur here.
	}

	// setter:

	public function setDob($dob) {
		// attempt conversion to Y-m-d format using tools class 
		$dob = Tools::convertDate($dob);

		if(empty($dob)) {
			Tools::errMsg('Date of birth format is not recognised. Please use yyyy-mm-dd format.');
			return FALSE;
		}

		// Get date 20 years ago
		$youngest = Date('Y-m-d', strtotime("-20 years"));
		if(empty($dob) || $dob < '1940-01-01' || $dob > $youngest) {			
			Tools::errMsg("The date of birth must be between '1940-01-01' and '$youngest'.");
			return FALSE;
		}	

		$this->dob = $dob;
		return TRUE;
	}

	// getter 

	public function getDob() {
		return $this->dob;
	}

}