<?php

// The /peff/Template/environment.js request is made once when the application is refreshed and the Angular framework is loaded
// It returns values accessible via getEnv('xxx') and @{env_xxx} in client side code and HTML attributes etc.
// You can add your own values to $args, and JavaScript code to the $response
// The values are accessible in the browser console by typing DIB [Enter]
// Use the 'reload-env' action to reload values if necessary

// NOTE: do not remove existing values except 'staff_id'

$settings = $this->getSettings("`name` IN ('auditTrailContainerName','defaultDateTimeFormat','defaultDateFormat')", 'pef_setting');

// Values 

$args = array(
    'staff_id' => (empty(DIB::$USER['staff_id']) ? null : DIB::$USER['staff_id']), // Remove or adjust as needed. See /configs/DibUserParams.php for details

    'site_name' => DIB::$SITENAME,
    'logo' => DIB::$SITELOGO,
    'user_fullname' => DIB::$USER['first_name'] . ' ' . DIB::$USER['last_name'],

    // Valid date formats: https://date-fns.org/v2.29.3/docs/format
    'default_date_time_format' => (isset($settings['defaultDateTimeFormat']) ? $settings['defaultDateTimeFormat'] : 'yyyy-MM-dd HH:mm:ss'),
    'default_date_format' => (isset($settings['defaultDateFormat']) ? $settings['defaultDateFormat'] : 'yyyy-MM-dd'),
   
    'audit_trail_container' => (isset($settings['auditTrailContainerName']) ? $settings['auditTrailContainerName'] : 'dibAuditTrailGrid'),
    'audit_trail_port' => '',

    'default_url' => isset(DIB::$USER['default_url']) ? DIB::$USER['default_url'] : '',
    'base_url' => DIB::$BASEURL,

    'larger_font' =>  (DIB::$USER['larger_font'] == '1') ? TRUE : FALSE, // ***TODO - accessibility option

    'queue_retry_count' => DIB::$ASYNCRETRYCOUNT,

    'debug' => DIB::$CLIENT_DEBUG_LEVEL,
    'can_dib_design' => $canDibDesign,
    'auth_id' => (isset(DIB::$USER['auth_id']) ? DIB::$USER['auth_id'] : null),
);

$response = 'var DIB = ' . json_encode($args);

// Add line breaks for debugging
// $response = str_replace('","', "\",\r\n\"", $response);

// JavaScript
$response .= "
    DIB['load_time'] = new Date().getTime();

    if (document.location.pathname == '/') {
        document.location.href = DIB.default_url; 
    }
";