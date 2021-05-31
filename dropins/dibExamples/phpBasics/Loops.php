<?php

	echo '<h2>-- Loops --</h2>';
	echo '<a href="./Functions.php"><b>Next: Functions</b></a> | <a href="./Index.html"><b>Main Index</b></a>';
	echo '<br><br>';

	// Simple loop with an exit condition
	
	$ourRecords = array(); // we'll be adding records to this array

	// $i starts with a value of 65. Before each loop $i is tested to be smaller than 70. After each loop, $i is incremented with one.
	for($i = 65; $i < 70; $i++) {
		$record = array(
			'number'=>$i,
			'letter'=>chr($i) // The chr() function returns the corresponding character in the ASCII table 
								// (https://www.rapidtables.com/code/text/ascii-table.html)
		);
		
		// Add this record to the array 
		// More info here: https://www.php.net/manual/en/function.array-push

		array_push($ourRecords, $record);
		// the following code would also work:
		// $ourRecords[] = $record;
	}

	/*  we now have:
		$ourRecords = array(
			0 => array('number'=>65, 'letter'=>'A'),
			1 => array('number'=>66, 'letter'=>'B'),
			2 => array('number'=>67, 'letter'=>'C'),
			3 => array('number'=>68, 'letter'=>'D'),
			4 => array('number'=>69, 'letter'=>'E')
		)

		NOTE: The Database::execute statement returns array with a similar structure:
		$ourRecords = Database::execute("SELECT id, name FROM test_company");
	*/

	// We can print the values in variables by using the var_dump() function 
	// Note, this is often done during debugging to inspect the values in an array 
	echo "<b>Using var_dump() to inspect \$ourRecords</b><br>";
	var_dump($ourRecords);

	// Alternatively, first encode the array to a string using the JSON format
	echo '<p><b>Using json_encode():</b><br>' . json_encode($ourRecords) . '</p>';
	
	// The foreach statement is often used to loop through records returned by a Database::execute statement...

	echo "<br><br>Building \$letterList:";
	$letterList = '';

	foreach($ourRecords as $index=>$record) {
		$letterList .= $index . '. ' . $record['number'] . ' - ' . $record['letter'] . '<br>';
		echo "<br>$letterList";
	}

	/* $letterList now contains:
		0. 65 - A
		1. 66 - B
		2. 67 - C
		3. 68 - D
		4. 69 - E
	*/

	// While loops continue until a certain condition is reached 
	echo "<br><br>Building \$numberList:";
	$i = 0;
	$numberList = '';

	while ($i < 3) {
		$letter = $ourRecords[$i]['letter'];
		$numberList .= "{$ourRecords[$i]['number']}. $letter, ";
		echo "<br>$numberList";
		
		$i++;
	}

	/* $numberList now contains:
		65. A, 66. B, 67. C, 

		We could remove the trailing comma like so:
		$numberList = substr($numberList, 0, -2);

		The official online PHP site is normally the best resource for help. 
		Google for eg. 'php substr' and follow the result under https://www.php.net
	*/


	// do ... while loops work exactly the same except that the condition is tested at the end of the loop
	//   meaning that the statements inside the loop are executed at least once (even if the condition is not met)

	echo "<br><br>Building \$newList:";
	$i = 0;
	$newList = '';

	do {
		$letter = $ourRecords[$i]['letter'];
		$newList .= "{$ourRecords[$i]['number']}. $letter, ";
		echo "<br>$newList";

		$i++;
	} while ($i < 3);

	/* $newList now contains the same list:
		65. A, 66. B, 67. C, 

		Note, if $i was set to 3 before the "while" and the "do ... while" loop, only the the latter loop would have executed (once).
	*/		

	// If you need to exit loops conditionally use the break statement 
	// If you need to skip certain statements within the loop (and continue at the end of the loop), use the continue statement 

	echo "<br><br>Building \$exitList:";
	$i = 0;
	$exitList = '';

	do {
		$letter = $ourRecords[$i]['letter'];

		if($letter == 'B') {
			$i++;
			continue;
		}

		$exitList .= "{$ourRecords[$i]['number']}. $letter, ";
		echo "<br>$exitList";

		if(strlen($exitList) > 19) break;

		$i++;
	} while ($i < 5);

	/* $exitList contains the same list, except it is missing the 2nd row. Note the exit condition says $i < 5
		65. A, 67. C, 

	*/		

	echo '<br><br><a href="./Classes.php"><b>Next: Classes</b></a> | <a href="./Index.html"><b>Main Index</b></a>';