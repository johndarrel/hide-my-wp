<?php
/**
 * Cookies Model
 *
 * @file  The Cookies file
 * @package HMWP/CookiesModel
 * @since 4.0.0
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Models_Cookies
{

    private $_admin_cookie_path = false;
    private $_plugin_cookie_path = false;

    public function __construct()
    {
        if (HMWP_Classes_Tools::$default['hmwp_admin_url'] <> HMWP_Classes_Tools::getOption('hmwp_admin_url') ) {
            $this->setCookieContants();

            //Hook all the authorization and add the requested cookies
            add_filter('redirect_post_location', array( $this, 'setPostCookie' ), PHP_INT_MAX, 2);
            add_action('set_auth_cookie', array( $this, 'setAuthCookie' ), PHP_INT_MAX, 2);
            add_action('clear_auth_cookie', array( $this, 'setCleanCookie' ), PHP_INT_MAX);
            add_action('set_logged_in_cookie', array( $this, 'setLoginCookie' ), PHP_INT_MAX, 2);
        }
    }

    /**
     * Set the cookie constants in case of admin change
     */
    public function setCookieContants()
    {
        if (! $this->_admin_cookie_path ) {
            if (HMWP_Classes_Tools::isMultisites() ) {
                global $blog_id;
                switch_to_blog($blog_id);

                ms_cookie_constants();
                if (! is_subdomain_install() || trim(parse_url(get_option('siteurl'), PHP_URL_PATH), '/') ) {
                    $this->_admin_cookie_path = SITECOOKIEPATH;
                } else {
                    $this->_admin_cookie_path = SITECOOKIEPATH . HMWP_Classes_Tools::getOption('hmwp_admin_url');
                }
                restore_current_blog();
            } else {
                wp_cookie_constants();
                $this->_admin_cookie_path = SITECOOKIEPATH . HMWP_Classes_Tools::getOption('hmwp_admin_url');
            }
        }
        if (! $this->_plugin_cookie_path ) {
            $this->_plugin_cookie_path = preg_replace('|https?://[^/]+|i', '', get_option('siteurl') . '/' . HMWP_Classes_Tools::getOption('hmwp_plugin_url'));
        }
    }

    /**
     * Set the cookies for saving posts process
     *
     * @param string $location
     * @param int $post_id
     * @return string
     */
    public function setPostCookie( $location, $post_id )
    {
        if ($this->_admin_cookie_path ) {
            if ($post_id > 0 ) {
                if (isset($_COOKIE['wp-saving-post']) && $_COOKIE['wp-saving-post'] === $post_id . '-check' ) {
	                $domain = !is_null(COOKIE_DOMAIN) ? COOKIE_DOMAIN : '';

	                setcookie('wp-saving-post', $post_id . '-saved', time() + DAY_IN_SECONDS, $this->_admin_cookie_path, $domain, is_ssl());
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
    public function testCookies()
    {
        $secure = is_ssl();
        if ($secure ) {
            $auth_cookie_name = SECURE_AUTH_COOKIE;
        } else {
            $auth_cookie_name = AUTH_COOKIE;
        }

        return ( isset($_COOKIE[ $auth_cookie_name ]) && $_COOKIE[ $auth_cookie_name ] );
    }

    /**
     * Set the secured current path for the plugin cookies
     *
     * @return bool
     */
    public function setCookiesCurrentPath()
    {
        global $current_user;

        if ($current_user->ID ) {
            wp_set_auth_cookie($current_user->ID);

            if ($this->testCookies() ) {
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
    public function setTestCookie()
    {

        if(headers_sent()) {
            return;
        }

	    $domain = !is_null(COOKIE_DOMAIN) ? COOKIE_DOMAIN : '';

	    if (! defined('TEST_COOKIE') ) {
            define('TEST_COOKIE', 'test_cookie');
        }

        $secure = is_ssl() && 'https' === parse_url(get_option('home'), PHP_URL_SCHEME);
        setcookie(TEST_COOKIE, 'WP Cookie check', 0, COOKIEPATH, $this->getCookieDomain(), $secure);
        if (SITECOOKIEPATH != COOKIEPATH ) {
            setcookie(TEST_COOKIE, 'WP Cookie check', 0, SITECOOKIEPATH, $domain, $secure);
        }
    }

	/**
	 * Add the test cookie in the login form
	 *
	 * @return void
	 */
	public function setWhitelistCookie()
	{

		if(headers_sent()) {
			return;
		}

		$secure = is_ssl() && 'https' === parse_url(get_option('home'), PHP_URL_SCHEME);
		setcookie(HMWP_LOGGED_IN_COOKIE . 'whitelist', 'whitelist', 0, COOKIEPATH, $this->getCookieDomain(), $secure);
		if (SITECOOKIEPATH != COOKIEPATH ) {
			setcookie(HMWP_LOGGED_IN_COOKIE . 'whitelist', 'whitelist', 0, SITECOOKIEPATH, COOKIE_DOMAIN, $secure);
		}
	}

    /**
     * Set the plugin cookies for the custom admin path
     * @param string $auth_cookie
     * @param int $expire
     * @return void
     */
    public function setAuthCookie( $auth_cookie, $expire )
    {

        if(headers_sent()) {
            return;
        }

	    $domain = !is_null(COOKIE_DOMAIN) ? COOKIE_DOMAIN : '';

	    if ($this->_admin_cookie_path ) {
            $secure = is_ssl();
            if ($secure ) {
                $auth_cookie_name = SECURE_AUTH_COOKIE;
            } else {
                $auth_cookie_name = AUTH_COOKIE;
            }

            if ($this->_plugin_cookie_path ) {
                setcookie($auth_cookie_name, $auth_cookie, $expire, PLUGINS_COOKIE_PATH, $domain, $secure, true);
                setcookie($auth_cookie_name, $auth_cookie, $expire, $this->_plugin_cookie_path, $this->getCookieDomain(), $secure, true);
            }

            setcookie($auth_cookie_name, $auth_cookie, $expire, PLUGINS_COOKIE_PATH, $domain, $secure, true);
            setcookie($auth_cookie_name, $auth_cookie, $expire, ADMIN_COOKIE_PATH, $domain, $secure, true);
            setcookie($auth_cookie_name, $auth_cookie, $expire, $this->_admin_cookie_path, $this->getCookieDomain(), $secure, true);
            setcookie(HMWP_LOGGED_IN_COOKIE . 'admin', $auth_cookie, $expire, $this->_admin_cookie_path, $this->getCookieDomain(), $secure, true);
        }
    }

    /**
     * Set the login cookie for the custom path
     *
     * @param string $logged_in_cookie
     * @param int $expire
     * @return void
     */
    public function setLoginCookie( $logged_in_cookie, $expire )
    {

        if(headers_sent()) {
            return;
        }

	    $domain = !is_null(COOKIE_DOMAIN) ? COOKIE_DOMAIN : '';

	    // Front-end cookie is secure when the auth cookie is secure and the site's home URL is forced HTTPS.
        $secure_logged_in_cookie = is_ssl() && 'https' === parse_url(get_option('home'), PHP_URL_SCHEME);

        setcookie(LOGGED_IN_COOKIE, $logged_in_cookie, $expire, COOKIEPATH, $this->getCookieDomain(), $secure_logged_in_cookie, true);
        if (COOKIEPATH != SITECOOKIEPATH ) {
            setcookie(LOGGED_IN_COOKIE, $logged_in_cookie, $expire, SITECOOKIEPATH, $this->getCookieDomain(), $secure_logged_in_cookie, true);
        }

        setcookie(LOGGED_IN_COOKIE, $logged_in_cookie, $expire, COOKIEPATH, $domain, $secure_logged_in_cookie, true);
        if (COOKIEPATH != SITECOOKIEPATH ) {
            setcookie(LOGGED_IN_COOKIE, $logged_in_cookie, $expire, SITECOOKIEPATH, $domain, $secure_logged_in_cookie, true);
        }

        if (defined('COOKIEHASH') ) {
            setcookie(HMWP_LOGGED_IN_COOKIE . 'login', $logged_in_cookie, $expire, COOKIEPATH, $domain, $secure_logged_in_cookie, true);
            if (COOKIEPATH != SITECOOKIEPATH ) {
                setcookie(HMWP_LOGGED_IN_COOKIE . 'login', $logged_in_cookie, $expire, SITECOOKIEPATH, $domain, $secure_logged_in_cookie, true);
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
    public function checkLoggedIP( $response )
    {
        if (isset($_SERVER['REMOTE_ADDR']) && isset($_COOKIE['wordpress_logged_address']) ) {
            if (md5($_SERVER['REMOTE_ADDR']) <> $_COOKIE['wordpress_logged_address'] ) {
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
    public function setCleanCookie()
    {

        if(headers_sent()) {
            return;
        }

	    $domain = !is_null(COOKIE_DOMAIN) ? COOKIE_DOMAIN : '';

	    if ($this->_admin_cookie_path && defined('PLUGINS_COOKIE_PATH') ) {
            setcookie(AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, $this->_admin_cookie_path, $this->getCookieDomain());
            setcookie(SECURE_AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, $this->_admin_cookie_path, $this->getCookieDomain());
            setcookie('wordpress_logged_address', ' ', time() - YEAR_IN_SECONDS, $this->_admin_cookie_path, $this->getCookieDomain());


            setcookie(AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, $this->_plugin_cookie_path, $this->getCookieDomain());
            setcookie(SECURE_AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, $this->_plugin_cookie_path, $this->getCookieDomain());
            setcookie('wordpress_logged_address', ' ', time() - YEAR_IN_SECONDS, $this->_plugin_cookie_path, $this->getCookieDomain());


            setcookie(HMWP_LOGGED_IN_COOKIE . 'login', ' ', time() - YEAR_IN_SECONDS, COOKIEPATH, $domain);
            setcookie(HMWP_LOGGED_IN_COOKIE  . 'login', ' ', time() - YEAR_IN_SECONDS, SITECOOKIEPATH, $domain);
            setcookie(HMWP_LOGGED_IN_COOKIE . 'admin', ' ', time() - YEAR_IN_SECONDS, $this->_admin_cookie_path, $this->getCookieDomain());
        }
    }


    /**
     * Get the cookie domain based on the website struction
     * Multisite/Singlesite
     */
    public function getCookieDomain()
    {
	    //In case the cookie domain is set to null
	    $domain = !is_null(COOKIE_DOMAIN) ? COOKIE_DOMAIN : '';

        if (HMWP_Classes_Tools::isMultisites() ) {
            global $blog_id;
            switch_to_blog($blog_id);
            $current_network = get_network();

            $domain = preg_replace('|^www\.|', '', parse_url(get_option('siteurl'), PHP_URL_HOST));

            if (! empty($current_network->cookie_domain) ) {
                if (strpos($current_network->cookie_domain, $domain) === false ) {
                    $domain = '.' . $domain;
                }
            } elseif (strpos($current_network->domain, $domain) === false ) {
                $domain = '.' . $domain;
            }
            restore_current_blog();
        }

        return $domain;
    }

}
