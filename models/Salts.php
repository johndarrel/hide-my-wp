<?php
/**
 * Change Salts Values
 *
 * @file  The Salts  file
 * @package HMWP/Salts
 * @since 7.3.0
 */
defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Models_Salts {

	protected $defines = array();


	public function __construct() {

		$defines = array(
			'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT',
			'LOGGED_IN_SALT', 'NONCE_SALT',
		);

		$this->defines = apply_filters( 'hmwp_salts', $defines );
	}


	/**
	 * Check if the Salt Values are valid
	 *
	 * @return bool
	 */
	public function validateSalts() {

		foreach ( $this->defines as $define ) {
			if ( ! defined( $define ) ) {
				return false;
			}

			$value = constant( $define );

			if ( ! $value || 'put your unique phrase here' === $value ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Add the new salt values in config file
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function generateSalts() {
		$return      = false;
		$config_file = HMWP_Classes_Tools::getConfigFile();

		/** @var HMWP_Models_Rules $rulesModel */
		$rulesModel = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rules' );

		//filesystem
		$wp_filesystem = HMWP_Classes_ObjController::initFilesystem();
		$content       = $wp_filesystem->get_contents( $config_file );

		foreach ( $this->defines as $define ) {
			if ( empty( $salts ) ) {
				$salts = $this->getNewSalts();
			}

			$salt = array_pop( $salts );

			if ( empty( $salt ) ) {
				$salt = wp_generate_password( 64, true, true );
			}

			$salt  = str_replace( '$', '\\$', $salt );
			$regex = "/(define\s*\(\s*(['\"])$define\\2\s*,\s*)(['\"]).+?\\3(\s*\)\s*;)/";

			$content = preg_replace( $regex, "\${1}'$salt'\${4}", $content );

		}

		return $wp_filesystem->put_contents( $config_file, $content );
	}

	/**
	 * Generate new salt values
	 *
	 * @return array|string[]
	 */
	public function getNewSalts() {

		try {
			$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_ []{}<>~`+=,.;:/?|';
			$max   = strlen( $chars ) - 1;
			for ( $i = 0; $i < 8; $i ++ ) {
				$key = '';
				for ( $j = 0; $j < 64; $j ++ ) {
					$key .= substr( $chars, random_int( 0, $max ), 1 );
				}
				$secret_keys[] = $key;
			}
		} catch ( Exception $ex ) {
			$secret_keys = wp_remote_get( 'https://api.wordpress.org/secret-key/1.1/salt/' );

			if ( is_wp_error( $secret_keys ) ) {
				$secret_keys = array();
				for ( $i = 0; $i < 8; $i ++ ) {
					$secret_keys[] = wp_generate_password( 64, true, true );
				}
			} else {
				$secret_keys = explode( "\n", wp_remote_retrieve_body( $secret_keys ) );
				foreach ( $secret_keys as $k => $v ) {
					$secret_keys[ $k ] = substr( $v, 28, 64 );
				}
			}
		}

		return $secret_keys;
	}

}
