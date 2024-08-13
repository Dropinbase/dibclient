<?php 

// Define SQL to obtain user's email address (field/alias must be named 'email')
$this->emailAddressSql = 
    "SELECT l.email
      FROM pef_login l
      WHERE l.id = :loginId";

// Define SQL statement returning values used in the $this->emailTmpl below. Note ~~randomPin will be added automatically
$this->emailTmplParamsSql = 
    "SELECT l.first_name, l.last_name
      FROM pef_login l
      WHERE l.id = :loginId";

// Define the email template
$this->emailTmpl = 
    "Dear ~~first_name ~~last_name<br><br>
    Please use the following OTP (one-time-pin) to sign-in:
    <div style='background-color:lightyellow; border-radius:5px; padding:20px; border: 1px solid grey; margin:10px'>~~randomPin</div>
    Regards
    System Admin";

// Define email subject
$this->emailSubject = "Email OTP $randomPin (".strlen($randomPin)." digits).";
