<?php
if($_POST['throwException'] == true)
{
	throw new Exception("this is a test");
}
else
{
	NodeJs::msgHeader('Notice', "Not throwing an exception!", 3000, 'notice');
}
?>