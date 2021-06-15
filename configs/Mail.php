<?php

// Mail account settings used to send emails

// PHPMailer is used. 
// Basic example settings for gmail here: http://phpmailer.worxware.com/?pg=examplebgmail

/* Useful links:
   https://blog.codinghorror.com/so-youd-like-to-send-some-email-through-code/ 
   https://www.mailgun.com/
*/

self::$account = array(
    'use_smtp' => true,
    'mail_host' => 'smtp-relay.gmail.com', // server, eg 'imap.gmail.com' | 'localhost'
    'debug_level' => 0, // enables SMTP debug information (for testing)
    					// 0 = none
                        // 1 = errors and messages
                        // 2 = messages only
    'smtp_auth' => TRUE,  // enable SMTP authentication
    'encryption' => 'ssl', // connection prefix to server: '', 'ssl' or 'tls'
    'port_outgoing' => '465', // SMTP port eg 587
    'username'=> "mail@gmail.com", // username
    'password'=>  "letmein", // password
    'from_address' => 'replyto@gmail.com',
    'display_name' => 'Dropinbase Administrator'
);
