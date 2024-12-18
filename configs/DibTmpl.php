<?php

// *** NOTE, the /configs/Dib.php file is auto-generated when deleted, based on the /configs/DibTmpl.php template file
//     Deleting it triggers a script that ensures certain key files and permission records are in place for DIB to function properly

header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Respond to preflights
// This was added to resolve issue with Angular requesting options for cross domain access
// The isset is necessary for Asynchronous PHP threads that skip Apache
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
	// Return only the headers and not the content
	// Only allow CORS if we're doing a GET - i.e. no saving for now.
	if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']) && $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] == 'GET')
		header('Access-Control-Allow-Headers: X-Requested-With');
	
	exit;
}

class DIB {

	// Uncommenting the following array will put the site in Maintenance Mode
	// See coverPage setting below for configuring the layout
    /*
    public static $SITEMAINTENANCE=array(
        'startTime' => '2024-02-20 15:01', // date & time at which site will become unavailable and 'coverPage' is displayed instead
        'warningMsg' => 'The site will be down for upgrades & maintenance from xxx today for about xxx minutes.', // 10 minute warning message for users
        'coverPageMsg' => 'Oops!<br>Temporarily unavailable due to scheduled upgrades &amp; maintenance<br>We should be up again soon, please check in later.',
        'coverPage' => 'sitemaintenance.php', // PHP file in /dropins/dibAuthenticate/views/ that returns HTML to display after startTime
        'allowedIps' => array() // list of IP addresses that are allowed to use the site while maintenance is active
    );
    */
    public static $SECRETSPATH = '~rootdir~configs~dirsep~'; 

	// Database indexes
	public static $DIBDB=1; // id/index value of the main Dropinbase database in Conn.php and the pef_database table
    public static $LOGINDB=1; // Database containing the pef_login, pef_perm_group and pef_security_policy tables	
	// NOTE: add more constants or variables here to use in your own PHP for other databases... 
	public static $ANGULARPATH = ''; // if empty then default folder in /dropinbase/dropins/setNgxMaterial/angular is assumed

    public static $ERRORLOGDB=1; // database containing the pef_error_log table where errors are logged. 
                                    // Note, also update the pef_sql.pef_database_id of the two or more 'qdibErrorLog...' query(ies) if needed, which determine which pef_error_log table to look at - there can (erroneously) be more than one in different databases.
    public static $SQLLOGDB=null; // id value of the database containing the pef_sql_log table. If not null, then ALL SQL statements except SELECT ... are logged with their paramater values.	
	public static $AUDITDB=1; // Database containing the default pef_audit_trail table (override this value using pef_container.pef_audit_trail_table_id). NOTE: Must also change pef_database_id in pef_table for 'pef_audit_trail'. Don't remove pef_audit_trail from the DIB database - it is still needed here to store eg Designer changes.
	
	
	// ***NOTE: add more constants/variables here to use in your own PHP for other databases... 


    // Basic settings
	public static $ENVIRONMENT = 'development'; // 'development' = auto-deletion of files, html beautified. 'production' = No deletion, compression of Javascript.
	public static $TIMEZONE = 'Africa/Johannesburg'; // See http://php.net/manual/en/timezones.php
    public static $SITENAME = 'Dropinbase'; // The title of the browser tab
	public static $SITELOGO = 'files/icons/logo.png'; // Used in Environment.php settings to make available client-side via the getEnv() function

	// Logs and error reporting
	public static $WRITE_ERRORS_TO = 'db'; // Write errors to file/db/file&db. Note if Dropinbase cannot write to the database, it will always attempt to write to the file (/runtime/logs/error.log).
    public static $DEBUG_LEVEL = 2; // 0 = no PHP errors logged. 1 = PHP errors logged with some detail. 2 = most detail logged for PHP errors.
    public static $CLIENT_DEBUG_LEVEL = 1; // 0 = no debug messages printed in browser Console and no debugger code generated. 1 = debug messages printed in browser Console and debugger code generated.
    public static $ELEUTHERIA_DEBUG_LEVEL = 2; // 0 = no Eleutheria pre-emptive syntax checking or error reporting.  1 = Eleutheria pre-emptive syntax checking.
                                             // 2 = SECURITY RISK: Adds echo-ing of errors to (1). 3 = SECURITY RISK: Adds creation of detailed trace file to (2).
	public static $LOGPERMISSUES = 1; // 0 = Don't log any permission issues. 1 = Log permission issues. 2 = Log permission and authentication issues.
	public static $INFORM_ADMIN_ERRORLEVEL = 2; // Administrator will be informed of any PHP error logged with level equal to or higher than this value. Errors logged with unspecified level defaults to 3.
	public static $ADMIN_EMAIL = null; // Administrator's email address. NOTE: Configure /config/mail.php for mail account settings, and PHPMailer must be installed via composer.
	public static $RECORDUNITTEST = FALSE; // TRUE or FALSE - Whether all requests must be recorded in pef_unit_test. Greatly affects performance. Normally FALSE.

	// Cache settings
	public static $CODEUSE = 1;  // 0 = Always overwrite dibCode files
							   // 1 = Delete a container's dibCode file if affected by design changes, else use if available (speeds up loads during development). 
							   // 2 = Use a dibCode file if it exists, else create it
                               // 3 = Allways use dibCode files (assume all necessary files exist)
	public static $USEPROXYPERMGROUP = FALSE; // (experimental) Generate less cache and crud files as for each container a representative "proxy" perm_group in pef_perm_active is set with same permissions.

	// Security settings
	public static $DESIGNER_CAN_READ_ERRORS = TRUE; // Whether the Designer can read and display errors from the database.
	public static $DESIGNER_CAN_READ_DEBUG = TRUE; // Whether the Designer can read and display debug logs.
	public static $CHECKUSERSESSIONS = TRUE; // Helps block session hijacking. Affects users where pef_login.check_user_session==1. These users can have only one active session. Note, pef_login.session_version is compared with value stored in PHP session with every request, which affects performance.
	public static $VERIFY_IP = TRUE; // Whether successive requests from the same web user must originate from the same IP address, else logged out. Note, is affected by Load-ballencers and dynamic ip addresses which will cause intermittent drops.
	public static $VERIFY_USER_AGENT = TRUE; // Whether successive requests from the same web user must have the same USER AGENT, else logged out. Note, affected by ISP's and browsers which updates info.
	public static $VERIFY_AUTH_TOKEN = TRUE; // Whether authentication tokens are checked on server requests, else logged out. This should be TRUE. Use eg. $REQUEST_TYPE='GET,POST,ignoretoken' in controller function parameters to override, for eg. file downloads.
	public static $USERNAME_REGEX='#^\w{4,30}$#'; // A semicolon delimited list of regular expressions that must validate successfully in order for usernames to be accepted
    public static $USERNAME_REGEXMSG='The username must contain between 4 and 30 alpha-numeric characters (no spaces, but underscore (_) is allowed).';
	public static $ENABLE_REMEMBERME = TRUE; // Whether to enable Remember Me functionality. Ensure that the /dropins/dibAuthentice/views/login.php contains the neccessary HTML.
	public static $PUBLICFILEPERMS = [
		'allow_uploads' => TRUE, // Allow system_public user to upload files.
		'allow_downloads' => TRUE, // Allow system_public user to download files.
		'allow_deletes' => TRUE, // Allow system_public user to delete files.
	];
	public static $ALLOWEDHTML = ''; // If empty, then the HTML of messages/prompts/popups sent to browser are not sanitized. Otherwise, specify a list of allowed HTML elements and attributes, using HtmlPurifier's syntax: http://htmlpurifier.org/live/configdoc/plain.html#HTML.Allowed
	
    // Path to the index file to bootstrap the application for a particular material dropin
	public static $DEFAULTFRAMEWORK = 'setNgxMaterial'; // client framework to load at startup
	public static $INDEXPATH = [
		'setNgxMaterial'=>'/setNgxMaterial/angular/dist/browser/index.html',
	];

	// Queue settings
	public static $ASYNCRETRYCOUNT=10; // Default count of tries the client will poll for actions in the Queue, before giving up. Can be set dynamically using Queue::updateIntervals().
	public static $OVERRIDEQUEUEWITH = 'None'; // None/NodeJs (Note, NodeJs requires expertise to maintain and run stably in some client environments)
	public static $NODEJSHOST=null; // NodeJs server connection details (eg 'http://localhost:8080'), OR null (NodeJs will not be initialized)
	public static $AUTO_START_WATCHER = TRUE; // Whether an attempt is made to start the NodeJs watcher automatically when compiling container's one-by-one.
	
	// Hooks
	public static $SETUPSCRIPT=null; // Path to any script that is run just after user indentification and before URL request is analysed, eg '/dropins/myDropin/components/SetValues.php'
    public static $AFTERLOGINSCRIPT = null; // Path to any script that is run just after a user has manually logged in, eg '/dropins/myDropin/components/RunOnceDaily.php'
    // Catchall event handler for any/all requests. Specifiy a DIB style URI, eg. /dropins/DROPIN/COMPONENTCLASSNAME/FUNCTIONNAME.
	// Function must be declard as 'public static function xxx($args, &$class, &$controllerArgs) {...}',
	// where the latter two args can be set by reference(&) to affect how the primary request is handled.
	// Return FALSE if targetted request must not be executed.
	public static $CATCHALLEVENT = null;

	// PATHS

	// Physical path to user-file uploads folder (NOTE, keep outside webserver's reach for security reasons)
    // IMPORTANT: See https://www.owasp.org/index.php/Unrestricted_File_Upload
    public static $USERSPATH='C:/dibwebuploads/'; 

	// Alternate path for /configs folder. Move outside www folder for (slightly) improved security
	public static $CONFIGSPATH = null; 
    
	// Full path to HtmlPurifier temp/cache folder. Ensure this folder has 775 rights. If empty, Dropinbase uses DIB::$RUNTIMEPATH . 'cache/htmlPurifier'
    public static $HTMLPURIFIERCACHEPATH = '';

    // Values generated automatically (hard-code them for custom environments)
	public static $BASEURL='~baseurl~';
	public static $BASEPATH='~rootdir~';
    public static $DROPINPATHDEV='~rootdir~dropins~dirsep~';
    public static $RUNTIMEPATH='~rootdir~runtime~dirsep~';
    public static $FILESPATH='~rootdir~files~dirsep~';
	public static $SYSTEMPATH='~systemdir~';
    public static $EXTPATH= '~systemdir~extensions~dirsep~';
    public static $VENDORPATH='~vendordir~';
    
	// Values set dynamically with every request
	public static $CRUDFILE; // Path to container or dropdownlist crud file for the current user (set just before crud operation is performed).
	public static $CODEPATH; // Folder where current container's generated files are stored. Is set when container permissions are checked.
	public static $DROPINPATH; // Path to either the system or user dropin folder, depending on the current request. 
                               // If it is not a dropin request, the path will point to the /droinbase/ folder (same as the $BASEPATH).
	public static $USER; // Array of all fields in pef_login (except password, dib_password, and notes)
	public static $DATABASES; // Array of connection details to all databases in pef_database
	public static $PERMGROUP; // Combination of the permission groups the user currently has rights to, eg x3x5x
	public static $LOCALE;	// The user's language (from pef_login)
	public static $RETURN_URL; // Dynamically set - remembers requested page when user is not logged in and returns to it after login.
	public static $CONTROLLER; // Name of the controller referenced by the current request
	public static $ACTION; // Name of the controller action
	public static $CONTAINERDATA; // array of the current container's info obtained from active permission record
	public static $ITEMLISTDATA; // array of the current item list's info obtained from active permission record    
}
