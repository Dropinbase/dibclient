<?php 

class TwoFactorConfig { 
    // Add comma seperated emails
    const ADMIN_EMAILS = "support@example.com";

    // Recovery subject
    const ADMIN_EMAILS_RECOVERY_SUBJECT = "Reset two factor authentication method";

    // Define field values used in Recovery Template below
    const ADMIN_EMAILS_RECOVERY_FIELD_SQL = "SELECT first_name, last_name, mobile, email 
                                            FROM pef_login 
                                            WHERE id = :loginId
                                            ";

    // Recovery Template
    const ADMIN_EMAILS_RECOVERY_BODY = "
        Dear Administrator,<br>
        <br>
        Please note that ~~first_name ~~last_name would like to reset their two factor authentication method.<br>
        <br>
        Please contact them urgently using the following details:<br>
        <br>
        <b>Mobile:</b> ~~mobile<br>
        <b>Email:</b> ~~email<br>
        <br>
        Regards<br>
        System Admin";

    // Recovery admin email sent message
    const ADMIN_EMAIL_SENT_MSG = "Please note that an email was sent to the System Support Team.";

    // Alternatively specify another Controller to handle the reset class
    const ADMIN_RECOVERY_LINK = "/dropins/dibAuthenticate/TwoFactor/adminRequest";

    // Allow users to disable their two factor method when they go through a reset of the two factor authentication
    const ALLOW_USER_TO_DISABLE_DURING_FORGOT_PROCESS = FALSE;
}