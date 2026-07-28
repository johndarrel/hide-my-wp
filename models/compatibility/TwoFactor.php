<?php
/**
 * Compatibility Class
 *
 * @file The TwoFactor Model file
 * @package HMWP/Compatibility/TwoFactor
 * @since 7.1.0
 */

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Models_Compatibility_TwoFactor extends HMWP_Models_Compatibility_Abstract {

	/**
	 * @throws Exception
	 */
	public function __construct() {
		parent::__construct();

		add_action( 'hmwp_files_handle_login', function( $url ) {

			if ( HMWP_Classes_Tools::getvalue( 'action' ) === 'validate_2fa' ) {

				if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] === 'POST' ) {

					add_filter( 'hmwp_process_hide_urls', '__return_false' );
					add_filter( 'hmwp_process_init', '__return_false' );

					$response = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Files' )->postRequest( $url );

					if ( $response ) {
						header( "HTTP/1.1 200 OK" );
						if ( ! empty( $response['headers'] ) ) {
							foreach ( $response['headers'] as $header ) {
								header( $header );
							}
						}

						//Echo the html file content
						echo $response['body']; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						exit();
					}
				}
			}

		}, PHP_INT_MAX, 1 );

	}


}
