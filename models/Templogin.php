<?php
/**
 * Temp Login Model
 *
 * @file  The Temp Login file
 * @package HMWP/Temp Login
 * @since 7.0.0
 */
defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Models_Templogin {

	/** @var array the user session expire options */
	public $expires;

	/** @var string the user session expire timestamp */
	const USER_SESSION_EXPIRE = '_hmwp_expire';

	/** @var string the user token key */
	const USER_TOKEN_KEY = '_hmwp_token';

	/** @var string the user type */
	const USER_TEMP_TYPE = '_hmwp_user';

	/** @var string the user-last login timestamp */
	const USER_LAST_LOGIN = '_hmwp_last_login';

	/** @var string the user redirect to */
	const USER_REDIRECT = '_hmwp_redirect_to';

	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	public function init() {
		$this->expires = array(
			'hour'     => array( 'label' => esc_html__( 'One Hour', 'hide-my-wp' ), 'timestamp' => HOUR_IN_SECONDS ),
			'3_hours'  => array( 'label' => esc_html__( 'Three Hours', 'hide-my-wp' ), 'timestamp' => HOUR_IN_SECONDS * 3 ),
			'day'      => array( 'label' => esc_html__( 'One Day', 'hide-my-wp' ), 'timestamp' => DAY_IN_SECONDS ),
			'3_days'   => array( 'label' => esc_html__( 'Three Days', 'hide-my-wp' ), 'timestamp' => DAY_IN_SECONDS * 3 ),
			'week'     => array( 'label' => esc_html__( 'One Week', 'hide-my-wp' ), 'timestamp' => WEEK_IN_SECONDS ),
			'month'    => array( 'label' => esc_html__( 'One Month', 'hide-my-wp' ), 'timestamp' => MONTH_IN_SECONDS ),
			'halfyear' => array( 'label' => esc_html__( 'Six Months', 'hide-my-wp' ), 'timestamp' => ( 6 * MONTH_IN_SECONDS ) ),
			'year'     => array( 'label' => esc_html__( 'One Year', 'hide-my-wp' ), 'timestamp' => YEAR_IN_SECONDS ),
		);
	}

	/**
	 * Get valid temporary user based on token
	 *
	 * @param string $token
	 *
	 * @return array|bool
	 * @since 7.0
	 *
	 */
	public function findUserByToken( $token = '' ) {

		if ( empty( $token ) ) {
			return false;
		}

		$args = array(
			'fields'  => 'all',
			'order'   => 'DESC',
			'orderby' => 'meta_value',
			'meta_query' => array( //phpcs:ignore
				'relation' => 'AND',
				array(
					'key'     => self::USER_SESSION_EXPIRE,
					'compare' => 'EXISTS',
				),
				array(
					'key'     => self::USER_TOKEN_KEY,
					'value'   => sanitize_text_field( $token ),
					'compare' => '=',
				),
			),
		);

		if ( HMWP_Classes_Tools::isMultisites() ) {

			//initiate users
			$users = array();

			$current_blog_id = get_current_blog_id();
			// Now, add this user to all sites
			$sites = get_sites( array( 'number' => 10000, 'public' => 1, 'deleted' => 0, ) );
			if ( ! empty( $sites ) && count( $sites ) > 0 ) {
				foreach ( $sites as $site ) {
					switch_to_blog( $site->blog_id );
					if ( $sub_query = new WP_User_Query( $args ) ) {
						$sub_users = $sub_query->get_results();
						$users     = array_merge( $users, $sub_users );
					}
				}
			}
			switch_to_blog( $current_blog_id );
		} else {
			$query = new WP_User_Query( $args );
			$users = $query->get_results();
		}

		if ( empty( $users ) ) {
			return false;
		}

		foreach ( $users as $user ) {
			if ( ! $expire = HMWP_Classes_Tools::getUserMeta( self::USER_SESSION_EXPIRE, $user->ID ) ) {
				return false;
			}

			if ( is_numeric( $expire ) && $expire <= $this->gtmTimestamp() ) {
				return false;
			} elseif ( $expire <= $this->gtmTimestamp() ) {
				$timestamp = ! empty( $this->expires[ $expire ] ) ? $this->expires[ $expire ]['timestamp'] : 0;
				HMWP_Classes_Tools::saveUserMeta( self::USER_SESSION_EXPIRE, $this->gtmTimestamp() + $timestamp, $user->ID );
			}

			$user->details = $this->getUserDetails( $user );

			return $user;
		}

		return false;

	}


	/**
	 * Get user temp login details
	 *
	 * @param $user
	 *
	 * @return mixed
	 *
	 * @since 7.0
	 */
	public function getUserDetails( $user ) {
		$details = array();

		if ( ! $user instanceof WP_User ) {
			return json_decode( wp_json_encode( $details ) );
		}

		$details['redirect_to']   = HMWP_Classes_Tools::getUserMeta( self::USER_REDIRECT, $user->ID );
		$details['expire']        = HMWP_Classes_Tools::getUserMeta( self::USER_SESSION_EXPIRE, $user->ID );
		$details['locale']        = HMWP_Classes_Tools::getUserMeta( 'locale', $user->ID );
		$details['templogin_url'] = $this->getTempLoginUrl( $user->ID );

		$details['last_login_time'] = HMWP_Classes_Tools::getUserMeta( self::USER_LAST_LOGIN, $user->ID );
		$details['last_login']      = esc_html__( 'Not yet logged in', 'hide-my-wp' );
		if ( ! empty( $details['last_login_time'] ) ) {
			$details['last_login'] = $this->timeElapsed( $details['last_login_time'], true );
		}

		$details['status'] = 'Active';
		if ( $this->isExpired( $user->ID ) ) {
			$details['status'] = 'Expired';
		}
		$details['is_active'] = 'active' === strtolower( $details['status'] );

		$details['user_role']      = '';
		$details['user_role_name'] = '';

		if ( HMWP_Classes_Tools::isMultisites() && is_super_admin( $user->ID ) ) {
			$details['user_role']      = 'super_admin';
			$details['user_role_name'] = esc_html__( 'Super Admin', 'hide-my-wp' );
		} else {
			global $wpdb;

			$capabilities = $user->{$wpdb->prefix . 'capabilities'};

			if ( HMWP_Classes_Tools::isMultisites() ) {
				if ( $blog_id = HMWP_Classes_Tools::getUserMeta( 'primary_blog', $user->ID ) ) {
					if ( defined( 'BLOG_ID_CURRENT_SITE' ) ) {
						if ( BLOG_ID_CURRENT_SITE <> $blog_id ) {
							$capabilities            = $user->{$wpdb->prefix . $blog_id . '_' . 'capabilities'};
							$details['user_blog_id'] = $blog_id;
						}
					}
				}
			}

			$wp_roles = new WP_Roles();
			if ( ! empty( $capabilities ) ) {
				foreach ( $wp_roles->role_names as $role => $name ) {
					if ( array_key_exists( $role, $capabilities ) ) {
						$details['user_role']      = $role;
						$details['user_role_name'] = $name;
					}
				}
			}

		}

		return json_decode( wp_json_encode( $details ) );
	}

	/**
	 * Create a Temporary user
	 *
	 * @return array
	 *
	 * @since 7.0
	 */
	public function createNewUser( $data ) {

		$result = array(
			'error' => true
		);

		$expire      = ! empty( $data['expire'] ) ? $data['expire'] : 'day';
		$blog_id     = $data['blog_id'] ?? false;
		$super_admin = ( $data['super_admin'] ?? false ) && is_super_admin();
		$password    = HMWP_Classes_Tools::generateRandomString();
		$username    = $this->createUsername( $data );
		$first_name  = isset( $data['first_name'] ) ? sanitize_text_field( $data['first_name'] ) : '';
		$last_name   = isset( $data['last_name'] ) ? sanitize_text_field( $data['last_name'] ) : '';
		$email       = isset( $data['user_email'] ) ? sanitize_email( $data['user_email'] ) : '';
		$role        = $this->sanitizeRole( isset( $data['user_role'] ) ? $data['user_role'] : '' );
		$redirect_to = ! empty( $data['redirect_to'] ) ? sanitize_text_field( $data['redirect_to'] ) : '';
		$user_args   = array(
			'first_name' => $first_name, 'last_name' => $last_name, 'user_login' => $username, 'user_pass' => $password,
			'user_email' => $email, 'role' => $role,
		);

		if ( $username <> '' && username_exists( $username ) ) {
			$result['errcode'] = 'username_exists';
			$result['message'] = esc_html__( 'Email address already exists', 'hide-my-wp' );
		} elseif ( $email <> '' && email_exists( $email ) ) {
			$result['errcode'] = 'email_exists';
			$result['message'] = esc_html__( 'Email address already exists', 'hide-my-wp' );
		} else {

			try {
				$user_id = wp_insert_user( $user_args );

				if ( is_wp_error( $user_id ) ) {
					$code = $user_id->get_error_code();

					$result['errcode'] = $code;
					$result['message'] = $user_id->get_error_message( $code );

				} else {

					if ( HMWP_Classes_Tools::isMultisites() ) {

						if ( $super_admin ) {
							// Grant super admin access to this temporary users
							grant_super_admin( (int) $user_id );

							// Now, add this user to all sites
							$sites = get_sites( array( 'number' => 10000, 'public' => 1, 'deleted' => 0, ) );

							if ( ! empty( $sites ) && count( $sites ) > 0 ) {
								foreach ( $sites as $site ) {
									// If user is not already member of blog? Add into this blog
									$this->createMemberWithRole( (int) $site->blog_id, (int) $user_id, 'administrator' );
								}
							}
						} elseif ( $blog_id ) {

							// If the user was created on the current site but should belong to another site, remove from current site.
							$current_blog_id = get_current_blog_id();
							if ( $current_blog_id && (int) $current_blog_id !== (int) $blog_id ) {
								remove_user_from_blog( (int)  $user_id, (int) $current_blog_id );
							}

							// Add and enforce role on the target blog (role is checked in that blog context).
							$this->createMemberWithRole( (int) $blog_id, (int) $user_id, $role );
						}

					}

					HMWP_Classes_Tools::saveUserMeta( self::USER_TEMP_TYPE, true, $user_id );
					HMWP_Classes_Tools::saveUserMeta( '_hmwp_created', $this->gtmTimestamp(), $user_id );
					HMWP_Classes_Tools::saveUserMeta( self::USER_SESSION_EXPIRE, $expire, $user_id );
					HMWP_Classes_Tools::saveUserMeta( self::USER_TOKEN_KEY, $this->generateToken( $user_id ), $user_id );
					HMWP_Classes_Tools::saveUserMeta( self::USER_REDIRECT, $redirect_to, $user_id );

					//set locale
					$locale = ! empty( $data['locale'] ) ? $data['locale'] : 'en_US';
					HMWP_Classes_Tools::saveUserMeta( 'locale', $locale, $user_id );

					$result['error']   = false;
					$result['user_id'] = $user_id;
				}
			} catch ( Exception $e ) {
				$result['errcode'] = 'invalid_user';
				$result['message'] = esc_html__( 'User could not be added', 'hide-my-wp' );
			}
		}

		return $result;

	}

	/**
	 * Creates a membership for a user with a specified role on a target blog.
	 *
	 * @param int $target_blog_id The ID of the target blog.
	 * @param int $target_user_id The ID of the target user.
	 * @param string $target_role The desired role for the user on the target blog.
	 *
	 * @return bool|WP_Error True on success, or a WP_Error object on failure.
	 */
    private function createMemberWithRole( $target_blog_id, $target_user_id, $target_role ) {

		$target_blog_id = (int) $target_blog_id;
		$target_user_id = (int) $target_user_id;
		$target_role    = sanitize_key( (string) $target_role );

		if ( $target_role === '' ) {
			$target_role = 'subscriber';
		}

		switch_to_blog( $target_blog_id );

		$roles = wp_roles();

		// If role not found in this blog context, try to restore defaults.
		if ( ! $roles || ! $roles->is_role( $target_role ) ) {
			if ( function_exists( 'populate_roles' ) ) {
				populate_roles();
				$roles = wp_roles();
			}
		}

		// Fallback to an existing role if still missing.
		if ( ! $roles || ! $roles->is_role( $target_role ) ) {
			$editable = array_keys( get_editable_roles() );
			if ( in_array( 'subscriber', $editable, true ) ) {
				$target_role = 'subscriber';
			} elseif ( ! empty( $editable ) ) {
				$target_role = $editable[0];
			} else {
				$target_role = 'subscriber';
			}
		}

		// Add membership if needed.
		if ( ! is_user_member_of_blog( $target_user_id, $target_blog_id ) ) {
			$added = add_user_to_blog( $target_blog_id, $target_user_id, $target_role );
			if ( is_wp_error( $added ) ) {
				restore_current_blog();
				return $added;
			}
		}

		// Force role in the correct blog context (prevents "no role" edge cases).
		$u = new WP_User( $target_user_id );
		if ( method_exists( $u, 'for_site' ) ) {
			$u->for_site( $target_blog_id );
		}
		$u->set_role( $target_role );

		restore_current_blog();
		return true;
	}

	/**
	 * Create a ranadom username for the temporary user
	 *
	 * @param array $data
	 *
	 * @return string
	 * @since 7.0
	 */
	public function createUsername( $data ) {

		$first_name = isset( $data['user_first_name'] ) ? $data['user_first_name'] : '';
		$last_name  = isset( $data['user_last_name'] ) ? $data['user_last_name'] : '';
		$email      = isset( $data['user_email'] ) ? $data['user_email'] : '';

		$name = '';
		if ( ! empty( $first_name ) || ! empty( $last_name ) ) {
			$name = str_replace( array( '.', '+' ), '', strtolower( trim( $first_name . $last_name ) ) );
		} else {
			if ( ! empty( $email ) ) {
				$explode = explode( '@', $email );
				$name    = str_replace( array( '.', '+' ), '', $explode[0] );
			}
		}

		if ( username_exists( $name ) ) {
			$name = $name . substr( uniqid( '', true ), - 6 );
		}

		$username = sanitize_user( $name, true );

		/**
		 * We are generating WordPress username from First Name & Last Name fields.
		 * When First Name or Last Name comes with non latin words, generated username
		 * is non latin and sanitize_user function discrad it and user is not being
		 * generated.
		 *
		 * To avoid this, if this situation occurs, we are generating random username
		 * for this user.
		 */
		if ( empty( $username ) ) {
			$username = HMWP_Classes_Tools::generateRandomString();
		}

		return sanitize_user( $username, true );
	}

	/**
	 * Update user
	 *
	 * @param array $data
	 *
	 * @return array|int|WP_Error
	 * @since 7.0
	 */
	public function updateUser( $data ) {

		$expire      = ! empty( $data['expire'] ) ? $data['expire'] : 'day';
		$blog_id     = $data['blog_id'] ?? false;
		$super_admin = ( $data['super_admin'] ?? false ) && is_super_admin();
		$first_name  = isset( $data['first_name'] ) ? sanitize_text_field( $data['first_name'] ) : '';
		$last_name   = isset( $data['last_name'] ) ? sanitize_text_field( $data['last_name'] ) : '';
		$redirect_to = isset( $data['redirect_to'] ) ? sanitize_text_field( $data['redirect_to'] ) : '';
		$role        = $this->sanitizeRole( isset( $data['user_role'] ) ? $data['user_role'] : '' );
		$user_args   = array(
			'first_name' => $first_name, 'last_name' => $last_name, 'role' => $role, 'ID' => $data['user_id']
		);

		$user_id = wp_update_user( $user_args );

		if ( is_wp_error( $user_id ) ) {
			$code = $user_id->get_error_code();

			return array(
				'error' => true, 'errcode' => $code, 'message' => $user_id->get_error_message( $code ),
			);
		}

		if ( HMWP_Classes_Tools::isMultisites() ) {
			if ( $super_admin ) {
				grant_super_admin( $user_id );
			} elseif ( $blog_id ) {
				$sites = get_sites( array( 'number' => 10000, 'public' => 1, 'deleted' => 0, ) );

				//if the website was changed for the current user
				if ( ! empty( $sites ) && count( $sites ) > 0 ) {
					foreach ( $sites as $site ) {
						if ( $site->blog_id <> $blog_id ) {
							// If user is not already member of blog? Add into this blog
							if ( is_user_member_of_blog( (int) $user_id, (int) $site->blog_id ) ) {
								remove_user_from_blog( (int) $user_id, (int) $site->blog_id );
							}
						}
					}
				}

				// If user is not already member of blog? Add into this blog
				if ( ! is_user_member_of_blog( (int) $user_id, (int) $blog_id ) ) {
					$this->createMemberWithRole( (int) $blog_id, (int) $user_id, $role );
				}
			}
		}

		HMWP_Classes_Tools::saveUserMeta( '_hmwp_updated', $this->gtmTimestamp(), $user_id );
		HMWP_Classes_Tools::saveUserMeta( self::USER_SESSION_EXPIRE, $expire, $user_id );
		HMWP_Classes_Tools::saveUserMeta( self::USER_REDIRECT, $redirect_to, $user_id );

		//set locale
		$locale = ! empty( $data['locale'] ) ? $data['locale'] : 'en_US';
		HMWP_Classes_Tools::saveUserMeta( 'locale', $locale, $user_id );

		return $user_id;

	}

	/**
	 * Get the expiration time based on string
	 *
	 * @param string $expire
	 * @param string $date
	 *
	 * @return false|float|int
	 * @since 7.0
	 *
	 */
	public function getUserExpireTime( $expire = 'day', $date = '' ) {

		$expire = in_array( $expire, array_keys( $this->expires ) ) ? $expire : 'day';

		$current_timestamp = $this->gtmTimestamp();
		$timestamp         = $this->expires[ $expire ]['timestamp'];

		return $current_timestamp + floatval( $timestamp );

	}

	/**
	 * Get current GMT date time
	 *
	 * @return false|int
	 * @since 7.0
	 *
	 */
	public function gtmTimestamp() {
		return strtotime( gmdate( 'Y-m-d H:i:s', time() ) );
	}

	/**
	 * Get Temporary Logins
	 *
	 * @param string $role
	 *
	 * @return array|bool
	 * @since 7.0
	 *
	 */
	public function getTempUsers( $role = '' ) {

		$args = array(
			'fields'  => 'all',
			'order'   => 'DESC',
			'orderby' => 'meta_value',
			'meta_query' => array( //phpcs:ignore
				'relation' => 'AND',
				array(
					'key'   => self::USER_SESSION_EXPIRE,
					'compare' => 'EXISTS',
				),
				array(
					'key'   => self::USER_TEMP_TYPE,
					'value' => 1,
				),
			),
		);

		if ( ! empty( $role ) ) {
			$args['role'] = $role;
		}

		if ( HMWP_Classes_Tools::isMultisites() ) {
			$users           = array();
			$current_blog_id = get_current_blog_id();
			// Now, add this user to all sites
			$sites = get_sites( array( 'number' => 10000, 'public' => 1, 'deleted' => 0, ) );
			if ( ! empty( $sites ) && count( $sites ) > 0 ) {
				foreach ( $sites as $site ) {
					switch_to_blog( $site->blog_id );
					if ( $sub_query = new WP_User_Query( $args ) ) {
						$sub_users = $sub_query->get_results();
						$users     = array_merge( $users, $sub_users );
					}
				}
			}
			switch_to_blog( $current_blog_id );
		} else {
			$query = new WP_User_Query( $args );
			$users = $query->get_results();
		}

		return $users;

	}

	/**
	 * Get the redable time elapsed string
	 *
	 * @param int $time
	 * @param bool $ago
	 *
	 * @return string
	 * @since 7.0
	 *
	 */
	public function timeElapsed( $time, $ago = false ) {

		if ( is_numeric( $time ) ) {

			if ( $ago ) {
				$etime = $this->gtmTimestamp() - $time;
			} else {
				$etime = $time - $this->gtmTimestamp();
			}

			if ( $etime < 1 ) {
				return esc_html__( 'Expired', 'hide-my-wp' );
			}

			$a = array(
				// 365 * 24 * 60 * 60 => 'year',
				// 30 * 24 * 60 * 60 => 'month',
				24 * 60 * 60 => 'day',
				60 * 60      => 'hour',
				60           => 'minute',
				1            => 'second',
			);

			foreach ( $a as $secs => $str ) {
				$d = $etime / $secs;

				if ( $d >= 1 ) {
					$r = round( $d );

					if ( $ago ) {
						switch ( $str ) {
							case 'day':
								/* translators: %d: Number of days. */
								$time_string = _n( '%d day ago', '%d days ago', $r, 'hide-my-wp' );
								break;
							case 'hour':
								/* translators: %d: Number of hours. */
								$time_string = _n( '%d hour ago', '%d hours ago', $r, 'hide-my-wp' );
								break;
							case 'minute':
								/* translators: %d: Number of minutes. */
								$time_string = _n( '%d minute ago', '%d minutes ago', $r, 'hide-my-wp' );
								break;
							case 'second':
								/* translators: %d: Number of seconds. */
								$time_string = _n( '%d second ago', '%d seconds ago', $r, 'hide-my-wp' );
								break;
						}
					} else {
						switch ( $str ) {
							case 'day':
								/* translators: %d: Number of days remaining. */
								$time_string = _n( '%d day remaining', '%d days remaining', $r, 'hide-my-wp' );
								break;
							case 'hour':
								/* translators: %d: Number of hours remaining. */
								$time_string = _n( '%d hour remaining', '%d hours remaining', $r, 'hide-my-wp' );
								break;
							case 'minute':
								/* translators: %d: Number of minutes remaining. */
								$time_string = _n( '%d minute remaining', '%d minutes remaining', $r, 'hide-my-wp' );
								break;
							case 'second':
								/* translators: %d: Number of seconds remaining. */
								$time_string = _n( '%d second remaining', '%d seconds remaining', $r, 'hide-my-wp' );
								break;
						}
					}

					return esc_html( sprintf( $time_string, (int) $r ) );
				}
			}

			return esc_html__( 'Expired', 'hide-my-wp' );

		} else {

			return ! empty( $expiry_options[ $time ] ) ? $this->expires[ $time ]['label'] : '';
		}

	}

	/**
	 * Check if temporary login expired
	 *
	 * @param int $user_id
	 *
	 * @return bool
	 *
	 * @since 7.0
	 */
	public function isExpired( $user_id = 0 ) {

		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		if ( empty( $user_id ) ) {
			return false;
		}

		$expire = HMWP_Classes_Tools::getUserMeta( self::USER_SESSION_EXPIRE, $user_id );

		return ! empty( $expire ) && is_numeric( $expire ) && $this->gtmTimestamp() >= floatval( $expire );

	}

	/**
	 * Get temporary login url
	 *
	 * @param $user_id
	 *
	 * @return string
	 * @since 7.0
	 *
	 */
	public function getTempLoginUrl( $user_id ) {

		if ( empty( $user_id ) ) {
			return '';
		}

		$is_valid_temporary_login = $this->isValidTempLogin( $user_id );
		if ( ! $is_valid_temporary_login ) {
			return '';
		}

		$token = HMWP_Classes_Tools::getUserMeta( self::USER_TOKEN_KEY, $user_id );
		if ( empty( $token ) ) {
			return '';
		}

		$url = home_url();

		if ( HMWP_Classes_Tools::isMultisites() ){
			$url = get_home_url( HMWP_Classes_Tools::getUserMeta( 'primary_blog', $user_id ) );
		}

		$login_url = add_query_arg( 'hmwp_token', $token, trailingslashit( $url ) );

		// Make it compatible with iThemes Security plugin with Custom URL Login enabled
		$login_url = apply_filters( 'itsec_notify_admin_page_url', $login_url );

		return apply_filters( 'hmwp_templogin_link', $login_url, $user_id );

	}

	/**
	 * Validate a requested role for a temporary login.
	 *
	 * The role arrives from the request, so it can name any role on the site -
	 * including administrator. Granting a temporary login capabilities that the
	 * user creating it does not hold would be a privilege escalation: the creator
	 * gets the temporary login URL back and can sign in through it. So an unknown
	 * role, or one that grants anything the current user lacks, falls back to the
	 * role configured for temporary logins.
	 *
	 * @param string $role The requested role slug.
	 *
	 * @return string A role slug that is safe for the current user to assign.
	 */
	public function sanitizeRole( $role ) {

		$default = HMWP_Classes_Tools::getOption( 'hmwp_templogin_role' );

		if ( empty( $default ) || ! get_role( $default ) ) {
			$default = 'subscriber';
		}

		$role = sanitize_text_field( $role );

		if ( empty( $role ) ) {
			return $default;
		}

		$role_object = get_role( $role );

		if ( ! $role_object ) {
			return $default;
		}

		// Super admins may assign anything.
		if ( function_exists( 'is_super_admin' ) && is_super_admin() ) {
			return $role;
		}

		$current_user = wp_get_current_user();

		if ( ! $current_user || ! $current_user->exists() ) {
			return $default;
		}

		// Compare against the caps the current user actually holds rather than
		// current_user_can(), so that caps map_meta_cap() filters per-request
		// (unfiltered_html on multisite, for one) don't wrongly reject a role
		// the user legitimately owns.
		$own_caps = (array) $current_user->allcaps;

		foreach ( (array) $role_object->capabilities as $cap => $granted ) {
			if ( $granted && empty( $own_caps[ $cap ] ) ) {
				return $default;
			}
		}

		return $role;
	}

	/**
	 * Checks whether user is valid temporary user
	 *
	 * @param int $user_id
	 *
	 * @return bool
	 */
	public function isValidTempLogin( $user_id = 0 ) {

		if ( empty( $user_id ) ) {
			return false;
		}

		$check = HMWP_Classes_Tools::getUserMeta( self::USER_TEMP_TYPE, $user_id );

		return ! empty( $check );

	}

	/**
	 * Generate Temporary Login Token
	 *
	 * @param $user_id
	 *
	 * @return false|string
	 *
	 * @since 7.0
	 */
	public function generateToken( $user_id ) {
		$byte_length = 64;

		if ( function_exists( 'random_bytes' ) ) {
			try {
				return bin2hex( random_bytes( $byte_length ) ); // phpcs:ignore
			} catch ( \Exception $e ) {
			}
		}

		// Fallback
		$str  = $user_id . microtime() . uniqid( '', true );
		$salt = substr( md5( $str ), 0, 32 );

		return hash( "sha256", $str . $salt );
	}

	/**
	 * Get all pages which needs to be blocked for temporary users
	 *
	 * @return array
	 * @since 7.0
	 *
	 */
	public function getRestrictedPages() {
		$pages = array( 'user-new.php', 'user-edit.php', 'profile.php' );

		return apply_filters( 'hmwp_templogin_restricted_pages', $pages );

	}

	/**
	 * Get all pages which needs to be blocked for temporary users
	 *
	 * @return array
	 * @since 7.0
	 *
	 */
	public function getRestrictedActions() {
		$actions = array( 'deleteuser', 'delete' );

		return apply_filters( 'hmwp_templogin_restricted_actions', $actions );

	}

	/**
	 * Update the temporary login status
	 *
	 * @param int $user_id
	 * @param string $action
	 *
	 * @return bool
	 * @since 7.0
	 *
	 */
	public function updateLoginStatus( $user_id = 0, $action = '' ) {

		if ( empty( $user_id ) || empty( $action ) ) {
			return false;
		}

		if ( ! $this->isValidTempLogin( $user_id ) ) {
			return false;
		}

		$manage_login = false;
		if ( 'disable' === $action ) {
			$manage_login = HMWP_Classes_Tools::saveUserMeta( self::USER_SESSION_EXPIRE, $this->gtmTimestamp(), $user_id );
		} elseif ( 'enable' === $action ) {
			$manage_login = HMWP_Classes_Tools::saveUserMeta( self::USER_SESSION_EXPIRE, 'day', $user_id  );
		}

		if ( $manage_login ) {
			return true;
		}

		return false;

	}

	/**
	 * Delete all temporary logins
	 *
	 * @since 7.0
	 */
	public function deleteTempLogins() {

		$users = $this->getTempUsers();

		if ( count( $users ) > 0 ) {
			foreach ( $users as $user ) {
				if ( $user instanceof WP_User ) {
					$user_id = $user->ID;

					wp_delete_user( $user_id ); // Delete User

					// delete user from Multisite network too!
					if ( HMWP_Classes_Tools::isMultisites() ) {

						// If it's a super admin, we can't directly delete user from network site.
						// We need to revoke super admin access first and then delete user
						if ( is_super_admin( $user_id ) ) {
							revoke_super_admin( $user_id );
						}

						wpmu_delete_user( $user_id );
					}
				}
			}
		}

	}

}
