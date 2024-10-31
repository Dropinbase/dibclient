<?php

// Mail account settings used to send emails

// PHPMailer is used
// See the following article if bulk sending email:
//   https://support.google.com/a/answer/81126?visit_id=638573991284456043-3792697846&fl=1&sjid=593764590510044166-NA#zippy=

self::$account = array(
    'use_smtp' => 1, // whether an SMTP account is used
    'mail_host' => 'smtp.gmail.com', // server, eg 'imap.gmail.com' | 'localhost'
    'debug_level' => 0, // enables SMTP debug information (for testing)
                        // 0 = none
                        // 1 = errors and messages
                        // 2 = messages only
    'smtp_auth' => true,  // enable SMTP authentication
    'encryption' => 'ssl', // connection prefix to server: '', 'ssl' or 'tls'
    'port_outgoing' => '465', // TLS port: 587
    'username'=> "mail@gmail.com", // username
    'password'=>  "letmein", // password
    'from_address' => 'replyto@gmail.com',
    'display_name' => 'Dropinbase Administrator'
);
