<?php

class DibUserParams {
    
    public static function getUserParams($loginId) {
        // All the values in the array returned by this function are stored in the user's PHP session which is loaded into DIB::$USER with each request.
        // Adjust the query below as per your needs.
        // All the fields in the (original) query below are required, except staff_id which was added for demo purposes.

        $sql = "SELECT l.id, l.username, l.perm_group, l.admin_user, l.email, l.test_user, l.first_name, l.last_name, l.default_url, l.supplier_code,
                    l.pef_security_policy_id, l.login_expiry, l.login_group_expiry, l.language, l.larger_font, s.name as policyName, l.dib_username
                FROM pef_login l
                    INNER JOIN pef_security_policy s ON l.pef_security_policy_id = s.id 
                WHERE l.id = :loginId";

        $sessionArgs = Database::fetch($sql, array('loginId'=>$loginId), DIB::$LOGINDB);

        if($sessionArgs === false) {
            Log::err("URGENT! The DibUserParams.php query returned a SQL error.\r\n" . Database::lastErrorAdminMsg(), 10);
            return 'System Error. Please be patient, while we look in to it. If the problem persists, please contact the system administrator.';
        }

        if(empty($sessionArgs)) { 
            // This case is for users that should not be able to login.
            // Note, by default the error message is exactly the same for all login issues caused by users. The less info hackers have the better.
            return 'Invalid username or password. Please try again.';
        }
        
        /* 
          The following line is for demo purposes only
           Normally a field like staff_id/client_id is added to the pef_login table and included in the SELECT statement above,
           whereby each user is linked to a staff/client record in the staff/client table.
           To access the value, use DIB::$USER['staff_id'] in PHP, and :dibUser_staff_id in queries and permission criteria.
        */        
        $sessionArgs['staff_id'] = 1;
        /*
           NOTE: For demo puposes DIB::$USER['staff_id'] was also added to the default /configs/Environment.php file, to make getEnv('staff_id') and @{env_staff_id} work in examples. 
           Remove the line from configs/Environment.php if needed...
           See /nav/dibexPermSegmentDataByUser for more info.
        */
        
        // NOTE: This value has also been included in /configs/Environment.php so that the demo's can access it client-side using getEnv('staff_id') or @{env_staff_id} etc. to hide/show buttons etc.
        // See /nav/dibexPermSegmentDataByUser for more info
        $sessionArgs['staff_id'] = 1;

        // Further error handling is done in Authenticator::logUserIn();
        // Return a message to display to the user on failure, otherwise an array with session values that will be available as User Parameters throughout the system (in queries and Client Data etc.)
        return $sessionArgs;
    }
}