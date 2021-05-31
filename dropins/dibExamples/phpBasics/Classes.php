<?php

	echo '<h2>-- Classes --</h2>';
	// '<a href="./Classes.php"><b>Next: Classes</b></a><br>		  
	echo '<a href="./Index.html"><b>Back to Main Index</b></a>';
	echo '<br><br>';

/* 	
	-- INCLUDING PHP FILES IN ONE ANOTHER --

	(This section is included here for completeness since it is a core part of PHP projects and often used with classes.
	 It is not needed in Dropinbase projects as will be noted below)

	Technically, we could put the code of an entire application all in one file.
	But this will be harder to maintain than multiple files, where each deals with their own part of the application.

	For this reason PHP provides a way of including files in one another.
	A user may for eg. call http://www.mydomain.com/updateData.php
	The updateData.php file will contain statements that include the code of other files as needed. These files will includes other files etc.

	(In the case of Dropinbase, the Apache webserver reroutes (see the .htaccess configuration file) all URLs to the Dropinbase index.php file.
	The index.php file includes other files etc.)

	There are two main statments used to include files. The details can be viewed here:
	https://www.php.net/manual/en/function.include.php
	https://www.php.net/manual/en/function.require.php

	with their counterparts: 
	https://www.php.net/manual/en/function.include-once.php
	https://www.php.net/manual/en/function.require-once.php

	NOTE: The Dropinbase framework provides convenient ways of including files automatically or manually as needed.
	More about this later ... 		
*/
   

/* 
	-- Classes Intro --

	As mentioned, functions provide a way of grouping statements together.
	Classes can group functions (methods), variables (properties) and constants together to form an "object".

	Multiple instances of this object can be declared and used.
	For eg one can have a Excel class with methods like createFile and addRows etc., and then have multiple instances each managing a separate Excel file.

	If a class is well designed and fit for purpose, they are fun to work with. 
	Developers merely call the class methods to make all kinds of magic happen in the background, eg 

	$xlsx = new Excel("C:/myExcel.xlsx");
	$xlsx->setCell("A1", "Hallo world");
	$xlsx->addRow($myArray);
	etc.

	But beware, not every problem fits this paradigm easily... in which case it is better to avoid classes.

	Below we discuss the basic implementations of functions and classes which should put you well on your way :).

	More information can be found here:
	https://www.php.net/manual/en/language.oop5.php

*/

/* 
	-- Basic Structure -- 

 	Classes are used to declare instances of "objects". Let's use a robot as an example of an object... 
 	A object can be described by its "properties", for eg our robot has two arms and two legs and a fire hose.
 	It can also do certain things, like walk, turn and spurt out water. Class functions are called "methods" (see below).
*/

	class robot {

		// Variables declared on class level are often named "properties"
		// "public" properties and methods are accesible from code outside this class
		// "private" properties and methods are accesible only from within the current class
		// "protected" properties and methods are accessible from within the current class, and classes that extend it (see below)

		// By using private and protected properties and methods, we prevent external code of using our internal variables
		// This safeguards us in case we wish to change our code in the future without breaking the external code
		
		public $color = 'red'; // This variable can be read/set from outside the class, eg $myRobot->color = 'blue';
		private $arms;
		private $legs;
		public $fireHose; 
		protected $name; // The superRobot class below needs to access the value of this property in its think() function, so we declare it as protected.

		const PREFIX = 'R2';

		// The function named "__construct" is special - it is used to initialize the object's properties, 
		//   and is automatically called when an instance of the class is declared 

		public function __construct($name){
			// Use $this-> to reference class properties, and self:: to reference class constants			
			$this->name = self::PREFIX . $name;
		}

		// *** the functions declared on class level are named "methods"

		function walk($steps) {
			// Use double-quotes and curly braces to include class properties in strings
			echo "{$this->name} walked $steps steps forward.<br>";
		}

		function turn($degrees) {
			echo "{$this->name} turned $degrees degrees.<br>";
		}

		function spurtWater($litres, $strength) {
			echo "{$this->name} spurted $litres litres of water at $strength bar.<br>";
		}
	}

	/*
	Note, Classes can also be used as building blocks of other classes.
	For eg. each of the arms and legs can be made up of other classes, like limbs and fingers with their own properties and functions. 

	Once we've completed the coding of all our building block classes and the robot class, we can declare instances of our robot class, and make them perform.
	*/

	echo "<h2>-- Robot class test --</h2>";

	$robot1 = New robot('Alpha');
	$robot2 = New robot('Beta');

	$robot2->color = 'blue';

	$robot1->walk(10);
	$robot1->turn(30);

	$robot2->turn(135);
	$robot2->spurtWater(0.5, 4);

	
	/* 
		-- 3.5 Extending Classes  --
		Classes can "extend" one another (even to multiple leves). Extended classes contain all the methods and properties of their parent classes.
		The following class extends the existing class to create an advanced robot.
	*/
	
	class superRobot extends robot {
		private $deelLearningLevel=1;

		public function think() {
			// $this-> is used to access properties and methods inside the current and parent class 
			$this->color = 'flasing red';

			if($this->name === 'R2D2' || $this->deelLearningLevel > 4) {
				echo "I think I think.<br>";
			}
		}
	}

	$robot3 = New superRobot('D2');
	$robot3->walk(3);
	$robot3->think();

	/* 
		The superRobot class can also have its own walk function and $arms property that will override the corresponding function and property in robot.
		See https://www.php.net/manual/en/language.oop5.visibility.php for more detail.
	/*

	/*
		-- 3.6 Static Properties and Methods

		Classes can have "static" properties and methods, which makes them accessible without the need to first instantiate the class.

			class database extends mySqlClass {
				public static $connectionString = '';

				public static executeSql($sql) {
					// use self:: to reference static properties within this class
					if(self::$connectionString !== '') {

						// use parent:: to reference static properties in the parent class
						$result = parent::execute($sql, self::$connectionString);
						...
					}
				}
			}

		There is now no need to instantiate the "database" class with the New statement. 
		If the above class is in memory (by using for eg. require_once() if it is in a separate file), then the execute function can simply be called as follows:

			database::execute("SELECT * FROM my_table");

		Static methods and properties like the above can be referenced from anywhere, eg from within the methods in our robot or superRobot classes.
		For this reason they are often used in general utility functions such as calls to interface with a database.

		Static and non-static properties and methods can both exist in the same class.
		Static properties exist only once in memory, irrespective of how many times the class was instantiated.
		It is not possible to reference a non-static property from within a function that is declared static. 

		Study the following example (see output below)
*/

class tests {
	public static $stat = 0;
	public $nonStat = 0;

	public static function statTest() {
		self::$stat++;
		echo 'In stat(). Stat value:' . self::$stat . '<br>';
	}

	public function bothTest() {
		self::$stat++;
		echo 'In nonStatTest(). Stat value: ' . self::$stat . '. nonStat value: ' . $this->nonStat . '<br>';
	}
}

echo '<br><br><h2>-- Test of static and non-static properties and methods in a single class --</h2>';

tests::statTest();

$t1 = new tests();
$t1->nonStat = 1; // setting 1st copy in memory
$t1->bothTest();

$t2 = new tests();
$t2->nonStat = 2; // setting 2nd copy in memory
$t2->bothTest();

tests::statTest();

/* 
Output:

In stat(). Stat value:1
In nonStatTest(). Stat value: 2. nonStat value: 1
In nonStatTest(). Stat value: 3. nonStat value: 2
In stat(). Stat value:4
*/

/*
	-- 3.7 Practical Example -- 

	Now let's put it all together and build a set of classes to track company staff and their skills

	Note, as with a typical project, we stored the classes each in their own file and use "require" to load them.
	This improves maintainability and reusability (a certain base class can be reused in multiple places in our project without the other classes)
*/

// Load class files 
// Get the path to the folder they are in (get same folder as the current file, and then add 'classes' subfolder)
$thisFolder = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR;

require $thisFolder . 'Tools.php';
require $thisFolder . 'Staff.php';

// To understand the code below, first inspect the following files in order:
// Tools.php
// Skills.php
// Person.php
// Staff.php

// Now that our classes are in place its like an API 
// We can merely call the class methods to set and get values and display messages 
echo "<br><br><h2>-- Skills App Test --</h2>";

$john = New Staff('Jack', 'Smith');

$result = $john->setDob('January 1 2000');
// Here could be code to handle case if $result is false ... 

$result = $john->addSkill('maths', 5);
$result = $john->addSkill('juggling', 7); // this will fail 
$result = $john->addSkill('snorkeling', 77); // this will fail 
$result = $john->addSkill('snorkeling', 7);
$result = $john->addSkill('maths', 5);  // this will generate a notice

// Display skills
$john->getSkill(0);	// arrays are indexed starting with 0 
$john->getSkill(1);	 

$paul = New Staff('Paul', 'Blaine');

$result = $paul->setDob('January 1st'); // this will fail
$result = $paul->setDob('1991/10/11');

// wait 2 seconds so that we can test lastSet feature
// Note, all output is only "flushed" to the client when the PHP script as a whole is done.
echo "<h3>.... waiting 2 seconds ...</h3>";

sleep(2);

$result = $paul->addSkill('cooking', 15);
$result = $paul->addSkill('maths', 1);
$result = $paul->addSkill('gardening', 27);

$paul->getAllSkills();

// Get the age difference in between Paul and John 
$diff = Tools::dateDifference($john->getDob(),  $paul->getDob());

Tools::msg("Age difference between John (" . $john->getDob() . ") and Paul (" . $paul->getDob() . ") is $diff years.");		

Tools::msg("Count of error messages: " . Tools::$errMsgCount);

echo '<br><br><a href="./Index.html"><b>Back to Main Index</b></a>';