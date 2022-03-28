<?php
/**
 * Files Handle Model
 * Called to handle the files when they are not found
 *
 * @file  The Files Handle file
 * @package HMWP/FilesModel
 * @since 4.0.0
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Models_Files
{

    protected $_files = array();
    protected $_safe_files = array();
    protected $_replace = array();
    protected $_rewrites = array();

    public function __construct()
    {
        //The list of handled file extensions
        $this->_files = array(
            'jpg',
            'jpeg',
            'png',
            'bmp',
            'gif',
            'jp2',
            'webp',
            'css',
            'scss',
            'js',
            'woff',
            'woff2',
            'ttf',
            'otf',
            'pfb',
            'pfm',
            'tfil',
            'eot',
            'svg',
            'pdf',
            'doc',
            'docx',
            'csv',
            'xls',
            'xslx',
            'mp2',
            'mp3',
            'mp4',
            'mpeg',
            'zip',
            'rar',
            'map',
            'txt'
        );

        //the safe extensions for static files
        $this->_safe_files = array(
            'jpgh',
            'jpegh',
            'pngh',
            'bmph',
            'gifh',
            'jp2h',
            'webph',
            'cssh',
            'scssh',
            'jsh',
            'woffh',
            'woff2h',
            'ttfh',
            'otfh',
            'pfbh',
            'pfmh',
            'tfilh',
            'eoth',
            'svgh',
            'pdfh',
            'doch',
            'docxh',
            'csvh',
            'xlsh',
            'xslxh',
            'mp2h',
            'mp3h',
            'mp4h',
            'mpegh',
            'ziph',
            'rarh',
            'maph',
            'rtxt'
        );

        //init the replacement array
        $this->_replace = array('from' => [], 'to' => []);
    }

    /**
     * Checking the mapping file
     *
     * @throws Exception
     */
    public function checkMappingFile()
    {

        if ($this->isFile($this->getCurrentURL()) ) {
            $this->showFile($this->getCurrentURL());
        }

    }

    /**
     * Check if the current URL is a file
     *
     * @throws Exception
     */
    public function checkBrokenFile()
    {

        //If the file doesn't exist
        //show the file content
        if (is_404() ) {
            $this->showFile($this->getCurrentURL());
        }

    }

    /**
     * If the rewrite config is not set
     * If there is a new file path, change it back to real path and show the file
     * Prevents errors when the paths are chnged but the rewrite config is not set up correctly
     *
     * @param $url
     *
     * @return bool|string
     */
    public function isFile( $url )
    {

        if ($url <> '' ) {
            if (strpos($url, '?') !== false ) {
                $url = substr($url, 0, strpos($url, '?'));
            }
            if (strrpos($url, '.') !== false ) {
                $ext = substr($url, strrpos($url, '.') + 1);
                if (in_array($ext, $this->_files) || in_array($ext, $this->_safe_files) ) {
                    return $ext;
                }
            }
        }

        return false;
    }

    /**
     * Get the current URL
     *
     * @return string
     */
    public function getCurrentURL()
    {
        $url = '';

        if (isset($_SERVER['HTTP_HOST']) ) {
            // build the URL in the address bar
            $url = is_ssl() ? 'https://' : 'http://';
            $url .= $_SERVER['HTTP_HOST'];
            $url .= $_SERVER['REQUEST_URI'];
        }

        return $url;
    }

    /**
     * Build the redirects array
     *
     * @throws Exception
     */
    public function buildRedirect()
    {
        $rewriteModel = HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite');

        //build the rules paths to change back the hidden paths
        if (empty($rewriteModel->_replace) ) {
            $rewriteModel->buildRedirect();
        }

        //URL Mapping
        $hmwp_url_mapping = json_decode(HMWP_Classes_Tools::getOption('hmwp_url_mapping'), true);
        if (isset($hmwp_url_mapping['from']) && !empty($hmwp_url_mapping['from']) ) {
            foreach ( $hmwp_url_mapping['from'] as $index => $row ) {
                if (substr($hmwp_url_mapping['from'][$index], -1) == '/' ) {
                    $this->_rewrites['from'][] = '#^/' . str_replace(array(home_url() . '/'), '', ltrim($hmwp_url_mapping['to'][$index], '/')) . '(.*)' . '#i';
                    $this->_rewrites['to'][] = '/' . str_replace(array(home_url() . '/'), '', ltrim($hmwp_url_mapping['from'][$index], '/')) . "$1";
                } else {
                    $this->_rewrites['from'][] = '#^/' . str_replace(array(home_url() . '/'), '', ltrim($hmwp_url_mapping['to'][$index], '/')) . '$' . '#i';
                    $this->_rewrites['to'][] = '/' . str_replace(array(home_url() . '/'), '', ltrim($hmwp_url_mapping['from'][$index], '/'));
                }
            }
        }

        if (!empty($rewriteModel->_replace['from']) && !empty($rewriteModel->_replace['to']) ) {
            foreach ( $rewriteModel->_replace['from'] as $index => $row ) {
                if (($index && isset($rewriteModel->_replace['to'][$index]) && substr($rewriteModel->_replace['to'][$index], -1) == '/') 
                    || strpos($rewriteModel->_replace['to'][$index], '/' . HMWP_Classes_Tools::getOption('hmwp_themes_style')) 
                ) {

                    $this->_rewrites['from'][] = '#^/' . $rewriteModel->_replace['to'][$index] . (substr($rewriteModel->_replace['to'][$index], -1) == '/' ? "(.*)" : "") . '#i';
                    $this->_rewrites['to'][] = '/' . $rewriteModel->_replace['from'][$index] . (substr($rewriteModel->_replace['to'][$index], -1) == '/' ? "$1" : "");

                }
            }
        }
    }

    /**
     * Get the original paths of a URL
     *
     * @param string $url URL
     *
     * @throws Exception
     * @return string
     */
    public function getOriginalUrl( $url )
    {

        //Buid the rewrite rules
        if(empty($this->_rewrites)) {
            $this->buildRedirect();
        }

        //Get the original URL based on rewrite rules
        $parse_url = parse_url($url);

        //Get the home root path
        $path = parse_url(home_url(), PHP_URL_PATH);

        //$parse_url['path'] = str_replace( $path, '', $parse_url['path'] );
        $parse_url['path'] = preg_replace('/^' . preg_quote($path, '/') . '/', '', $parse_url['path']);

        //Replace paths back to original
        if (isset($this->_rewrites['from']) && isset($this->_rewrites['to']) 
            && !empty($this->_rewrites['from']) && !empty($this->_rewrites['to']) 
        ) {
            $parse_url['path'] = preg_replace($this->_rewrites['from'], $this->_rewrites['to'], $parse_url['path'], 1);
        }
        //get the original URL
        if(isset($parse_url['port']) && $parse_url['port'] <> 80) {
            $new_url = $parse_url['scheme'] . '://' . $parse_url['host'] . ':' . $parse_url['port'] . $path . $parse_url['path'];
        }else{
            $new_url = $parse_url['scheme'] . '://' . $parse_url['host'] . $path . $parse_url['path'];
        }

        return str_replace('/wp-admin/wp-admin/', '/wp-admin/', $new_url); //remove duplicates

    }

    /**
     * Get the original path from url
     *
     * @param  $new_url
     * @return string
     */
    public function getOriginalPath( $new_url )
    {
        $new_path = str_replace(home_url(), '', $new_url);
        return HMWP_Classes_Tools::getRootPath() . ltrim($new_path, '/');
    }

    /**
     * Show the file when the server rewrite is not added
     *
     * @param string $url broken URL
     *
     * @throws Exception
     */
    public function showFile( $url )
    {

        //Initialize WordPress Filesystem
        $wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

        //remove the redirect hook
        remove_filter('wp_redirect', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite'), 'sanitize_redirect'), PHP_INT_MAX);
        remove_filter('template_directory_uri', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite'), 'find_replace_url'), PHP_INT_MAX);

        //In case of SAFEMODE URL or File mapping
        if (HMW_DYNAMIC_FILES ) {
            $url = str_replace($this->_safe_files, $this->_files, $url);
        }

        //Buid the rewrite rules
        $this->buildRedirect();

        //Get the original URL and path based on rewrite rules
        $new_url = $this->getOriginalUrl($url);
        $new_path = $this->getOriginalPath($new_url);

        $ctype = false;

        if ($ext = $this->isFile($new_url) ) {

            //if the file exists on the server
            if ($wp_filesystem->exists($new_path) ) {

                //If the plugin is not set to mapp all the files dynamically
                if (!HMW_DYNAMIC_FILES && !HMWP_Classes_Tools::getOption('hmwp_mapping_file') ) {
                    //if file is loaded through WordPress rewrites and not through config file
                    if ( parse_url($url) && $url <> $new_url && in_array($ext, array('png', 'jpg', 'jpeg', 'webp', 'gif'))) {
	                    if(stripos($new_url,'wp-admin') === false) {
							//if it's a valid URL
		                    //add the url in the WP rewrite list
		                    $mappings = (array) HMWP_Classes_Tools::getOption( 'file_mappings' );
		                    if ( count( $mappings ) < 10 ) {
			                    $mappings[ md5( $url ) ] = $url;
			                    HMWP_Classes_Tools::saveOptions( 'file_mappings', $mappings );
		                    }

		                    //for debug
		                    do_action( 'hmwp_debug_files', $url );
	                    }
                    }

                }
                //////////////////////////////////////////////////////////////////////////

                switch ( $ext ) {
                case "scss":
                case "css":
                    $ctype = "text/css";
                    break;
                case "js":
                    $ctype = "application/javascript";
                    break;
                case "svg":
                    $ctype = "image/svg+xml";
                    break;
                default:
                    if (function_exists('mime_content_type') ) {
                        $ctype = @mime_content_type($new_path);
                    }
                }

                ob_clean(); //clear the buffer
                $content = $wp_filesystem->get_contents($new_path);

                header("HTTP/1.1 200 OK");
                header("Cache-Control: max-age=2592000");
                header("Expires: " . gmdate('r', strtotime("+1 month")));
                header('Vary: Accept-Encoding');
                if ($ctype ) {
                    header('Content-Type: ' . $ctype . '; charset: UTF-8');
                }

                //change the .cssh and .jsh to .css and .js in files
                if (HMW_DYNAMIC_FILES ) {
                    if (strpos($new_url, '.js') ) {
                        $content = preg_replace(
                            array_map(
                                function ( $ext ) {
                                    return '/([\'|"][\/0-9a-zA-Z\.\_\-]+).' . $ext . '([\'|"|\?])/s'; 
                                }, $this->_files
                            ), array_map(
                                function ( $ext ) {
                                                     return '$1.' . $ext . '$2'; 
                                }, $this->_safe_files
                            ), $content
                        );
                        $content = preg_replace( '/([\'|"][\/0-9a-zA-Z\.\_\-]+).cssh([\'|"|\?])/si', '$1.css$2', $content );

                    } elseif (strpos($new_url, '.css') || strpos($new_url, '.scss') ) {
                        $content = preg_replace(
                            array_map(
                                function ( $ext ) {
                                    return  '/([\'|"|\(][\/0-9a-zA-Z\.\_\-]+).' . $ext . '([\'|"|\)|\?])/si';
                                }, $this->_files
                            ), array_map(
                                function ( $ext ) {
                                                     return '$1.' . $ext . '$2'; 
                                }, $this->_safe_files
                            ), $content
                        );
                    }
                }

                //if CSS, JS or SCSS
                if (strpos($new_url, '.js') || strpos($new_url, '.css') || strpos($new_url, '.scss') ) {

                    //remove comments
                    $content = preg_replace('/\/\*.*?\*\//s', '', $content, 1);

                    //Text Mapping for all css and js files
                    if (HMWP_Classes_Tools::getOption('hmwp_mapping_file') && !is_admin() && (function_exists('is_user_logged_in') && !is_user_logged_in() )) {

                        $hmwp_text_mapping = json_decode(HMWP_Classes_Tools::getOption('hmwp_text_mapping'), true);
                        if (isset($hmwp_text_mapping['from']) && !empty($hmwp_text_mapping['from'])
                            && isset($hmwp_text_mapping['to']) && !empty($hmwp_text_mapping['to'])
                        ) {

                            foreach ( $hmwp_text_mapping['to'] as &$value ) {
                                if ($value <> '' ) {
                                    if (strpos($value, '{rand}') !== false ) {
                                        $value = str_replace('{rand}', HMWP_Classes_Tools::generateRandomString(5), $value);
                                    } elseif (strpos($value, '{blank}') !== false ) {
                                        $value = str_replace('{blank}', '', $value);
                                    }
                                }
                            }

                            //change only the classes and ids
                            if (HMWP_Classes_Tools::getOption('hmwp_mapping_classes') ) {

                                foreach ( $hmwp_text_mapping['from'] as $index => $from ) {
                                    $content = preg_replace("'(?:([^/])" . addslashes($from) . "([^/]))'is", '$1' . $hmwp_text_mapping['to'][$index] . '$2', $content);
                                }

                            } else {
                                $content = str_ireplace($hmwp_text_mapping['from'], $hmwp_text_mapping['to'], $content);
                            }

                        }
                    }
                }

                //gzip the CSS
                if (function_exists('gzencode') ) {
                    header("Content-Encoding: gzip"); //HTTP 1.1
                    $content = gzencode($content);
                }

                //Show the file html content
                header('Content-Length: ' . strlen($content));
                echo $content;
                exit();
            }

        } elseif (strpos($new_url, 'wp-login.php') || strpos($new_url, HMWP_Classes_Tools::getOption('hmwp_login_url')) ) {

            $actions = array(
                'postpass',
                'logout',
                'lostpassword',
                'retrievepassword',
                'resetpass',
                'rp',
                'register',
                'login',
                'confirmaction'
            );
            $_REQUEST['action'] = $this->strposa($new_url, $actions);

            ob_start();
            include ABSPATH . '/wp-login.php';
            $content = ob_get_clean();

            header("HTTP/1.1 200 OK");

            //Echo the html file content
            echo $content;
            exit();

        } elseif (strpos($new_url, '/wp-activate.php') ) {

            ob_start();
            include ABSPATH . '/wp-activate.php';
            $content = ob_get_clean();

            header("HTTP/1.1 200 OK");

            //Echo the html file content
            echo $content;
            exit();

        } elseif (strpos($new_url, '/wp-signup.php') ) {

            ob_start();
            include ABSPATH . '/wp-signup.php';
            $content = ob_get_clean();

            header("HTTP/1.1 200 OK");

            //Echo the html file content
            echo $content;
            exit();

        } elseif (strpos($new_url, '/' . HMWP_Classes_Tools::$default['hmwp_wp-json']) !== false && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
            $response = $this->postRequest($url);

            header("HTTP/1.1 200 OK");
            if (!empty($response['headers']) ) {
                foreach ( $response['headers'] as $header ) {
                    header($header);
                }
            }

            //Echo the html file content
            echo $response['body'];
            exit();

        } elseif ($url <> $new_url ) {
            if (!HMWP_Classes_Tools::getValue('noredirect') ) {

	            $uri = parse_url($url, PHP_URL_QUERY);

	            if($uri && strpos($new_url, '?') === false){
		            $new_url .= '?' . $uri;
	            }

                wp_safe_redirect(add_query_arg(array('noredirect' => true), $new_url), 301);
                exit();
            }
        }
    }

    /**
     * Do a Post request
     *
     * @param  $url
     * @return array
     */
    public function postRequest( $url )
    {
        $return = array();

        $headers = getallheaders();
        $options = array(
            'method' => 'POST',
            'headers' => $headers,
            'body' => $_POST,
            'timeout' => 60,
            'sslverify' => false,
        );


        $response = wp_remote_post($url, $options);

        $return['body'] = wp_remote_retrieve_body($response);
        foreach ( wp_remote_retrieve_headers($response) as $key => $value ) {
            if (!is_array($value) ) {
                $return['headers'][] = "$key: $value";
            } else {
                foreach ( $value as $v ) {
                    $return['headers'][] = "$key: $v";
                }
            }
        }

        return $return;
    }

    /**
     * Do a Get request
     *
     * @param  $url
     * @return array
     */
    public function getRequest( $url )
    {
        $return = array();

        $headers = getallheaders();
        $options = array(
            'method' => 'GET',
            'headers' => $headers,
            'timeout' => 60,
            'sslverify' => false,
        );


        $response = wp_remote_get($url, $options);

        $return['body'] = wp_remote_retrieve_body($response);
        foreach ( wp_remote_retrieve_headers($response) as $key => $value ) {
            if (!is_array($value) ) {
                $return['headers'][] = "$key: $value";
            } else {
                foreach ( $value as $v ) {
                    $return['headers'][] = "$key: $v";
                }
            }
        }

        return $return;
    }

    /**
     * Look into array of actions
     *
     * @param $haystack
     * @param array $needles
     * @param int   $offset
     *
     * @return bool|mixed
     */
    function strposa( $haystack, $needles = array(), $offset = 0 )
    {
        foreach ( $needles as $needle ) {
            if (strpos($haystack, $needle, $offset) !== false ) {
                return $needle;
            }
        }

        return false;
    }

}
