<?php
    //phpinfo(); die(); // To view installed PHP extensions, uncomment this line

    $DIR = __DIR__;

    // Check if framework is installed
    $path = str_replace('/', DIRECTORY_SEPARATOR, './vendor/dropinbase/dropinbase/installer/index.php');

    if(!file_exists($path)) {
        $path = str_replace('/', DIRECTORY_SEPARATOR, '../vendor/dropinbase/dropinbase/installer/index.php'); // Docker

        if(!file_exists($path)) {

            $realPath = dirname(__FILE__);
            $pathStr = ($realPath === '/dropinbase') ? '/path/to/your/project' : $realPath;

            $path = str_replace('/', DIRECTORY_SEPARATOR, '/vendor/dropinbase/dropinbase');
            
            $str = '<h3>Error - the Dropinbase framework has not been installed</h3>
                    The vendor folder for the Dropinbase framework is expected in <b>' . $pathStr . '</b><br><br>
                    If another installation of the Dropinbase vendor folder already exists on this machine, use a symbolic link to point the path above to it.
                    <br>Otherwise install it using Composer:
            
                    <ul>
                        <li>
                            Make sure Composer is installed (<a target="_blank" href="https://getcomposer.org/download">https://getcomposer.org/download</a>)
                        </li>
                        <li>
                            Open a terminal window, and run the following commands:<br><br>
                            <span>
                                <b>cd ' . $pathStr . '</b></br>
                                <b>composer update</b>
                            </span>
                        </li>
                    </ul>
                    ';
            echo $str;
            die();
        }
    }

    include_once($path);