<?php
defined('ABSPATH') || die('Cheatin\' uh?');

class HMW_Models_Files {

    protected $_files = array();
	protected $_replace = array();
	protected $_rewrites = array();

    public function __construct() {
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
		    'txt'
	    );

        //init the replace array
        $this->_replace = array('from' => [], 'to' => []);
    }

    /**
     * Check if the current URL is a file
     */
    public function checkBrokenFile() {
        //don't let to rename and hide the current paths if logout is required
        if (HMW_Classes_Tools::getOption('error') || HMW_Classes_Tools::getOption('logout')) {
            return;
        }

        //stop here is the option is default.
        //the prvious code is needed for settings change and validation
        if (HMW_Classes_Tools::getOption('hmw_mode') == 'default') {
            return;
        }

        if (is_404()) {
            $this->showFile($this->getCurrentURL());
        }
    }

    /**
     *
     * If the rewrite config is not set
     * If there is a new file path, change it back to real path and show the file
     * Prevents errors when the paths are chnged but the rewrite config is not set up correctly
     * @param $url
     * @return bool|string
     */
    public function isFile($url) {
        if ($url <> '') {
            if (strpos($url, '?') !== false) $url = substr($url, 0, strpos($url, '?'));
            if (strrpos($url, '.') !== false) {
                $ext = substr($url, strrpos($url, '.') + 1);
                if (in_array($ext, $this->_files)) {
                    return $ext;
                }
            }
        }

        return false;
    }

    /**
     * Get the current URL
     * @return string
     */
    public function getCurrentURL() {
        $url = '';

        if (isset($_SERVER['HTTP_HOST'])) {
            // build the URL in the address bar
            $url = is_ssl() ? 'https://' : 'http://';
            $url .= $_SERVER['HTTP_HOST'];
            $url .= $_SERVER['REQUEST_URI'];
        }

        return $url;
    }

	/**
	 * Build the redirects array
	 * @throws Exception
	 */
	public function buildRedirect() {
		$rewriteModel = HMW_Classes_ObjController::getClass( 'HMW_Models_Rewrite' );

		//build the rules paths to change back the hidden paths
		if ( !isset( $rewriteModel->_replace['from'] ) && !isset( $rewriteModel->_replace['to'] ) ) {
			$rewriteModel->buildRedirect();
		}

		foreach ( $rewriteModel->_replace['from'] as $key => $row ) {
			if ( $rewriteModel->_replace['rewrite'][$key] ) {
				$this->_rewrites['from'][] = '#^/' . $rewriteModel->_replace['to'][$key] . (substr( $rewriteModel->_replace['to'][$key], -1 ) == '/' ? "(.*)" : "") . '#i';
				$this->_rewrites['to'][] = '/' . $rewriteModel->_replace['from'][$key] . (substr( $rewriteModel->_replace['to'][$key], -1 ) == '/' ? "$1" : "");
			}
		}
	}

	/**
	 * Get the original paths of an URL
	 *
	 * @param string $url URL
	 *
	 * @throws Exception
	 * @return string
	 */
	public function getOriginalUrl($url){
		if ( !defined( 'ABSPATH' ) ) {
			return $url;
		}

		//Buid the rewrite rules
		$this->buildRedirect();

		//Get the original URL based on rewrite rules
		$parse_url = parse_url( $url );

		//Get the home root path
		$home_root = parse_url( home_url() );
		if ( isset( $home_root['path'] ) ) {
			$home_root = $home_root['path'];
			$parse_url['path'] = str_replace( $home_root, '', $parse_url['path'] );
		} else {
			$home_root = '';
		}

		$parse_url['query'] = ((isset($parse_url['query']) && $parse_url['query']) ? '?' . $parse_url['query'] : '');
		$parse_url['path'] = preg_replace( $this->_rewrites['from'], $this->_rewrites['to'], $parse_url['path'] );
		$new_url = $parse_url['scheme'] . '://' . $parse_url['host'] . $home_root . $parse_url['path'] . $parse_url['query'];
		$new_url = str_replace( '/wp-admin/wp-admin/', '/wp-admin/', $new_url ); //remove duplicates

		return $new_url;
	}

	/**
	 * Show the file when the server rewrite is not added
	 *
	 * @param string $url broken URL
	 *
	 * @throws Exception
	 */
	public function showFile( $url ) {
		if ( !defined( 'ABSPATH' ) ) {
			return;
		}

		//remove the redirect hook
		remove_filter( 'wp_redirect', array(HMW_Classes_ObjController::getClass( 'HMW_Models_Rewrite' ), 'sanitize_redirect'), PHP_INT_MAX );

		//Buid the rewrite rules
		$this->buildRedirect();

		//Get the original URL based on rewrite rules
		$parse_url = parse_url( $url );

		//Get the home root path
		$home_root = parse_url( home_url() );
		if ( isset( $home_root['path'] ) ) {
			$home_root = $home_root['path'];
			$parse_url['path'] = str_replace( $home_root, '', $parse_url['path'] );
		} else {
			$home_root = '';
		}

		$parse_url['query'] = ((isset($parse_url['query']) && $parse_url['query']) ? '?' . $parse_url['query'] : '');
		$parse_url['path'] = preg_replace( $this->_rewrites['from'], $this->_rewrites['to'], $parse_url['path'] );
		$new_url = $parse_url['scheme'] . '://' . $parse_url['host'] . $home_root . $parse_url['path'] . $parse_url['query'];
		$new_url = str_replace( '/wp-admin/wp-admin/', '/wp-admin/', $new_url ); //remove duplicates

		$ctype = false;
		if ( $ext = $this->isFile( $new_url ) ) {

			$new_path = HMW_Classes_Tools::getRootPath() . $parse_url['path'];
			if ( file_exists( $new_path ) ) {

				//if file is loaded through WordPress rewrites and not through config file
				if ( strpos( $url, HMW_Classes_Tools::getOption( 'hmw_wp-content_url' ) ) && $url <> $new_url ) {
					$redirects = (int)HMW_Classes_Tools::getOption( 'rewrites' );
					HMW_Classes_Tools::saveOptions( 'rewrites', ($redirects + 1) );
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
						if ( function_exists( 'mime_content_type' ) ) {
							$ctype = @mime_content_type( $new_path );
						}
				}

				ob_clean(); //clear the buffer
				$content = @file_get_contents( $new_path );

				header( "HTTP/1.1 200 OK" );
				header( "Cache-Control: max-age=2592000" );
				header( "Expires: " . gmdate( 'r', strtotime( "+1 month" ) ) );
				header( 'Vary: Accept-Encoding' );

				if ( $ctype ) {
					header( 'Content-Type: ' . $ctype . '; charset: UTF-8' );
				}

				if ( strpos( $new_url, '.js' ) || strpos( $new_url, '.css' ) || strpos( $new_url, '.scss' ) ) {
					//remove comments
					$content = preg_replace( '/\*\*\*+/s', '', $content, 1 );
					$content = preg_replace( '/(\/\*[^\*]+\*\/)/s', '', $content, 1 );

					//Text Mapping for all css and js files
					if ( HMW_Classes_Tools::getOption( 'hmw_mapping_classes' ) ) {
						$hmw_text_mapping = json_decode( HMW_Classes_Tools::getOption( 'hmw_text_mapping' ), true );
						if ( isset( $hmw_text_mapping['from'] ) && !empty( $hmw_text_mapping['from'] ) &&
						     isset( $hmw_text_mapping['to'] ) && !empty( $hmw_text_mapping['to'] ) ) {

							foreach ( $hmw_text_mapping['to'] as &$value ) {
								if($value <> '') {
									if (strpos( $value, '{rand}' ) !== false ) {
										$value = str_replace( '{rand}', HMW_Classes_Tools::generateRandomString( 5 ), $value );
									} elseif ( strpos( $value, '{blank}' ) !== false ) {
										$value = str_replace( '{blank}', '', $value );
									}
								}
							}

							if ( HMW_Classes_Tools::getOption( 'hmw_mapping_classes' ) ) {

								foreach ( $hmw_text_mapping['from'] as $index => $from ) {
									$content = preg_replace( "'(?:([^/])" . addslashes( $from ) . "([^/]))'is", '$1' . $hmw_text_mapping['to'][$index] . '$2', $content );
								}

							} else {
								$content = str_ireplace( $hmw_text_mapping['from'], $hmw_text_mapping['to'], $content );
							}

						}
					}
				}

				//gzip the CSS
				if ( function_exists( 'gzencode' ) ) {
					header( "Content-Encoding: gzip" ); //HTTP 1.1
					$content = gzencode( $content );
				}

				//Show the content
				header( 'Content-Length: ' . strlen( $content ) );
				echo $content;
				exit();
			}

		} elseif ( strpos( $new_url, 'wp-login.php' ) || strpos( $new_url, HMW_Classes_Tools::getOption( 'hmw_login_url' ) ) ) {

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
			$_REQUEST['action'] = $this->strposa( $new_url, $actions );

			ob_start();
			include(ABSPATH . '/wp-login.php');
			$content = ob_get_clean();

			header( "HTTP/1.1 200 OK" );
			echo $content;
			exit();

		} elseif ( strpos( $new_url, '/wp-activate.php' ) ) {

			ob_start();
			include(ABSPATH . '/wp-activate.php');
			$content = ob_get_clean();

			header( "HTTP/1.1 200 OK" );
			echo $content;
			exit();

		} elseif ( strpos( $new_url, '/wp-signup.php' ) ) {

			ob_start();
			include(ABSPATH . '/wp-signup.php');
			$content = ob_get_clean();

			header( "HTTP/1.1 200 OK" );
			echo $content;
			exit();

		} elseif ( strpos( $new_url, '/' . HMW_Classes_Tools::$default['hmw_wp-json'] ) && isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			$response = $this->postRequest( $url );

			header( "HTTP/1.1 200 OK" );
			if ( !empty( $response['headers'] ) ) {
				foreach ( $response['headers'] as $header ) {
					header( $header );
				}
			}
			echo $response['body'];

			exit();

		} elseif ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			$response = $this->postRequest( $new_url );

			header( "HTTP/1.1 200 OK" );
			if ( !empty( $response['headers'] ) ) {
				foreach ( $response['headers'] as $header ) {
					header( $header );
				}
			}
			echo $response['body'];

			exit();

		} elseif ( $url <> $new_url ) {
			wp_safe_redirect( $new_url, 301 );
			exit();
		}
	}

	/**
	 * Do a Post request
	 * @param $url
	 * @return array
	 */
	public function postRequest( $url ) {
		$return = array();

		$headers = getallheaders();
		$options = array(
			'method' => 'POST',
			'headers' => $headers,
			'body' => $_POST,
			'timeout' => 60,
			'sslverify' => false,
		);


		$response = wp_remote_post( $url, $options );

		$return['body'] = wp_remote_retrieve_body( $response );
		foreach ( wp_remote_retrieve_headers( $response ) as $key => $value ) {
			if ( !is_array( $value ) ) {
				$return['headers'][] = "$key: $value";
			} else {
				foreach ( $value as $v )
					$return['headers'][] = "$key: $v";
			}
		}

		return $return;
	}

	/**
	 * Do a Get request
	 * @param $url
	 * @return array
	 */
	public function getRequest( $url ) {
		$return = array();

		$headers = getallheaders();
		$options = array(
			'method' => 'GET',
			'headers' => $headers,
			'timeout' => 60,
			'sslverify' => false,
		);


		$response = wp_remote_get( $url, $options );

		$return['body'] = wp_remote_retrieve_body( $response );
		foreach ( wp_remote_retrieve_headers( $response ) as $key => $value ) {
			if ( !is_array( $value ) ) {
				$return['headers'][] = "$key: $value";
			} else {
				foreach ( $value as $v )
					$return['headers'][] = "$key: $v";
			}
		}

		return $return;
	}

	/**
	 * Look into array of actions
	 *
	 * @param $haystack
	 * @param array $needles
	 * @param int $offset
	 *
	 * @return bool|mixed
	 */
    function strposa($haystack, $needles = array(), $offset = 0) {
        foreach ($needles as $needle) {
            if (strpos($haystack, $needle, $offset) !== false) {
                return $needle;
            }
        }
        return false;
    }

}
