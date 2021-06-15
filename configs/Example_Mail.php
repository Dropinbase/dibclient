<?php

// Mail account settings used to send emails

// PHPMailer is used. 
// Basic example settings for gmail here: http://phpmailer.worxware.com/?pg=examplebgmail

/*
self::$account = array(
    'use_smtp'=> false,
    'debug_level' => 0, // enables SMTP debug information (for testing)
                        // 0 = none
                        // 1 = errors and messages
                        // 2 = messages only
    'from_address' => 'noreply@moonstoneinfo.co.za',
    'display_name' => 'Faisexam Admin',       
);
*/

self::$account = array(
    'use_smtp'=> true,
	'mail_host' => 'imap.gmail.com', // SMTP server, eg 'imap.gmail.com' | 'localhost'
    'debug_level' => 0, // enables SMTP debug information (for testing)
    					// 0 = none
                        // 1 = errors and messages
                        // 2 = messages only
    'smtp_auth' => TRUE,  // enable SMTP authentication
    'encryption' => 'ssl', // connection prefix to server: '', 'ssl' or 'tls'
    'port_outgoing' => '465', // SMTP port eg 587
    'username' => 'xxx@gmail.com',
    'password' =>  '***',
    'from_address' => 'yyy@gmail.com', // reply to address
    'display_name' => 'Dropinbase Administrator'
);
