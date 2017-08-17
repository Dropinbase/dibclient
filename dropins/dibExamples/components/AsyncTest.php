<?php
// Script called asynchronously from PhpController.php for demo purposes

// Get variables passed to script
$msg = $AVars['msg'];
$another = $AVars['another_var'];

// do nothing for 5 seconds
sleep(5);

// Display message to user (messages containing 'error' cause an exception):

if(strpos($msg, 'error') !== FALSE)
	throw new Exception("Your message contained the word 'error'...");
else
	Queue::addMsg('Your message:', $msg, 'dialog');
