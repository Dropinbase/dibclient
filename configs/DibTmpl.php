<?php


if (isset($_SERVER['HTTP_ORIGIN']))
	header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);

header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
// header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
// respond to preflights

//this was added to resolve issue on angular requesting options for cross domain access
// The isset is necessary for Asynchronous PHP threads that skip Apache
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
	// return only the headers and not the content
	// only allow CORS if we're doing a GET - i.e. no saving for now.
	if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']) && $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] == 'GET') {
		// header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Headers: X-Requested-With');
	}
	exit;
}

class DIB {
    // Basic settings    
	public static $ENVIRONMENT='development'; // 'development' = auto-deletion of files, html beautified. 'production' = No deletion, compression of Javascript.
	public static $TIMEZONE='Africa/Johannesburg'; // See http://php.net/manual/en/timezones.php
    public static $SITENAME='Dropinbase'; // The title of the browser tab
	public static $SITELOGO='files/icons/logo.png'; // Available in pef_item.expression

	public static $CACHEUSE=0; // 0 = Always overwrite cache files
							   // 1 = Delete a container's cache file if affected by design changes, else use cache if available (speeds up loads during development). 
							   // 2 = Use a cache file if it exists, else create it
							   // 3 = Allways use cache (assume all necessary files exist)
	const DBINDEX=1; // id/index value of the main Dropinbase database in pef_database and Conn.php
	const LOGINDBINDEX=1; // Database containing the pef_login and pef_security_policy tables
	public static $AUDITDBINDEX=1; // Database containing the default pef_audit_trail table (override this value using pef_container.pef_audit_trail_table_id). NOTE: Must also change pef_database_id in pef_table for 'pef_audit_trail'. Don't remove pef_audit_trail from the DIB database - it is still needed here to store eg Designer changes.
	
    public static $DEBUG_LEVEL=2; // 0 = no errors logged to error.log. 1 = errors logged with some detail. 2 = most detail logged.
    public static $CLIENT_DEBUG_LEVEL=1; // 0 = no debug messages printed in client Console. 1 = debug messages printed in client Console
    public static $ELEUTHERIA_DEBUG_LEVEL=2; // 0 = no pre-emptive syntax checking or Eleutheria error reporting.  1 = pre-emptive syntax checking.
                                             // 2 = SECURITY RISK: Adds echo-ing of errors to (1). 3 = SECURITY RISK: Adds creation of detailed trace file to (2).
	public static $LOGPERMISSUES=1; // 0 = Don't log anything. 1 = Log permission issues. 2 = Log permission and authentication issues.
	public static $INFORM_ADMIN_ERRORLEVEL=2; // Administrator will be informed of any error logged with level equal to or higher than this value. Errors logged with unspecified level defaults to 3.
	public static $ADMIN_EMAIL=null; // Administrator's email address. NOTE: See /config/mail.php for mail account settings	
	public static $VERIFY_IP = TRUE; // Whether successive requests from the same web user must originate from the same IP address. 
	public static $VERIFY_USER_AGENT = TRUE; // Whether successive requests from the same web user must have the same USER AGENT. 
	public static $VERIFY_AUTH_TOKEN = TRUE; // Whether authentication tokens are checked on server requests
	public static $USERNAME_REGEX='#^\w{4,30}$#'; // A semicolon delimited list of regular expressions that must validate successfully in order for usernames to be accepted
    public static $USERNAME_REGEXMSG='The username must contain between 4 and 30 alpha-numeric characters (no spaces, but underscore (_) is allowed).';
	public static $PUBLICFILEPERMS = array( 
		'allow_uploads'=>TRUE, // Allow system_public user to upload files.
		'allow_downloads'=>TRUE, // Allow system_public user to download files.
		'allow_deletes'=>TRUE, // Allow system_public user to delete files.
	);	

	// Paths to the possible ui dropin index files used to bootstrap the application
	public static $INDEXPATH=array(
								'setNgMaterial'=>'/setNgMaterial/dibAngular/dist/index.html',
								'setSencha'=>'/setSencha/dibSencha/src/index.php',
						 	); // Path to the index file in a dropin used to bootstrap the application
	public static $DEFAULTFRAMEWORK='setNgMaterial'; // client framework to load at startup
	public static $OVERRIDEQUEUEWITH = 'None'; // None/NodeJs (Note, NodeJs requires expertise to maintain and run stably in some client environments)
	public static $NODEJSHOST=null; // NodeJs server connection details (eg 'http://localhost:8080'), OR null (NodeJs will not be initialized)
	public static $ASYNCRETRYCOUNT=10; // Default count of tries the client will poll for actions in the Queue, before giving up. Can be set dynamically using Queue::updateIntervals().
	
	public static $SETUPSCRIPT=null; // Path to any script that is run just before calls to any controllers are made, eg '/dropins/myDropin/components/setValues.php'
	public static $AFTERLOGINSCRIPT = null; // Path to any script that is run just after a user has manually logged in, eg '/dropins/myDropin/components/RunOnceDaily.php'
	public static $RECORDUNITTEST=FALSE; // FALSE, or TRUE (or Batch Name) - Whether all requests must be recorded in pef_unit_test
	public static $ALLOWEDCHARS=array(' ','_'); // Array of allowed characters (other than non-aplhanumeric) in Submission Data validated by the s_ prefix
    public static $PARAMVALIDATION=FALSE; // Whether global validation of Submission Data parameters prefixed with a_ or n_ must occur.
	
	public static $USERSPATH='C:/dibUploads/'; // Physical path to user-file uploads folder (NOTE, keep outside webserver's reach for security reasons)
											   // Note, use forward slashes ... back-slashes escape characters... 	
											   // IMPORTANT: See https://www.owasp.org/index.php/Unrestricted_File_Upload
	   
	// A SQL statement that is executed when users authenticate. The return values, if any, are added to the PHP session (accessible via DIB::$USER) and replace any existing values.
	// Eg. Null, OR array('databaseId'=>self::LOGINDBINDEX, 'sql'=>'SELECT first_name, last_name, company_id FROM staff WHERE id = :staff_id', 'params'=>'staff_id')
	// The 'params' argument is a semicolon-delimitted list of parameter names used in 'sql' and must match field names in pef_login.
	// NOTE: Do not override fields used by the system (eg username, email, admin_user, etc.) unless eg CrudEvents is used to maintain them in pef_login.
	public static $SESSIONINCLUDE = NULL;
	
    // Values generated automatically:
	public static $BASEURL='~baseurl~';
	public static $BASEPATH='~rootdir~';
    public static $DROPINPATHDEV='~rootdir~dropins~dirsep~';
    public static $RUNTIMEPATH='~rootdir~runtime~dirsep~';
    public static $FILESPATH='~rootdir~files~dirsep~';
	public static $SYSTEMPATH='~systemdir~';
    public static $EXTPATH= '~systemdir~extensions~dirsep~';
    
	// Values set dynamically with every request
	public static $CRUDFILE; // Path to container or dropdownlist crud file for the current user (set just before crud operation is performed).
	public static $CACHEPATH; // Folder where current container's cache files are stored for the current user (incorporates user's Source Folder). Is set when container permissions are checked.
	public static $DROPINPATH; // Path to either the system or user dropin folder, depending on the current request. 
                               // If it is not a dropin request, the path will point to the /droinbase/ folder (same as the $BASEPATH).
	public static $USER; // Array of all fields in pef_login (except password, dib_password, and notes)
	public static $DATABASES; // Array of connection details to all databases in pef_database
	public static $PERMGROUP; // Combination of the permission groups the user currently has rights to
	public static $LOCALE;	// The user's language (from pef_login)
	public static $RETURN_URL; // Dynamically set - remembers requested page when user is not logged in and returns to it after login.
	public static $CONTROLLER; // Name of the controller referenced by the current request
	public static $ACTION; // Name of the controller action
	public static $CONTAINERDATA; // semicolon delimitted list of the current container's info obtained from permission files
	public static $ITEMLISTDATA; // PrimaryTableId(if table list)/'D'DatabaseId(if SQL list)
    
}