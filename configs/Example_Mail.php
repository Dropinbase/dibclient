<?php

// Mail account settings used to send emails

// PHPMailer is used. 

// NOTE, gmail requires various cloud settings to be in place... ask AI :)

self::$account = array(
	'mail_host' => 'smtp.gmail.com', // SMTP server, or 'imap.gmail.com', or 'localhost'
    'debug_level' => 0, // enables SMTP debug information (for testing)
    					// 0 = none
                        // 1 = errors and messages
                        // 2 = messages only
    'smtp_auth' => TRUE,  // enable SMTP authentication
    'encryption' => 'ssl', // connection prefix to server: '', 'ssl' or 'tls'
    'port_outgoing' => '465', // TLS port: 587, imap : 993
    'username' => 'xxx@gmail.com',
    'password' =>  '***',
    'from_address' => 'yyy@gmail.com', // reply to address
    'display_name' => 'Dropinbase Administrator'
);
