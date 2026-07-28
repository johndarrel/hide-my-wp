<?php
/**
 * Compatibility Class
 *
 * @file The ReallySimpleSsl Model file
 * @package HMWP/Compatibility/ReallySimpleSsl
 * @since 7.0.0
 */

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Models_Compatibility_ReallySimpleSsl extends HMWP_Models_Compatibility_Abstract {

	public function hookAdmin() {
		add_action( 'hmwp_flushed_rewrites', array( $this, 'checkSimpleSSLRewrites' ) );
	}

	/**
	 * Add rules to be compatible with Simple SSL plugins
	 */
	public function checkSimpleSSLRewrites() {

		//Initialize WordPress Filesystem
		$wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

		try {
			$options = get_option( 'rlrsssl_options' );

			if ( isset( $options['htaccess_redirect'] ) && $options['htaccess_redirect'] ) {
				$config_file = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rules' )->getConfFile();

				if ( $config_file <> '' && $wp_filesystem->exists( $config_file ) ) {
					$htaccess = $wp_filesystem->get_contents( $config_file );
					preg_match( "/#\s?BEGIN\s?rlrssslReallySimpleSSL.*?#\s?END\s?rlrssslReallySimpleSSL/s", $htaccess, $match );

					if ( ! empty( $match[0] ) ) {
						$htaccess = preg_replace( "/#\s?BEGIN\s?rlrssslReallySimpleSSL.*?#\s?END\s?rlrssslReallySimpleSSL/s", "", $htaccess );
						$htaccess = $match[0] . PHP_EOL . $htaccess;
						$htaccess = preg_replace( "/\n+/", "\n", $htaccess );
						$wp_filesystem->put_contents( $config_file, $htaccess );
					}
				}

			}
		} catch ( Exception $e ) {
		}
	}

}
