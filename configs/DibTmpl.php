<?php
header("Access-Control-Allow-Origin: *");
class DIB {
    // Basic settings    
	public static $ENVIRONMENT='development'; // 'development' = auto-deletion of files, html beautified. 'production' = No deletion, compression of Javascript.
	public static $TIMEZONE='Africa/Johannesburg'; // See http://php.net/manual/en/timezones.php
    public static $SITENAME='Dropinbase'; // The title of the browser tab
	public static $SITELOGO='files/icons/logo.png'; // Available in pef_item.expression
	
    public static $DEBUG_LEVEL=2; // 0 = no errors logged to error.txt. 1 = errors logged with some detail. 2 = most detail logged.
    public static $CLIENT_DEBUG_LEVEL=1; // 0 = no debug messages printed in client Console. 1 = debug messages printed in client Console
    public static $ELEUTHERIA_DEBUG_LEVEL=2; // 0 = no pre-emptive syntax checking or Eleutheria error reporting.  1 = pre-emptive syntax checking.
                                             // 2 = SECURITY RISK: Adds echo-ing of errors to (1). 3 = SECURITY RISK: Adds creation of detailed trace file to (2).
	public static $LOGPERMISSUES=1; // 0 = Don't log anything. 1 = Log permission issues. 2 = Log permission and authentication issues.
	public static $INFORM_ADMIN_ERRORLEVEL=2; // Administrator will be informed of any error logged with level equal to or higher than this value.
	public static $ADMIN_EMAIL=null; // Administrator's email address. NOTE: See /config/mail.php for mail account settings
	
	public static $CACHEUSE=0; // 0 = Always overwrite cache files (also occurs in 'development' mode); 
							   // 1 = Delete a container's cache file if affected by design changes, else use cache if available (speeds up loads during development). 
							   // 2 = Use a cache file if it exists, else create it; 
							   // 3 = Allways use cache (assume all necessary files exist)
	public static $INDEXPATH=array(
								'setNgMaterial'=>'/setNgMaterial/dibAngular/dist/index.html',
								'setNgxMaterial'=>'/setNgxMaterial/angular/dist/browser/index.html',
								'setSencha'=>'/setSencha/dibSencha/src/index.php',
						 	); // Path to the index file in a dropin used to bootstrap the application
	public static $DEFAULTFRAMEWORK='setNgxMaterial'; // client framework to load at startup
	public static $OVERRIDEQUEUEWITH = 'None'; // None/NodeJs (Note, NodeJs requires expertise to maintain and run stably in some client environments)
	public static $NODEJSHOST=null; // NodeJs server connection details (eg 'http://localhost:8080'), OR null (NodeJs will not be initialized)
	public static $ASYNCRETRYCOUNT=10; // Default count of tries the client will poll for actions in the Queue, before giving up. Can be set dynamically using Queue::updateIntervals().
	
    public static $SETUPSCRIPT=null; // Path to any script that is run just before the call to the controller is setup.
    public static $RECORDUNITTEST=FALSE; // TRUE or FALSE - Whether all requests must be recorded in pef_unit_test
    public static $PARAMVALIDATION=FALSE; // Whether global validation of Submission Data parameters prefixed with a_ or n_ must occur.
    public static $ALLOWEDCHARS=array(' ','_'); // Array of allowed characters (other than non-aplhanumeric) in Submission Data validated by the s_ prefix
    public static $USERNAME_REGEX='#^\w{4,30}$#'; // A semicolon delimited list of regular expressions that must validate successfully in order for usernames to be accepted
    public static $USERNAME_REGEXMSG='The username must contain between 4 and 30 alpha-numeric characters (no spaces, but underscore (_) is allowed).';
    public static $USERSPATH='C:/dibUploads/'; // Physical path to user-file uploads folder (NOTE, keep outside webserver's reach for security reasons)
    											 // IMPORTANT: See https://www.owasp.org/index.php/Unrestricted_File_Upload
    
    // Database connection index to the main Dropinbase database in Conn.php and pef_database
	const DBINDEX=1; // id value of the main Dropinbase database in pef_database
	const LOGINDBINDEX=1; // id value of the database containing the pef_login and pef_security_policy tables
	public static $AUDITDBINDEX=1; // Determine where the audit trails will be stored
	
	// A SQL statement that is executed when users authenticate. The return values, if any, are added to the PHP session and replace any existing values.
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
	public static $CONTAINERDATA; // semicolon delimitted list obtained from A-permission files: ContainerName;ContainerId;Model;DatabaseId;TableName/pef_sql_id;Crud Path;Cache Path;Criteria from pef_perm_record_temp specifying permissions to open container('##A' = all, '##C'= prefix to criteria)
	public static $ITEMLISTDATA; // PrimaryTableId(if table list)/'D'DatabaseId(if SQL list)
    
}