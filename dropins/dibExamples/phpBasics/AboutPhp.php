<html>
<head>

    <!-- *** NOTE: Security measure: always specify UTF-8 encoding, which corresponds with the UTF-8 header which Eleutheria sends to the client -->
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    
    <title>PHP Basics</title>
</head>

<body>

	<?php
	/**
	 * About PHP
	 * 
	 NOTE: Copy all files in this folder to eg /www/phpBasics
	 Then navigate to http://localhost/phpBasics/Index.html
	 * 
	 * FOR A MORE COMPLETE REFERENCE SEE https://www.php.net/manual/en/langref.php
	 */
	?>

	<h2>-- About PHP --</h2>

	<a href="./Variables.php"><b>Next: Variables</b></a> | <a href="./Index.html"><b>Back to Main Index</b></a><br>

	<br>
	PHP is one of the simplest scripting languages to learn, yet powerful and fast enough to run major sites such as Facebook.<br>
	It runs on most platforms, can be used for all kinds of applications, and an abundance of documentation and support exists.<br>
	It has matured over the years from humble beginnings and is one of the most popular languages in use.<br>
	<br>
	<b>More info here:</b><br>
	<a href="https://en.wikipedia.org/wiki/PHP">https://en.wikipedia.org/wiki/PHP</a><br>
	<i>(Wikipedia itself runs on PHP too)</i><br><br>
	<br>



	<?php
	$str = "<b>Open this file in PHP editor and study its contents</b><br>";
	
	$str = "The <b>&lt;?php</b> tag tells the webserver that what follows should be processed by the PHP interpreter program (eg. php.exe on Windows).<br>
			A corresponding <b>?&gt;</b> tag is used to indicate the end of the code block.<br>
			<br>
			PHP projects sometimes have multiple &lt;?php ... ?&gt; sections in files, followed and preceded with HTML or JavaScript code.<br>
			The PHP code is executed on the server and generates custom client-side/browser code (HTML/JavaScript).<br>
			The generated client-side code is inserted between the existing client-side code before everything is sent to the browser.<br>
			<br>
			However, code is more manageable if server-side code and client-side code is kept separate in their own files.<br>
			This is simplified with modern frameworks such as <a href=\"https://en.wikipedia.org/wiki/PHP\">Dropinbase</a>.<br>";
	echo $str;
	?>


	<h2>-- PHP used for Web development --</h2>	

	PHP is a server-side language.<br>
	Typically a web user will navigate to eg. http://localhost/test/myPhpPage.php or click a buton that will send a similar request to the webserver.<br>
	The Apache webserver (or IIS etc.) will receive the request and then open and parse the myPhpPage.php file in the /www/test/ folder.<br>
	(Note, configurations in a php.ini or .htaccess file can determine otherwise).<br>
	Any code found between PHP tags in the file are sent to the PHP executable to process.<br>
	The result is then returned to the browser.<br>
	<br>
	If you are using Chrome, press F12 which will open the Developer Console.<br>
	The Network tab will list all the requests sent to the server.<br>
	Clicking on one will present tabs where the details of the request and the returned result can be inspected.<br>
	<br>
	The very top-left icon in the Console can be used to inspect HTML elements in the current page. <br>
	Click it and then click on any item in the page to view the corresponding HTML/CSS code in the Console. <br>
	<br>
	<br>
	<a href="./Variables.php"><b>Next: Variables</b></a> | <a href="./Index.html"><b>Back to Main Index</b></a><br>

</body>
</html>