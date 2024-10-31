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

    /// Variables used to refer to Databases in PHP. The values reference the id/index value specified in /secrets/Conn.php and the pef_database table.
    public static $DIBDB = 1; // The main Dropinbase database 
    public static $LOGINDB = 1; // Database containing the pef_login, pef_login_group, pef_perm_group, pef_two_factor and pef_security_policy tables

    public static $ERRORLOGDB = 1; // id value of the database containing the pef_error_log table where errors are logged. 
                                    // Note, also update the pef_sql.pef_database_id of the two or more 'qdibErrorLog...' query(ies), which determine which pef_error_log table to look at - there can (erroneously) be more than one in different databases.
    public static $SQLLOGDB = null; // id value of the database containing the pef_sql_log table. If not null, then ALL SQL statements except SELECT ... are logged with their paramater values.    
    public static $AUDITDB = 1; // Database containing the default pef_audit_trail table (overide this value using pef_container.pef_audit_trail_table_id). NOTE: Must also change pef_database_id in pef_table for 'pef_audit_trail'. Don't remove pef_audit_trail from the DIB database - it is still needed here to store eg Designer changes.
    public static $ACTIVITYLOGDB = 1; // Database containing the pef_activity_log table. Both the $ACTIVITYLOGDB and $ACTIVITYLOG (see below) settings must not be empty for logging to be activated.
    
    // ***NOTE: add more constants/variables here to use in your own PHP for other databases... 


    /// Basic settings
    public static $ENVIRONMENT = 'development'; // 'development' = auto-deletion of files, html beautified. 'production' = No deletion, compression of Javascript.
    public static $TIMEZONE = 'Africa/Johannesburg'; // See http://php.net/manual/en/timezones.php
    public static $SITENAME = 'Dropinbase'; // The title of the browser tab
    public static $SITELOGO = 'files/icons/logo.png'; // Used in Environment.php settings to make available client-side via the getEnv() function

    /// Logs and error reporting
    public static $WRITE_ERRORS_TO = 'db'; // Write errors to file/db/file&db. Note if Dropinbase cannot write to the database, it will always attempt to write to the file (/runtime/logs/error.log).
    public static $DEBUG_LEVEL = 2; // 0 = no PHP errors logged. 1 = PHP errors logged with some detail. 2 = most detail logged for PHP errors.
    public static $CLIENT_DEBUG_LEVEL = 1; // 0 = no debug messages printed in browser Console and no debugger code generated. 1 = debug messages printed in browser Console and debugger code generated.
    public static $ELEUTHERIA_DEBUG_LEVEL = 2; // 0 = no Eleutheria pre-emptive syntax checking or error reporting.  1 = Eleutheria pre-emptive syntax checking.
                                             // 2 = SECURITY RISK: Adds echo-ing of errors to (1). 3 = SECURITY RISK: Adds creation of detailed trace file to (2).
    public static $LOGPERMISSUES = 1; // 0 = Don't log any permission issues. 1 = Log permission issues. 2 = Log permission and authentication issues.
    public static $INFORM_ADMIN_ERRORLEVEL = 2; // Administrator will be informed of any PHP error logged with level equal to or higher than this value. Errors logged with unspecified level defaults to 3.
    public static $ADMIN_EMAIL = null; // Administrator's email address. NOTE: Configure /secrets/mail.php for mail account settings, and PHPMailer must be installed via composer.

    /// Activity Log
    // The array below specifies sets of conditions in the URI, container name, and permgroup that must all match to add an entry to pef_activity_log. Note, at least one of the sets of conditions specified must match.
    // Note, to improve performance, preferably don't overlap with pef_audit_trail entries
    // Both the $ACTIVITYLOGDB and $ACTIVITYLOG settings must not be empty for logging to be activated.

    public static $ACTIVITYLOG = [
        [
            // Boolean -> use the PHP fnMatch function that supports wildcards for matching strings below, instead of merely testing whether the string is contained (using strpos). Very small performance hit, though dependant on complexity.
            // If fnMatch is activated, it must either be an exact match, or wildcard characters must be present, eg. '/peff/Crud/update/*'=>'update'
            'usePhp_fnMatch' => false,

            // Array of paths to functions (array key) and action-label (array value). For possible path values, see the Browser Console->Netword-tab  or  /nav/dibDocs/?area=docs&doc=Common-API-Requests. 
            // NOTES: Use '/' to match all, '/nav/' is not supported.
            // Examples: '/peff/Crud/update'=>'update'  or  '/peff/Container/getPortInfo'=>'open'  or  '/my'=>'my dropins'   or   '/myDropin/'=>'myDropin'  or  '/myDropin/myFunction'=>'myFunction'  or '/mySet/myDropin/myFunction'=>'myFunction'   or  '/myDropin/myFunction/myContainer'=>'myOtherFunc' etc.
            'uriList' => [
                '/peff/Container/getPortInfo' => 'open',
                '/peff/Crud/create' => 'create',
                '/peff/Crud/update' => 'update',
                '/peff/Crud/delete' => 'delete',
                '/dropins/dibExcel/Export/exportDibExcel' => 'excel',
            ],

            // Array of container names, or partial container names (empty array to match all). Use the DIB::$DEFAULTPERMSCONTAINER value for open functions. 
            // Examples: 'adm'  or  'admDashboard'  or  'admDashoardChart1'.
            'containerList' => [],

            // Array of session field names obtained from DibUserParams.php (array key) and values to match or partially match (array value). Empty array to match all values of any session field.
            // Examples: 'perm_group' => 'x3x' (will match any permgroup that contains 'x3x'), 'admin_user' => 1
            'sessionFieldList' => [
                'perm_group' => 'x3x',
                'admin_user' => 4
            ],
        ],
    ];

    public static $RECORDUNITTEST = FALSE; // (not fully functional yet) - Whether all requests must be recorded in pef_unit_test. Greatly affects performance. Only set to TRUE when recording Unit Test requests.

    /// Cache/Compile/Queue settings
    public static $CODEUSE = 1;  // 0 = Always overwrite dibCode files
                               // 1 = Delete a container's dibCode file if affected by design changes, else use if available (speeds up loads during development). 
                               // 2 = Use a dibCode file if it exists, else create it
                               // 3 = Allways use dibCode files (assume all necessary files exist)
    public static $USEPROXYPERMGROUP = FALSE; // (experimental) Generate less cache and crud files as for each container a representative "proxy" perm_group in pef_perm_active is set with same permissions.
    public static $AUTO_START_WATCHER = TRUE; // Whether an attempt is made to start the node.js Angular watcher automatically when compiling container's one-by-one.
    public static $ASYNCRETRYCOUNT=10; // Default count of tries the client will poll for actions in the Queue, before giving up. Can be set dynamically using Queue::updateIntervals().
    
    /// Security settings
    public static $DESIGNER_CAN_READ_ERRORS = TRUE; // Whether the Designer can read and display errors from the database.
    public static $DESIGNER_CAN_READ_DEBUG = TRUE; // Whether the Designer can read and display debug logs.
    public static $CHECKUSERSESSIONS = TRUE; // Helps block session hijacking. Affects users where pef_login.check_user_session==1. These users can have only one active session. Note, pef_login.session_version is compared with value stored in PHP session with every request, which affects performance.
    public static $VERIFY_IP = TRUE; // Whether successive requests from the same web user must originate from the same IP address, else logged out. Note, is affected by Load-ballencers and dynamic ip addresses which will cause intermittent drops.
    public static $VERIFY_USER_AGENT = FALSE; // Whether successive requests from the same web user must have the same USER AGENT, else logged out. Note, affected by webservers, ISP's and browsers which updates info.
    public static $VERIFY_AUTH_TOKEN = TRUE; // Whether authentication tokens are checked on server requests, else logged out. This should be TRUE. Use eg. $REQUEST_TYPE='GET,POST,ignoretoken' in controller function parameters to override, for eg. file downloads.
    public static $USERNAME_REGEX='#^\w{4,30}$#'; // A semicolon delimited list of regular expressions that must validate successfully in order for usernames to be accepted
    public static $USERNAME_REGEXMSG='The username must contain between 4 and 30 alpha-numeric characters (no spaces, but underscore (_) is allowed).';
    public static $ENABLE_REMEMBERME = TRUE; // Whether to enable Remember Me functionality. Ensure that the /dropins/dibAuthentice/views/login.php contains the neccessary HTML.

    public static $PUBLICFILEPERMS = [
        'allow_uploads' => TRUE, // Allow system_public user to upload files.
        'allow_downloads' => TRUE, // Allow system_public user to download files.
        'allow_deletes' => TRUE, // Allow system_public user to delete files.
    ];
    
    public static $DEFAULTPERMSCONTAINER = 'defaultPermsContainer'; // The container that specifies permissions for requests where $containerName is not in function parameters. If empty, all requests from system_public will fail, unless eg $REQUEST_TYPE='POST,ignorecontainerperms' is included in controller function parameters.

    // If empty, then the HTML of messages/prompts/popups sent to the browser are not sanitized. Otherwise, configure the use of HtmlPurifier, or allowed tags and other HTML to allow.
    public static $ALLOWEDHTML = [
        'htmlPurifier' => false, 'allowedHtml' => '', // specify TRUE to use the sophisticated HtmlPurifier. Use the allowedHtml string to override HTMLPurifier's default configuration (see http://htmlpurifier.org/live/configdoc/plain.html#HTML.Allowed)
        'basic_tags' => ['br', 'p', 'b', 'i', 'h1', 'h2', 'h3', 'span'], // this allows tags like <br>,<p>,</p>,<b>,</b>,<i>,</i> etc with no HTML attributes.
        'other_html' => ['<span style="color:red">', '<a href="/nav/dibexActionEmitEvent">Investigate Further</a>'] // list complete HTML strings that must be allowed.
    ];

    // Path to the index file to bootstrap the application for a particular material dropin
    public static $DEFAULTFRAMEWORK = 'setNgxMaterial'; // client framework to load at startup
    public static $INDEXPATH = [
        'setNgxMaterial'=>'/setNgxMaterial/angular/dist/browser/index.html',
    ];
    
    /// Hooks
    public static $SETUPSCRIPT=null; // Path to any script that is run just after user indentification and before URL request is analysed, eg '/dropins/myDropin/components/SetValues.php'
    public static $AFTERLOGINSCRIPT = null; // Path to any script that is run just after a user has manually logged in, eg '/dropins/myDropin/components/RunOnceDaily.php'
    // Catchall event handler for any/all requests. Specifiy a DIB style URI, eg. /dropins/DROPIN/COMPONENTCLASSNAME/FUNCTIONNAME.
    // Function must be declard as 'public static function xxx($args, &$class, &$controllerArgs) {...}',
    // where the latter two args can be set by reference(&) to affect how the primary request is handled.
    // Return FALSE if targetted request must not be executed.
    public static $CATCHALLEVENT = null;

    /// SITE URL
    public static $BASEURL='~baseurl~';

    /// PATHS

    // THE FOLLOWING PATHS SHOULD BE MOVED OUTSIDE THE WEBSERVER'S DOCUMENT-ROOT FOLDER FOR IMPROVED SECURITY

    // (Read/write) Full path where user files are uploaded to (see https://www.owasp.org/index.php/Unrestricted_File_Upload)
    public static $USERSPATH='C:/mysite_uploads/';

    // (Read) Alternate full path for /dropinbase/dropins/setNgxMaterial/angular folder. 
    // (Write)-rights are required on the /angular/ngtmp folder on development servers only.
    public static $ANGULARPATH = null;

    // (Read) Full path to /secrets folder. Eg. '/var/www/www-read-only/secrets/'
    public static $SECRETSPATH = '~rootdir~secrets~dirsep~'; 

    // (Read/Write) Full path to HtmlPurifier temp/cache folder. Ensure the webserver user has read/write rights. If empty, Dropinbase uses DIB::$RUNTIMEPATH . 'cache/htmlPurifier'
    public static $HTMLPURIFIERCACHEPATH = '';

    // (Read/Write) Full path to the /runtime folder - where Dropinbase stores temporary files, and the generated site index.html (.dtxt) file.
    public static $RUNTIMEPATH='~rootdir~runtime~dirsep~';

    // (Read) Full path to the Composer /vendor folder
    public static $VENDORPATH='~vendordir~';

    /// OTHER PATHS REQUIRED BY DROPINBASE

    // Values generated automatically (hard-code them for custom environments)
    public static $BASEPATH='~rootdir~';
    public static $DROPINPATHDEV='~rootdir~dropins~dirsep~';
    public static $FILESPATH='~rootdir~files~dirsep~';
    public static $SYSTEMPATH='~systemdir~';
    public static $EXTPATH= '~systemdir~extensions~dirsep~';
    
    /// Values set dynamically with every request

    public static $CRUDFILE = ''; // Path to container or dropdownlist crud file for the current user (set just before crud operation is performed).
    public static $CODEPATH = ''; // Folder where current container's generated files are stored. Is set when container permissions are checked.
    public static $DROPINPATH = ''; // Path to either the system or user dropin folder, depending on the current request. 
                                    // If it is not a dropin request, the path will point to the /droinbase/ folder (same as the $BASEPATH).
    public static $PERMGROUP = ''; // Combination of the permission groups the user currently has rights to, eg x3x5x
    public static $LOCALE = '';    // The user's language (from pef_login)
    public static $RETURN_URL = ''; // Dynamically set - remembers requested page when user is not logged in and returns to it after login.
    public static $CONTROLLER = ''; // Name of the controller referenced by the current request
    public static $ACTION = ''; // Name of the controller action

    public static $USER = []; // Array of all fields in pef_login (except password, dib_password, and notes)
    public static $DATABASES = []; // Array of connection details to all databases in pef_database
    public static $CONTAINERDATA = []; // array of the current container's info obtained from active permission record
    public static $ITEMLISTDATA = []; // array of the current item list's info obtained from active permission record
}