<?php

/* 
   Security headers - customize for your application
   Note: all or some of the headers can be moved to your webserver's config file
*/

// Request types
header("Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS");

// Prevent access to sensitive resources from other origins
header("Cross-Origin-Resource-Policy: same-origin");

// Restrict CORS for APIs (or other interactions)
if (isset($_SERVER['HTTP_ORIGIN']))
    header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);

// Respond to preflights - Angular requests options for cross domain access
// The isset is necessary for PHP threads that skip Apache
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    // Return only the headers and not the content
    // Only allow CORS if we're doing a GET - i.e. no saving for now.
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']) && $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] == 'GET')
        header('Access-Control-Allow-Headers: X-Requested-With');

    exit;
}

header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Content-Security-Policy
header("Content-Security-Policy: default-src https: 'self' https://fonts.googleapis.com *.cloudflare.com *.google-analytics.com; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://unpkg.com/ *.googleapis.com *.cloudflare.com *.google-analytics.com https://cjshare.com *.cjshare.com *.cleverjump.org *.jsdelivr.net https://sharebutton.net *.sharebutton.net api.whichbrowser.net code.jquery.com https://www.google.com/recaptcha/ https://www.gstatic.com/recaptcha/ https://www.gstatic.com; style-src 'self' https://unpkg.com/ https://fonts.cdnfonts.com/css/poppins https://fonts.googleapis.com https://netdna.bootstrapcdn.com https://maxcdn.bootstrapcdn.com 'unsafe-inline' *.jsdelivr.net https://www.gstatic.com; img-src data: *; worker-src 'self' blob:; frame-ancestors 'self'; ");

// Permissions Policy
header("Permissions-Policy: storage-access=(self), fullscreen=(self), autoplay=(), bluetooth=(), browsing-topics=(), camera=(), compute-pressure=(), display-capture=(), encrypted-media=(), gamepad=(), gyroscope=(), hid=(), identity-credentials-get=(), idle-detection=(), local-fonts=(), magnetometer=(), microphone=(), midi=(), otp-credentials=(), payment=(), picture-in-picture=(), publickey-credentials-create=(), publickey-credentials-get=(), screen-wake-lock=(), serial=(), usb=(), web-share=(), window-management=(), xr-spatial-tracking=()");

/*
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
header("X-Content-Type-Options: nosniff"); // prevent MIME type sniffing
header("X-Frame-Options: SAMEORIGIN"); // prevent clickjacking
header("Referrer-Policy: no-referrer");
*/
