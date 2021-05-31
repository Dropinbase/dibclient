<?php

echo '<h2>-- Functions --</h2>';
	echo '<a href="./Classes.php"><b>Next: Classes</b></a> | <a href="./Index.html"><b>Main Index</b></a>';
	echo '<br><br>';

 /* 
	-- Functions Intro --
	
	Functions provide a convenient way of grouping statements together that perform a certain task.
	Each time a certain task is required, its corresponding function can be called.
	Functions are normally called with parameters which are used to customize the execution of the task.

	Your own functions can be called in a very similiar way that PHP functions are called.
	The only difference is when functions are declared inside classes they will have a prefix.

	Below we discuss the basic implementations of functions which should put you well on your way :).

	More information can be found here:
	https://www.php.net/manual/en/language.functions.php
*/

/* 
	Practical example of a function
*/

// The printStars function prints a specified count of spaces, followed by a specified count of stars

function printStars($indent, $countOfStars, $col) {	
	// specify $indent x spaces 
	$str = str_repeat('&nbsp;', $indent); // See https://www.php.net/manual/en/function.str-repeat.php\
	// add $countOfStars stars (wrappped in <span>...</span> to specify color) and a line break
	$str .= "<span style=\"color:$col\">" . str_repeat('*', $countOfStars) . '</span><br>';

	// Insert the value of $str where the function was called from 
	return $str;
}

echo '<h2> -- Functions Xmas Tree --</h2>';
echo "<b>calling printStars(10, 5, 'blue')</b><br><br>";

echo printStars(10, 5, 'blue');

// This function can now be called repeatedly to create various parts of a tree 

function xmasTree($width, $trunkHeight) {
	// Some validation 
	if($width > 40 || $width < 5)
		return 'Width must be between 5 and 40';

	$str = '';

	// Create tree top
	for($i=0; $i<=$width; $i++)
		$str .= printStars (50 - $i, $i + 1, 'limegreen');	

	// Create trunk
	for($i=1; $i<=$trunkHeight; $i++)
		$str .= printStars (50 - 2, 4, 'brown');
		
	return $str;
}

// The xmasTree function can be called to create trees of various sizes

echo '<br><br><b>Here is our tree using xmasTree(15, 5)</b><br>';
echo xmasTree(15, 5);

echo '<br><br><b>And a mini using xmasTree(10, 3)</b><br>';
echo xmasTree(10, 3);

// Imagine how many lines of code is saved by using functions!

/* -- Exiting functions --

	If you need to exit the current code block (eg. function) completely at any time (even within a loop) then use the 'return' statement
	If no return value or return statement is specified, then NULL is returned by default to the statement that called the function.

		if($i >= 25) 
			return; // this returns NULL.
		elseif($i >= 10)
			return $i;

	If you need to completely quit any further code execution, then use die();
	
		if($i > 5)
			die();

	PHP will automatically clean-up any information held in memory before quiting.		
*/

/*
	-- Function parameters with default values --

	If no default value is specified for a parameter, then a value must be supplied when calling the function.
	Parameters with default values must all be at the end of the list of parameters, else they have no effect.

	Eg. 	
	function testDefault($a=1, $b) {
		echo $a + $b;
	}

	// Neither of the following is allowed
	testDefault (,2);
	testDefault (2);

*/

/*
	-- Passing parameters by reference --

	By default, function arguments are passed by value.
	If the value of the argument within the function is changed, it does not get changed outside of the function. 
	To allow changes to arguments to be visible outside the function, they must be passed by reference.
	Simply prefix the desired arguments with an ampersand (&) in the function definition.
*/

	function testRef(&$a, $b=5) {
		$a = $a + $b;
		return "$a x $b = " . $a * $b;
	}

	echo '<br><br><h2> -- Pass by Reference Test --</h2>';	

	$val = 2;
	echo "\$val is now $val.<br>";
	echo testRef($val) . '<br>';
	echo "And now \$val's value is $val";

/*	
	In effect PHP passes a pointer to the variable ($val) in memory, instead of creating a new memory space for the argument ($a).
	Note, you cannot send a hard-coded value (eg 5) to an argument declared by reference (because its value can't change).

	This will cause an error:
	testRef (5); 
*/


/*
	-- Function parameter type declarations --

	PHP does not require you to declare the types of variables, which makes it possible to pass for eg. a boolean or a string to the same parameter in separate calls.
	This can lead to code that is more flexible, but harder to debug, which is why many developers prefer languages that restrict code in this way.
	
	From PHP 7.x onwards you can indicate the desired parameter type with one of the following prefixes:

		array, int, string, float, bool  (Note: boolean is NOT an alternative for bool and will cause errors)

	Other allowed types (see https://www.php.net/manual/en/functions.arguments.php for more info):

		class name, callable, self, iterable, object

	Example:

		function doSomething (int $a, bool $b=FALSE) { ... }
	
	NOTE, the above does not enforce types. PHP will coerce values of the wrong type into the expected scalar type if possible. 
	For example, a function that is given an integer for a parameter that expects a string will get a variable of type string.

	To enforce types, you must have the following declare statement in your code:
	
		declare(strict_types=1);
		
	See more info about Strict Typing here:
	https://www.php.net/manual/en/functions.arguments.php
}
	
   
/*
	-- Recursive functions --
	These are functions that call themselves until a certain end condition is met.
	Some of the most elegant pieces of code are well written recursive functions. 

	Let's look at an example that adds the list of numbers smaller than n.
	For eg. if n = 4, then we want to calculate 1 + 2 + 3 + 4 = 10. 
*/


function sumOfNumbers($n) {
	if($n === 1)
		return $n;
	else 
		return $n + sumOfNumbers($n - 1);
}

echo '<br><br><h2> -- Recursion Test --</h2>';
echo 'sumOfNumbers(5) = ' . sumOfNumbers(5);

// If we want to list the numbers being added, we can include a function argument passed by reference: 

function sumList($n, &$list) {
	$list = $n . ' + ' . $list;
	if($n === 1)
		return $n;
	else 
		return $n + sumList($n - 1, $list);
}

$list = '';
$answer = sumList(5, $list);
// Remove trailing ' + ' characters from $list
$list = substr($list, 0, -3);
echo "<br>$list = " . $answer;

/*
	Just for fun ... 
	The following function uses the sin() function to print the classic sine wave
*/


function wave($step, $width=50, $end=360) {
	// Do some validation 
	if($end < 90 || $end > 360) {
		echo "The end degree must be between 90 and 360";
		return;
	}

	if($width < 40 || $width > 200) {
		echo "The width must be between 40 and 200";
		return;
	}

	// Loop through 360 degrees
	for($deg = 1; $deg <= $end; $deg = $deg + $step) {
		// Convert the degrees to radians
		$radians = deg2rad($deg);
		// Calculate how many spaces to print before printing 'hello'
		// Get the sine, and multiply it with $width. Add $width since sine returns a negative value between 180 and 360		
		$n = $width + (sin($radians) * $width);
		// Print $n x spaces, followed by the word 'hello' and a new line character
		echo str_repeat('&nbsp;', $n) . 'hello<br>';	
	}
}

echo '<br><br><h2> -- Wave hello --</h2>';
wave(10);
wave(20);

echo '<br><br><a href="./Classes.php"><b>Next: Classes</b></a> | <a href="./Index.html"><b>Main Index</b></a>';

