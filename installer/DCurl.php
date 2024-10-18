<?php 
    
/*
  HELPFUL:
  https://curl.se/docs/caextract.html
  https://reqbin.com/req/php/c-1n4ljxb9/curl-get-request-example
  https://www.webhostface.com/kb/knowledgebase/php-curl-examples/

  TROUBLESHOOTING:
  https://stackoverflow.com/questions/50840101/curl-35-error1408f10bssl-routinesssl3-get-recordwrong-version-number
  https://serverfault.com/questions/755248/curl-unable-to-get-local-issuer-certificate-how-to-debug
*/

class DCurl {

    private $domain = ''; // eg 'https://faisexam.co.za'
    private $sessionId = ''; // used to store unique curl_cookiefiles
    public $addRequestVerificationTokenHdr = FALSE;
    public $dibToken = '';
    public $inclHeaders = array();
    public $thisClassIsInDibFramework = TRUE;
    public $caCertPemFilePath = ''; // ensure you download the latest from a reputable source
    public $retryCount = 5;
    public $retryWaitTime = 4;

    /**
     * @param $domain string eg http://google.com
     * @param $caCertPemFilePath string full path to ssl certificate. If empty, will use DIB's cert. 
     * @param $thisClassIsInDibFramework bool whether this class is instantiated from within a DIB framework or used externally
    */
    public function __construct($domain, $caCertPemFilePath='', $thisClassIsInDibFramework=TRUE) {
        $this->domain = $domain;
        $this->caCertPemFilePath = $caCertPemFilePath; // if empty, will default to cert stored in /dropins/dibAdmin/template/cacert.pem
        $this->thisClassIsInDibFramework = $thisClassIsInDibFramework;
    }

    /**
     * Login to a Dropinbase application
     * @param string $username username
     * @param string $password  password
     * @param boolean $debug  whether detailed debug information is printed to error log
     * @param string $apiToken  security token for this client (must match value on server in eg /configs/apiTokens.php - see SiteController.php)
     * @return mixed boolean TRUE on success, string $errMsg on failure
     */
    public function dibLogin($username, $password, $debug=FALSE, $apiToken='') {

        /* Equivalent to:
        (-v is verbose... to get phpsessionid)

        curl -v -X POST https://xxx.xxx.xx/dropins/dibAuthenticate/Site/login?api=1 \
        --header 'Content-Type: application/json' \
        --data-raw '{"username": "admin","password": "test","form_token": "A123456","email1": ""}'
        
        curl -X POST https://xxx.xxx.xx/dropins/xxx/xxx/xxx?aaaa=1&bbbb=2 \
        -H "RequestVerificationToken=xxx" \
        -H "Cookie: PHPSESSID=xxxxxxxxx" \
        -H "Content-Type: application/json" \
        --data-raw '{"params": {"cccc":"xxxxx"}}'
        
        */

        $this->sessionId = md5($this->domain . '_' . DIB::$USER['unique_id']);
        
        // login and get RequestVerificationToken
        $post = array(
            'username' => $username,
            'password' => $password,
            'email1' =>  '',
            'api' => 1,
            'form_token' => $apiToken
        );
        
        $result = $this->request('POST', '/dropins/dibAuthenticate/Site/login', $post, 'json', array(), $debug);

        if(!empty($result) && strpos($result, '/dropins/dibAuthenticate/Site/login') !== false)
            return 'Could not authenticate. Turn on debugging to check response.';

        $result = json_decode($result, true);

        if(!empty($result) && array_key_exists('status', $result)) {
            if($result['status'] !== 'success')
                return 'Authentication failed. Check the login credentials, and the error and permission logs of the target site.';

            if(array_key_exists('RequestVerificationToken', $result))
                $this->dibToken = $result['RequestVerificationToken'];
            else
                return 'Authentication failed. RequestVerificationToken expected in the response array.';

        } else {
            // We have to get RequestVerificationToken value (to get around "missing request token" response)
            // else DIB will only accept requests where controller functions have following in function parameters:
            //   , $REQUEST_TYPE = "POST,GET,ignoretoken"

            $uri = '/peff/Template/environment.js?v=' . uniqid('', true);

            $result = $this->request('GET', $uri, array(), 'query', array(), $debug);

            // get RequestVerificationToken value stored in 'auth_id'
            $i = strpos($result, '"auth_id":"');

            if($i === FALSE) {
                $i = strpos($result, '"secure_id":"'); // backwards compatibility
            
                if($i === FALSE) {
                    if($debug) Log::w("Api/Curl reponse from call to $uri:\r\n" . $result);
                    return 'Could not parse response from environment.js call to obtain RequestVerificationToken. Turn on debugging to check response in Debug.log.';
                }
                $l = strlen('secure_id":"');
                
            } else
                $l = strlen('auth_id":"');

            $j = strpos($result, '"', $i + $l + 1);
            $this->dibToken = substr($result, $i + $l + 1, $j - $i - $l - 1);
        }

        // set bool so that RequestVerificationToken will be added to headers automatically.
        $this->addRequestVerificationTokenHdr = true;
        if($debug) Log::w('Logged-in successfully. Got RequestVerificationToken: ' . $this->dibToken);

        return TRUE;
    }

    /**
     * @param string $requestType POST/GET/etc
     * @param string $url URL to request excluding the domain part, eg /dropins/myDropin/App/greeting
     * @param mixed $params data values to post
     * @param string $encoding how $params is encoded: raw/query/json. Use raw when sending files.
     * @param array $headers any headers to add
     * @param boolean $debug whether detailed debugging info is logged to error log
     * @param array $files array of absolute paths to files to upload
     * @return mixed data result on success, false on failure
     */
    function request($requestType, $url, $params=array(), $encoding='query', $headers=array(), $debug=FALSE, $files=array()) {
        try {
            
            $requestType = strtoupper($requestType);
            $postValue = '';
            $time = microtime(true);

            if(empty($headers)) $headers = array();

            if(!empty($params)) {
                if($requestType === 'GET' || $encoding === 'query')
                    $postValue = http_build_query($params);

                elseif(!empty($files)) {
                    $postValue = $params;
                   // headers are automatically set with boundaries... // $headers[] = 'Content-Type: multipart/form-data';

                } elseif ($encoding === 'json') {
                    $postValue = json_encode($params);
                    $headers[] = 'Content-Type: application/json';

                } else
                    $postValue = $params;
            }

            $allHeaders = (!empty($this->inclHeaders)) ? array_merge($headers, $this->inclHeaders) : $headers;

            if($this->addRequestVerificationTokenHdr)
                $allHeaders = array_merge($allHeaders, array('RequestVerificationToken: ' . $this->dibToken));

            if($this->thisClassIsInDibFramework) {
                $cookieFile = DIB::$RUNTIMEPATH . 'tmp' . DIRECTORY_SEPARATOR . 'curl_' . $this->sessionId . '.ses';
                if(empty($this->caCertPemFilePath))
                    $this->caCertPemFilePath = DIB::$SYSTEMPATH . 'dropins' . DIRECTORY_SEPARATOR . 'dibAdmin' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'cacert.pem';
            } else {
                $cookieFile = __DIR__ . DIRECTORY_SEPARATOR . 'curl_' . $this->sessionId . '.ses';
                if(empty($this->caCertPemFilePath))
                    return 'The $caCertPemFilePath variable must be set to the full path of a valid cacert.pem file. Ensure the latest is downloaded from a reputable source.';
            }
           
            if($requestType === 'POST') {
                // *** NOTE: the order of for eg. CURLOPT_POST matters in the $options array! When posting files, CURL breaks when CURLOPT_POST is at the bottom.
                $options = array(CURLOPT_POST => true);

            } else {
                $options = array(CURLOPT_CUSTOMREQUEST => 'GET');
                $url .= '?' . $postValue;
            }

            $optionsOther = array(

                CURLOPT_RETURNTRANSFER => true,             // return of web page
                CURLOPT_ENCODING       => "",               // handle all encodings
                CURLOPT_USERAGENT      => "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:53.0) Gecko/20100101 Firefox/53.0",
                CURLOPT_CONNECTTIMEOUT => (60 * 58),        // timeout in sec to connect
                CURLOPT_TIMEOUT        => (60 * 60),        // timeout in sec for CURL functions to run

                CURLOPT_FOLLOWLOCATION => false,            // follow of redirects
                CURLOPT_AUTOREFERER    => true,             // set referer on redirect
                CURLOPT_MAXREDIRS      => 3,                // stop after 3 redirects

                CURLOPT_HEADER         => false,            // return of headers
                CURLOPT_HTTPHEADER     => $allHeaders,
                CURLOPT_POSTFIELDS     => $postValue,       // Note, GET requests can also send data, but normally use URL query string to do so

                CURLOPT_COOKIEJAR      => $cookieFile,      // where to write cookie data
                CURLOPT_COOKIEFILE     => $cookieFile,      // where to read cookie data from
                CURLOPT_SSL_VERIFYPEER => $this->caCertPemFilePath, // set to false/0 for debug only, never in prod else vulnerable to Man-in-the-middle attacks
                CURLOPT_SSL_VERIFYHOST => $this->caCertPemFilePath  // set to false/0 for debug only, never in prod else vulnerable to Man-in-the-middle attacks
            );

            $options = $options + $optionsOther; // *** the order of for eg. CURLOPT_POST matters in the $options array!

            if(!empty($files)) {

                if(!is_array($postValue)) {
                    $msg = "Call to DCurl failed. When sending files, set \$encoding to 'raw', and postData to an array of values (or an empty array if none)";
                    Log::err($msg);

                    if($debug && !$this->thisClassIsInDibFramework)
                        print_r($msg);

                    return false;
                }

                foreach($files as $name => $filePath) {
                    if(!file_exists($filePath)) {
                        $msg = 'Call to DCurl failed - it included the following file that does not exist: ' . $filePath;
                        Log::err($msg);

                        if($debug && !$this->thisClassIsInDibFramework)
                            print_r($msg);

                        return false;
                    }

                    $mimeType = $this->getMimeType($filePath);
                    $postValue['userfile'] = new \CURLFile($filePath, $mimeType, $name);
                }

                $options[CURLOPT_POSTFIELDS] = $postValue;
            }

            $verbose = '';

            if($debug) {
                $options[CURLOPT_VERBOSE] = true;
                $options[CURLOPT_STDERR] = $verbose = fopen('php://temp', 'rw+');
            }

            $msg = '';
            $data = false;
            $errno = 0;
            $retryCounter = 0;

            $url = rtrim($this->domain, '/') . '/' . ltrim($url, '/');

            // Sometimes path over network/internet is shaky... retry request
            while(($data === false || $errno != 0) && $retryCounter < $this->retryCount) {

                if($retryCounter > 0) sleep($this->retryWaitTime);

                $ch = curl_init($url);

                curl_setopt_array($ch, $options);
                $data = curl_exec($ch);

                $errno = curl_errno($ch);

                $retryCounter++;
            }

            if($ch === false) {
                $msg .= 'CURL ERROR: Could not initialize CURL with curl_init. Retried action ' . $retryCounter . ' time(s). Please investigate. <br>';
                $debug = TRUE;

            } elseif($data === false) {
                $msg .= 'CURL ERROR: Could not execute CURL request. Retried action ' . $retryCounter . ' time(s). Please investigate.<br>';
                $debug = TRUE;
            } 

            $errmsg = curl_error($ch);
            $status = curl_getinfo($ch);

            if($errno != 0) $debug = TRUE;

            if($debug) {
                $time = microtime(true) - $time;
                $debugStr = '';
                
                foreach($params as $key=>$v) {
                    if(is_array($v)) $v = json_encode($v);
                    $debugStr .= "$key=$v&";
                }

                $debugStr = rtrim($debugStr, '&');
                if(!empty($files)) {
                    $params = array_merge($params, array('debug-info-for-files' => implode(', ', array_keys($files))));
                    $postStr = json_encode($params);

                } else
                    $postStr = (is_array($postValue)) ? json_encode($postValue) : $postValue;

                if($requestType === 'POST')
                    $debugStr = 'curl -X POST -d "' . $debugStr . '" "' . $url . '"';
                else
                    $debugStr = 'curl "' . $url . '"?' . $debugStr;

                $dataTmp = $data;
                // Check if the data contains any non-printable characters
                if(preg_match('~[^\x20-\x7E\t\r\n]~', $data) > 0)
                    $dataTmp = '(looks like unprintable binary data)';

                $msg .= "<br><br><b>REQUEST</b>: " . $url . "<br>
                         <b>DEBUG WITH CURL:</b> $debugStr<br>
                         <b>POST PARAMS:</b> $postStr<br>
                         <b>RESULT:</b> $dataTmp<br>
                         <b>CURL ERRNO:</b> $errno<br>
                         <b>ERRMSG:</b> $errmsg<br>
                         <b>TIME:</b> $time <br> 
                         <b>STATUS:</b> " . str_replace("\n", "<br>", print_r($status, TRUE)) . " <br> 
                         <b>VERBOSE:</b>" . $verbose;

                if($this->thisClassIsInDibFramework)
                    Log::err(str_replace(array('<br>', '<b>', '</b>'), array("\r\n", '', ''), $msg));
                else
                    print_r($msg);
            }

            curl_close($ch);

            if($data === false || $errno != 0)
                return FALSE;

            return $data;

        } catch (Exception $e) {
            $msg = "CURL request error. Details: " . $e->getMessage();
            if($this->thisClassIsInDibFramework)
                Log::err($msg);
            else
                print_r($msg);

            return FALSE;
        }
    }

    function buildPostData($args) {
        // http_build_query(
        $str = '';
        foreach($args as $key=>$val) {
            if(is_array($val)) $val = json_encode($val);
            $str .= '&' . $key . '=' . urlencode($val);
        }

        return ltrim($str, '&');
    }

    private static function getMimeType($filePath) {
		$mimeType = null; // important to return NULL for CURL's new CURLFile() call

		if (function_exists('finfo_open')) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$mimeType = finfo_file($finfo, $filePath);
			finfo_close($finfo);

		} elseif (function_exists('mime_content_type')) 
			$mimeType = mime_content_type($filePath);
		
		return $mimeType;
	}

}