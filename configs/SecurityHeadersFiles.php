<?php 

// Control access to your files
header("Cross-Origin-Resource-Policy: same-origin");

// Serve strict CORS headers for resources
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Additional headers to enhance security
header("X-Content-Type-Options: nosniff");

// Respond to preflights. This was added to resolve issue on angular requesting options for cross domain access
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	// Return only the headers and not the content
	// Only allow CORS if we're doing a GET - i.e. no saving for now.
	if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']) && $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] === 'GET') {
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Headers: X-Requested-With');
	}
	exit;

} elseif (isset($_SERVER['HTTP_ORIGIN']))
    header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);


