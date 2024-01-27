<?php

/*
NOTE this file defines a whitelist of folders accessible by public users via /files/ requests, 
and another whitelist of allowed file extensions and their related mime-types
    
*** WARNING! DO NOT add the following file extensions:
    ​php       (server-side code)
    dtxt      (used for cache files)
    ts        (typescript)
*/


/**
 * Checks for:
 * - allowed characters in the URL and filename, 
 * - whether the URL references a folder in the defined whitelist of folders accessible by system_public,
 * - and finally calls getMimeType to check the file extension and return the mimetype (to eg. files.php)
 * 
 * @param string $url eg 'files/dropins/dibAdmin/css'
 * @param string $fileName eg 'theme.css'
 * @param string $ext eg 'css'
 */
function checkFile($url, $fileName, $ext) {

    // List the folders where any user (including system_public) may access files, using a /files/ type URL
    // This is a whitelist of folders - all other folders will be blocked.
    
    $allowedFolders = array(
        'files/dropins/setNgxMaterial/angular/dist/browser',
        'files/dropins/setNgxMaterial/dibAdmin/js/template',
        'files/dropins/dibAdmin/js',
        'files/dropins/dibAdmin/css',
        'files/dropins/dibDocs/css',
        'files/dropins/dibAuthenticate/css',
        'files/dropins/dibAuthenticate/images',
        'files/dropins/dibAuthenticate/js',
        'files/dropins/dibAdmin/images',
        'files/dropins/dibAdmin/images/docs',
        'files/dropins/dibAdmin/images/logos',
        'files/dropins/dibAdmin/images/icons',
        'files/dropins/setNgxMaterial/shared/img/icons',
        'files/dropins/setNgxMaterial/dibAdmin/images/dashboard',
        'files/dropins/setNgxMaterial/dibAdmin/images',
        'files/dropins/dibCustom/designImages',

        'files/files/icons',
        'files/files/images',
        'files/files/themes/overrides/css',
        'files/dropins/setCharts/dibPlotly/js',
        'files/dropins/dibDynamicUI/js',

         // DIB Examples
        'files/dropins/dibExamples/img',
        
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
 * NOTE: Any file extention not listed here, is not allowed to be accessed
 * @param $ext string eg txt
 * @return mixed string or array (if multiple mime-types) of mime-types, or boolean FALSE on failure
 */
function getMimeType($ext) {
    
    $allowedExtensions = array(
        // Dropinbase required
        'css' => 'text/css',
        'js' => 'application/x-javascript',
        'svg' => 'image/svg+xml',
        'jpg' => array('image/jpg','image/jpeg'),
        'png' => 'image/png',
        'ico' => 'image/x-icon',

        'csv' => array(
            'text/csv',
            'text/plain',
            'application/csv',
            'text/comma-separated-values',
            'application/excel',
            'application/vnd.ms-excel', // Windows
            'application/vnd.msexcel',
            'text/anytext',
            'application/octet-stream',
            'application/txt',
        ),
        'pdf' => 'application/pdf',
        'jpg' => array('image/jpg','image/jpeg'),
        'jpeg' => array('image/jpg','image/jpeg'),
        'png' => 'image/png',
        //'gif' => 'image/gif',
        //'bmp' => 'image/bmp',
        //'tiff' => 'image/tiff',
        'xlsx' => array('application/zip','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','application/octet-stream'),
        'docx' => 'application/msword',
        
        /*
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

    $ext = strtolower((string)$ext);

    if(!array_key_exists($ext, $allowedExtensions))
        return FALSE;

        //  echo $allowedExtensions[$ext] . '<br><br>';
    
    return $allowedExtensions[$ext];
}
