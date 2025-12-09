<?php
/**
 * Cache Model
 *
 * @file  The Cache file
 * @package HMWP/CacheModel
 * @since 5.0.0
 */
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

class HMWP_Models_Cache {

	protected $_replace = array();
	protected $_cachepath = '';
	protected $chmod = 644;

	public function __construct() {
		$this->setCachePath( WP_CONTENT_DIR . '/cache/' );

	}

	/**
	 * Set the cache storage path
	 *
	 * @param  string  $path  The path where cache files are stored
	 *
	 * @return void
	 */
	public function setCachePath( $path ) {
		$this->_cachepath = $path;
	}

	/**
	 * Retrieve the current cache path.
	 *
	 * Initialize the WordPress filesystem and determine the appropriate cache
	 * path, considering multisite configurations.
	 *
	 * @return string|bool The cache path if it exists, otherwise false.
	 */
	public function getCachePath() {

		// Initialize WordPress Filesystem
		$wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

		// Get the website cache path
		$path = $this->_cachepath;

		if ( HMWP_Classes_Tools::isMultisites() ) {
			if ( $wp_filesystem->is_dir( $path . get_current_blog_id() . '/' ) ) {
				$path .= get_current_blog_id() . '/';
			}
		}

		if ( ! $wp_filesystem->is_dir( $path ) ) {
			return false;
		}

		return $path;
	}

	/**
	 * Build the redirection rules for the application.
	 *
	 * This method constructs the necessary redirection paths and patterns
	 * for URL rewriting in the application. It ensures the replacement paths
	 * are set properly, adds the domain to the rewrites if necessary, and
	 * verifies only the intended paths are modified.
	 *
	 * @return void
	 */
	public function buildRedirect() {

		// If the replacement was not already set
		if ( empty( $this->_replace ) ) {

			/**
			 * The Rewrite Model
			 *
			 * @var HMWP_Models_Rewrite $rewriteModel
			 */
			$rewriteModel = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rewrite' );

			// Build the rules paths to change back the hidden paths
			if ( ! isset( $rewriteModel->_replace['from'] ) && ! isset( $rewriteModel->_replace['to'] ) ) {
				$rewriteModel->buildRedirect();

				// Add the domain to rewrites if not multisite
				if ( HMWP_Classes_Tools::getOption( 'hmwp_fix_relative' ) && ! HMWP_Classes_Tools::isMultisites() ) {
					$rewriteModel->prepareFindReplace();
				}
			}

			// Verify only the rewrites
			if ( isset( $rewriteModel->_replace['from'] ) && isset( $rewriteModel->_replace['to'] ) && ! empty( $rewriteModel->_replace['from'] ) && ! empty( $rewriteModel->_replace['to'] ) ) {
				if ( ! empty( $rewriteModel->_replace['rewrite'] ) ) {
					foreach ( $rewriteModel->_replace['rewrite'] as $index => $value ) {
						//add only the paths or the design path
						if ( ( isset( $rewriteModel->_replace['to'][ $index ] ) && substr( $rewriteModel->_replace['to'][ $index ], - 1 ) == '/' ) || strpos( $rewriteModel->_replace['to'][ $index ], '/' . HMWP_Classes_Tools::getOption( 'hmwp_themes_style' ) ) ) {
							$this->_replace['from'][] = $rewriteModel->_replace['from'][ $index ];
							$this->_replace['to'][]   = $rewriteModel->_replace['to'][ $index ];
						}
					}
				}

				// Add the domain to rewrites
				if ( HMWP_Classes_Tools::getOption( 'hmwp_fix_relative' ) ) {
					$this->_replace['from'] = array_map( array(
						$rewriteModel,
						'addDomainUrl'
					), (array) $this->_replace['from'] );
					$this->_replace['to']   = array_map( array(
						$rewriteModel,
						'addDomainUrl'
					), (array) $this->_replace['to'] );
				}
			}
		}

	}

	/**
	 * Change paths in CSS files in the cache directory
	 *
	 * This method scans the cache directory for CSS files, reads their contents,
	 * and performs find-and-replace operations based on predefined redirects.
	 * If changes are made to the contents, the modified file is written back
	 * to the disk.
	 *
	 * @return void
	 */
	public function changePathsInCss() {
		if ( HMWP_Classes_Tools::getOption( 'error' ) ) {
			return;
		}

		try {
			if ( $this->getCachePath() ) {

				$cssfiles = $this->rsearch( $this->getCachePath() . '*.css' );

				if ( ! empty( $cssfiles ) ) {

					// Load the redirects into array
					$this->buildRedirect();

					foreach ( $cssfiles as $file ) {
						// Only if the file is writable
						if ( ! $content = $this->readFile( $file ) ) {
							continue;
						}

						// Find replace the content
						$newcontent = $this->findReplace( $content );
						if ( $newcontent <> $content ) {
							//write into file
							$this->writeFile( $file, $newcontent );
						}

					}
				}
			}

		} catch ( Exception $e ) {
		}
	}

	/**
	 * Modifies paths in JavaScript files by searching and replacing content.
	 *
	 * This method scans for JavaScript files within a specified cache directory,
	 * reads their content, and performs find-and-replace operations based on
	 * predefined rules. If modifications are made, the updated content is written
	 * back to the respective files.
	 *
	 * @return void
	 */
	public function changePathsInJs() {
		if ( HMWP_Classes_Tools::getOption( 'error' ) ) {
			return;
		}

		try {
			if ( $this->getCachePath() ) {

				$jsfiles = $this->rsearch( $this->getCachePath() . '*.js' );

				if ( ! empty( $jsfiles ) ) {

					// Load the redirects into array
					$this->buildRedirect();

					foreach ( $jsfiles as $file ) {

						// Only if the file is writable
						if ( ! $content = $this->readFile( $file ) ) {
							continue;
						}

						// Find replace the content
						$newcontent = $this->findReplace( $content );
						if ( $newcontent <> $content ) {
							//echo $newcontent;exit();
							//write into file
							$this->writeFile( $file, $newcontent );
						}
					}
				}
			}
		} catch ( Exception $e ) {
		}
	}

	/**
	 * Changes paths in the HTML files located in the cache directory.
	 *
	 * This method initializes the WordPress filesystem, searches for HTML files
	 * in the cache directory, and performs find-and-replace operations on their content.
	 * Modified content is then written back to the files if changes were made and if they are writable.
	 *
	 * @return void
	 */
	public function changePathsInHTML() {

		//Initialize WordPress Filesystem
		$wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

		if ( HMWP_Classes_Tools::getOption( 'error' ) ) {
			return;
		}

		try {
			if ( $this->getCachePath() ) {
				$htmlfiles = $this->rsearch( $this->getCachePath() . '*.html' );

				if ( ! empty( $htmlfiles ) ) {

					// Load the redirects into array
					$this->buildRedirect();

					foreach ( $htmlfiles as $file ) {
						// Only if the file is writable
						if ( ! $wp_filesystem->is_writable( $file ) ) {
							continue;
						}

						// Get the file content
						$content = $wp_filesystem->get_contents( $file );

						// Find & replace the content
						$newcontent = $this->findReplace( $content );

						if ( $newcontent <> $content ) {
							// Write into file
							$this->writeFile( $file, $newcontent );
						}
					}
				}
			}
		} catch ( Exception $e ) {
		}
	}

	/**
	 * Replaces specified paths within the content based on predefined mappings.
	 *
	 * @param  string  $content  The content in which the paths are to be replaced.
	 *
	 * @return string The modified content with the specified paths replaced.
	 * @throws Exception
	 */
	public function findReplace( $content ) {

		// If there are replaced paths
		if ( ! empty( $this->_replace ) && isset( $this->_replace['from'] ) && isset( $this->_replace['to'] ) ) {

			// If there is content in the file
			if ( $content <> '' ) {
				// If the file has unchanged paths
				if ( strpos( $content, HMWP_Classes_Tools::$default['hmwp_admin_url'] ) !== false || strpos( $content, HMWP_Classes_Tools::$default['hmwp_wp-content_url'] ) !== false || strpos( $content, HMWP_Classes_Tools::$default['hmwp_wp-includes_url'] ) !== false ) {

					// Fix the relative links before
					if ( HMWP_Classes_Tools::getOption( 'hmwp_fix_relative' ) ) {
						$content = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rewrite' )->fixRelativeLinks( $content );
					}

					$content = str_ireplace( $this->_replace['from'], $this->_replace['to'], $content );

				}

				// Text Mapping for all css files - Experimental
				if ( HMWP_Classes_Tools::getOption( 'hmwp_mapping_text_show' ) && HMWP_Classes_Tools::getOption( 'hmwp_mapping_file' ) ) {
					$hmwp_text_mapping = json_decode( HMWP_Classes_Tools::getOption( 'hmwp_text_mapping' ), true );
					if ( isset( $hmwp_text_mapping['from'] ) && ! empty( $hmwp_text_mapping['from'] ) && isset( $hmwp_text_mapping['to'] ) && ! empty( $hmwp_text_mapping['to'] ) ) {

						// Only classes & ids
						if ( HMWP_Classes_Tools::getOption( 'hmwp_mapping_classes' ) ) {

							foreach ( $hmwp_text_mapping['from'] as $index => $from ) {
								if ( strpos( $content, $from ) !== false ) {
									$content = preg_replace( "'(?:([^/])" . addslashes( $from ) . "([^/]))'is", '$1' . $hmwp_text_mapping['to'][ $index ] . '$2', $content );
								}
							}

						} else {
							$content = str_ireplace( $hmwp_text_mapping['from'], $hmwp_text_mapping['to'], $content );
						}
					}

				}
			}
		}

		return $content;
	}

	/**
	 * Recursively searches for files matching a pattern.
	 *
	 * @param  string  $pattern  The pattern to search for.
	 * @param  int  $flags  Optional flags to control the search behavior.
	 *
	 * @return array An array of files matching the pattern.
	 */
	public function rsearch( $pattern, $flags = 0 ) {
		$files = array();

		if ( function_exists( 'glob' ) ) {
			$files = glob( $pattern, $flags );
			foreach ( glob( dirname( $pattern ) . '/*', GLOB_ONLYDIR | GLOB_NOSORT ) as $dir ) {
				$files = array_merge( $files, $this->rsearch( $dir . '/' . basename( $pattern ), $flags ) );
			}
		}

		return $files;
	}

	/**
	 * Read the contents of a file.
	 *
	 * @param  string  $file  The path to the file to be read.
	 *
	 * @return string|false The contents of the file if it is writable; otherwise, false.
	 */
	public function readFile( $file ) {

		// Initialize WordPress Filesystem
		$wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

		if ( $wp_filesystem->is_writable( $file ) ) {
			return $wp_filesystem->get_contents( $file );
		}

		return false;
	}

	/**
	 * Writes content to a file if the file is writable.
	 *
	 * @param  string  $file  The path to the file to be written.
	 * @param  string  $content  The content to be written to the file.
	 *
	 * @return void
	 */
	public function writeFile( $file, $content ) {

		// Initialize WordPress Filesystem
		$wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

		if ( $wp_filesystem->is_writable( $file ) ) {
			$wp_filesystem->put_contents( $file, $content );
		}

	}

}
