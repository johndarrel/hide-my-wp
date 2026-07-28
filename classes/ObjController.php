<?php
/**
 * The class creates object for plugin classes
 *
 * @file The Object Creator Class file
 * @package HMWP/Objects
 * @since 4.0.0
 */

defined('ABSPATH') || die('Cheating uh?');

/**
 * Controller for handling object instances and configurations.
 */
class HMWP_Classes_ObjController
{

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
     */
    public static function getClass($className, $args = array())
    {
		try {
			// Check if the class can be found by its path
			if ( $class = self::getClassByPath( $className ) ) {

				// Check if the class instance already exists
				if ( ! isset( self::$instances[ $className ] ) ) {
					// Check if the class is already defined
					if ( ! class_exists( $className ) ) {
						// Include the class file
						self::includeClass( $class['dir'], $class['name'] );

						// Check if it's an abstract class
						$check    = new ReflectionClass( $className );
						$abstract = $check->isAbstract();
						if ( ! $abstract ) {
							// Instantiate the class and store it in the instances array
							self::$instances[ $className ] = new $className();
							if ( ! empty( $args ) ) {
								call_user_func_array( array( self::$instances[ $className ], '__construct' ), $args );
							}

							return self::$instances[ $className ];
						} else {
							// Mark abstract classes as true in instances array
							self::$instances[ $className ] = true;
						}

					}
				} else {
					// Return the existing instance
					return self::$instances[ $className ];
				}

			} else {

				// Stop all hooks on error
				defined( 'HMWP_DISABLE' ) || define( 'HMWP_DISABLE', true );

				// Show the file not found error
				HMWP_Classes_Error::showError( 'Class Not Found: ' . $className, 'danger' );

			}
		} catch (Exception $e) {
			HMWP_Classes_Error::showError( 'Class Not Found: ' . $className, 'danger' );
		}

        return false;
    }

    /**
     * Clear the class instance
     *
     * @param  string  $className  - The name of the class to instantiate
     * @param  array  $args  - Arguments to pass to the class constructor
     *
     * @return mixed - The class instance or false on failure
     * @throws Exception
     */
    public static function newInstance($className, $args = array())
    {

        // Check if the class can be found by its path
        if (self::getClassByPath($className)) {
            // Check if the class is already defined
            if (class_exists($className)) {
                // Initialize the new class
                self::$instances[$className] = new $className();
                if ( ! empty($args)) {
                    call_user_func_array(array(self::$instances[$className], '__construct'), $args);
                }

                return self::$instances[$className];
            } else {
                return self::getClass($className, $args);
            }
        }

        return false;
    }

    /**
     * Include Class if exists
     *
     * @param  string  $classDir  - Directory of the class file
     * @param  string  $className  - Name of the class file
     *
     * @throws Exception
     */
    private static function includeClass($classDir, $className)
    {

        $path = $classDir.$className.'.php';
        // Include the bundled class file if it exists.
        if (file_exists($path)) {
            include_once $path;
        }

    }

    /**
     * Check if the class is correctly set
     *
     * @param  string  $className  - The name of the class to check
     *
     * @return boolean - True if the class path is valid, False otherwise
     */
    private static function checkClassPath($className)
    {
        $path = preg_split('/[_]+/', $className);
        if (is_array($path) && count($path) > 1) {
            if (in_array(_HMWP_NAMESPACE_, $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the path of the class and name of the class
     *
     * @param  string  $className  - The name of the class
     *
     * @return array|false - Array with 'dir' and 'name', or false on failure
     */
    public static function getClassPath($className)
    {
        $dir = '';

        // Check if the class path is valid
        if (self::checkClassPath($className)) {

            $path = preg_split('/[_]+/', $className);
            for ($i = 1; $i < sizeof($path) - 1; $i++) {
                $dir .= strtolower($path[$i]).'/';
            }

            return array(
                'dir' => _HMWP_ROOT_DIR_.'/'.$dir, 'name' => $path[sizeof($path) - 1]
            );

        }

        return false;

    }

    /**
     * Get the valid class by path
     *
     * @param  string  $className  - The name of the class
     *
     * @return array|bool|false - Array with class directory and name, or false on failure
     */
    public static function getClassByPath($className)
    {

        // Get the class dir and name
        $class = self::getClassPath($className);

        // Return the class if the bundled file exists.
        // Local plugin file, so use file_exists() directly and avoid loading the
        // WordPress Filesystem API on the class-resolution hot path (see includeClass()).
        if (is_array($class) && file_exists($class['dir'].$class['name'].'.php')) {
            return $class;
        }

        return false;

    }

    /**
     * Instantiates the WordPress filesystem
     *
     * @return mixed
     */
    public static function initFilesystem()
    {
        // The WordPress filesystem.
        global $wp_filesystem;

        // Reuse the filesystem once it has been initialized.
        if (is_object($wp_filesystem) && is_a($wp_filesystem, 'WP_Filesystem_Base')) {
            return $wp_filesystem;
        }

        if ( ! function_exists('WP_Filesystem')) {
            include_once ABSPATH.'wp-admin/includes/file.php';
        }

        // Read the FTP/SSH credentials a site may define in wp-config.php, mirroring what WordPress core reads.
        $credentials = array();
        $constants   = array(
            'hostname'    => 'FTP_HOST',
            'username'    => 'FTP_USER',
            'password'    => 'FTP_PASS',
            'public_key'  => 'FTP_PUBKEY',
            'private_key' => 'FTP_PRIKEY',
        );
        foreach ($constants as $key => $constant) {
            if (defined($constant)) {
                $credentials[ $key ] = constant($constant);
            }
        }

        if ( ! empty($credentials['hostname'])) {
            // A remote filesystem (FTP/SSH) is explicitly configured: honor it.
            if ((defined('FTP_SSH') && FTP_SSH) || (defined('FS_METHOD') && 'ssh2' === FS_METHOD)) {
                $credentials['connection_type'] = 'ssh';
            } elseif (defined('FTP_SSL') && FTP_SSL) {
                $credentials['connection_type'] = 'ftps';
            }
            WP_Filesystem($credentials);
        }

        // Fall back to a direct connection when no valid remote credentials are
        // available (the common case, and always true under WP-CLI).
        if ( ! is_object($wp_filesystem) || ! is_a($wp_filesystem, 'WP_Filesystem_Base') || ! $wp_filesystem->connect()) {
            add_filter('filesystem_method', array(__CLASS__, 'getDirectFilesystemMethod'), 999);
            WP_Filesystem();
            remove_filter('filesystem_method', array(__CLASS__, 'getDirectFilesystemMethod'), 999);
        }

        // return the filesystem object
        return $wp_filesystem;
    }

    /**
     * Force the WordPress "direct" filesystem method.
     *
     * Used as a `filesystem_method` filter callback so the FTP/SSH handlers are
     * never selected when no remote credentials are available.
     *
     * @return string
     */
    public static function getDirectFilesystemMethod()
    {
        return 'direct';
    }

}
