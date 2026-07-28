<?php
/**
 * Rules Model
 * Called to handle the vulnerability Rules in the config file based on the server type
 *
 * @file  The Rules Model file
 * @package HMWP/RulesModel
 * @since 4.0.0
 */
defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Models_Rules {

	public $root_path;
	/**
	 * @var false|string Current Server Config File for rewrite rules
	 */
	public $config_file;
	public $config_chmod;
	/**
	 * @var array known logged in cookies & whitelist cookies
	 */
	public $whitelist_cookies = array();
	/**
	 * @var array known logged in ips & whitelist ips
	 */
	public $whitelist_ips = array();

	public function __construct() {
		$this->root_path = HMWP_Classes_Tools::getRootPath();

		if ( HMWP_Classes_Tools::isLocalFlywheel() && HMWP_Classes_Tools::isNginx() ) {

			//set the path to the config directory
			$root_config = realpath( dirname( $this->root_path, 2 ) . '/conf/nginx/includes' );

			//check if config directory exists
			if ( is_dir( $root_config ) ) {
				$this->config_file = str_replace( '\\', '/', $root_config ) . '/' . 'hidemywp.conf';
			}

		} elseif ( HMWP_Classes_Tools::isNginx() ) {
			$this->config_file = $this->root_path . 'hidemywp.conf';
		} elseif ( HMWP_Classes_Tools::isIIS() ) {
			$this->config_file = $this->root_path . 'web.config';
		} elseif ( HMWP_Classes_Tools::isApache() || HMWP_Classes_Tools::isLitespeed() ) {
			$this->config_file = $this->root_path . '.htaccess';
		} else {
			$this->config_file = false;
		}

		//Set known logged in cookies
		$this->whitelist_cookies[] = 'wordpress_logged_in_';
		$this->whitelist_cookies[] = HMWP_LOGGED_IN_COOKIE;
		//Let third party cookie check
		$this->whitelist_cookies = apply_filters( 'hmwp_rules_whitelisted_cookies' , $this->whitelist_cookies );
		$this->whitelist_cookies = array_unique( $this->whitelist_cookies );

		// Add the whitelist IPs in config for hidden path access
		if( $whitelist_ip = HMWP_Classes_Tools::getOption( 'whitelist_ip' ) ){
			if( !empty( $whitelist_ip ) ){
				if( $whitelist_ip = (array) json_decode( $whitelist_ip, true ) ){
					$this->whitelist_ips = array_merge( $this->whitelist_ips, $whitelist_ip );
				}
			}
		}

		// Let third party IP addresses
		$this->whitelist_ips = apply_filters( 'hmwp_rules_whitelisted_ips' , $this->whitelist_ips );

		// Filter / Sanitize IP addresses
		if( !empty($this->whitelist_ips) ){
			$this->whitelist_ips = array_filter(array_map( function ( $ip ){
				if ( !filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return false;
				}
				return trim($ip);
			}, $this->whitelist_ips ));
		}
		$this->whitelist_ips = array_unique( $this->whitelist_ips );

	}

	/**
	 * Get the config file
	 *
	 * @return mixed|void
	 */
	public function getConfFile() {
		return apply_filters( 'hmwp_config_file', $this->config_file );
	}

	/**
	 * Check if the config file is writable
	 *
	 * @param string $config_file
	 *
	 * @return bool
	 */
	public function isConfigWritable( $config_file = null ) {

		//Initialize WordPress Filesystem
		$wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

		//get the global config file if not specified
		if ( ! isset( $config_file ) ) {
			$config_file = $this->getConfFile();
		}

		if ( $config_file ) {
			//If the config file does not exist
			if ( ! $wp_filesystem->exists( $config_file ) ) {
				//can write into directory
				if ( ! $wp_filesystem->is_writable( dirname( $config_file ) ) ) {
					return false;
				}
				//can create the file
				if ( ! $wp_filesystem->touch( $config_file ) ) {
					return false;
				}

			} elseif ( ! $wp_filesystem->is_writable( $config_file ) ) { //is writable
				return false;
			}
		}

		return true;
	}


	/**
	 * Write to config file
	 *
	 * @param $rules
	 * @param string $header
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function writeToFile( $rules, $header = 'HMWP_RULES' ) {
		if ( $this->getConfFile() ) {
			if ( HMWP_Classes_Tools::isNginx() ) {
				return $this->writeInNginx( $rules, $header );
			} elseif ( HMWP_Classes_Tools::isIIS() && ! HMWP_Classes_Tools::getOption( 'logout' ) ) {
				return HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rewrite' )->flushRewrites();
			} elseif ( HMWP_Classes_Tools::isApache() || HMWP_Classes_Tools::isLitespeed() ) {
				return $this->writeInHtaccess( $rules, $header );
			}
		}

		return false;
	}

	/**
	 * Find the regex text in specific file
	 *
	 * @param string $find
	 * @param string $file
	 *
	 * @return bool
	 */
	public function find( $find, $file ) {
		$lines = file( $file );

		foreach ( (array) $lines as $line ) {
			if ( preg_match( "/$find/", $line ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Replace text in file
	 *
	 * @param $old
	 * @param $new
	 * @param $file
	 *
	 * @return bool
	 */
	public function findReplace( $old, $new, $file ) {
		$added = false;
		$lines = file( $file );

		//If the line is found
		if ( $new <> '' ) {
			if ( $this->find( $old, $file ) ) {
				$fd = fopen( $file, 'w' ); //phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
				foreach ( (array) $lines as $line ) {
					if ( ! preg_match( "/$old/", $line ) ) {
						fputs( $fd, $line ); //phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputs
					} else {
						//add the new line and replace the old line
						fputs( $fd, $new ); //phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputs
						$added = true;
					}
				}
				fclose( $fd ); //phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			} else {
				return $this->addLine( $new, $file );
			}
		}

		return $added;
	}

	/**
	 * Add the new line in file
	 *
	 * @param $new
	 * @param $file
	 *
	 * @return bool
	 */
	public function addLine( $new, $file ) {

		$added = false;
		$lines = file( $file );

		if ( $new <> '' ) {
			$fd = fopen( $file, 'w' ); //phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
			foreach ( (array) $lines as $line ) {
				fputs( $fd, $line ); //phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputs

				if ( preg_match( '/\$table_prefix/', $line ) ) {
					fputs( $fd, $new ); //phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputs
					$added = true;
				}
			}
			fclose( $fd ); //phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		}

		return $added;
	}

	/**
	 * Write the rules in the conf file
	 *
	 * @param $rules
	 * @param $header
	 *
	 * @return bool
	 */
	public function writeInNginx( $rules, $header = 'HMWP_RULES' ) {
		return $this->insertWithMarkers( $header, $rules );
	}

	/**
	 * Write the rules into htaccess file
	 *
	 * @param $rules
	 * @param $header
	 *
	 * @return bool
	 */
	public function writeInHtaccess( $rules, $header = 'HMWP_RULES' ) {
		if ( HMWP_Classes_Tools::isModeRewrite() ) {
			return $this->insertWithMarkers( $header, $rules );
		}

		return false;
	}

	/**
	 * Inserts an array of strings into a file (.htaccess ), placing it between
	 * BEGIN and END markers.
	 *
	 * Replaces existing marked info. Retains surrounding
	 * data. Creates file if none exists.
	 *
	 * @param string $marker The marker to alter.
	 * @param array|string $insertion The new content to insert.
	 *
	 * @return bool True on write success, false on failure.
	 */
	public function insertWithMarkers( $marker, $insertion ) {

		//Initialize WordPress Filesystem
		$wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

		if ( ! $this->isConfigWritable() ) {
			if ( ! $this->forceOpenConfigFile( $this->getConfFile() ) ) {
				return false;
			}
		}

		$start_marker = "# BEGIN $marker";
		$end_marker   = "# END $marker";

		if ( $insertion == '' ) { //delete the marker if there is no data to add in it

			if ( method_exists( $wp_filesystem, 'get_contents' ) && method_exists( $wp_filesystem, 'put_contents' ) ) {
				try {
					$htaccess = $wp_filesystem->get_contents( $this->getConfFile() );
					$htaccess = preg_replace( "/$start_marker.*$end_marker/s", "", $htaccess );
					$htaccess = preg_replace( "/\n+/", "\n", $htaccess );
					$wp_filesystem->put_contents( $this->getConfFile(), $htaccess );

					return true;
				} catch ( Exception $e ) {
				}
			}
		}

		if ( ! is_array( $insertion ) ) {
			$insertion = explode( "\n", $insertion );
			$insertion = array_filter( $insertion );
		}

		//open the file only if writable
		if ( $wp_filesystem->is_writable( $this->getConfFile() ) ) {

			$fp = fopen( $this->getConfFile(), 'r+' ); //phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
			if ( ! $fp ) {
				return false;
			}

			// Attempt to get a lock. If the filesystem supports locking, this will block until the lock is acquired.
			flock( $fp, LOCK_EX );

			$lines = array();
			while ( ! feof( $fp ) ) {
				$lines[] = rtrim( fgets( $fp ), "\r\n" ); //phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fgets
			}

			// Split out the existing file into the preceding lines, and those that appear after the marker
			$pre_lines    = $post_lines = $existing_lines = array();
			$found_marker = $found_end_marker = false;
			foreach ( $lines as $line ) {
				if ( ! $found_marker && false !== strpos( $line, $start_marker ) ) {
					$found_marker = true;
					continue;
				} elseif ( ! $found_end_marker && false !== strpos( $line, $end_marker ) ) {
					$found_end_marker = true;
					continue;
				}
				if ( ! $found_marker ) {
					$pre_lines[] = $line;
				} elseif ( $found_end_marker ) {
					$post_lines[] = $line;
				} else {
					$existing_lines[] = $line;
				}
			}

			// Check to see if there was a change
			if ( $existing_lines === $insertion ) {
				flock( $fp, LOCK_UN ); //phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_flock
				fclose( $fp ); //phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

				//Set the chmod back on file close
				$this->closeConfigFile( $this->getConfFile() );

				return true;
			}

			// Generate the new file data
			if ( ! $found_marker ) {
				$new_file_data = implode( "\n", array_merge( array( $start_marker ), $insertion, array( $end_marker ), $pre_lines ) );
			} else {
				$new_file_data = implode( "\n", array_merge( $pre_lines, array( $start_marker ), $insertion, array( $end_marker ), $post_lines ) );
			}

			// Write to the start of the file, and truncate it to that length
			fseek( $fp, 0 );
			$bytes = fwrite( $fp, $new_file_data ); //phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
			if ( $bytes ) {
				ftruncate( $fp, ftell( $fp ) );
			}
			fflush( $fp );
			flock( $fp, LOCK_UN );
			fclose( $fp ); //phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

			//Set the chmod back on file close
			$this->closeConfigFile( $this->getConfFile() );

			return (bool) $bytes;
		}

		return false;
	}

	/**
	 * Force opening the file
	 *
	 * @param $config_file
	 *
	 * @return bool
	 */
	public function forceOpenConfigFile( $config_file ) {
		$this->config_chmod = false;

		//Initialize WordPress Filesystem
		$wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

		if ( ! HMWP_Classes_Tools::isWindows() && $wp_filesystem->exists( $config_file ) ) {

			if ( method_exists( $wp_filesystem, 'getchmod' ) && method_exists( $wp_filesystem, 'chmod' ) ) {
				$this->config_chmod = $wp_filesystem->getchmod( $config_file );
				$wp_filesystem->chmod( $config_file, 0664 );

				if ( is_writeable( $config_file ) ) { //phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writeable
					if ( method_exists( $wp_filesystem, 'copy' ) ) {
						$wp_filesystem->copy( $config_file, $config_file . '_bk' );
					}

					return true;
				}
			}
		}

		return false;
	}

	/**
	 *  Set the chmod back on file close
	 *
	 * @param $config_file
	 */
	public function closeConfigFile( $config_file ) {

		//Initialize WordPress Filesystem
		$wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

		if ( $this->config_chmod && isset( $wp_filesystem ) ) {
			$wp_filesystem->chmod( $config_file, octdec( $this->config_chmod ) );

		}
	}

	/**
	 * Hide the Old Paths like /hmwp_wp-content_url, /hmwp_wp-includes_url
	 */
	public function getHideOldPathRewrite() {
		$rules = '';

		$home_root = '/';
		if ( HMWP_Classes_Tools::isMultisites() && defined( 'PATH_CURRENT_SITE' ) ) {
			$path = PATH_CURRENT_SITE;
		} else {
			$path = wp_parse_url( site_url(), PHP_URL_PATH );
		}

		if ( $path ) {
			$home_root = trailingslashit( $path );
		}

		$wp_content  = HMWP_Classes_Tools::getDefault( 'hmwp_wp-content_url' );
		$wp_includes = HMWP_Classes_Tools::getDefault( 'hmwp_wp-includes_url' );

		$extensions = array();
		$types      = (array) HMWP_Classes_Tools::getOption( 'hmwp_hide_oldpaths_types' );
		$files      = (array) HMWP_Classes_Tools::getOption( 'hmwp_hide_commonfiles_files' );

		if ( ! empty( $files ) && ( $key = array_search( HMWP_Classes_Tools::getDefault( 'hmwp_wp-comments-post' ), $files ) ) !== false ) {
			unset( $files[ $key ] );
		}

		//Do not hide upgrade.php file from logged users
		if ( ! empty( $files ) && ( $key = array_search( 'upgrade.php', $files ) ) !== false ) {
			unset( $files[ $key ] );
		}

		if ( in_array( 'css', $types ) ) {
			$extensions[] = '\.css';
			$extensions[] = '\.scss';
		}
		if ( in_array( 'js', $types ) ) {
			$extensions[] = '\.js';
		}
		if ( in_array( 'php', $types ) ) {
			$extensions[] = '\.php';
		}
		if ( in_array( 'html', $types ) ) {
			$extensions[] = '\.htm';
			$extensions[] = '\.html';
		}
		if ( in_array( 'txt', $types ) ) {
			$extensions[] = '\.rtf';
			$extensions[] = '\.rtx';
			$extensions[] = '\.txt';
		}
		if ( in_array( 'xml', $types ) ) {
			$extensions[] = '\.xsd';
			$extensions[] = '\.xml';
		}
		if ( in_array( 'json', $types ) ) {
			$extensions[] = '\.json';
		}
		if ( in_array( 'lock', $types ) ) {
			$extensions[] = '\.lock';
		}
		if ( in_array( 'image', $types ) ) {
			$extensions[] = '\.jpg';
			$extensions[] = '\.jpeg';
			$extensions[] = '\.tiff';
			$extensions[] = '\.gif';
			$extensions[] = '\.bmp';
			$extensions[] = '\.png';
			$extensions[] = '\.svg';
			$extensions[] = '\.webp';
		}

		//Hook the list of the extensions to block on old paths
		$extensions = apply_filters( 'hmwp_common_paths_extensions', $extensions );

		if ( HMWP_Classes_Tools::isNginx() ) {

			if ( HMWP_Classes_Tools::getOption( 'hmwp_hide_oldpaths' ) ) {

                //redirect media from the old paths to the new paths
                if ( in_array( 'media', $types ) ) {
                    if ( HMWP_Classes_Tools::getDefault( 'hmwp_upload_url' ) <> HMWP_Classes_Tools::getOption( 'hmwp_upload_url' ) ) {
                        $rules .= 'set $cond "";' . PHP_EOL;
	                    if ( !empty($this->whitelist_ips) ){
		                    foreach ($this->whitelist_ips as $ip){
			                    $rules .= 'if ($remote_addr = "' . $ip . '" ) {  set $cond "whitelist"; }' . PHP_EOL;
		                    }
	                    }
                        $rules .= 'if ($http_cookie !~* "' . join( '|', $this->whitelist_cookies ) . '") {  set $cond "${cond}cookie"; }' . PHP_EOL;
                        $rules .= 'if ($request_uri ~* ^' . $home_root . $wp_content . "/" . HMWP_Classes_Tools::getDefault( 'hmwp_upload_url' ) . '/[^\.]+.[^\.]+) {  set $cond "${cond}+redirect_uri"; }' . PHP_EOL;
                        $rules .= 'if ($cond = "cookie+redirect_uri") { rewrite ^' . $home_root . $wp_content . "/" . HMWP_Classes_Tools::getDefault( 'hmwp_upload_url' ) . "/(.*)$ " . $home_root . HMWP_Classes_Tools::getOption( 'hmwp_upload_url' ).'/$1 redirect; }' . PHP_EOL;
                    }
                }

				if ( HMWP_Classes_Tools::getDefault( 'hmwp_wp-content_url' ) <> HMWP_Classes_Tools::getOption( 'hmwp_wp-content_url' ) ) {
					$rules .= 'set $cond "";' . PHP_EOL;
					if ( !empty($this->whitelist_ips) ){
						foreach ($this->whitelist_ips as $ip){
							$rules .= 'if ($remote_addr = "' . $ip . '" ) {  set $cond "whitelist"; }' . PHP_EOL;
						}
					}
					$rules .= 'if ($http_cookie !~* "' . join( '|', $this->whitelist_cookies ) . '") {  set $cond "${cond}cookie"; }' . PHP_EOL;
					$rules .= 'if ($request_uri ~* ^' . $home_root . $wp_content . '/?$) { set $cond "${cond}+deny_uri"; }' . PHP_EOL;
					$rules .= 'if ($request_uri ~* ^' . $home_root . $wp_content . '/[^\.]+/?$) { set $cond "${cond}+deny_uri"; }' . PHP_EOL;
					if ( HMWP_Classes_Tools::getDefault( 'hmwp_wp-includes_url' ) <> HMWP_Classes_Tools::getOption( 'hmwp_wp-includes_url' ) ) {
						$rules .= 'if ($request_uri ~* ^' . $home_root . $wp_includes . '/?$) { set $cond "${cond}+deny_uri"; }' . PHP_EOL;
					}
					if ( ! empty( $extensions ) ) {
						if ( HMWP_Classes_Tools::getDefault( 'hmwp_plugin_url' ) <> HMWP_Classes_Tools::getOption( 'hmwp_plugin_url' ) ) {
							$rules .= 'if ($request_uri ~* ^' . $home_root . HMWP_Classes_Tools::getDefault( 'hmwp_plugin_url' ) . '/[^\.]+(' . join( '|', $extensions ) . ')) { set $cond "${cond}+deny_uri"; }' . PHP_EOL;
						}
						if ( HMWP_Classes_Tools::getDefault( 'hmwp_themes_url' ) <> HMWP_Classes_Tools::getOption( 'hmwp_themes_url' ) ) {
							$rules .= 'if ($request_uri ~* ^' . $home_root . $wp_content . '/' . HMWP_Classes_Tools::getDefault( 'hmwp_themes_url' ) . '/[^\.]+(' . join( '|', $extensions ) . ')) { set $cond "${cond}+deny_uri"; }' . PHP_EOL;
						}
						if ( HMWP_Classes_Tools::getDefault( 'hmwp_upload_url' ) <> HMWP_Classes_Tools::getOption( 'hmwp_upload_url' ) ) {
							$rules .= 'if ($request_uri ~* ^' . $home_root . $wp_content . '/' . HMWP_Classes_Tools::getDefault( 'hmwp_upload_url' ) . '/[^\.]+(' . join( '|', $extensions ) . ')) { set $cond "${cond}+deny_uri"; }' . PHP_EOL;
						}
						if ( HMWP_Classes_Tools::getDefault( 'hmwp_wp-includes_url' ) <> HMWP_Classes_Tools::getOption( 'hmwp_wp-includes_url' ) ) {
							$rules .= 'if ($request_uri ~* ^' . $home_root . $wp_includes . '/[^\.]+(' . join( '|', $extensions ) . ')) { set $cond "${cond}+deny_uri"; }' . PHP_EOL;
						}
					}

					//hide the installation and upgrade files for not loggedin users
					$rules .= 'if ($request_uri ~* ^' . $home_root . 'wp-admin/(install.php|upgrade.php)) { set $cond "${cond}+deny_uri"; }' . PHP_EOL;
					$rules .= 'if ($cond = "cookie+deny_uri") {  return 404; } ' . PHP_EOL;
				}
			}
			if ( HMWP_Classes_Tools::getOption( 'hmwp_hide_commonfiles' ) && ! empty( $files ) ) {
				$rules .= 'location ~ ^/(' . join( '|', $files ) . ') { deny all; }' . PHP_EOL;
			}

		} elseif ( HMWP_Classes_Tools::isApache() || HMWP_Classes_Tools::isLitespeed() ) {


            if ( in_array( 'media', $types ) ) {
                if ( HMWP_Classes_Tools::getDefault( 'hmwp_upload_url' ) <> HMWP_Classes_Tools::getOption( 'hmwp_upload_url' ) ) {
                    $rules .= "<IfModule mod_rewrite.c>" . PHP_EOL;
                    $rules .= "RewriteEngine On" . PHP_EOL;
                    $rules .= "RewriteBase $home_root" . PHP_EOL;
					if ( !empty($this->whitelist_ips) ){
						foreach ($this->whitelist_ips as $ip){
							$rules .= "RewriteCond %{REMOTE_ADDR} !^$ip$" . PHP_EOL;
						}
					}
                    $rules .= "RewriteCond %{HTTP:Cookie} !(" . join( '|', $this->whitelist_cookies ) . ") [NC]" . PHP_EOL;
                    $rules .= "RewriteCond %{THE_REQUEST} " . $home_root . $wp_content . "/" . HMWP_Classes_Tools::getDefault( 'hmwp_upload_url' ) . "/[^\.]+.[^\.]+ [NC]" . PHP_EOL;
                    $rules .= "RewriteRule ^([_0-9a-zA-Z-]+/)?" . $wp_content  . "/" . HMWP_Classes_Tools::getDefault( 'hmwp_upload_url' ) . "/(.*)$ " . $home_root . HMWP_Classes_Tools::getOption( 'hmwp_upload_url' ) . "/$2  [L,R=301]" . PHP_EOL;
                    $rules .= "</IfModule>" . PHP_EOL . PHP_EOL;
                }
            }

			if ( HMWP_Classes_Tools::getOption( 'hmwp_sqlinjection_location' ) == 'file' && HMWP_Classes_Tools::getOption( 'hmwp_sqlinjection' ) && (int) HMWP_Classes_Tools::getOption( 'hmwp_sqlinjection_level' ) > 0 ) {
				$rules .= "<IfModule mod_rewrite.c>" . PHP_EOL;
				$rules .= "RewriteEngine On" . PHP_EOL;
				$rules .= "RewriteBase $home_root" . PHP_EOL;

				// Let the whitelisted IPs bypass the firewall rules (added without [OR] so they are AND-ed before the rule group)
				if ( !empty($this->whitelist_ips) ){
					foreach ($this->whitelist_ips as $ip){
						$rules .= "RewriteCond %{REMOTE_ADDR} !^$ip$" . PHP_EOL;
					}
				}

				// Prevent -f checks on index.php.
				if ( (int) HMWP_Classes_Tools::getOption( 'hmwp_sqlinjection_level' ) == 1 ) {
					$rules .= "RewriteCond %{THE_REQUEST} etc/passwd [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (\\<|%3C).*object.*(\\>|%3E) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (<|%3C)([^o]*o)+bject.*(>|%3E) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (\\<|%3C).*iframe.*(\\>|%3E) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (<|%3C)([^i]*i)+frame.*(>|%3E) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} base64_encode.*\\(.*\\) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} base64_(en|de)code[^(]*\\([^)]*\\) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (localhost|loopback|127\\.0\\.0\\.1) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} concat[^\\(]*\\( [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} union([^s]*s)+elect [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} union([^a]*a)+ll([^s]*s)+elect [NC,OR]" . PHP_EOL;

					$rules .= "RewriteCond %{QUERY_STRING} (sp_executesql) [NC]" . PHP_EOL;


				}
				if ( (int) HMWP_Classes_Tools::getOption( 'hmwp_sqlinjection_level' ) == 2 ) {
					$rules .= "RewriteCond %{HTTP_USER_AGENT} (%0A|%0D|%3C|%3E|%00) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{HTTP_USER_AGENT} (;|<|>|'|\\\"|\\)|\\(|%0A|%0D|%22|%28|%3C|%3E|%00).*(libwww-perl|wget|python|nikto|curl|scan|java|winhttp|HTTrack|clshttp|archiver|loader|email|harvest|extract|grab|miner) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{THE_REQUEST} (\\*|%2a)+(%20+|\\s+|%20+\\s+|\\s+%20+|\\s+%20+\\s+)HTTP(:/|/) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{THE_REQUEST} etc/passwd [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{THE_REQUEST} (%0A|%0D|\\r|\\n) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} owssvr\\.dll [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{HTTP_REFERER} (%0A|%0D|%3C|%3E|%00) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{HTTP_REFERER} \\.opendirviewer\\. [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{HTTP_REFERER} users\\.skynet\\.be.* [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} [a-zA-Z0-9_]=http:// [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} [a-zA-Z0-9_]=(\\.\\.//?)+ [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} [a-zA-Z0-9_]=/([a-z0-9_.]//?)+ [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} \\=PHP[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12} [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (\\.\\./|%2e%2e%2f|%2e%2e/|\\.\\.%2f|%2e\\.%2f|%2e\\./|\\.%2e%2f|\\.%2e/) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} ftp\\: [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} \\=\\|w\\| [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} ^(.*)/self/(.*)$ [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} ^(.*)cPath=http://(.*)$ [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} base64_encode.*\\(.*\\) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} base64_(en|de)code[^(]*\\([^)]*\\) [NC,OR]" . PHP_EOL;

					if ( ! HMWP_Classes_Tools::isPluginActive( 'backup-guard-gold/backup-guard-pro.php' ) && ! HMWP_Classes_Tools::isPluginActive( 'wp-reset/wp-reset.php' ) && ! HMWP_Classes_Tools::isPluginActive( 'wp-statistics/wp-statistics.php' ) ) {
						$rules .= "RewriteCond %{QUERY_STRING} (\\<|%3C).*script.*(\\>|%3E) [NC,OR]" . PHP_EOL;
						$rules .= "RewriteCond %{QUERY_STRING} (<|%3C)([^s]*s)+cript.*(>|%3E) [NC,OR]" . PHP_EOL;
						$rules .= "RewriteCond %{QUERY_STRING} (\\<|%3C).*embed.*(\\>|%3E) [NC,OR]" . PHP_EOL;
						$rules .= "RewriteCond %{QUERY_STRING} (<|%3C)([^e]*e)+mbed.*(>|%3E) [NC,OR]" . PHP_EOL;
						$rules .= "RewriteCond %{QUERY_STRING} (\\<|%3C).*object.*(\\>|%3E) [NC,OR]" . PHP_EOL;
						$rules .= "RewriteCond %{QUERY_STRING} (<|%3C)([^o]*o)+bject.*(>|%3E) [NC,OR]" . PHP_EOL;
						$rules .= "RewriteCond %{QUERY_STRING} (\\<|%3C).*iframe.*(\\>|%3E) [NC,OR]" . PHP_EOL;
						$rules .= "RewriteCond %{QUERY_STRING} (<|%3C)([^i]*i)+frame.*(>|%3E) [NC,OR]" . PHP_EOL;
						$rules .= "RewriteCond %{QUERY_STRING} ^.*(\\(|\\)|<|>|%3c|%3e).* [NC,OR]" . PHP_EOL;
						$rules .= "RewriteCond %{QUERY_STRING} (<|>|'|%0A|%0D|%3C|%3E|%00) [NC,OR]" . PHP_EOL;
						$rules .= "RewriteCond %{QUERY_STRING} (;|<|>|'|\"|\\)|%0A|%0D|%22|%27|%3C|%3E|%00).*(/\\*|union|select|insert|drop|delete|cast|create|char|convert|alter|declare|script|set|md5|benchmark|encode) [NC,OR]" . PHP_EOL;
					}

					$rules .= "RewriteCond %{QUERY_STRING} ^.*(\\x00|\\x04|\\x08|\\x0d|\\x1b|\\x20|\\x3c|\\x3e|\\x7f).* [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (NULL|OUTFILE|LOAD_FILE) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (\\.{1,}/)+(motd|etc|bin) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (localhost|loopback|127\\.0\\.0\\.1) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} concat[^\\(]*\\( [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} union([^s]*s)+elect [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} union([^a]*a)+ll([^s]*s)+elect [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} \\-[sdcr].*(allow_url_include|allow_url_fopen|safe_mode|disable_functions|auto_prepend_file) [NC,OR]" . PHP_EOL;

					$rules .= "RewriteCond %{QUERY_STRING} (sp_executesql) [NC]" . PHP_EOL;

				}
				if ( (int) HMWP_Classes_Tools::getOption( 'hmwp_sqlinjection_level' ) == 3 ) {
					$rules .= "RewriteCond %{HTTP_USER_AGENT} ([a-z0-9]{2000,}) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{HTTP_USER_AGENT} (&lt;|%0a|%0d|%27|%3c|%3e|%00|0x00) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{HTTP_USER_AGENT} ((c99|php|web)shell|remoteview|site((.){0,2})copier) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{HTTP_USER_AGENT} (econtext|eolasbot|eventures|liebaofast|nominet|oppo\sa33) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{HTTP_USER_AGENT} (base64_decode|bin/bash|disconnect|eval|lwp-download|unserialize) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{HTTP_USER_AGENT} (acapbot|acoonbot|asterias|attackbot|backdorbot|becomebot|binlar|blackwidow|blekkobot|blexbot|blowfish|bullseye|bunnys|butterfly|careerbot|casper|checkpriv|cheesebot|cherrypick|chinaclaw|choppy|clshttp|cmsworld|copernic|copyrightcheck|cosmos|crescent|cy_cho|datacha|demon|diavol|discobot|dittospyder|dotnetdotcom|dumbot|emailcollector|emailsiphon|emailwolf|extract|eyenetie|feedfinder|flaming|flashget|flicky|foobot|g00g1e|getright|go-ahead-got|gozilla|grabnet|grafula|harvest|httrack|icarus6j|jetbot|jetcar|kmccrew|leechftp|libweb|linkextractor|linkscan|linkwalker|loader|masscan|miner|mechanize|morfeus|moveoverbot|netmechanic|netspider|nicerspro|nikto|ninja|octopus|pagegrabber|planetwork|postrank|proximic|purebot|pycurl|python|queryn|queryseeker|radian6|radiation|realdownload|seekerspider|semalt|siclab|sindice|sitebot|sitesnagger|skygrid|smartdownload|snoopy|sosospider|spankbot|spbot|sqlmap|stackrambler|stripper|sucker|surftbot|sux0r|suzukacz|suzuran|takeout|teleport|telesoft|true_robots|turingos|turnit|vampire|vikspider|voideye|webleacher|webreaper|webstripper|webvac|webviewer|webwhacker|winhttp|wwwoffle|woxbot|xaldon|xxxyy|youda|zeus|zmeu|zune|zyborg) [NC,OR]" . PHP_EOL;

					$rules .= "RewriteCond %{QUERY_STRING} ([a-z0-9]{2000,}) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (/|%2f)(:|%3a)(/|%2f) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (order(\s|%20)by(\s|%20)1--) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (/|%2f)(\*|%2a)(\*|%2a)(/|%2f) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (ckfinder|fckeditor|fullclick) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} ((.*)header:|(.*)set-cookie:(.*)=) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (cmd|command)(=|%3d)(chdir|mkdir)(.*)(x20) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (globals|mosconfig([a-z_]{1,22})|request)(=|\[) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (/|%2f)((wp-)?config)((\.|%2e)inc)?((\.|%2e)php) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (thumbs?(_editor|open)?|tim(thumbs?)?)((\.|%2e)php) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (absolute_|base|root_)(dir|path)(=|%3d)(ftp|https?) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (localhost|loopback|127(\.|%2e)0(\.|%2e)0(\.|%2e)1) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (s)?(ftp|inurl|php)(s)?(:(/|%2f|%u2215)(/|%2f|%u2215)) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (\.|20)(get|the)(_|%5f)(permalink|posts_page_url)(\(|%28) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} ((boot|win)((\.|%2e)ini)|etc(/|%2f)passwd|self(/|%2f)environ) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (((/|%2f){3,3})((\.|%2e){3,3})|((\.|%2e){2,2})(/|%2f|%u2215)) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (benchmark|char|exec|fopen|function|html)(.*)(\(|%28)(.*)(\)|%29) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (php)([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (e|%65|%45)(v|%76|%56)(a|%61|%31)(l|%6c|%4c)(.*)(\(|%28)(.*)(\)|%29) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (/|%2f)(=|%3d|$&|_mm|inurl(:|%3a)(/|%2f)|(mod|path)(=|%3d)(\.|%2e)) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (\+|%2b|%20)(d|%64|%44)(e|%65|%45)(l|%6c|%4c)(e|%65|%45)(t|%74|%54)(e|%65|%45)(\+|%2b|%20) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (\+|%2b|%20)(i|%69|%49)(n|%6e|%4e)(s|%73|%53)(e|%65|%45)(r|%72|%52)(t|%74|%54)(\+|%2b|%20) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (\+|%2b|%20)(s|%73|%53)(e|%65|%45)(l|%6c|%4c)(e|%65|%45)(c|%63|%43)(t|%74|%54)(\+|%2b|%20) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (\+|%2b|%20)(u|%75|%55)(p|%70|%50)(d|%64|%44)(a|%61|%41)(t|%74|%54)(e|%65|%45)(\+|%2b|%20) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (g|%67|%47)(l|%6c|%4c)(o|%6f|%4f)(b|%62|%42)(a|%61|%41)(l|%6c|%4c)(s|%73|%53)(=|\[|%[0-9A-Z]{0,2}) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (_|%5f)(r|%72|%52)(e|%65|%45)(q|%71|%51)(u|%75|%55)(e|%65|%45)(s|%73|%53)(t|%74|%54)(=|\[|%[0-9A-Z]{2,}) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (j|%6a|%4a)(a|%61|%41)(v|%76|%56)(a|%61|%31)(s|%73|%53)(c|%63|%43)(r|%72|%52)(i|%69|%49)(p|%70|%50)(t|%74|%54)(:|%3a)(.*)(;|%3b|\)|%29) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (b|%62|%42)(a|%61|%41)(s|%73|%53)(e|%65|%45)(6|%36)(4|%34)(_|%5f)(e|%65|%45|d|%64|%44)(e|%65|%45|n|%6e|%4e)(c|%63|%43)(o|%6f|%4f)(d|%64|%44)(e|%65|%45)(.*)(\()(.*)(\)) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (@copy|\\\$_(files|get|post)|allow_url_(fopen|include)|auto_prepend_file|blexbot|browsersploit|(c99|php)shell|curl(_exec|test)|disable_functions?|document_root|elastix|encodeuricom|exploit|fclose|fgets|file_put_contents|fputs|fsbuff|fsockopen|gethostbyname|grablogin|hmei7|input_file|null|open_basedir|outfile|passthru|phpinfo|popen|proc_open|quickbrute|remoteview|root_path|safe_mode|shell_exec|site((.){0,2})copier|sux0r|trojan|user_func_array|wget|xertive) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} ((\+|%2b)(concat|delete|get|select|union)(\+|%2b)) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (union)(.*)(select)(.*)(\(|%28) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (concat|eval)(.*)(\(|%28) [NC,OR]" . PHP_EOL;

					if ( ! HMWP_Classes_Tools::isPluginActive( 'wp-reset/wp-reset.php' ) && ! HMWP_Classes_Tools::isPluginActive( 'wp-statistics/wp-statistics.php' ) ) {
						$rules .= "RewriteCond %{QUERY_STRING} (<|%3c)(.*)(e|%65|%45)(m|%6d|%4d)(b|%62|%42)(e|%65|%45)(d|%64|%44)(.*)(>|%3e) [NC,OR]" . PHP_EOL;
						$rules .= "RewriteCond %{QUERY_STRING} (<|%3c)(.*)(i|%69|%49)(f|%66|%46)(r|%72|%52)(a|%61|%41)(m|%6d|%4d)(e|%65|%45)(.*)(>|%3e) [NC,OR]" . PHP_EOL;
						$rules .= "RewriteCond %{QUERY_STRING} (<|%3c)(.*)(o|%4f|%6f)(b|%62|%42)(j|%4a|%6a)(e|%65|%45)(c|%63|%43)(t|%74|%54)(.*)(>|%3e) [NC,OR]" . PHP_EOL;
						$rules .= "RewriteCond %{QUERY_STRING} (<|%3c)(.*)(s|%73|%53)(c|%63|%43)(r|%72|%52)(i|%69|%49)(p|%70|%50)(t|%74|%54)(.*)(>|%3e) [NC,OR]" . PHP_EOL;
						$rules .= "RewriteCond %{QUERY_STRING} (;|<|>|\'|\\\"|\)|%0a|%0d|%22|%27|%3c|%3e|%00)(.*)(/\*|alter|base64|benchmark|cast|concat|convert|create|encode|declare|delete|drop|insert|md5|request|script|select|union|update) [NC,OR]" . PHP_EOL;
					}

					$rules .= "RewriteCond %{REQUEST_URI} (\^|`|<|>|\\\|\|) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} ([a-z0-9]{2000,}) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (=?\\\(\'|%27)/?)(\.) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (/)(\*|\\\"|\'|\.|,|&|&amp;?)/?$ [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (\.)(php)(\()?([0-9]+)(\))?(/)?$ [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (/)(vbulletin|boards|vbforum)(/)? [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} /((.*)header:|(.*)set-cookie:(.*)=) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (/)(ckfinder|fckeditor|fullclick) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (\.(s?ftp-?)config|(s?ftp-?)config\.) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (\{0\}|\\\"?0\\\"?=\\\"?0|\(/\(|\+\+\+|\\\\\\\") [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (thumbs?(_editor|open)?|tim(thumbs?)?)(\.php) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (\.|20)(get|the)(_)(permalink|posts_page_url)(\() [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (///|\?\?|/&&|/\*(.*)\*/|/:/|\\\\\\\\|0x00|%00|%0d%0a) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (/%7e)(root|ftp|bin|nobody|named|guest|logs|sshd)(/) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (/)(etc|var)(/)(hidden|secret|shadow|ninja|passwd|tmp)(/)?$ [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (s)?(ftp|inurl|php)(s)?(:(/|%2f|%u2215)(/|%2f|%u2215)) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (/)(=|\\\$&?|&?(pws|rk)=0|_mm|_vti_|(=|/|;|,)nt\.) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (\.)(ds_store|htaccess|htpasswd|init?|mysql-select-db)(/)?$ [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (/)(bin)(/)(cc|chmod|chsh|cpp|echo|id|kill|mail|nasm|perl|ping|ps|python|tclsh)(/)?$ [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (/)(::[0-9999]|%3a%3a[0-9999]|127\.0\.0\.1|localhost|loopback|makefile|pingserver|wwwroot)(/)? [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (\(null\)|\{\\\$itemURL\}|cAsT\(0x|echo(.*)kae|etc/passwd|eval\(|self/environ|\+union\+all\+select) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (/)?j((\s)+)?a((\s)+)?v((\s)+)?a((\s)+)?s((\s)+)?c((\s)+)?r((\s)+)?i((\s)+)?p((\s)+)?t((\s)+)?(%3a|:) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (/)(awstats|(c99|php|web)shell|document_root|error_log|listinfo|muieblack|remoteview|site((.){0,2})copier|sqlpatch|sux0r) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (/)((php|web)?shell|crossdomain|fileditor|locus7|nstview|php(get|remoteview|writer)|r57|remview|sshphp|storm7|webadmin)(.*)(\.|\() [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (/)(author-panel|bitrix|(db|mysql)-?admin|filemanager|htdocs|httpdocs|https?|mailman|mailto|msoffice|mysql|_?php-my-admin(.*)|tmp|undefined|vhosts|webmaster|www)(/) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (base64_(en|de)code|benchmark|child_terminate|curl_exec|e?chr|eval|function|fwrite|(f|p)open|html|leak|passthru|p?fsockopen|phpinfo|posix_(kill|mkfifo|setpgid|setsid|setuid)|proc_(close|get_status|nice|open|terminate)|(shell_)?exec|system)(.*)(\()(.*)(\)) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (/)(^$|00.temp00|0day|3index|3xp|70bex?|admin_events|bkht|(php|web)?shell|c99|config(\.)?bak|curltest|db|dompdf|filenetworks|hmei7|index\.php/index\.php/index|jahat|kcrew|keywordspy|libsoft|marg|mobiquo|mysql|php-?info|racrew|sql|vuln|(web-?|wp-)?(conf\b|config(uration)?)|xertive)(\.php) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (\.)(7z|ab4|ace|afm|ashx|aspx?|bash|ba?k?|bin|bz2|cfg|cfml?|conf\b|config|ctl|dat|db|dist|dll|eml|engine|env|et2|exe|fec|fla|hg|inc|ini|inv|jsp|log|lqd|make|mbf|mdb|mmw|mny|module|old|one|orig|out|passwd|pdb|phtml|pl|psd|pst|ptdb|pwd|py|qbb|qdf|rar|rdf|save|sdb|sql|sh|soa|svn|swf|swl|swo|swp|stx|tar|tax|tgz|theme|tls|tmd|wow|xtmpl|zlib)$ [NC,OR]" . PHP_EOL;

					$rules .= "RewriteCond %{REMOTE_HOST} (163data|colocrossing|crimea|g00g1e|justhost|kanagawa|loopia|masterhost|onlinehome|poneytel|sprintdatacenter|reverse.softlayer|safenet|ttnet|woodpecker|wowrack) [NC,OR]" . PHP_EOL;

					$rules .= "RewriteCond %{HTTP_REFERER} (semalt.com|todaperfeita) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{HTTP_REFERER} (order(\s|%20)by(\s|%20)1--) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{HTTP_REFERER} (blue\spill|cocaine|ejaculat|erectile|erections|hoodia|huronriveracres|impotence|levitra|libido|lipitor|phentermin|pro[sz]ac|sandyauer|tramadol|troyhamby|ultram|unicauca|valium|viagra|vicodin|xanax|ypxaieo) [NC,OR]" . PHP_EOL;

					$rules .= "RewriteCond %{REQUEST_METHOD} ^(connect|debug|move|trace|track) [NC]" . PHP_EOL;

				}
				if ( (int) HMWP_Classes_Tools::getOption( 'hmwp_sqlinjection_level' ) == 4 ) {
					$rules .= "RewriteCond %{HTTP_USER_AGENT} ([a-z0-9]{2000,}) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{HTTP_USER_AGENT} (&lt;|%0a|%0d|%27|%3c|%3e|%00|0x00) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{HTTP_USER_AGENT} (oppo\sa33|(c99|php|web)shell|site((.){0,2})copier) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{HTTP_USER_AGENT} (base64_decode|bin/bash|disconnect|eval|unserializ) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{HTTP_USER_AGENT} (acapbot|acoonbot|alexibot|asterias|attackbot|awario|backdor|becomebot|binlar|blackwidow|blekkobot|blex|blowfish|bullseye|bunnys|butterfly|careerbot|casper) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{HTTP_USER_AGENT} (checkpriv|cheesebot|cherrypick|chinaclaw|choppy|clshttp|cmsworld|copernic|copyrightcheck|cosmos|crescent|datacha|diavol|discobot|dittospyder) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{HTTP_USER_AGENT} (dotnetdotcom|dumbot|econtext|emailcollector|emailsiphon|emailwolf|eolasbot|eventures|extract|eyenetie|feedfinder|flaming|flashget|flicky|foobot|fuck) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{HTTP_USER_AGENT} (g00g1e|getright|go-ahead-got|gozilla|grabnet|grafula|harvest|httracks?|icarus6j|jetbot|jetcar|kmccrew|leechftp|libweb|liebaofast) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{HTTP_USER_AGENT} (linkscan|linkwalker|loader|lwp-download|masscan|miner|mechanize|morfeus|moveoverbot|netmechanic|netspider|nicerspro|nikto|ninja|nominet) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{HTTP_USER_AGENT} (pagegrabber|planetwork|postrank|proximic|purebot|queryn|queryseeker|radian6|radiation|realdownload|remoteview|scan|seekerspid) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{HTTP_USER_AGENT} (semalt|siclab|sindice|sitebot|sitesnagger|skygrid|smartdownload|snoopy|sosospider|spankbot|spbot|sqlmap|stackrambler|stripper|sucker|surftbot) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{HTTP_USER_AGENT} (sux0r|suzukacz|suzuran|takeout|teleport|telesoft|true_robots|turingos|turnit|vampire|vikspider|voideye|webleacher|webreaper|webstripper|webvac|webviewer|webwhacker) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{HTTP_USER_AGENT} (winhttp|wwwoffle|woxbot|xaldon|xxxyy|youda|zeus|zmeu|zune|zyborg) [NC,OR]" . PHP_EOL;

					$rules .= "RewriteCond %{QUERY_STRING} ([a-z0-9]{4000,}) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (/|%2f)(:|%3a)(/|%2f) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (etc/(hosts|motd|shadow)) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (order(\s|%20)by(\s|%20)1--) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (/|%2f)(\*|%2a)(\*|%2a)(/|%2f) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (`|<|>|\^|\|\\\\|0x00|%00|%0d%0a) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (f?ckfinder|f?ckeditor|fullclick) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} ((.*)header:|(.*)set-cookie:(.*)=) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (localhost|127(\.|%2e)0(\.|%2e)0(\.|%2e)1) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (cmd|command)(=|%3d)(chdir|mkdir)(.*)(x20) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (globals|mosconfig([a-z_]{1,22})|request)(=|\[) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (/|%2f)((wp-)?config)((\.|%2e)inc)?((\.|%2e)php) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (thumbs?(_editor|open)?|tim(thumbs?)?)((\.|%2e)php) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (absolute_|base|root_)(dir|path)(=|%3d)(ftp|https?) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (s)?(ftp|inurl|php)(s)?(:(/|%2f|%u2215)(/|%2f|%u2215)) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (\.|20)(get|the)(_|%5f)(permalink|posts_page_url)(\(|%28) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} ((boot|win)((\.|%2e)ini)|etc(/|%2f)passwd|self(/|%2f)environ) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (((/|%2f){3,3})((\.|%2e){3,3})|((\.|%2e){2,2})(/|%2f|%u2215)) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (benchmark|char|exec|fopen|function|html)(.*)(\(|%28)(.*)(\)|%29) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (php)([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (e|%65|%45)(v|%76|%56)(a|%61|%31)(l|%6c|%4c)(.*)(\(|%28)(.*)(\)|%29) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (/|%2f)(=|%3d|$&|_mm|inurl(:|%3a)(/|%2f)|(mod|path)(=|%3d)(\.|%2e)) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (\+|%2b|%20)(d|%64|%44)(e|%65|%45)(l|%6c|%4c)(e|%65|%45)(t|%74|%54)(e|%65|%45)(\+|%2b|%20) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (\+|%2b|%20)(i|%69|%49)(n|%6e|%4e)(s|%73|%53)(e|%65|%45)(r|%72|%52)(t|%74|%54)(\+|%2b|%20) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (\+|%2b|%20)(s|%73|%53)(e|%65|%45)(l|%6c|%4c)(e|%65|%45)(c|%63|%43)(t|%74|%54)(\+|%2b|%20) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (\+|%2b|%20)(u|%75|%55)(p|%70|%50)(d|%64|%44)(a|%61|%41)(t|%74|%54)(e|%65|%45)(\+|%2b|%20) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (\\\\x00|(\\\"|%22|\'|%27)?0(\\\"|%22|\'|%27)?(=|%3d)(\\\"|%22|\'|%27)?0|cast(\(|%28)0x|or%201(=|%3d)1) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (g|%67|%47)(l|%6c|%4c)(o|%6f|%4f)(b|%62|%42)(a|%61|%41)(l|%6c|%4c)(s|%73|%53)(=|\[|%[0-9A-Z]{0,2}) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (_|%5f)(r|%72|%52)(e|%65|%45)(q|%71|%51)(u|%75|%55)(e|%65|%45)(s|%73|%53)(t|%74|%54)(=|\[|%[0-9A-Z]{2,}) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (b|%62|%42)(a|%61|%41)(s|%73|%53)(e|%65|%45)(6|%36)(4|%34)(_|%5f)(e|%65|%45|d|%64|%44)(e|%65|%45|n|%6e|%4e)(c|%63|%43)(o|%6f|%4f)(d|%64|%44)(e|%65|%45)(.*)(\()(.*)(\)) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (@copy|\\\$_(files|get|post)|allow_url_(fopen|include)|auto_prepend_file|blexbot|browsersploit|call_user_func_array|(php|web)shell|curl(_exec|test)|disable_functions?|document_root) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (elastix|encodeuricom|exploit|fclose|fgets|file_put_contents|fputs|fsbuff|fsockopen|gethostbyname|grablogin|hmei7|hubs_post-cta|input_file|invokefunction|(\b)load_file|open_basedir|outfile|p3dlite) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (pass(=|%3d)shell|passthru|phpinfo|phpshells|popen|proc_open|quickbrute|remoteview|root_path|safe_mode|shell_exec|site((.){0,2})copier|sp_executesql|sux0r|trojan|udtudt|user_func_array|wget|wp_insert_user|xertive) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} ((\+|%2b)(concat|delete|get|select|union)(\+|%2b)) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (union)(.*)(select)(.*)(\(|%28) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{QUERY_STRING} (concat|eval)(.*)(\(|%28) [NC,OR]" . PHP_EOL;

					if ( ! HMWP_Classes_Tools::isPluginActive( 'wp-reset/wp-reset.php' ) && ! HMWP_Classes_Tools::isPluginActive( 'wp-statistics/wp-statistics.php' ) ) {
						$rules .= "RewriteCond %{QUERY_STRING} (<|%3c)(.*)(e|%65|%45)(m|%6d|%4d)(b|%62|%42)(e|%65|%45)(d|%64|%44)(.*)(>|%3e) [NC,OR]" . PHP_EOL;
						$rules .= "RewriteCond %{QUERY_STRING} (<|%3c)(.*)(i|%69|%49)(f|%66|%46)(r|%72|%52)(a|%61|%41)(m|%6d|%4d)(e|%65|%45)(.*)(>|%3e) [NC,OR]" . PHP_EOL;
						$rules .= "RewriteCond %{QUERY_STRING} (<|%3c)(.*)(o|%4f|%6f)(b|%62|%42)(j|%4a|%6a)(e|%65|%45)(c|%63|%43)(t|%74|%54)(.*)(>|%3e) [NC,OR]" . PHP_EOL;
						$rules .= "RewriteCond %{QUERY_STRING} (<|%3c)(.*)(s|%73|%53)(c|%63|%43)(r|%72|%52)(i|%69|%49)(p|%70|%50)(t|%74|%54)(.*)(>|%3e) [NC,OR]" . PHP_EOL;
						$rules .= "RewriteCond %{QUERY_STRING} (;|<|>|\'|\\\"|\)|%0a|%0d|%22|%27|%3c|%3e|%00)(.*)(/\*|alter|base64|benchmark|cast|concat|convert|create|encode|declare|delete|drop|insert|md5|request|script|select|union|update) [NC,OR]" . PHP_EOL;
					}

					$rules .= "RewriteCond %{REQUEST_URI} (,,,) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (-------) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (\^|`|<|>|\\\\|\|) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} ([a-z0-9]{2000,}) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (=?\\\\(\'|%27)/?)(\.) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (/)(\*|\\\"|\'|\.|,|&|&amp;?)/?$ [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (\.)(php)(\()?([0-9]+)(\))?(/)?$ [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} /((.*)header:|(.*)set-cookie:(.*)=) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (/)(ckfinder|fckeditor|fullclick) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (\.(s?ftp-?)config|(s?ftp-?)config\.) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (/)((force-)?download|framework/main)(\.php) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (\{0\}|\\\"?0\\\"?=\\\"?0|\(/\(|\+\+\+|\\\\\\\") [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (/)(vbull(etin)?|boards|vbforum|vbweb|webvb)(/)? [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (thumbs?(_editor|open)?|tim(thumbs?)?)(\.php) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (\.|20)(get|the)(_)(permalink|posts_page_url)(\() [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (///|\?\?|/&&|/\*(.*)\*/|/:/|\\\\\\\\|0x00|%00|%0d%0a) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (/)(cgi_?)?alfa(_?cgiapi|_?data|_?v[0-9]+)?(\.php) [NC,OR]" . PHP_EOL;

					$rules .= "RewriteCond %{REQUEST_URI} (/)((boot)?_?admin(er|istrator|s)(_events)?)(\.php) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (/%7e)(root|ftp|bin|nobody|named|guest|logs|sshd)(/) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (archive|backup|db|master|sql|wp|www|wwwroot)\.(gz|zip) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (/)(\.?mad|alpha|c99|php|web)?sh(3|e)ll([0-9]+|\w)(\.php) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (/)(admin-?|file-?)(upload)(bg|_?file|ify|svu|ye)?(\.php) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (/)(etc|var)(/)(hidden|secret|shadow|ninja|passwd|tmp)(/)?$ [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (s)?(ftp|inurl|php)(s)?(:(/|%2f|%u2215)(/|%2f|%u2215)) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (/)(=|\\\$&?|&?(pws|rk)=0|_mm|_vti_|(=|/|;|,)nt\.) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (\.)(ds_store|htaccess|htpasswd|init?|mysql-select-db)(/)?$ [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (/)(bin)(/)(cc|chmod|chsh|cpp|echo|id|kill|mail|nasm|perl|ping|ps|python|tclsh)(/)?$ [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (/)(::[0-9999]|%3a%3a[0-9999]|127\.0\.0\.1|ccx|localhost|makefile|pingserver|wwwroot)(/)? [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (/)?j((\s)+)?a((\s)+)?v((\s)+)?a((\s)+)?s((\s)+)?c((\s)+)?r((\s)+)?i((\s)+)?p((\s)+)?t((\s)+)?(%3a|:) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} ^(/)(old-?site(back)?|old(web)?site(here)?|sites?|staging|undefined)(/)?$ [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (/)(filemanager|htdocs|httpdocs|https?|login|mailman|mailto|msoffice|undefined|usage|var|vhosts|webmaster|www)(/) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (\(null\)|\{\\\$itemURL\}|cast\(0x|echo(.*)kae|etc/passwd|eval\(|null(.*)null|open_basedir|self/environ|\+union\+all\+select) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (/)(db-?|j-?|my(sql)?-?|setup-?|web-?|wp-?)?(admin-?)?(setup-?)?(conf\b|conf(ig)?)(uration)?(\.?bak|\.inc)?(\.inc|\.old|\.php|\.txt) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (/)((.*)crlf-?injection|(.*)xss-?protection|__(inc|jsc)|administrator|author-panel|downloader|(db|mysql)-?admin)(/) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (/)(haders|head|hello|helpear|incahe|includes?|indo(sec)?|infos?|install|ioptimizes?|jmail|js|king|kiss|kodox|kro|legion|libsoft)(\.php) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (/)(awstats|document_root|dologin\.action|error.log|extension/ext|htaccess\.|lib/php|listinfo|phpunit/php|remoteview|server/php|www\.root\.) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (base64_(en|de)code|benchmark|curl_exec|e?chr|eval|function|fwrite|(f|p)open|html|leak|passthru|p?fsockopen|phpinfo)(.*)(\(|%28)(.*)(\)|%29) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (posix_(kill|mkfifo|setpgid|setsid|setuid)|(child|proc)_(close|get_status|nice|open|terminate)|(shell_)?exec|system)(.*)(\(|%28)(.*)(\)|%29) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (/)((c99|php|web)?shell|crossdomain|fileditor|locus7|nstview|php(get|remoteview|writer)|r57|remview|sshphp|storm7|webadmin)(.*)(\.|%2e|\(|%28) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} /((wp-)((201\d|202\d|[0-9]{2})|ad|admin(fx|rss|setup)|booking|confirm|crons|data|file|mail|one|plugins?|readindex|reset|setups?|story))(\.php) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (/)(^$|-|\!|\w|\.(.*)|100|123|([^iI])?ndex|index\.php/index|3xp|777|7yn|90sec|99|active|aill|ajs\.delivery|al277|alexuse?|ali|allwrite)(\.php) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (/)(analyser|apache|apikey|apismtp|authenticat(e|ing)|autoload_classmap|backup(_index)?|bakup|bkht|black|bogel|bookmark|bypass|cachee?)(\.php) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (/)(clean|cm(d|s)|con|connector\.minimal|contexmini|contral|curl(test)?|data(base)?|db|db-cache|db-safe-mode|defau11|defau1t|dompdf|dst)(\.php) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (/)(elements|emails?|error.log|ecscache|edit-form|eval-stdin|evil|fbrrchive|filemga|filenetworks?|f0x|gank(\.php)?|gass|gel|guide)(\.php) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (/)(logo_img|lufix|mage|marg|mass|mide|moon|mssqli|mybak|myshe|mysql|mytag_js?|nasgor|newfile|nf_?tracking|nginx|ngoi|ohayo|old-?index)(\.php) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (/)(olux|owl|pekok|petx|php-?info|phpping|popup-pomo|priv|r3x|radio|rahma|randominit|readindex|readmy|reads|repair-?bak|root)(\.php) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (/)(router|savepng|semayan|shell|shootme|sky|socket(c|i|iasrgasf)ontrol|sql(bak|_?dump)?|support|sym403|sys|system_log|test|tmp-?(uploads)?)(\.php) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (/)(traffic-advice|u2p|udd|ukauka|up__uzegp|up14|upxx?|vega|vip|vu(ln)?(\w)?|webroot|weki|wikindex|wp_logns?|wp_wrong_datlib)(\.php) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (/)((wp-?)?install(ation)?|wp(3|4|5|6)|wpfootes|wpzip|ws0|wsdl|wso(\w)?|www|(uploads|wp-admin)?xleet(-shell)?|xmlsrpc|xup|xxu|xxx|zibi|zipy)(\.php) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (bkv74|cachedsimilar|core-stab|crgrvnkb|ctivrc|deadcode|deathshop|dkiz|e7xue|eqxafaj90zir|exploits|ffmkpcal|filellli7|(fox|sid)wso|gel4y|goog1es|gvqqpinc) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (@md5|00.temp00|0byte|0d4y|0day|0xor|wso1337|1h6j5|3xp|40dd1d|4price|70bex?|a57bze893|abbrevsprl|abruzi|adminer|aqbmkwwx|archivarix|backdoor|beez5|bgvzc29) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (handler_to_code|hax(0|o)r|hmei7|hnap1|home_url=|ibqyiove|icxbsx|indoxploi|jahat|jijle3|kcrew|keywordspy|laobiao|lock360|longdog|marijuan|mod_(aratic|ariimag)) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (mobiquo|muiebl|osbxamip|phpunit|priv8|qcmpecgy|r3vn330|racrew|raiz0|reportserver|r00t|respectmus|rom2823|roseleif|sh3ll|site((.){0,2})copier|sqlpatch|sux0r) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (sym403|telerik|uddatasql|utchiha|visualfrontend|w0rm|wangdafa|wpyii2|wsoyanzo|x5cv|xattack|xbaner|xertive|xiaolei|xltavrat|xorz|xsamxad|xsvip|xxxs?s?|zabbix|zebda) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (\.)(7z|ab4|ace|afm|alfa|as(h|m)x?|aspx?|aws|axd|bash|ba?k?|bat|bin|bz2|cfg|cfml?|cms|conf\b|config|ctl|dat|db|dist|dll|eml|eng(ine)?|env|et2|fec|fla|git(ignore)?)$ [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (\.)(hg|idea|inc|index|ini|inv|jar|jspa?|lib|local|log|lqd|make|mbf|mdb|mmw|mny|mod(ule)?|msi|old|one|orig|out|passwd|pdb|php\.(php|suspect(ed)?)|php([^\/])|phtml?|pl|profiles?)$ [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} (\.)(psd|pst|ptdb|production|pwd|py|qbb|qdf|rar|rdf|remote|save|sdb|sql|sh|soa|svn|swf|swl|swo|swp|stx|tar|tax|tgz?|theme|tls|tmb|tmd|wok|wow|xsd|xtmpl|xz|ya?ml|za|zlib)$ [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{HTTP_REFERER} (order(\s|%20)by(\s|%20)1--) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{HTTP_REFERER} (@unlink|assert\(|print_r\(|x00|xbshell) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{HTTP_REFERER} (100dollars|best-seo|blue\spill|cocaine|ejaculat|erectile|erections|hoodia|huronriveracres|impotence|levitra|libido|lipitor|mopub\.com|phentermin) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{HTTP_REFERER} (pornhelm|pro[sz]ac|sandyauer|semalt\.com|social-buttions|todaperfeita|tramadol|troyhamby|ultram|unicauca|valium|viagra|vicodin|xanax|ypxaieo) [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_METHOD} ^(connect|debug|move|trace|track) [NC]" . PHP_EOL;

				}

				$rules .= "RewriteRule ^(.*)$ - [F]" . PHP_EOL;
				$rules .= "</IfModule>" . PHP_EOL . PHP_EOL;
			}

			$rules .= "<IfModule mod_rewrite.c>" . PHP_EOL;
			$rules .= "RewriteEngine On" . PHP_EOL;
			$rules .= "RewriteBase $home_root" . PHP_EOL;
			if ( !empty($this->whitelist_ips) ){
				foreach ($this->whitelist_ips as $ip){
					$rules .= "RewriteCond %{REMOTE_ADDR} !^$ip$" . PHP_EOL;
				}
			}
			$rules .= "RewriteCond %{HTTP:Cookie} !(" . join( '|', $this->whitelist_cookies ) . ") [NC]" . PHP_EOL;
			if ( defined( 'WP_ROCKET_MINIFY_CACHE_URL' ) ) { //If WP-Rocket is installed
				$rules .= "RewriteCond %{REQUEST_URI} !" . str_replace( array(
						home_url() . '/', HMWP_Classes_Tools::getDefault( 'hmwp_wp-content_url' )
					), '', ltrim( WP_ROCKET_MINIFY_CACHE_URL, '/' ) ) . " [NC]" . PHP_EOL;
			}

			if ( HMWP_Classes_Tools::getOption( 'hmwp_hide_oldpaths' ) ) {
				if ( HMWP_Classes_Tools::getDefault( 'hmwp_wp-content_url' ) <> HMWP_Classes_Tools::getOption( 'hmwp_wp-content_url' ) ) {
					$rules .= "RewriteCond %{REQUEST_URI} ^" . $home_root . $wp_content . "/?$ [NC,OR]" . PHP_EOL;
					$rules .= "RewriteCond %{REQUEST_URI} ^" . $home_root . $wp_content . "/[^\.]+/?$ [NC,OR]" . PHP_EOL;
				}
				if ( HMWP_Classes_Tools::getDefault( 'hmwp_wp-includes_url' ) <> HMWP_Classes_Tools::getOption( 'hmwp_wp-includes_url' ) ) {
					$rules .= "RewriteCond %{THE_REQUEST} " . $home_root . $wp_includes . "/?$ [NC,OR]" . PHP_EOL;
				}
				if ( ! empty( $extensions ) ) {

					//hide wp-admin files like load-scripts.php when 7G and 8G firewall is loaded
//					if ( HMWP_Classes_Tools::getOption( 'hmwp_sqlinjection' ) &&
//					     ((int) HMWP_Classes_Tools::getOption( 'hmwp_sqlinjection_level' ) == 3 || (int) HMWP_Classes_Tools::getOption( 'hmwp_sqlinjection_level' ) == 4)) {
//						if ( HMWP_Classes_Tools::getDefault( 'hmwp_admin_url' ) <> HMWP_Classes_Tools::getOption( 'hmwp_admin_url' ) ) {
//							$rules .= "RewriteCond %{THE_REQUEST} " . $home_root . HMWP_Classes_Tools::getDefault( 'hmwp_admin_url' ) . "/[^\.]+(" . join( '|', $extensions ) . ") [NC,OR]" . PHP_EOL;
//						}
//					}
					if ( HMWP_Classes_Tools::getDefault( 'hmwp_plugin_url' ) <> HMWP_Classes_Tools::getOption( 'hmwp_plugin_url' ) ) {
						$rules .= "RewriteCond %{THE_REQUEST} " . $home_root . HMWP_Classes_Tools::getDefault( 'hmwp_plugin_url' ) . "/[^\.]+(" . join( '|', $extensions ) . ") [NC,OR]" . PHP_EOL;
					}
					if ( HMWP_Classes_Tools::getDefault( 'hmwp_themes_url' ) <> HMWP_Classes_Tools::getOption( 'hmwp_themes_url' ) ) {
						$rules .= "RewriteCond %{THE_REQUEST} " . $home_root . $wp_content . "/" . HMWP_Classes_Tools::getDefault( 'hmwp_themes_url' ) . "/[^\.]+(" . join( '|', $extensions ) . ")    [NC,OR]" . PHP_EOL;
					}
					if ( HMWP_Classes_Tools::getDefault( 'hmwp_upload_url' ) <> HMWP_Classes_Tools::getOption( 'hmwp_upload_url' ) ) {
						$rules .= "RewriteCond %{THE_REQUEST} " . $home_root . $wp_content . '/' . HMWP_Classes_Tools::getDefault( 'hmwp_upload_url' ) . "/[^\.]+(" . join( '|', $extensions ) . ") [NC,OR]" . PHP_EOL;
					}
					if ( HMWP_Classes_Tools::getDefault( 'hmwp_wp-includes_url' ) <> HMWP_Classes_Tools::getOption( 'hmwp_wp-includes_url' ) ) {
						$rules .= "RewriteCond %{THE_REQUEST} " . $home_root . $wp_includes . "/[^\.]+(" . join( '|', $extensions ) . ") [NC,OR]" . PHP_EOL;
					}
				}

			}

			if ( HMWP_Classes_Tools::getOption( 'hmwp_hide_commonfiles' ) && ! empty( $files ) ) {
				$rules .= "RewriteCond %{THE_REQUEST} /([_0-9a-zA-Z-]+/)?(" . str_replace( '.', '\\.', join( '|', $files ) ) . ") [NC]" . PHP_EOL;
			} else {
				$rules .= "RewriteCond %{THE_REQUEST} /([_0-9a-zA-Z-]+/)?(upgrade\\.php|install\\.php) [NC]" . PHP_EOL;
			}

			$rules .= "RewriteRule ^(.*)$ - [L,R=404]" . PHP_EOL;
			$rules .= "</IfModule>" . PHP_EOL . PHP_EOL;

			if ( HMWP_Classes_Tools::getOption( 'hmwp_hide_commonfiles' ) ) {
				$rules .= "<IfModule mod_rewrite.c>" . PHP_EOL;
				$rules .= "RewriteEngine On" . PHP_EOL;
				if ( ! empty( $files ) ) {
					$rules .= "RewriteCond %{REQUEST_URI} /(" . str_replace( '.', '\\.', join( '|', $files ) ) . ") [NC]" . PHP_EOL;
				} else {
					$rules .= "RewriteCond %{REQUEST_URI} /(error_log|wp-config-sample\\.php|readme\\.html|readme\\.txt|license\\.txt|install\\.php|wp-config\\.php|php\\.ini|php5\\.ini|bb-config\\.php) [NC]" . PHP_EOL;
				}
				$rules .= "RewriteRule ^(.*)$ - [L,R=404]" . PHP_EOL;
				$rules .= "</IfModule>" . PHP_EOL . PHP_EOL;
			}


		} elseif ( HMWP_Classes_Tools::isIIS() ) {
			if ( HMWP_Classes_Tools::getOption( 'hmwp_hide_oldpaths' ) ) {
				if ( HMWP_Classes_Tools::getDefault( 'hmwp_wp-content_url' ) <> HMWP_Classes_Tools::getOption( 'hmwp_wp-content_url' ) ) {
					$rules .= '
                    <rule name="WpGhost: block_oldpaths_content" stopProcessing="true">
                        <match url="^' . $wp_content . '/?$" ignoreCase="false" />
                         <conditions>
                          <add input="{HTTP_COOKIE}" pattern="(' . join( '|', $this->whitelist_cookies ) . ')" negate="true" />
                         </conditions>
                        <action type="CustomResponse" statusCode="404" statusReason="File Not Found" statusDescription="The requested path was not found" />
                    </rule>';
					$rules .= '
                    <rule name="WpGhost: block_oldpaths_content_paths" stopProcessing="true">
                        <match url="^' . $wp_content . '/[^\.]+/?$" ignoreCase="false" />
                         <conditions>
                          <add input="{HTTP_COOKIE}" pattern="(' . join( '|', $this->whitelist_cookies ) . ')" negate="true" />
                         </conditions>
                        <action type="CustomResponse" statusCode="404" statusReason="File Not Found" statusDescription="The requested path was not found" />
                    </rule>';
				}
				if ( ! empty( $extensions ) ) {
					if ( HMWP_Classes_Tools::getDefault( 'hmwp_plugin_url' ) <> HMWP_Classes_Tools::getOption( 'hmwp_plugin_url' ) ) {
						$rules .= '
                    <rule name="WpGhost: block_oldpaths_plugin" stopProcessing="true">
                        <match url="^' . HMWP_Classes_Tools::getDefault( 'hmwp_plugin_url' ) . '/[^\.]+(' . join( '|', $extensions ) . ')' . '" ignoreCase="false" />
                         <conditions>
                          <add input="{HTTP_COOKIE}" pattern="(' . join( '|', $this->whitelist_cookies ) . ')" negate="true" />
                         </conditions>
                        <action type="CustomResponse" statusCode="404" statusReason="File Not Found" statusDescription="The requested path was not found" />
                    </rule>';
					}
					if ( HMWP_Classes_Tools::getDefault( 'hmwp_themes_url' ) <> HMWP_Classes_Tools::getOption( 'hmwp_themes_url' ) ) {
						$rules .= '
                    <rule name="WpGhost: block_oldpaths_themes" stopProcessing="true">
                        <match url="^' . HMWP_Classes_Tools::getDefault( 'hmwp_themes_url' ) . '/[^\.]+(' . join( '|', $extensions ) . ')' . '" ignoreCase="false" />
                         <conditions>
                          <add input="{HTTP_COOKIE}" pattern="(' . join( '|', $this->whitelist_cookies ) . ')" negate="true" />
                         </conditions>
                        <action type="CustomResponse" statusCode="404" statusReason="File Not Found" statusDescription="The requested path was not found" />
                    </rule>';
					}
				}
				$rules .= '
                    <rule name="WpGhost: block_upgrade" stopProcessing="true">
                        <match url="^([_0-9a-zA-Z-]+/)?(upgrade.php|install.php)" ignoreCase="false" />
                         <conditions>
                          <add input="{HTTP_COOKIE}" pattern="(' . join( '|', $this->whitelist_cookies ) . ')" negate="true" />
                         </conditions>
                        <action type="CustomResponse" statusCode="404" statusReason="File Not Found" statusDescription="The requested path was not found" />
                    </rule>';
			}
		}

		return $rules;
	}

	/**
	 * Add rules to protect the website from sql injection
	 *
	 * @return string
	 */
	public function getInjectionRewrite() {
		$rules = '';

		if ( HMWP_Classes_Tools::isNginx() ) {

			if ( HMWP_Classes_Tools::getOption( 'hmwp_hide_authors' ) ) {
				$rules .= 'set $cond "";' . PHP_EOL;
				$rules .= 'if ($http_cookie !~* "' . join( '|', $this->whitelist_cookies ) . '") {  set $cond cookie; }' . PHP_EOL;
				$rules .= 'if ($request_uri ~* author=\d+$) { set $cond "${cond}+author_uri"; }' . PHP_EOL;
				$rules .= 'if ($cond = "cookie+author_uri") {  return 404; } ' . PHP_EOL;
			}

			if ( HMWP_Classes_Tools::getOption( 'hmwp_disable_browsing' ) ) {
				$rules .= 'autoindex off;' . PHP_EOL;
			}

			if ( HMWP_Classes_Tools::getOption( 'hmwp_security_header' ) ) {

				$headers = (array) HMWP_Classes_Tools::getOption( 'hmwp_security_headers' );

				if ( ! empty( $headers ) ) {
					foreach ( $headers as $name => $value ) {
						if ( $value <> '' ) {
							$rules .= 'add_header ' . $name . ' "' . str_replace( '"', '\"', $value ) . '";' . PHP_EOL;
						}
					}
				}
			}

			if ( HMWP_Classes_Tools::getOption( 'hmwp_detectors_block' ) ) {
				$rules .= 'if ( $remote_addr = "35.214.130.87" ) { return 404; }' . PHP_EOL;
				$rules .= 'if ( $remote_addr = "192.185.4.40" ) { return 404; }' . PHP_EOL;
				$rules .= 'if ( $remote_addr = "15.235.50.223" ) { return 404; }' . PHP_EOL;
				$rules .= 'if ( $remote_addr = "172.105.48.130" ) { return 404; }' . PHP_EOL;
				$rules .= 'if ( $remote_addr = "167.99.233.123" ) { return 404; }' . PHP_EOL;
				$rules .= 'if ( $http_user_agent ~ "wpthemedetector" ) { return 404; }' . PHP_EOL;
				$rules .= 'if ( $http_referer ~ "wpthemedetector" ) { return 404; }' . PHP_EOL;
				$rules .= 'if ( $http_user_agent ~ "builtwith" ) { return 404; }' . PHP_EOL;
				$rules .= 'if ( $http_user_agent ~ "isitwp" ) { return 404; }' . PHP_EOL;
				$rules .= 'if ( $http_user_agent ~ "wappalyzer" ) { return 404; }' . PHP_EOL;
				$rules .= 'if ( $http_user_agent ~ "Wappalyzer" ) { return 404; }' . PHP_EOL;
				$rules .= 'if ( $http_referer ~ "mShots" ) { return 404; }' . PHP_EOL;
				$rules .= 'if ( $http_referer ~ "WhatCMS" ) { return 404; }' . PHP_EOL;
			}

		} elseif ( HMWP_Classes_Tools::isApache() || HMWP_Classes_Tools::isLitespeed() ) {

			if ( HMWP_Classes_Tools::getOption( 'hmwp_hide_authors' ) ) {
				$rules .= "<IfModule mod_rewrite.c>" . PHP_EOL;
				$rules .= "RewriteEngine On" . PHP_EOL;
				$rules .= "RewriteCond %{REQUEST_URI} !/wp-admin [NC]" . PHP_EOL;
				$rules .= "RewriteCond %{QUERY_STRING} ^author=\d+ [NC]" . PHP_EOL;
				$rules .= "RewriteRule ^(.*)$ - [L,R=404]" . PHP_EOL;
				$rules .= "</IfModule>" . PHP_EOL . PHP_EOL;
			}

			if ( HMWP_Classes_Tools::getOption( 'hmwp_disable_browsing' ) ) {
				$rules .= "Options -Indexes" . PHP_EOL;
			}

			if ( HMWP_Classes_Tools::getOption( 'hmwp_detectors_block' ) ) {
				$rules .= "<IfModule mod_rewrite.c>" . PHP_EOL;
				$rules .= "RewriteEngine On" . PHP_EOL;
				$rules .= "RewriteCond %{REMOTE_ADDR} ^35.214.130.87$ [NC,OR]" . PHP_EOL;
				$rules .= "RewriteCond %{REMOTE_ADDR} ^192.185.4.40$ [NC,OR]" . PHP_EOL;
				$rules .= "RewriteCond %{REMOTE_ADDR} ^15.235.50.223$ [NC,OR]" . PHP_EOL;
				$rules .= "RewriteCond %{REMOTE_ADDR} ^172.105.48.130$ [NC,OR]" . PHP_EOL;
				$rules .= "RewriteCond %{REMOTE_ADDR} ^167.99.233.123$ [NC,OR]" . PHP_EOL;
				$rules .= "RewriteCond %{HTTP_USER_AGENT} (wpthemedetector|builtwith|isitwp|wappalyzer|Wappalyzer|mShots|WhatCMS|gochyu|wpdetector|scanwp) [NC]" . PHP_EOL;
				$rules .= "RewriteRule ^(.*)$ - [L,R=404]" . PHP_EOL;
				$rules .= "</IfModule>" . PHP_EOL . PHP_EOL;
			}

			if ( HMWP_Classes_Tools::getOption( 'hmwp_hide_unsafe_headers' ) ) {
				$rules .= "<IfModule mod_headers.c>" . PHP_EOL;
				$rules .= 'Header always unset x-powered-by' . PHP_EOL;
				$rules .= 'Header always unset server' . PHP_EOL;
				$rules .= 'ServerSignature Off' . PHP_EOL;
				$rules .= "</IfModule>" . PHP_EOL . PHP_EOL;
			}


			if ( HMWP_Classes_Tools::getOption( 'hmwp_security_header' ) ) {

				$headers = (array) HMWP_Classes_Tools::getOption( 'hmwp_security_headers' );

				if ( ! empty( $headers ) ) {
					$rules .= "<IfModule mod_headers.c>" . PHP_EOL;

					foreach ( $headers as $name => $value ) {
						if ( $value <> '' ) {
							$rules .= 'Header set ' . $name . ' "' . str_replace( '"', '\"', $value ) . '"' . PHP_EOL;
						}
					}

					$rules .= "</IfModule>" . PHP_EOL . PHP_EOL;
				}
			}

		} elseif ( HMWP_Classes_Tools::isIIS() ) {
			if ( HMWP_Classes_Tools::getOption( 'hmwp_hide_commonfiles' ) ) {

				$rules .= '
                    <rule name="WpGhost: block_commonfiles" stopProcessing="true">
                        <match url="^(error_log|wp-config-sample.php|readme.html|license.txt|install.php|wp-config.php|php.ini|bb-config.php)" ignoreCase="false" />
                        <action type="CustomResponse" statusCode="404" statusReason="File Not Found" statusDescription="The requested path was not found" />
                    </rule>';
			}
			if ( HMWP_Classes_Tools::getOption( 'hmwp_hide_authors' ) ) {
				$rules .= '
                    <rule name="WpGhost: block_author_id" stopProcessing="true">
                        <match url="(.*)"/>
                        <conditions logicalGrouping="MatchAny">
                          <add input="{QUERY_STRING}" pattern="^author=\d+" negate="false" />
                        </conditions>
                        <action type="CustomResponse" statusCode="404" statusReason="File Not Found" statusDescription="The requested path was not found" />
                    </rule>';
			}
		}

		// Add in the rules
		return $rules . PHP_EOL;
	}

	/**
	 * Check if the ADMIN_COOKIE_PATH is present in wp-config.php
	 *
	 * @return bool
	 */
	public function isConfigAdminCookie() {
		$config_file = HMWP_Classes_Tools::getConfigFile();

		//Initialize WordPress Filesystem
		$wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

		if ( $wp_filesystem->exists( $config_file ) ) {
			$lines = file( $config_file );

			foreach ( (array) $lines as $line ) {
				if ( preg_match( "/ADMIN_COOKIE_PATH/", $line ) && ! preg_match( "/^\/\//", trim( $line ) ) ) {
					return true;
				}
			}
		}

		return false;
	}


}
