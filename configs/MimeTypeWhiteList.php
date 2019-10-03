<?php

    /* 
    
    NOTE this list acts as the primary whitelist of allowed file extensions for downloading and /files/ requests
    Files with other extensions are rejected
    
    *** IMPORTANT! Any files within the following folders that have file extensions listed here, can be downloaded by users:
​		- /public_html/files/
		- Any subfolder of a /dropins/ folder, except within /dropins/DROPINNAME/templates/
    
    *** DO NOT add the following file extensions:
		​php       (server-side code)
		dtxt      (used for cache files)
		ethtml    (certain Eleutheria templates outside of /templates/ folders)
		etjs      (certain Eleutheria templates outside of /templates/ folders)
	*/
	
	function mimeType($ext) {
        switch (strtolower($ext)) {
            case 'js' :
                return 'application/x-javascript';
            case 'css' :
                return 'text/css';           
            case 'html' :
            case 'htm' :
               return 'text/html';            
            case 'txt' :
            case 'svg' :
                return 'text/plain'; // Note, as an example, this code will return 'text/plain' for the following extensions: svg and txt
            case 'json' :
                return 'application/json';
            case 'csv' :
                return 'text/csv';
            case 'ico' :
                return 'image/x-icon';
            case 'jpg' :
            case 'jpeg' :
                return array('image/jpg','image/jpeg');
            case 'png' :
            case 'gif' :
            case 'bmp' :
            case 'tiff' :
                return 'image/' . strtolower($ext);
            case 'xml' :
                return 'application/xml';
            case 'doc' :
            case 'docx' :
                return 'application/msword';
            case 'xls' :
            case 'xlsx' :
            case 'xlt' :
            case 'xlm' :
            case 'xld' :
            case 'xla' :
            case 'xlc' :
            case 'xlw' :
            case 'xll' :
                return array('application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            case 'ppt' :
            case 'pptx' :
            case 'pps' :
                return 'application/vnd.ms-powerpoint';
            case 'rtf' :
                return 'application/rtf';
            case 'pdf' :
                return 'application/pdf';

            case 'mpeg' :
            case 'mpg' :
            case 'mpe' :
                return 'video/mpeg';
            case 'mp3' :
                return 'audio/mpeg3';
            case 'wav' :
                return 'audio/wav';
            case 'aiff' :
            case 'aif' :
                return 'audio/aiff';
            case 'avi' :
                return 'video/msvideo';
            case 'wmv' :
                return 'video/x-ms-wmv';
            case 'mov' :
                return 'video/quicktime';
            case 'zip' :
                return 'application/zip';
            case 'tar' :
                return 'application/x-tar';
            case 'swf' :
                return 'application/x-shockwave-flash';
            default :
                return FALSE;
        }
    }
    