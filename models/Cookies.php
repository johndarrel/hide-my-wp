<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

class HMW_Models_Cookies {

	public function __construct() {
		if ( HMW_Classes_Tools::$default['hmw_admin_url'] <> HMW_Classes_Tools::getOption( 'hmw_admin_url' ) ) {
			$this->setCookieContants();

			add_filter( 'redirect_post_location', array( $this, 'setPostCookie' ), PHP_INT_MAX, 2 );
			add_action( 'set_auth_cookie', array( $this, 'setAuthCookie' ), PHP_INT_MAX, 2 );
			add_action( 'clear_auth_cookie', array( $this, 'setCleanCookie' ), PHP_INT_MAX );
			add_action( 'set_logged_in_cookie', array( $this, 'setLoginCookie' ), PHP_INT_MAX, 2 );
		}
	}

	/**
	 * Set the cookie constants in case of admin change
	 */
	public function setCookieContants() {
		if ( ! defined( 'HMW_ADMIN_COOKIE_PATH' ) ) {
			if ( is_multisite() ) {
				global $blog_id;
				switch_to_blog( $blog_id );

				ms_cookie_constants();
				if ( ! is_subdomain_install() || trim( parse_url( get_option( 'siteurl' ), PHP_URL_PATH ), '/' ) ) {
					define( 'HMW_ADMIN_COOKIE_PATH', SITECOOKIEPATH );
				} else {
					define( 'HMW_ADMIN_COOKIE_PATH', SITECOOKIEPATH . HMW_Classes_Tools::getOption( 'hmw_admin_url' ) );
				}
				restore_current_blog();
			} else {
				wp_cookie_constants();
				define( 'HMW_ADMIN_COOKIE_PATH', SITECOOKIEPATH . HMW_Classes_Tools::getOption( 'hmw_admin_url' ) );
			}
		}
		if ( ! defined( 'HMW_PLUGINS_COOKIE_PATH' ) ) {
			define( 'HMW_PLUGINS_COOKIE_PATH', preg_replace( '|https?://[^/]+|i', '', get_option( 'siteurl' ) . '/' . HMW_Classes_Tools::getOption( 'hmw_plugin_url' ) ) );
		}
	}


	public function setPostCookie( $location, $post_id ) {
		if ( defined( 'HMW_ADMIN_COOKIE_PATH' ) ) {
			if ( $post_id > 0 ) {
				if ( isset( $_COOKIE['wp-saving-post'] ) && $_COOKIE['wp-saving-post'] === $post_id . '-check' ) {
					setcookie( 'wp-saving-post', $post_id . '-saved', time() + DAY_IN_SECONDS, HMW_ADMIN_COOKIE_PATH, COOKIE_DOMAIN, is_ssl() );
				}
			}
		}

		return $location;
	}

	public function setTestCookie() {
		if ( ! defined( 'TEST_COOKIE' ) ) {
			define( 'TEST_COOKIE', 'test_cookie' );
		}

		$secure = is_ssl() && 'https' === parse_url( get_option( 'home' ), PHP_URL_SCHEME );
		setcookie( TEST_COOKIE, 'WP Cookie check', 0, COOKIEPATH, $this->getCookieDomain(), $secure );
		if ( SITECOOKIEPATH != COOKIEPATH ) {
			setcookie( TEST_COOKIE, 'WP Cookie check', 0, SITECOOKIEPATH, COOKIE_DOMAIN, $secure );
		}
	}

	public function testCookies() {
		$secure = is_ssl();
		if ( $secure ) {
			$auth_cookie_name = SECURE_AUTH_COOKIE;
		} else {
			$auth_cookie_name = AUTH_COOKIE;
		}

		return ( isset( $_COOKIE[ $auth_cookie_name ] ) && $_COOKIE[ $auth_cookie_name ] );
	}

	public function setCookiesCurrentPath() {
		global $current_user;

		if ( isset( $current_user->ID ) && function_exists( 'wp_set_auth_cookie' ) ) {
			wp_set_auth_cookie( $current_user->ID );

			if ( $this->testCookies() ) {
				return true;
			}
		}

		return false;
	}

	public function setAuthCookie( $auth_cookie, $expire ) {
		if ( defined( 'HMW_ADMIN_COOKIE_PATH' ) ) {
			$secure = is_ssl();
			if ( $secure ) {
				$auth_cookie_name = SECURE_AUTH_COOKIE;
			} else {
				$auth_cookie_name = AUTH_COOKIE;
			}

			if ( defined( 'HMW_PLUGINS_COOKIE_PATH' ) ) {
				setcookie( $auth_cookie_name, $auth_cookie, $expire, PLUGINS_COOKIE_PATH, COOKIE_DOMAIN, $secure, true );
				setcookie( $auth_cookie_name, $auth_cookie, $expire, HMW_PLUGINS_COOKIE_PATH, $this->getCookieDomain(), $secure, true );
			}

			setcookie( $auth_cookie_name, $auth_cookie, $expire, PLUGINS_COOKIE_PATH, COOKIE_DOMAIN, $secure, true );
			setcookie( $auth_cookie_name, $auth_cookie, $expire, ADMIN_COOKIE_PATH, COOKIE_DOMAIN, $secure, true );

			setcookie( $auth_cookie_name, $auth_cookie, $expire, HMW_ADMIN_COOKIE_PATH, $this->getCookieDomain(), $secure, true );
		}
	}

	public function setLoginCookie( $logged_in_cookie, $expire ) {
		// Front-end cookie is secure when the auth cookie is secure and the site's home URL is forced HTTPS.
		$secure_logged_in_cookie = is_ssl() && 'https' === parse_url( get_option( 'home' ), PHP_URL_SCHEME );

		setcookie( LOGGED_IN_COOKIE, $logged_in_cookie, $expire, COOKIEPATH, $this->getCookieDomain(), $secure_logged_in_cookie, true );
		if ( COOKIEPATH != SITECOOKIEPATH ) {
			setcookie( LOGGED_IN_COOKIE, $logged_in_cookie, $expire, SITECOOKIEPATH, $this->getCookieDomain(), $secure_logged_in_cookie, true );
		}

		setcookie( LOGGED_IN_COOKIE, $logged_in_cookie, $expire, COOKIEPATH, COOKIE_DOMAIN, $secure_logged_in_cookie, true );
		if ( COOKIEPATH != SITECOOKIEPATH ) {
			setcookie( LOGGED_IN_COOKIE, $logged_in_cookie, $expire, SITECOOKIEPATH, COOKIE_DOMAIN, $secure_logged_in_cookie, true );
		}

	}

	/**
	 * Check if the current user IP is always the same
	 * If not, request a relogin
	 *
	 * @param $response
	 *
	 * @return mixed
	 */
	public function checkLoggedIP( $response ) {
		if ( isset( $_SERVER['REMOTE_ADDR'] ) && isset( $_COOKIE['wordpress_logged_address'] ) ) {
			if ( md5( $_SERVER['REMOTE_ADDR'] ) <> $_COOKIE['wordpress_logged_address'] ) {
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
	/**
	 * Clean the user cookies on logout
	 */
	public function setCleanCookie() {
		if ( defined( 'HMW_ADMIN_COOKIE_PATH' ) && defined( 'PLUGINS_COOKIE_PATH' ) ) {
			setcookie( AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, HMW_ADMIN_COOKIE_PATH, $this->getCookieDomain() );
			setcookie( SECURE_AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, HMW_ADMIN_COOKIE_PATH, $this->getCookieDomain() );
			setcookie( 'wordpress_logged_address', ' ', time() - YEAR_IN_SECONDS, HMW_ADMIN_COOKIE_PATH, $this->getCookieDomain() );

			setcookie( AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, HMW_PLUGINS_COOKIE_PATH, $this->getCookieDomain() );
			setcookie( SECURE_AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, HMW_PLUGINS_COOKIE_PATH, $this->getCookieDomain() );
			setcookie( 'wordpress_logged_address', ' ', time() - YEAR_IN_SECONDS, HMW_PLUGINS_COOKIE_PATH, $this->getCookieDomain() );
		}
	}

	public function getCookieDomain() {
		$domain = COOKIE_DOMAIN;

		if ( is_multisite() ) {
			global $blog_id;
			switch_to_blog( $blog_id );
			$current_network = get_network();

			$domain = preg_replace( '|^www\.|', '', parse_url( get_option( 'siteurl' ), PHP_URL_HOST ) );

			if ( ! empty( $current_network->cookie_domain ) ) {
				if ( strpos( $current_network->cookie_domain, $domain ) === false ) {
					$domain = '.' . $domain;
				}
			} elseif ( strpos( $current_network->domain, $domain ) === false ) {
				$domain = '.' . $domain;
			}
			restore_current_blog();
		}

		return $domain;
	}

}