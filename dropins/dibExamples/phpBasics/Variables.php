<?php

	echo "<h2>-- PHP Variables --</h2>";

	echo '<a href="./Conditions.php"><b>Next: Conditions</b></a> | <a href="./Index.html"><b>Back to Main Index</b></a>';
	echo '<br><br>';
	
	/* Basic manipulation of variable values  */ 
	
	$i = 20; // create a new variable and set its value to 20
				// its visibility scope will be limitted to this function only (since it was initialized within it)
	$i = $i + 10; // add 10 to $i 		
	$i++; // add 1 to $i 
	$i--; // subtract 1 from $i
	$i += 5; // add 5 to $i
	$i -= 5; // subtract 5 from $i

	$k = $j = 50.5; // Assign value of 4 to both new variables $k and $j
	$k = 50.5; $j = 50.5; // statements are separated by ; which means more than one statement can be on a single line and one statement can span multiple lines

	const MYCONST = "this value can't change"; // declare a constant value (it is a convention to use all caps, but developers often don't)

	/* 
		Multiple $i's value with 10, 
		then add the value in the $p class variable (50.5), 
		and then divide the result by 5 and store the final result in $i again.
	*/
	$i = (($i * 10) + $k) / 5;  // $i now contains 70.1

	// variables declared inside classes are often called class properties (more about this later)
	// the -> operator can be used to access (non-static) property values from within class functions (methods)
	$str = 'hello'; // set the value of the class property $str
	$str = $str . ' world'; // append the string ' world' (the . operator is used as "glue")
	$str .= ' again.'; // a shortcut way to append the string ' again.'
	$str .= ' I\'ve learnt '; // append another string and use \ to escape the ' character inside the string
	$str .= MYCONST . ".\r\n"; // add the value stored in our class constant, followed by a newline character

	// By using double-quotes ("), variables' values can be included inside strings, and escaped with \
	$str .= "The answer stored in variable \$i is indeed $i.\r\n";
	// The above will now contain: 
	// The answer stored in variable $i is 70.1.

	// Note, if double-quotes are used, single quotes inside the string are treated like any other character
	$str .= "Our variable '\$k' contains $k.";

	// Lets send the contents of $str to the browser using the "echo" statement
	// Note that PHP statements can span over multiple lines
	// The HTML tags <h2> ... </h2> are used to define a heading

	// Newline characters may make the resulting HTML easier to read, but they do not display in browsers, so we wherever they are in $str we add '<br>'
	$str = str_replace("\r\n", "\r\n<br>", $str);
		
	echo '<h2>Playing with variables</h2>' . 
			"Here we go:<br>" . 
			'We calculated $i as ' . $i . " and built \$str to contain:<br><b>$str</b>";

	/* The above will now print:
		Playing with variables
		Here we go:
		We calculated $i as 70.1 and built $str to contain: hello world again.. I've learnt this value can't change.
		The answer stored in variable $i is indeed 70.1.
		Our variable '$k' contains 50.5.
	*/					 

	// Declare an array of values 
	$values = array(2, 4.5, 'apple', $str);

	// Get the first and third value in the array:
	$str = 'I have ' . $values[0] . ' ' . $values[2] . 's'; // $str will now contain 'I have 2 apples'
	// Instead, use curly braces to include values from the array inside the string:
	$str = "I have {$values[0]} {$values[2]}s, followed by our same long string:<br>
			<b>{$values[3]}</b>";

	echo "<br><br>$str"; // <br> is used in HTML print a new line

	/* A common question is when to use " or '
		" is handy if you need to include values from variables and it also makes code easier to read.
		' should execute (very slightly) faster since no extra compiling needs to be done, but this is really only a factor with very large strings or long running scripts.

		Be very careful when including values that originate from users since they could contain malicious code. 
		If you are building a SQL string rather use PDO variables to include user values.
		If you are building a string that will be displayed to a user or injected into an Excel document etc. 
			then make sure the values are in a whitelist (blacklists are deemed unsafe).
		NEVER trust values that originate from users, and be aware of the context they are used in.
	*/

	// Use custom index values:
	$record1 = array(
		'age' => 10,
		'length' =>  6.2,
		'name' => 'John',
		'notes' => $str 
	);

	$record2 = array(
		'age' => 5,
		'length' =>  4.1,
		'name' => 'Jill',
		'dob' => '2015-01-01', // extra field 
		'notes' => '' 
	);

	// Get the difference in ages:
	$str = 'John is ' . ($record1['age'] - $record2['age']) . ' years older than Jill.';

	echo "<br><br>$str";

	// store both records in a single array
	// Note, unindexed values in an array are indexed automatically starting with 0
	// So $record1 will be stored at position 0 in the $ourRecords array
	$ourRecords = array($record1, $record2);

	// Get the two lengths:
	$jillLength = $ourRecords[1]['length'];
	$str = "John's length is {$ourRecords[0]['length']} feet, while Jill's is $jillLength";		

	echo "<br><br>$str";
	
	echo '<br><br><a href="./Conditions.php"><b>Next: Conditions</b></a> | <a href="./Index.html"><b>Back to Main Index</b></a>';

// NOTE: the PHP end-tag below is strictly not needed and can cause issues in API code if it is followed by any characters like \r\n which will be sent to the browser!
?>