<?php
/**
 * The class creates object for plugin classes
 *
 * @file The Object Creator Class file
 * @package HMWP/Objects
 * @since 4.0.0
 */

defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Controller for handling object instances and configurations.
 */
class HMWP_Classes_ObjController {

	/**
	 * Array of instance objects for quick call
	 *
	 * @var array of instances
	 */
	public static $instances;

	/**
	 * Configuration settings for the application
	 *
	 * @var array
	 */
	public static $config;

	/**
	 * Retrieves an instance of the specified class with optional constructor arguments.
	 *
	 * @param  string  $className  Name of the class to retrieve.
	 * @param  array  $args  Optional constructor arguments to pass when instantiating the class.
	 *
	 * @return object|false Instance of the specified class if successful, otherwise false.
	 *
	 * @throws Exception if the class file does not exist or is abstract.
	 */
	public static function getClass( $className, $args = array() ) {

		if ( $class = self::getClassByPath( $className ) ) {

			if ( ! isset( self::$instances[ $className ] ) ) {
				/* check if class is already defined */
				if ( ! class_exists( $className ) ) {
					//include the class file
					self::includeClass( $class['dir'], $class['name'] );

					//check if abstract
					$check    = new ReflectionClass( $className );
					$abstract = $check->isAbstract();
					if ( ! $abstract ) {
						self::$instances[ $className ] = new $className();
						if ( ! empty( $args ) ) {
							call_user_func_array( array( self::$instances[ $className ], '__construct' ), $args );
						}

						return self::$instances[ $className ];
					} else {
						self::$instances[ $className ] = true;
					}

				}
			} else {
				return self::$instances[ $className ];
			}

		} else {

			// Stop all hooks on error
			defined( 'HMWP_DISABLE' ) || define( 'HMWP_DISABLE', true );

			// Get the class dir and name
			$class = self::getClassPath( $className );

			// Show the file not found error
			HMWP_Classes_Error::showError( 'File not found: ' . $class['dir'] . $class['name'] . '.php', 'danger' );

		}

		return false;
	}

	/**
	 * Clear the class instance
	 *
	 * @param  string  $className
	 * @param  array  $args
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public static function newInstance( $className, $args = array() ) {

		if ( self::getClassByPath( $className ) ) {
			/* check if class is already defined */
			if ( class_exists( $className ) ) {
				//check if abstract
				self::$instances[ $className ] = new $className();
				if ( ! empty( $args ) ) {
					call_user_func_array( array( self::$instances[ $className ], '__construct' ), $args );
				}

				return self::$instances[ $className ];
			} else {
				return self::getClass( $className, $args );
			}
		}

		return false;
	}

	/**
	 * Include Class if exists
	 *
	 * @param $classDir
	 * @param $className
	 *
	 * @throws Exception
	 */
	private static function includeClass( $classDir, $className ) {

		//Initialize WordPress Filesystem
		$wp_filesystem = self::initFilesystem();

		$path = $classDir . $className . '.php';
		if ( $wp_filesystem->exists( $path ) ) {
			include_once $path;
		}

	}

	/**
	 * Check if the class is correctly set
	 *
	 * @param  string  $className
	 *
	 * @return boolean
	 */
	private static function checkClassPath( $className ) {
		$path = preg_split( '/[_]+/', $className );
		if ( is_array( $path ) && count( $path ) > 1 ) {
			if ( in_array( _HMWP_NAMESPACE_, $path ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the path of the class and name of the class
	 *
	 * @param  string  $className
	 *
	 * @return array|false
	 */
	public static function getClassPath( $className ) {
		$dir = '';

		if ( self::checkClassPath( $className ) ) {

			$path = preg_split( '/[_]+/', $className );
			for ( $i = 1; $i < sizeof( $path ) - 1; $i ++ ) {
				$dir .= strtolower( $path[ $i ] ) . '/';
			}

			return array(
				'dir'  => _HMWP_ROOT_DIR_ . '/' . $dir,
				'name' => $path[ sizeof( $path ) - 1 ]
			);

		}

		return false;

	}

	/**
	 * Get the valid class by path
	 *
	 * @param  $className
	 *
	 * @return array|bool|false
	 */
	public static function getClassByPath( $className ) {

		// Initialize WordPress Filesystem
		$wp_filesystem = self::initFilesystem();

		// Get the class dir and name
		$class = self::getClassPath( $className );

		if ( $wp_filesystem->exists( $class['dir'] . $class['name'] . '.php' ) || file_exists( $class['dir'] . $class['name'] . '.php' ) ) {
			return $class;
		}

		return false;

	}

	/**
	 * Instantiates the WordPress filesystem
	 *
	 * @return mixed
	 */
	public static function initFilesystem() {
		// The WordPress filesystem.
		global $wp_filesystem;

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			include_once ABSPATH . 'wp-admin/includes/file.php';
		}

		// Call WordPress filesystem function
		WP_Filesystem();

		// If the filesystem is not connected to the files,
		// Initiate filesystem with direct connection to the server files
		if ( ! $wp_filesystem->connect() ) {
			add_filter( 'filesystem_method', function ( $method ) {
				return 'direct';
			}, 1 );
			WP_Filesystem();
		}

		// return the filesystem object
		return $wp_filesystem;
	}

}
