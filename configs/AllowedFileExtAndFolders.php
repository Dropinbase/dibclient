<?php

/*
NOTE this file defines a whitelist of folders accessible by public users via /files/ requests, 
and another whitelist of allowed file extensions and their related mimetypes for uploading and downloading 
    
*** WARNING! DO NOT add the following file extensions:
    ​php       (server-side code)
    dtxt      (used for cache files)
    ts        (typescript)
*/

/**
 * Checks for allowed characters in the url and filename, 
 *  whether the url references a folder in the defined whitelist of folders accessible by system_public,
 *  and finally calls getMimeType to check the file extension and return the mimetype.
 * 
 * @param string $url eg 'files/dropins/dibAdmin/css'
 * @param string $fileName eg 'theme.css'
 * @param string $ext eg 'css'
 */
function checkFile($url, $fileName, $ext) {

    // List the folders where any user (including system_public) may access files, using a /files/ type URL
    // This is a whitelist of folders - all other folders will be blocked.
    
    $allowedFolders = array(
        // Dropinbase required
        'files/dropins/setNgxMaterial/angular/dist/browser',
        'files/dropins/setNgxMaterial/dibAdmin/js/template',
        'files/dropins/dibAdmin/js',
        'files/dropins/dibAdmin/css',
        'files/dropins/dibAuthenticate/css',
        'files/dropins/setNgxMaterial/dibAdmin/designImages',
        'files/dropins/dibAdmin/images',
        'files/dropins/setNgxMaterial/shared/img/icons',
        'files/dropins/setNgxMaterial/dibAdmin/images/dashboard',
        'files/dropins/setNgxMaterial/dibAdmin/images',
        'files/dropins/setNgMaterial/dibGlobals/images/svg',
        'files/dropins/setNgMaterial/dibAdmin/images',
        'files/files/icons',
        'files/files/images',

        // Application specific
        'files/dropins/setNgxMaterial/wisl/files/images',
        'files/files/themes/overrides/images',
        'files/dropins/fitproper/files',
        'files/dropins/fitproper/files/manuals',
        'files/dropins/fitproper/files/help',
        
    );

    // Check for invalid characters in url
    if(!ctype_alnum(str_replace(array('/','_','-'), '', $url)))
        return FALSE;

    // Check for invalid characters in file name
    if(!ctype_alnum(str_replace(array('.','_','-',' '), '', $fileName)))
        return FALSE;

    // Check if folder is whitelisted
    if(!in_array($url, $allowedFolders))
        return FALSE;

    return getMimeType($ext);
}


/**
 * Checks if file extention is allowed and returns the corresponding mime-type if it is
 * @param $ext string eg txt
 * @return mixed string or array (if multiple mime-types), or boolean FALSE on failure
 */
function getMimeType($ext) {
    
    // List the file extensions of files that are allowed to be uploaded and downloaded, with their assoicated mimetypes
    // This is a whitelist of file extensions - all other will be blocked.
    // Uploaded files are checked against the related mimetype and blocked if found different.
    
    $allowedExtensions = array(
        // Dropinbase required
        'css' => 'text/css',
        'js' => 'application/x-javascript',
        'svg' => 'image/svg+xml',
        'jpg' => array('image/jpg','image/jpeg'),
        'png' => 'image/png',
        'ico' => 'image/x-icon',
        // Application specific (enable/add as few as possible)
        'csv' => array('text/csv','application/vnd.ms-excel'), // Windows reports uploaded csv files as vnd.ms-excel :(
        'pdf' => 'application/pdf',
        'jpg' => array('image/jpg','image/jpeg'),
        'jpeg' => array('image/jpg','image/jpeg'),
        'png' => 'image/png',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        //'tiff' => 'image/tiff',
        'xlsx' => array('application/zip','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','application/octet-stream'),
        'xls' => array('application/vnd.ms-excel'),
        'map' => 'text/plain',
        /*
        'docx' => 'application/msword',
        'xls' => array('application/vnd.ms-excel'),
        'doc' => 'application/msword',
        'xlt' => array('application/vnd.ms-excel'),
        'xlm' => array('application/vnd.ms-excel'),
        'xld' => array('application/vnd.ms-excel'),
        'xla' => array('application/vnd.ms-excel'),
        'xlc' => array('application/vnd.ms-excel'),
        'xlw' => array('application/vnd.ms-excel'),
        'xll' => array('application/vnd.ms-excel'),
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.ms-powerpoint',
        'pps' => 'application/vnd.ms-powerpoint',
        'rtf' => 'application/rtf',

        'html' => 'text/html',
        'htm' => 'text/html',
        'txt' => 'text/plain',
        'json' => 'application/json',
        'xml' => 'application/xml',

        'mpeg' => 'video/mpeg',
        'mpg' => 'video/mpeg',
        'mpe' => 'video/mpeg',
        'mp3' => 'audio/mpeg3',
        'wav' => 'audio/wav',
        'aiff' => 'audio/aiff',
        'aif' => 'audio/aiff',
        'avi' =>'video/msvideo',
        'wmv' =>'video/x-ms-wmv',
        'mov' =>'video/quicktime',
        
        'zip' =>'application/zip',
        'tar' =>'application/x-tar',
        */
    );

    $ext = strtolower($ext);

    if(!array_key_exists($ext, $allowedExtensions))
        return FALSE;
    
    return $allowedExtensions[$ext];
}