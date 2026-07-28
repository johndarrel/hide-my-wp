<?php
/**
 * Cookies Model
 *
 * @file  The Cookies file
 * @package HMWP/CookiesModel
 * @since 4.0.0
 */

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Models_Cookies {

	private $_admin_cookie_path = false;
	private $_plugin_cookie_path = false;

	public function __construct() {
		if ( HMWP_Classes_Tools::getDefault( 'hmwp_admin_url' ) <> HMWP_Classes_Tools::getOption( 'hmwp_admin_url' ) ) {
			$this->setCookieContants();

			//Hook all the authorization and add the requested cookies
			add_filter( 'redirect_post_location', array( $this, 'setPostCookie' ), PHP_INT_MAX, 2 );
			add_action( 'clear_auth_cookie', array( $this, 'setCleanCookie' ), PHP_INT_MAX );
			add_action( 'set_auth_cookie', array( $this, 'setAuthCookie' ), PHP_INT_MAX, 2 );
			add_action( 'set_logged_in_cookie', array( $this, 'setLoginCookie' ), PHP_INT_MAX, 2 );
		}
	}

	/**
	 * Set the cookie constants in case of admin change
	 */
	public function setCookieContants() {
		if ( HMWP_Classes_Tools::isMultisites() && ! $this->_admin_cookie_path ) {
			global $blog_id;
			ms_cookie_constants();

			//Set current site path
			$site_path = wp_parse_url( get_site_url( $blog_id ), PHP_URL_PATH );

			//is path based and path exists
			if ( ! is_subdomain_install() || is_string( $site_path ) && trim( $site_path, '/' ) ) {
				$this->_admin_cookie_path = SITECOOKIEPATH;
			} else {
				$this->_admin_cookie_path = SITECOOKIEPATH . HMWP_Classes_Tools::getOption( 'hmwp_admin_url' );
			}

		} else {

			wp_cookie_constants();
			$this->_admin_cookie_path = SITECOOKIEPATH . HMWP_Classes_Tools::getOption( 'hmwp_admin_url' );

		}

		if ( ! $this->_plugin_cookie_path ) {
			$this->_plugin_cookie_path = preg_replace( '|https?://[^/]+|i', '', get_option( 'siteurl' ) . '/' . HMWP_Classes_Tools::getOption( 'hmwp_plugin_url' ) );
		}

	}

	/**
	 * Set the cookies for saving posts process
	 *
	 * @param string $location
	 * @param int $post_id
	 *
	 * @return string
	 */
	public function setPostCookie( $location, $post_id ) {
		if ( $this->_admin_cookie_path ) {
			if ( $post_id > 0 ) {
				if ( isset( $_COOKIE['wp-saving-post'] ) && $_COOKIE['wp-saving-post'] === $post_id . '-check' ) {
					setcookie( 'wp-saving-post', $post_id . '-saved', time() + DAY_IN_SECONDS, $this->_admin_cookie_path, $this->getWpCookieDomain(), is_ssl() );
				}
			}
		}

		return $location;
	}

	/**
	 * Get the test cookie
	 *
	 * @return bool
	 */
	public function testCookies() {
		$secure = is_ssl();
		if ( $secure ) {
			$auth_cookie_name = SECURE_AUTH_COOKIE;
		} else {
			$auth_cookie_name = AUTH_COOKIE;
		}

		return ( isset( $_COOKIE[ $auth_cookie_name ] ) && $_COOKIE[ $auth_cookie_name ] ); //phpcs:ignore
	}

	/**
	 * Set the secured current path for the plugin cookies
	 *
	 * @return bool
	 */
	public function setCookiesCurrentPath() {
		global $current_user;

		if ( $current_user->ID ) {
			wp_set_auth_cookie( $current_user->ID );

			if ( $this->testCookies() ) {

				//trigger action after apply the cookies
				do_action( 'hmwp_cookies_changes' );

				return true;
			}
		}

		return false;
	}

	/**
	 * Add the test cookie in the login form
	 *
	 * @return void
	 */
	public function setTestCookie() {

		if ( headers_sent() ) {
			return;
		}

		if ( ! defined( 'TEST_COOKIE' ) ) {
			define( 'TEST_COOKIE', 'test_cookie' );
		}

		$secure = is_ssl() && 'https' === wp_parse_url( get_option( 'home' ), PHP_URL_SCHEME );
		setcookie( TEST_COOKIE, 'WP Cookie check', 0, COOKIEPATH, $this->getCookieDomain(), $secure );
		if ( SITECOOKIEPATH != COOKIEPATH ) {
			setcookie( TEST_COOKIE, 'WP Cookie check', 0, SITECOOKIEPATH, $this->getWpCookieDomain(), $secure );
		}
	}

	/**
	 * Set the plugin cookies for the custom admin path
	 *
	 * @param string $auth_cookie
	 * @param int $expire
	 *
	 * @return void
	 */
	public function setAuthCookie( $auth_cookie, $expire ) {

		if ( headers_sent() ) {
			return;
		}

		if ( $this->_admin_cookie_path ) {

			$secure = is_ssl();
			if ( $secure ) {
				$auth_cookie_name = SECURE_AUTH_COOKIE;
			} else {
				$auth_cookie_name = AUTH_COOKIE;
			}

			if ( $this->_plugin_cookie_path ) {
				setcookie( $auth_cookie_name, $auth_cookie, $expire, PLUGINS_COOKIE_PATH, $this->getWpCookieDomain(), $secure, true );
				setcookie( $auth_cookie_name, $auth_cookie, $expire, $this->_plugin_cookie_path, $this->getCookieDomain(), $secure, true );
			}

			setcookie( $auth_cookie_name, $auth_cookie, $expire, PLUGINS_COOKIE_PATH, $this->getWpCookieDomain(), $secure, true );
			setcookie( $auth_cookie_name, $auth_cookie, $expire, ADMIN_COOKIE_PATH, $this->getWpCookieDomain(), $secure, true );
			setcookie( $auth_cookie_name, $auth_cookie, $expire, $this->_admin_cookie_path, $this->getCookieDomain(), $secure, true );
			setcookie( HMWP_LOGGED_IN_COOKIE . 'admin', $auth_cookie, $expire, $this->_admin_cookie_path, $this->getCookieDomain(), $secure, true );

		}
	}

	/**
	 * Set the login cookie for the custom path
	 *
	 * @param string $logged_in_cookie
	 * @param int $expire
	 *
	 * @return void
	 */
	public function setLoginCookie( $logged_in_cookie, $expire ) {

		if ( headers_sent() ) {
			return;
		}

		// Front-end cookie is secure when the auth cookie is secure and the site's home URL is forced HTTPS.
		$secure_logged_in_cookie = is_ssl() && 'https' === wp_parse_url( get_option( 'home' ), PHP_URL_SCHEME );

		setcookie( LOGGED_IN_COOKIE, $logged_in_cookie, $expire, COOKIEPATH, $this->getCookieDomain(), $secure_logged_in_cookie, true );
		if ( COOKIEPATH != SITECOOKIEPATH ) {
			setcookie( LOGGED_IN_COOKIE, $logged_in_cookie, $expire, SITECOOKIEPATH, $this->getCookieDomain(), $secure_logged_in_cookie, true );
		}

		setcookie( LOGGED_IN_COOKIE, $logged_in_cookie, $expire, COOKIEPATH, $this->getWpCookieDomain(), $secure_logged_in_cookie, true );
		if ( COOKIEPATH != SITECOOKIEPATH ) {
			setcookie( LOGGED_IN_COOKIE, $logged_in_cookie, $expire, SITECOOKIEPATH, $this->getWpCookieDomain(), $secure_logged_in_cookie, true );
		}

		if ( defined( 'COOKIEHASH' ) ) {
			setcookie( HMWP_LOGGED_IN_COOKIE . 'login', $logged_in_cookie, $expire, COOKIEPATH, $this->getWpCookieDomain(), $secure_logged_in_cookie, true );
			if ( COOKIEPATH != SITECOOKIEPATH ) {
				setcookie( HMWP_LOGGED_IN_COOKIE . 'login', $logged_in_cookie, $expire, SITECOOKIEPATH, $this->getWpCookieDomain(), $secure_logged_in_cookie, true );
			}
		}
	}

	/**
	 * Check if the current user IP is always the same
	 * If not, request a relogin
	 *
	 * @param array $response
	 *
	 * @return array
	 */
	public function checkLoggedIP( $response ) {
		if ( isset( $_SERVER['REMOTE_ADDR'] ) && isset( $_COOKIE['wordpress_logged_address'] ) ) {
			if ( md5( $_SERVER['REMOTE_ADDR'] ) <> $_COOKIE['wordpress_logged_address'] ) { //phpcs:ignore
				global $current_user;
				$current_user->ID          = null;
				$response['wp-auth-check'] = false;
			}
		}

		return $response;
	}

	/**
	 * Clean the user cookies on logout
	 */
	public function setCleanCookie() {

		if ( headers_sent() ) {
			return;
		}

		if ( $this->_admin_cookie_path && defined( 'PLUGINS_COOKIE_PATH' ) ) {
			setcookie( AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, $this->_admin_cookie_path, $this->getCookieDomain() );
			setcookie( SECURE_AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, $this->_admin_cookie_path, $this->getCookieDomain() );
			setcookie( 'wordpress_logged_address', ' ', time() - YEAR_IN_SECONDS, $this->_admin_cookie_path, $this->getCookieDomain() );

			setcookie( AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, $this->_plugin_cookie_path, $this->getCookieDomain() );
			setcookie( SECURE_AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, $this->_plugin_cookie_path, $this->getCookieDomain() );
			setcookie( 'wordpress_logged_address', ' ', time() - YEAR_IN_SECONDS, $this->_plugin_cookie_path, $this->getCookieDomain() );

			setcookie( HMWP_LOGGED_IN_COOKIE . 'login', ' ', time() - YEAR_IN_SECONDS, COOKIEPATH, $this->getWpCookieDomain() );
			setcookie( HMWP_LOGGED_IN_COOKIE . 'login', ' ', time() - YEAR_IN_SECONDS, SITECOOKIEPATH, $this->getWpCookieDomain() );
			setcookie( HMWP_LOGGED_IN_COOKIE . 'admin', ' ', time() - YEAR_IN_SECONDS, $this->_admin_cookie_path, $this->getCookieDomain() );
		}
	}


	/**
	 * Get the cookie domain based on the website structure
	 * Multisite/Singlesite
	 */
	public function getCookieDomain() {
		$domain = $this->getWpCookieDomain();

		//on multisite without doman cookie
		if ( HMWP_Classes_Tools::isMultisites() ) {

			//get current domain
			global $blog_id;

			if ( $host = preg_replace( '|^www\.|', '', wp_parse_url( get_site_url( $blog_id ), PHP_URL_HOST ) ) ) {
				//change the cookie for the current domain
				if ( ! $domain || strpos( $domain, $host ) === false ) {
					$domain = $host;
				}
			}

		}

		return $domain;
	}


	/**
	 * Return WordPress default Cookie Domain
	 *
	 * @return array|false|int|string|null
	 */
	public function getWpCookieDomain() {

		if ( ! defined( 'COOKIE_DOMAIN' ) && HMWP_Classes_Tools::isMultisites() ) {

			$current_network = get_network();
			if ( ! empty( $current_network->cookie_domain ) ) {
				define( 'COOKIE_DOMAIN', '.' . $current_network->cookie_domain );
			} else {
				define( 'COOKIE_DOMAIN', '.' . $current_network->domain );
			}

		}

		if ( ! defined( 'COOKIE_DOMAIN' ) ) {
			define( 'COOKIE_DOMAIN', false );
		}

		return COOKIE_DOMAIN;
	}

	/**
	 * Checks if the logged-in cookie is valid and determines the user's logged-in status.
	 *
	 * @return bool|false|string Returns the cookie validation result or false if the user is not logged in or ineligible contexts.
	 */
	public function isLoggedInCookie() {

		// If pluggable is already available, use the canonical check.
		if ( function_exists( 'is_user_logged_in' ) ) {
			return is_user_logged_in();
		}

		// Ensure cookie constants if possible (default-constants.php).
		if ( ! defined( 'LOGGED_IN_COOKIE' ) && function_exists( 'wp_cookie_constants' ) ) {
			wp_cookie_constants();
		}

		// Most reliable if constant exists.
		if ( defined( 'LOGGED_IN_COOKIE' ) && ! empty( $_COOKIE[ LOGGED_IN_COOKIE ] ) ) {
			return true;
		}

		// Your custom cookie (if you use it).
		if ( defined( 'HMWP_LOGGED_IN_COOKIE' ) && ! empty( $_COOKIE[ HMWP_LOGGED_IN_COOKIE . 'login' ] ) ) {
			return true;
		}

		// Fallback for very early bootstrap / edge cases:
		// Multisite + normal installs use wordpress_logged_in_{hash}.
		foreach ( $_COOKIE as $name => $val ) {
			if ( $val === '' ) {
				continue;
			}

			if ( strpos( $name, 'wordpress_logged_in_' ) === 0 ) {
				return true;
			}
		}

		return false;
	}


}
