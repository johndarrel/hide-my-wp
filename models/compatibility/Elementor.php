<?php
/**
 * Compatibility Class
 *
 * @file The Elementor Model file
 * @package HMWP/Compatibility/Elementor
 * @since 8.0.0
 */

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Models_Compatibility_Elementor extends HMWP_Models_Compatibility_Abstract {

	public function hookFrontend() {

		//Check if login brute force protection is loaded on element form
		if ( HMWP_Classes_Tools::getOption( 'hmwp_bruteforce' ) ) {
			add_action( 'elementor/controls/register', array( $this, 'fix_login_form' ), 11, 1 );
		}

	}

	public function fix_login_form( $controls ) {

		if ( headers_sent() ) {
			return;
		}

		if ( HMWP_Classes_Tools::getDefault( 'hmwp_login_url' ) <> HMWP_Classes_Tools::getOption( 'hmwp_login_url' ) ) {

			$url       = ( isset( $_SERVER['REQUEST_URI'] ) ? untrailingslashit( strtok( wp_unslash( $_SERVER["REQUEST_URI"] ), '?' ) ) : false ); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$http_post = ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' == $_SERVER['REQUEST_METHOD'] );

			if ( $url && ! $http_post && ! HMWP_Classes_Tools::isLoggedInUser() ) {

				if ( ! isset( $_COOKIE['elementor_pro_login'] ) ) {
					$login_tck = md5( time() . wp_rand( 111111, 999999 ) );
					setcookie( 'elementor_pro_login', $login_tck, time() + ( 86400 * 30 ), "/" );
				} else {
					$login_tck = sanitize_key($_COOKIE['elementor_pro_login']);
				}

				add_filter( 'site_url', function( $url, $path ) use ( $login_tck ) {

					if ( $path == HMWP_Classes_Tools::getDefault( 'hmwp_login_url' ) ) {
						return add_query_arg( 'ltk', $login_tck, $url );
					}

					return $url;
				}, PHP_INT_MAX, 2 );

			}

		}

	}


}
