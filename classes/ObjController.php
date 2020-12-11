<?php
defined('ABSPATH') || die('Cheatin\' uh?');

/**
 * The class creates object for plugin classes
 */
class HMW_Classes_ObjController {

    /** @var array of instances */
    public static $instances;

    /** @var array from core config */
    public static $config;

    /**
     * @param $className
     * @param array $args
     * @return bool|mixed
     */
    public static function getClass($className, $args = array()) {

        if ($class = self::getClassPath($className)) {
            if (!isset(self::$instances[$className])) {
                /* check if class is already defined */
                if (!class_exists($className) || $className == get_class()) {
                    try {
                        self::includeClass($class['dir'], $class['name']);

                        //check if abstract
                        $check = new ReflectionClass($className);
                        $abstract = $check->isAbstract();
                        if (!$abstract) {
                            self::$instances[$className] = new $className();
                            if (!empty($args)) {
                                call_user_func_array(array(self::$instances[$className], '__construct'), $args);
                            }
                            return self::$instances[$className];
                        } else {
                            self::$instances[$className] = true;
                        }
                    } catch (Exception $e) {
                    }
                }
            } else
                return self::$instances[$className];
        }
        return false;
    }

	/**
	 * Clear the class instance
	 *
	 * @param string $className
	 * @param array $args
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public static function newInstance( $className, $args = array() ) {
		if ( $class = self::getClassPath( $className ) ) {
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
     * @param $classDir
     * @param $className
     * @throws Exception
     */
    private static function includeClass($classDir, $className) {
        try {
            if (file_exists($classDir . $className . '.php')) {
                include_once($classDir . $className . '.php');
            }
        } catch (Exception $e) {
            throw new Exception('Controller Error: ' . $e->getMessage());
        }
    }

    /**
     * Get the class domain
     * @param $className
     * @param array $args
     * @return mixed
     * @throws Exception
     */
    public static function getDomain($className, $args = array()) {
        if ($class = self::getClassPath($className)) {

            /* check if class is already defined */

            self::includeClass($class['dir'], $class['name']);
            return new $className($args);
        }

        throw new Exception('Could not create domain: ' . $className);
    }


    /**
     * Check if the class is correctly set
     *
     * @param string $className
     * @return boolean
     */
    private static function checkClassPath($className) {
        $path = preg_split('/[_]+/', $className);
        if (is_array($path) && count($path) > 1) {
            if (in_array(_HMW_NAMESPACE_, $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the path of the class and name of the class
     *
     * @param string $className
     * @return array | boolean
     * array(
     * dir - absolute path of the class
     * name - the name of the file
     * }
     */
    public static function getClassPath($className) {
        $dir = '';

        if (self::checkClassPath($className)) {
            $path = preg_split('/[_]+/', $className);
            for ($i = 1; $i < sizeof($path) - 1; $i++)
                $dir .= strtolower($path[$i]) . '/';

            $class = array('dir' => _HMW_ROOT_DIR_ . '/' . $dir,
                'name' => $path[sizeof($path) - 1]);

            if (file_exists($class['dir'] . $class['name'] . '.php')) {
                return $class;
            }
        }
        return false;
    }

}