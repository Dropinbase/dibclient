<?php

$thisFolder = dirname(__FILE__) . DIRECTORY_SEPARATOR;

require $thisFolder . 'Person.php';
require $thisFolder . 'Skills.php';

// NOTE, although we use the methods of the Tools class here, at this point it has already been loaded in memory by the PhpBasicsClasses.php file.
// However, we may reuse the Staff class somewhere else in our project oneday without loading the PhpBasicsClasses.php file.
// This is where we need require_once to ensure the Tools class is loaded once only in both cases. 
// Loading it twice would attempt to define it twice which would cause an error.
require_once $thisFolder . 'Tools.php';

// The following class extends the person class and will make use of the skill class
class Staff extends Person {
	
	private $skills = array(); // the person's list of skills. Each item in the array will be an instance of the skill class. 

	/** 
	*	@param string $skill one of the approved skills
	*   @param int $years count of years experience in this skill
	*	@return mixed TRUE on success, err message on failure
	*/
	public function addSkill($skill, $years) {
		// ensure this skill has not been added already ...
		// We'll need to loop through the list of skill objects, testing each one in turn, which would be very inefficient if each person could have 1000s of skills.
		// Storing the skills in an array, eg array('gardening'=>5, 'maths'=>13) would enable us to use isset() to do this test without any loops.
		// This would obviously be a better design choice, but we'll stick with the class properties for the sake of demonstrating how classes work...
		// Note, implementing classes/properties for everything is not always a good fit, and can complicate things unnecessarily. 

		foreach($this->skills as $s) {
			if($s->getSkill() === $skill) {
				// Notify user
				Tools::msg("'$skill' has previously been linked to {$this->firstName} {$this->lastName}");
				return TRUE; // returning true since user already has skill
			}
		}		

		// create a new instance of the skill class
		$newSkill = new Skills();

		// attempt to set values to this instance
		$result = $newSkill->setSkill($skill, $years);
		if($result !== TRUE) {
			Tools::errMsg($result);
			return FALSE; // adding skill failed, so return FALSE
		}

		// add the skill to this staff member's $skills array
		$this->skills[] = $newSkill;

		Tools::msg("Skill '$skill' added successfully to the repertoire of '{$this->firstName} {$this->lastName}'");
		return TRUE;
	}

	public function getSkill($i) {
		if(array_key_exists($i, $this->skills)) {
			$lastSet = $this->skills[$i]->lastSet;

			$str = $this->skills[$i]->getSkill() . ' (' . $this->skills[$i]->getYears() . ") last updated on $lastSet.";
			Tools::msg("Skill " . ($i + 1) . " for <b>'{$this->firstName} {$this->lastName}'</b>: $str");
			
			return TRUE;
		}

		return FALSE;
	}

	/** 
	 * Displays a list of the staff member's skills   
	 */
	public function getAllSkills() {
		$skills = ''; 
		// Note the $skills variable above occupies a different space in memory than $this->skills. 
		// It would be a better design choice to name it differently though to avoid possible confusion.

		foreach($this->skills as $key=>$s) {
			$lastSet = $this->skills[$key]->lastSet;
			$skills .= "&nbsp;&nbsp;{$s->getSkill()} ({$s->getyears()} yrs) last uppdated on $lastSet.<br>";
		}

		Tools::msg("Skills for <b>'{$this->firstName} {$this->lastName}'</b>:<br>" . $skills);		
	}
}

// Here we could have another class extending person, eg class manager ... 
