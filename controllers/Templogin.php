<?php
/**
 * Temporary Login Class
 * Called on Temporary Logins
 *
 * @file The Temporary Logins file
 * @package HMWP/Templogin
 * @since 7.0.0
 */

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Controllers_Templogin extends HMWP_Classes_FrontController {

	/**
	 * Fires on the WP init hook (admin-side).
	 *
	 * Hides the Temporary Login menu and related features for users who are
	 * currently logged in via a temporary-login link, and applies their saved
	 * locale preference. Also checks whether the temporary login has expired.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function hookInit() {

		// Don't show the Temporary Login menu for temporary logged users
		if ( $this->model->isValidTempLogin( get_current_user_id() ) ) {

			add_filter( 'hmwp_menu', function( $menu ) {
				unset( $menu['hmwp_templogin'] );

				return $menu;
			} );

			add_filter( 'hmwp_features', function( $features ) {
				foreach ( $features as &$feature ) {
					if ( $feature['option'] == 'hmwp_templogin' ) {
						$feature['show'] = false;
					}
				}

				return $features;
			} );


			add_filter( 'locale', function( $locale ) {
				if ( $hmwp_locale = get_user_meta( get_current_user_id(), 'locale', true ) ) {
					if ( $hmwp_locale <> 'en_US' ) {
						return $hmwp_locale;
					}
				}

				return $locale;
			}, 1, 1 );
		}

		// First, check if the user is still active
		$this->checkTempLoginExpired();
	}

	/**
	 * Listen temporary login on load
	 *
	 * @return void
	 */
	public function hookFrontinit() {

		// First, check if the user is still active
		$this->checkTempLoginExpired();

		if ( HMWP_Classes_Tools::getValue( 'hmwp_token' ) <> '' ) {

			// Return is header was already sent
			if ( headers_sent() ) {
				return;
			}

			// Initialize the redirect
			$redirect_to = add_query_arg( 'hmwp_login', 'success', admin_url() );
			add_filter( 'hmwp_option_hmwp_hide_wplogin', '__return_false' );
			add_filter( 'hmwp_option_hmwp_hide_login', '__return_false' );

			// Check if token is set
			$token = sanitize_key( HMWP_Classes_Tools::getValue( 'hmwp_token' ) );

			if ( ! $user = $this->model->findUserByToken( $token ) ) {

				$redirect_to = home_url(); //redirect to home page

			} else {

				$do_login = true;
				if ( HMWP_Classes_Tools::isLoggedInUser() ) {
					if ( $user->ID !== get_current_user_id() ) {
						wp_logout();
					} else {
						$do_login = false;
					}
				}

				if ( $do_login ) {

					// Remove other filters on authenticate
					remove_all_filters( 'authenticate' );
					remove_all_actions( 'wp_login_failed' );

					// Disable brute force reCaptcha on temporary login
					add_filter( 'hmwp_option_brute_use_math', '__return_false' );
					add_filter( 'hmwp_option_brute_use_captcha', '__return_false' );
					add_filter( 'hmwp_option_brute_use_captcha_v3', '__return_false' );

					// Login process
					if ( ! wp_set_current_user( $user->ID, $user->login ) ) {
						wp_die( esc_html__( 'Could not login with this user.', 'hide-my-wp' ), esc_html__( 'Temporary Login', 'hide-my-wp' ), array( 'response' => 403 ) );
					}
					wp_set_auth_cookie( $user->ID, true );

					// Log current user login
					update_user_meta( $user->ID, '_hmwp_last_login', $this->model->gtmTimestamp() );

					// Add filter for success login
					do_action( 'hmwp_user_auth_success', $user, 'temporary_login' );

					// Set login count
					// If we already have a count, increment by 1
					if ( $login_count = get_user_meta( $user->ID, '_hmwp_login_count', true ) ) {
						$login_count ++;
					} else {
						$login_count = 1;
					}

					update_user_meta( $user->ID, '_hmwp_login_count', $login_count );
					do_action( 'wp_login', $user->login, $user );

					if ( $user->details->redirect_to <> '' ) {
						$redirect_to = $user->details->redirect_to;
					} elseif ( isset( $user->details->user_blog_id ) ) {
						$redirect_to = get_admin_url( $user->details->user_blog_id );
					}
				}

			}

			wp_safe_redirect( $redirect_to ); // Redirect to given url after successful login.
			exit();
		}

	}

	/**
	 * Check if the temporary login is still active
	 *
	 * @return void
	 */
	public function checkTempLoginExpired() {

		// Restrict unauthorized page access for temporary users
		if ( HMWP_Classes_Tools::isLoggedInUser() && ! HMWP_Classes_Tools::isAjax() ) {

			$user_id = get_current_user_id();
			if ( ! empty( $user_id ) && $this->model->isValidTempLogin( $user_id ) ) {


				if ( $this->model->isExpired( $user_id ) ) {

					wp_logout();
					wp_safe_redirect( home_url() );
					exit();

				} else {

					global $pagenow;
					$restricted_pages   = $this->model->getRestrictedPages();
					$restricted_actions = $this->model->getRestrictedActions();
					$page               = HMWP_Classes_Tools::getValue( 'page' );
					$action             = HMWP_Classes_Tools::getValue( 'action' );

					if ( $page <> '' && in_array( $page, $restricted_pages ) || ( ! empty( $pagenow ) && ( in_array( $pagenow, $restricted_pages ) ) ) || ( ! empty( $pagenow ) && ( 'users.php' === $pagenow && in_array( $action, $restricted_actions ) ) ) ) { //phpcs:ignore
						wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'hide-my-wp' ) );
					}

				}
			}
		}
	}

	/**
	 * Admin actions
	 */
	public function action() {
		parent::action();

		// If current user can't manage settings
		if ( ! HMWP_Classes_Tools::userCan( HMWP_CAPABILITY ) ) {
			return;
		}

		// If current user is temporary user
		if ( $this->model->isValidTempLogin( get_current_user_id() ) ) {
			return;
		}

		switch ( HMWP_Classes_Tools::getValue( 'action' ) ) {
			case 'hmwp_temploginsettings':

				HMWP_Classes_Tools::saveOptions( 'hmwp_templogin', HMWP_Classes_Tools::getValue( 'hmwp_templogin', 0 ) );
				HMWP_Classes_Tools::saveOptions( 'hmwp_templogin_role', HMWP_Classes_Tools::getValue( 'hmwp_templogin_role', 0 ) );
				HMWP_Classes_Tools::saveOptions( 'hmwp_templogin_redirect', HMWP_Classes_Tools::getValue( 'hmwp_templogin_redirect', '' ) );
				HMWP_Classes_Tools::saveOptions( 'hmwp_templogin_expires', HMWP_Classes_Tools::getValue( 'hmwp_templogin_expires', 'hour_after_access' ) );
				HMWP_Classes_Tools::saveOptions( 'hmwp_templogin_delete_uninstal', HMWP_Classes_Tools::getValue( 'hmwp_templogin_delete_uninstal', 0 ) );

				// Add action for later use
				do_action( 'hmwp_temploginsettings_saved' );

				HMWP_Classes_Error::setNotification( esc_html__( 'Saved', 'hide-my-wp' ), 'success' );

				break;
			case 'hmwp_templogin_new':
				$data = HMWP_Classes_Tools::getValue( 'hmwp_details', array() );

				if ( empty( $data['user_email'] ) ) {
					HMWP_Classes_Error::setNotification( esc_html__( 'Empty email address', 'hide-my-wp' ), 'danger', false );
				} elseif ( ! is_email( $data['user_email'] ) ) {
					HMWP_Classes_Error::setNotification( esc_html__( 'Invalid email address', 'hide-my-wp' ), 'danger', false );
				} elseif ( email_exists( $data['user_email'] ) ) {
					HMWP_Classes_Error::setNotification( esc_html__( 'Email address already exists', 'hide-my-wp' ), 'danger', false );
				}

				if ( ! HMWP_Classes_Error::isError() ) {

					$users = $this->model->getTempUsers();

					if ( count( $users ) >= 3 ) {
						HMWP_Classes_Error::setNotification( esc_html__( 'Free version allows only 3 temporary accounts.', 'hide-my-wp' ), 'danger', false );
						break;
					}

					$user = $this->model->createNewUser( $data );

					if ( isset( $user['error'] ) && isset( $user['message'] ) && $user['error'] ) {
						HMWP_Classes_Error::setNotification( $user['message'], 'danger', false );
					} else {
						HMWP_Classes_Error::setNotification( esc_html__( 'User successfully created.', 'hide-my-wp' ), 'success' );

						$user_id       = isset( $user['user_id'] ) ? $user['user_id'] : 0;
						$templogin_url = $this->model->getTempLoginUrl( $user_id );
						$templogin_url = '<span class="hmwp-clipboard-text"  style="max-width:50%" >' . $templogin_url . '</span> <i id="token_notification" class="fa fa-copy hmwp_clipboard_copy" data-clipboard-text="' . $templogin_url . '"></i>';

						HMWP_Classes_Error::setNotification( esc_html__( 'Temporary Login', 'hide-my-wp' ) . ': ' . $templogin_url, 'success' );
					}
				}

				break;

			case 'hmwp_templogin_update':
				$data            = HMWP_Classes_Tools::getValue( 'hmwp_details', array() );
				$data['user_id'] = absint( HMWP_Classes_Tools::getValue( 'user_id', 0 ) );
				HMWP_Classes_Error::clearErrors();

				if ( $data['user_id'] == 0 ) {
					HMWP_Classes_Error::setNotification( esc_html__( 'Could not detect the user', 'hide-my-wp' ), 'danger', false );
				} elseif ( ! $this->model->isValidTempLogin( $data['user_id'] ) ) {
					// This screen only manages temporary logins
					HMWP_Classes_Error::setNotification( esc_html__( 'This user is not a temporary login.', 'hide-my-wp' ), 'danger', false );
				}

				if ( ! HMWP_Classes_Error::isError() ) {
					//Update the user ... return user_id or array of error
					$user = $this->model->updateUser( $data );

					if ( isset( $user['error'] ) && isset( $user['message'] ) && $user['error'] ) {
						HMWP_Classes_Error::setNotification( $user['message'], 'danger', false );
					} else {
						HMWP_Classes_Error::setNotification( esc_html__( 'User successfully updated.', 'hide-my-wp' ), 'success' );
					}

				}

				break;

			case 'hmwp_templogin_block':
				$user_id = HMWP_Classes_Tools::getValue( 'user_id', 0 );

				if ( $this->model->updateLoginStatus( absint( $user_id ), 'disable' ) ) {
					HMWP_Classes_Error::setNotification( esc_html__( 'User successfully disabled.', 'hide-my-wp' ), 'success' );
				} else {
					HMWP_Classes_Error::setNotification( esc_html__( 'User could not be disabled.', 'hide-my-wp' ), 'danger', false );
				}
				break;

			case 'hmwp_templogin_activate':
				$user_id = HMWP_Classes_Tools::getValue( 'user_id', 0 );

				if ( $this->model->updateLoginStatus( absint( $user_id ), 'enable' ) ) {
					HMWP_Classes_Error::setNotification( esc_html__( 'User successfully activated.', 'hide-my-wp' ), 'success' );
				} else {
					HMWP_Classes_Error::setNotification( esc_html__( 'User could not be activated.', 'hide-my-wp' ), 'danger', false );
				}
				break;

			case 'hmwp_templogin_delete':
				$user_id = absint( HMWP_Classes_Tools::getValue( 'user_id', 0 ) );

				// Only temporary logins may be deleted from this screen.
				if ( ! $user_id || ! $this->model->isValidTempLogin( $user_id ) ) {
					HMWP_Classes_Error::setNotification( esc_html__( 'User could not be deleted.', 'hide-my-wp' ), 'danger', false );
					break;
				}

				//remove actions on remove_user_from_blog to avoid errors on other plugins
				remove_all_actions( 'remove_user_from_blog' );

				$delete_user = wp_delete_user( $user_id, get_current_user_id() );

				// delete user from Multisite network too!
				if ( HMWP_Classes_Tools::isMultisites() ) {

					// If it's a super admin, we can't directly delete user from network site.
					// We need to revoke super admin access first and then delete user
					if ( is_super_admin( $user_id ) ) {
						revoke_super_admin( $user_id );
					}

					$delete_user = wpmu_delete_user( $user_id );

				}

				if ( ! is_wp_error( $delete_user ) ) {
					HMWP_Classes_Error::setNotification( esc_html__( 'User successfully deleted.', 'hide-my-wp' ), 'success' );
				} else {
					HMWP_Classes_Error::setNotification( esc_html__( 'User could not be deleted.', 'hide-my-wp' ), 'danger', false );
				}
				break;


		}
	}


}
