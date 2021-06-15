<?php
	echo '<h2>-- Conditions --</h2>';
	echo '<a href="./Loops.php"><b>Next: Loops</b></a> | <a href="./Index.html"><b>Back to Main Index</b></a>';
	echo '<br><br>';

	$a = 2;
	$b = 4;

	// Basic comparisons
	if($a < $b) $c = 3; // $c will now contain 3 
	if($a > $b) $d = 3; // $d will now NOT be instantiated and if used below this line will cause an exception.

	// If true, execute more than one statement
	if($a < $b) {
		$c = 3;
		$e = 4;
	}

	// use of && (AND) and || (OR)
	if($a > 1 && $a < 4) $e = 5; 

	// NOTE if you have both && and || in the same expression then always use brackets () to determine the exact flow and improve code readability:
	if($a > 1 || ($a < 4 && $b > 6)) $e = 5;

	// Expressions between brackets are evaluated before the rest that are not. Brackets can be nested to any level.

	// Use of else and elseif (remember that $c is now 3)
	if($a > $b)
		$c = 5;
	elseif($c > 4)
		$c = 6;
	elseif($c < 1) {
		$c = 7;
		$e = 5;
	} else 
		$c = 8;

	// $c now has a value of 8 
	echo "The value in \$c is now $c";

	// Nested if statements are often used		
	if($a < $b) {

		if($b > 5) {
			$c = 7;
			$e = 5;

		} elseif ($b > 3) {
			if($b < 8)
				$e = 8;
			else 
				$e = 7;
		} 

	} else {
		$e = 2; // if there is only one statement in a block, the curly braces are optional (see above)
	}

	echo "<br><br>The value in \$e is now $e";

	// ! is used to indicate NOT 
	if(!($a < $b)) $e = 4;

	// When testing for equal values, the double equal operator (==) is used
	$i = 1;
	$j = '1';

	if($i == $j) $c = 3; // $c is now 3
	/* Important, the == operator compares values after 'type juggling' 		   
		So the following lists contain values that are equal:
		1 == '1' == true
		0 == '0' == '' == false

		When booleans or null are in a comparison, all values are converted to boolean and then compared, eg 
		1 == TRUE
		but
		100 < TRUE     will evaluate to FALSE since 100 is converted to TRUE

		Since strings are first converted to numbers and then compared, watch out for these:
		'a' == 0   // Here 'a' is converted to '' which is equal to 0
		'1e4' == 10000    // 'e' is special since it is used to indicate an 'exponent' of 10  (scientific notation of numbers)
		
	*/
	
	// To include checking of values as well as types, use === 
	// (As a safeguard, use === where type juggling can cause problems as noted above)
	if($i === $j) $c = 4; // $c's value did not change

	// It is also a COMMON error to use = instead of ==
	if($m = 2) $c = 5; 
	// $m is now 2 and $c is now 5

	// Use != for not equal, and !== to avoid type juggling
	if($i != $j) $c = 6; // $c has not changed 

	if($i !== $j) $c = 6; // $c is now 6;

	/* 
		Other operators:
		<   smaller than 
		>   greater than
		<=  smaller or equal than
		>=  greater or equal than
	*/

	// Useful shortcut alternative to 'if'

	$a = ($i == 1) ? 4 : 5;
	// This evaluates the condition ($i == 1) and since it is true, $a is set to 4 (else 5)

	// Used with expressions:
	$a = ($i != 1) ? "Hello world $i" : (4 * 6) + 1; // $a is now 25
	echo "<br><br>The value in \$a is now $a";

	// The empty() function is often used in if statements:
	// More info here: https://www.php.net/manual/en/function.empty.php

	$u = null; // null is generally used to indicate a value that is not yet set
	$myValue = 1;
	if(empty($u) || !empty($myValue)) $e = 5; // Note the use of !, meaning NOT
	
	/* Beware... the following values will all return TRUE with empty(): 
		0   null   false   ''
		Also empty($blah) will return TRUE if $blah has not yet been initialized anywhere. 
	*/

	// The switch statement is handy if a certain variable must be compared to multiple possible values
	//   and the corresponding statements must be executed:
	
	$food = 'bar';

	switch ($food) {
		case 'apple':
			$str = 'food is apple';
			$i = 4;
			break;

		case 'cucumber':
			$str =  'food is cucumber';
			$a = 5;
			break;

		case 'bread':
		case 'cake':
			$str =  'food is carbs';
			break;

		default: 
			$str = 'food is UNKNOWN';
	}

	// If food is 'bread' or 'cake' then $str is set to 'food is carbs'
	// If food is neither 'apple', 'cucumber', 'bread' or 'cake' then $str is set to 'food is UNKNOWN' which is what occurs above.

	echo "<br><br>The value in \$str is now $str";

	echo '<br><br><a href="./Loops.php"><b>Next: Loops</b></a> | <a href="./Index.html"><b>Back to Main Index</b></a>';
	