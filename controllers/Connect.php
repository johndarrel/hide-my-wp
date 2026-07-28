<?php
/**
 * Cloud Connect
 * Called for the Token Activation
 *
 * @package HMWP/Connect
 * @file The Cloud Connect file
 */

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Controllers_Connect extends HMWP_Classes_FrontController {

	/**
	 * Call Account API Server
	 *
	 * @param  string  $email
	 * @param  string  $redirect_to
	 *
	 * @return array|mixed|void
	 */
	public static function checkAccountApi( $email = null, $redirect_to = '' ) {

		$check   = array();
		$howtolessons = HMWP_Classes_Tools::getValue( 'howtolessons', 1 );
		$domain  = ( HMWP_Classes_Tools::isMultisites() && defined( 'BLOG_ID_CURRENT_SITE' ) ) ? get_home_url( BLOG_ID_CURRENT_SITE ) : home_url();

		if ( isset( $email ) && $email <> '' ) {
			$args     = array(
				'email'        => $email,
				'url'          => $domain,
				'howtolessons' => (int) $howtolessons,
				'monitor'      => 0,
				'source'       => 'hide-my-wp'
			);
			$response = HMWP_Classes_Tools::hmwp_remote_get( _HMWP_API_SITE_ . '/api/free/token', $args, array( 'timeout' => 10 ) );
		} elseif ( HMWP_Classes_Tools::getOption( 'hmwp_token' ) ) {
			$args     = array(
				'token'        => HMWP_Classes_Tools::getOption( 'hmwp_token' ),
				'url'          => $domain,
				'howtolessons' => (int) $howtolessons,
				'source'       => 'hide-my-wp'
			);
			$response = HMWP_Classes_Tools::hmwp_remote_get( _HMWP_API_SITE_ . '/api/free/token', $args, array( 'timeout' => 10 ) );
		} else {
			return $check;
		}

		if ( $response && $response = json_decode( $response, true ) ) {

			HMWP_Classes_Tools::saveOptions( 'hmwp_token', ( $response['token'] ?? 0 ) );
			HMWP_Classes_Tools::saveOptions( 'api_token', ( $response['api_token'] ?? false ) );
			HMWP_Classes_Tools::saveOptions( 'error', isset( $response['error'] ) );

			if ( ! isset( $response['error'] ) ) {
				if ( $redirect_to <> '' ) {
					wp_safe_redirect( $redirect_to );
					exit();
				}
			} elseif ( isset( $response['message'] ) ) {
				HMWP_Classes_Error::setNotification( $response['message'], 'notice', false );
			}
		} else {
			HMWP_Classes_Error::setNotification(
				sprintf(
				/* translators: %s: Account URL link. */
					esc_html__( 'CONNECTION ERROR! Make sure your website can access: %s', 'hide-my-wp' ),
					'<a href="' . esc_url( _HMWP_ACCOUNT_SITE_ ) . '" target="_blank">' . esc_html( _HMWP_ACCOUNT_SITE_ ) . '</a>'
				)
				. '<br />'
				. sprintf(
				/* translators: 1: Opening strong tag, 2: Closing strong tag, 3: Opening link tag, 4: Closing link tag. */
					esc_html__( 'Ask your host to check outbound blocks and whitelist IP %1$s 116.203.193.175 %2$s for remote access! %3$s Read More %4$s', 'hide-my-wp' ),
					'<strong>',
					'</strong>',
					'<a href="' . esc_url( HMWP_Classes_Tools::getOption( 'hmwp_plugin_website' ) . '/kb/connection-error-make-sure-your-website-can-access-https-account-wpghost-com/' ) . '" target="_blank">',
					'</a>'
				),
				'notice',
				false
			);
		}

		return $response;

	}

	/**
	 * Called when an action is triggered
	 *
	 * @throws Exception
	 */
	public function action() {
		parent::action();

		// Check user permission.
		if ( ! HMWP_Classes_Tools::userCan( HMWP_CAPABILITY ) ) {
			return;
		}

		switch ( HMWP_Classes_Tools::getValue( 'action' ) ) {

			case 'hmwp_connect':
				// Connect to API with the Email
				$email = sanitize_email( HMWP_Classes_Tools::getValue( 'hmwp_email', '' ) );

				if ( $email <> '' ) {
					$redirect_to = HMWP_Classes_Tools::getSettingsUrl();
					$this->checkAccountApi( $email, $redirect_to );
				} else {
					HMWP_Classes_Error::setNotification( __( 'ERROR! Please make sure you use the right email to activate the plugin.', 'hide-my-wp' ) . " <br /> " );
				}
				break;

			case 'hmwp_reconnect':

				// Clear the current connection and start over
				$redirect_to = HMWP_Classes_Tools::getSettingsUrl();

				HMWP_Classes_Tools::saveOptions( 'hmwp_token', false );
				HMWP_Classes_Tools::saveOptions( 'error', false );

				wp_safe_redirect( $redirect_to );
				exit();

			case 'hmwp_dont_connect':

				// If skipped activation, generate a random local token
				$redirect_to = HMWP_Classes_Tools::getSettingsUrl();

				HMWP_Classes_Tools::saveOptions( 'hmwp_token', md5( home_url() ) );
				HMWP_Classes_Tools::saveOptions( 'error', false );

				// Save the working options into backup
				HMWP_Classes_Tools::saveOptionsBackup();

				wp_safe_redirect( $redirect_to );
				exit();

		}


	}

}
