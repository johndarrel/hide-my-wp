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
     * Show the file if in the list of extensions
     *
     * @throws Exception
     */
    public function maybeShowFile()
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
    public function maybeShowNotFound()
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
	        $url .= rawurldecode( $_SERVER['REQUEST_URI'] );
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
			    $this->_rewrites['from'][] = '#^/' . $rewriteModel->_replace['to'][$index] . (substr($rewriteModel->_replace['to'][$index], -1) == '/' ? "(.*)" : "") . '#i';
			    $this->_rewrites['to'][] = '/' . $rewriteModel->_replace['from'][$index] . (substr($rewriteModel->_replace['to'][$index], -1) == '/' ? "$1" : "");
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

	    //Backslash the paths
	    if($path <> '') {
		    $parse_url['path'] = preg_replace('/^' . preg_quote($path, '/') . '/', '', $parse_url['path']);
	    }

	    //Replace paths back to original
	    if (isset($this->_rewrites['from']) && isset($this->_rewrites['to']) && !empty($this->_rewrites['from']) && !empty($this->_rewrites['to'])) {
		    $parse_url['path'] = preg_replace($this->_rewrites['from'], $this->_rewrites['to'], $parse_url['path'], 1);
	    }

	    //get the original URL
	    if(isset($parse_url['port']) && $parse_url['port'] <> 80) {
		    $new_url = $parse_url['scheme'] . '://' . $parse_url['host'] . ':' . $parse_url['port'] . $path . $parse_url['path'];
	    }else{
		    $new_url = $parse_url['scheme'] . '://' . $parse_url['host'] . $path . $parse_url['path'];
	    }

	    if( isset($_SERVER['QUERY_STRING']) && !empty( $_SERVER['QUERY_STRING'] ) ){
		    $query = $_SERVER['QUERY_STRING'];
		    $query = str_replace(array('?', '%3F'),'&', $query);
		    $new_url .= ( ! strpos( $new_url, '?' ) ? '?' : '&') . $query ;
	    }

	    return $new_url; //remove duplicates

    }

    /**
     * Get the original path from url
     *
     * @param  $new_url
     * @return string
     */
    public function getOriginalPath( $new_url )
    {
	    //remove domain from path
	    $new_path = str_replace(home_url(), '', $new_url);

	    //remove queries from path
	    if(strpos($new_path , '?') !== false){
		    $new_path = substr($new_path, 0, strpos($new_path , '?'));
	    }

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
	            $etag = md5_file($new_path);

	            header("HTTP/1.1 200 OK");
	            header("Cache-Control: max-age=2592000, must-revalidate");
	            header("Expires: " . gmdate('r', strtotime("+1 month")));
	            header('Vary: Accept-Encoding');
	            header("Pragma: public");
	            header("Etag: \"{$etag}\"");

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

        } elseif (strpos($new_url, '/' .HMWP_Classes_Tools::getOption('hmwp_login_url')) ||
                  strpos($new_url, '/' .HMWP_Classes_Tools::$default['hmwp_login_url']) ||
                  (HMWP_Classes_Tools::getOption('hmwp_lostpassword_url') && strpos($new_url, '/' .HMWP_Classes_Tools::getOption('hmwp_lostpassword_url')))||
                  (HMWP_Classes_Tools::getOption('hmwp_logout_url') && strpos($new_url, '/' .HMWP_Classes_Tools::getOption('hmwp_logout_url')))||
                  (HMWP_Classes_Tools::getOption('hmwp_register_url') && strpos($new_url, '/' .HMWP_Classes_Tools::getOption('hmwp_register_url')))) {

	        add_filter('hmwp_option_hmwp_remove_third_hooks', '__return_true');

	        header("HTTP/1.1 200 OK");

	        $this->handleLogin($new_url);

        } elseif ( $url <> $new_url ) {

	        if (stripos($new_url, '/' . HMWP_Classes_Tools::getDefault('hmwp_admin-ajax_url')) !== false ||
	            stripos($new_url, '/' . HMWP_Classes_Tools::getDefault('hmwp_wp-json') . '/') !== false) {

		        $response = false;

		        if(isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			        $response = $this->postRequest($new_url);
		        }elseif(isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'GET' ) {
			        $response = $this->getRequest($new_url);
		        }

		        if($response){
			        header("HTTP/1.1 200 OK");
			        if (!empty($response['headers']) ) {
				        foreach ( $response['headers'] as $header ) {
					        header($header);
				        }
			        }

			        //Echo the html file content
			        echo $response['body'];
			        exit();
		        }

		        exit();

	        } elseif (strpos($new_url, '/' . HMWP_Classes_Tools::getDefault('hmwp_activate_url')) !== false ||
	                  strpos($new_url, '/' . HMWP_Classes_Tools::getDefault('hmwp_wp-signup_url')) !== false ) {

		        ob_start();
		        include $new_path;
		        $content = ob_get_clean();

		        header("HTTP/1.1 200 OK");

		        //Echo the html file content
		        echo $content;
		        exit();

	        }elseif (!HMWP_Classes_Tools::getValue('nordt') ) {

		        $uri = parse_url($url, PHP_URL_QUERY);

		        if($uri && strpos($new_url,'?') === false){
			        $new_url .= '?' . $uri;
		        }

		        wp_safe_redirect(add_query_arg(array('nordt' => true), $new_url), 301);
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


	/**
	 * Handle the Login if the rules were not added in the config file
	 *
	 * @param $url
	 * @return void
	 */
	public function handleLogin($url){
		$url = rawurldecode( $url );

		if ( ! ( HMWP_Classes_Tools::getvalue('action') === 'postpass' && HMWP_Classes_Tools::getIsset('post_password') ) ) {

			//If it's the login page
			if(strpos($url, '/' . HMWP_Classes_Tools::getOption('hmwp_login_url')) ||
			   strpos($url, '/' . HMWP_Classes_Tools::$default['hmwp_login_url']) ||
			   (HMWP_Classes_Tools::getOption('hmwp_lostpassword_url') && strpos($url, '/' .HMWP_Classes_Tools::getOption('hmwp_lostpassword_url'))) ||
			   (HMWP_Classes_Tools::getOption('hmwp_register_url') && strpos($url, '/' .HMWP_Classes_Tools::getOption('hmwp_register_url')))) {

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
				$_REQUEST['action'] = $this->strposa($url, $actions);

				$urled_redirect_to = '';
				if ( isset( $_REQUEST['redirect_to'] ) ) {
					$urled_redirect_to = $_REQUEST['redirect_to'];
				}

				if ( is_user_logged_in() ) {
					$user = wp_get_current_user();
					if ( ! isset( $_REQUEST['action'] ) ) {
						$logged_in_redirect = apply_filters( 'hmwp_url_login_redirect', admin_url(), $urled_redirect_to, $user );
						wp_safe_redirect( $logged_in_redirect );
						die();
					}
				}

				global $error, $interim_login, $action, $user_login;
				@require_once ABSPATH . 'wp-login.php';
				die();

			} elseif (HMWP_Classes_Tools::getOption('hmwp_logout_url') <> '' && strpos($url, '/' . HMWP_Classes_Tools::getOption('hmwp_logout_url'))){

				check_admin_referer( 'log-out' );

				$user = wp_get_current_user();

				wp_logout();

				if ( ! empty( $_REQUEST['redirect_to'] ) ) {
					$redirect_to           = $_REQUEST['redirect_to'];
					$requested_redirect_to = $redirect_to;
				} else {
					$redirect_to = add_query_arg(
						array(
							'loggedout' => 'true',
							'wp_lang'   => get_user_locale( $user ),
						),
						wp_login_url()
					);

					$requested_redirect_to = '';
				}

				$redirect_to = apply_filters( 'logout_redirect', $redirect_to, $requested_redirect_to, $user );

				wp_safe_redirect( $redirect_to );
				exit;
			}

		}

	}

}
