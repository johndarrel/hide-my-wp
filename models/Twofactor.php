<?php
/**
 * TwoFactor Model
 * Handles the plugin twofactor process
 *
 * @file  The TwoFactor Model file
 * @package HMWPP/TwofactorModel
 * @since 1.0.0
 */
defined( 'ABSPATH' ) || die( 'Cheating uh?' );

/**
 * Class handling Two-Factor Authentication (2FA) functionality within the HMWPP plugin.
 */
class HMWP_Models_Twofactor {

    /** @var array All auth tokens */
    public static $tokens = array();

    /** @var string login success */
    const USER_NONCE = '_hmwp_nonce';

    /** @var string fail login attempts */
    const USER_FAILURES = '_hmwp_login_failure';

    /** @var string login attempts */
    const USER_ATTEMPTS = '_hmwp_login_attempts';

    /** @var string login attempts */
    const USER_SUCCESS = '_hmwp_last_login';

    /** @var string 2fa method activated */
    const USER_2FA = '_hmwp_2fa_method';

    /**
     * Checks if a specific service is active for the given user.
     *
     * @param WP_User $user The WP_User instance representing the user to check the service for.
     * @param string $name The name of the service to be checked.
     *
     * @return bool True if the service is active, false otherwise.
     */
    public function isActiveService( $user, $name ) {

        if ( HMWP_Classes_Tools::getOption( 'hmwp_2fa_user' ) ) {
            if ($selected = HMWP_Classes_Tools::getUserMeta( self::USER_2FA, $user->ID )){
                return ($selected == $name);
            }
        }

        return HMWP_Classes_Tools::getOption( $name );

    }

    /**
     * Check whether the user has at least one 2FA method fully configured
     * (an authenticator key, a verified email code, or a registered passkey).
     *
     * @param WP_User $user The user to check.
     *
     * @return bool True if any 2FA service is active for the user.
     * @throws Exception
     */
    public function hasConfiguredTwoFactor( $user ) {

        if ( ! isset( $user->ID ) ) {
            return false;
        }

        /** @var HMWP_Models_Twofactor_Tftotp $twoFactorService */
        $twoFactorService = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Twofactor_Tftotp' );

        /** @var HMWP_Models_Twofactor_Email $emailService */
        $emailService = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Twofactor_Email' );

        /** @var HMWP_Models_Twofactor_Passkey $passkeyService */
        $passkeyService = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Twofactor_Passkey' );

        return ( $twoFactorService->isServiceActive( $user ) || $emailService->isServiceActive( $user ) || $passkeyService->isServiceActive( $user ) );
    }

    /**
     * Check whether the given user is required to set up 2FA.
     *
     * True only when the "Force 2FA setup" option is enabled, the user has a role
     * that is in scope (empty scope list means all roles), and at least one 2FA
     * method is enabled on the site so the user can actually enroll. Fails open
     * (returns false) whenever enrollment would be impossible, so nobody is locked
     * out. The result can be overridden with the `hmwp_2fa_force_setup` filter.
     *
     * @param WP_User $user The user to check.
     *
     * @return bool True if the user must set up 2FA.
     */
    public function isForcedForUser( $user ) {

        $forced = false;

        // Only force users that can actually reach their profile page (where the
        // setup UI lives). Every standard role has 'read'; this fails open for any
        // custom role without it, so nobody is redirected to a page they can't load.
        if ( isset( $user->ID ) && $user->exists() && user_can( $user, 'read' )
            && HMWP_Classes_Tools::getOption( 'hmwp_2falogin' )
            && HMWP_Classes_Tools::getOption( 'hmwp_2fa_forced' ) ) {

            // Make sure at least one method is enabled, otherwise enrollment is impossible.
            if ( HMWP_Classes_Tools::getOption( 'hmwp_2fa_totp' )
                || HMWP_Classes_Tools::getOption( 'hmwp_2fa_email' )
                || HMWP_Classes_Tools::getOption( 'hmwp_2fa_passkey' ) ) {

                $scoped_roles = (array) HMWP_Classes_Tools::getOption( 'hmwp_2fa_forced_roles' );
                $user_roles   = ( isset( $user->roles ) && is_array( $user->roles ) ) ? $user->roles : array();

                // Empty scope means every role is required.
                if ( empty( $scoped_roles ) || array_intersect( $scoped_roles, $user_roles ) ) {
                    $forced = true;
                }
            }
        }

        return (bool) apply_filters( 'hmwp_2fa_force_setup', $forced, $user );
    }

    /**
     * Display the login form.
     *
     * @param WP_User $user The WP_User instance representing the currently logged-in user.
     *
     * @throws Exception
     */
    public function showTwoFactorLogin( $user ) {

        if ( ! $user ) {
            $user = wp_get_current_user();
        }

        $login_nonce = $this->createLoginNonce( $user->ID );

        if ( empty( $login_nonce ) ) {
            wp_die( esc_html__( 'Failed to create a login nonce.', 'hide-my-wp' ) );
        }

        $redirect_to = HMWP_Classes_Tools::getValue( 'redirect_to', admin_url() );

        $this->loginHtml( $user, $login_nonce['key'], $redirect_to );
    }

    /**
     * Generates the html form for the second step of the authentication process.
     *
     * @param WP_User $user The WP_User instance representing the currently logged-in user.
     * @param string $login_nonce A string nonce stored in usermeta.
     * @param string $redirect_to The URL to which the user would like to be redirected.
     * @param string $error_msg Optional. Login error message.
     *
     * @throws Exception
     */
    public function loginHtml( $user, $login_nonce, $redirect_to, $error_msg = '' ) {

        $twoFactorService = false;

        /** @var HMWP_Models_Twofactor_Passkey $passkeyService */
        $passkeyService = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Twofactor_Passkey' );

        /** @var HMWP_Models_Twofactor_Tftotp $twoFactorService */
        $twoFactorService = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Twofactor_Tftotp' );

        /** @var HMWP_Models_Twofactor_Email $emailService */
        $emailService = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Twofactor_Email' );

        // If none of the services are active
        if ( ! $passkeyService->isServiceActive( $user ) && ! $twoFactorService->isServiceActive( $user ) && ! $emailService->isServiceActive( $user ) ) {
            return '';
        }

        if ( $passkeyService->isServiceActive( $user ) ) {
            $service = $passkeyService;
        }

        if ( $emailService->isServiceActive( $user ) ) {
            $service = $emailService;
        }

        if ( $twoFactorService->isServiceActive( $user ) ) {
            $service = $twoFactorService;
        }

        $interim_login = HMWP_Classes_Tools::getValue( 'interim-login' );

        //check if remember is on
        $remember = (int) $this->remember();

        if ( ! function_exists( 'login_header' ) ) {
            // We really should migrate login_header() out of `wp-login.php` so it can be called from an includes file.
            include_once _HMWP_THEME_DIR_ . 'wplogin/header.php';
        }

        if ( function_exists( 'login_header' ) ) {
            login_header();
        }

        if ( ! empty( $error_msg ) ) {
            echo '<div id="login_error"><strong>' . esc_html( $error_msg ) . '</strong><br /></div>';
        } else {
            $this->showNotices( $user );
        }
        ?>

        <form name="validate_2fa_form" id="loginform"
              action="<?php echo esc_url( $this->loginUrl( array( 'action' => 'validate_2fa' ), 'login_post' ) ); ?>"
              method="post" autocomplete="off">
            <input type="hidden" name="wp-auth-id" id="wp-auth-id" value="<?php echo esc_attr( $user->ID ); ?>"/>
            <input type="hidden" name="wp-auth-nonce" id="wp-auth-nonce"
                   value="<?php echo esc_attr( $login_nonce ); ?>"/>
            <?php if ( $interim_login ) { ?>
                <input type="hidden" name="interim-login" value="1"/>
            <?php } else { ?>
                <input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>"/>
            <?php } ?>
            <input type="hidden" name="rememberme" id="rememberme" value="<?php echo esc_attr( $remember ); ?>"/>

            <?php $service->authenticationPage( $user ); ?>
        </form>
        <style>
            /* Prevent Jetpack from hiding our controls, see https://github.com/Automattic/jetpack/issues/3747 */
            .jetpack-sso-form-display #loginform > p,
            .jetpack-sso-form-display #loginform > div {
                display: block;
            }

            form#loginform p.hmwp-prompt {
                margin-bottom: 1.5em;
                text-align: center;
            }

            form#loginform .input.authcode {
                letter-spacing: .3em;
            }

            form#loginform .input.authcode::placeholder {
                opacity: 0.5;
            }
        </style>
        <script>
            (function () {
                // Enforce numeric-only input for numeric inputmode elements.
                const form = document.querySelector('#loginform'),
                    inputEl = document.querySelector('input.authcode[inputmode="numeric"]'),
                    expectedLength = inputEl?.dataset.digits || 0;

                if (inputEl) {
                    let spaceInserted = false;
                    inputEl.addEventListener(
                        'input',
                        function () {
                            let value = this.value.replace(/[^0-9 ]/g, '').trimStart();

                            if (!spaceInserted && expectedLength && value.length === Math.floor(expectedLength / 2)) {
                                value += ' ';
                                spaceInserted = true;
                            } else if (spaceInserted && !this.value) {
                                spaceInserted = false;
                            }

                            this.value = value;

                            // Auto-submit if it's the expected length.
                            if (expectedLength && value.replace(/ /g, '').length == expectedLength) {
                                if (undefined !== form.requestSubmit) {
                                    form.requestSubmit();
                                    form.submit.disabled = "disabled";
                                }
                            }
                        }
                    );
                }
            })();
        </script>
        <?php
        if ( ! function_exists( 'login_footer' ) ) {
            include_once _HMWP_THEME_DIR_ . 'wplogin/footer.php';
        }

        if ( function_exists( 'login_footer' ) ) {
            login_footer();
        }

        exit();
    }

    /**
     * Generate the two-factor login form URL.
     *
     * @param array $params List of query argument pairs to add to the URL.
     * @param string $scheme URL scheme context.
     *
     * @return string
     */
    public static function loginUrl( $params = array(), $scheme = 'login' ) {
        if ( ! is_array( $params ) ) {
            $params = array();
        }

        $params = urlencode_deep( $params );

        if ( defined( 'HMWP_DEFAULT_LOGIN' ) && HMWP_DEFAULT_LOGIN ) {
            if ( stripos( HMWP_DEFAULT_LOGIN, site_url() ) !== false ) {
                $login = HMWP_DEFAULT_LOGIN;
            } else {
                $login = site_url( HMWP_DEFAULT_LOGIN, $scheme );
            }
        } else {
            $login = site_url( 'wp-login.php', $scheme );
        }

        return add_query_arg( $params, $login );
    }

    /**
     * Validate 2FA login attempt with the current attempt type
     *
     * @return void
     * @throws Exception
     */
    public function validateTwoFactor() {

        $user_id     = HMWP_Classes_Tools::getValue( 'wp-auth-id' );
        $nonce       = HMWP_Classes_Tools::getValue( 'wp-auth-nonce' );
        $redirect_to = HMWP_Classes_Tools::getValue( 'redirect_to', '' );

        if ( ! $user_id || ! $nonce ) {
            return;
        }

        //check if it's a valid user
        if ( ! $user = get_userdata( $user_id ) ) {
            return;
        }

        //check the current user nonce
        if ( true !== $this->verifyLoginNonce( $user->ID, $nonce ) ) {
            wp_safe_redirect( home_url() );
            exit;
        }

        /** @var HMWP_Models_Twofactor_Email $emailService */
        $emailService = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Twofactor_Email' );

        /** @var HMWP_Models_Twofactor_Tftotp $twoFactorService */
        $twoFactorService = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Twofactor_Tftotp' );

        /** @var HMWP_Models_Twofactor_Passkey $passkeyService */
        $passkeyService = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Twofactor_Passkey' );

        /** @var HMWP_Models_Twofactor_Codes $backupService */
        $backupService = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Twofactor_Codes' );

        // If none of the services are active
        if ( ! $twoFactorService->isServiceActive( $user ) && ! $emailService->isServiceActive( $user ) && ! $passkeyService->isServiceActive( $user ) ) {
            return;
        }

        if ( $emailService->isServiceActive( $user ) ) {

            // Allow the provider to re-send email code.
            if ( true === $emailService->preAuthentication( $user ) ) {
                $login_nonce = $this->createLoginNonce( $user->ID );

                if ( empty( $login_nonce ) ) {
                    wp_die( esc_html__( 'Failed to create a login nonce.', 'hide-my-wp' ) );
                }

                $this->loginHtml( $user, $login_nonce['key'], $redirect_to, '' );
                exit;
            }
        }

        //if 2FA is active for the current user
        if ( $twoFactorService->isServiceActive( $user ) ) {

            // If the form hasn't been submitted, just display the auth form.
            if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) ) { // phpcs:ignore WordPress.Security, no need for escape

                //create login nonce
                $login_nonce = $this->createLoginNonce( $user->ID );
                if ( empty( $login_nonce ) ) {
                    wp_die( esc_html__( 'Failed to create a login nonce.', 'hide-my-wp' ) );
                }

                //show 2fa login form
                $this->loginHtml( $user, $login_nonce['key'], $redirect_to, '' );
                exit;
            }

        }

        // Rate limit 2FA authentication attempts.
        $this->checkUserAttemptsLimit( $user );

        // Verify & Validate the 2FA process
        if ( ( $backupService->isServiceActive( $user ) && $backupService->validateAuthentication( $user ) ) ||
             ( $twoFactorService->isServiceActive( $user ) && $twoFactorService->validateAuthentication( $user ) ) ||
             ( $emailService->isServiceActive( $user ) && $emailService->validateAuthentication( $user ) ) ||
             ( $passkeyService->isServiceActive( $user ) && $passkeyService->validateAuthentication( $user ) )
        ) {

            // Delete login nonce on success
            $this->deleteLoginNonce( $user->ID );

            // Delete fail logins and attempts
            HMWP_Classes_Tools::deleteUserMeta( self::USER_FAILURES, $user->ID );
            HMWP_Classes_Tools::deleteUserMeta( self::USER_ATTEMPTS, $user->ID );
            HMWP_Classes_Tools::saveUserMeta( self::USER_SUCCESS, time(), $user->ID );

            // Check if remember me option is on
            $remember = $this->remember();

            // Save the current device is checked by the user
            $this->rememberDevice( $user->ID );

            // Add compatibility with other plugins
            remove_filter( 'send_auth_cookies', '__return_false', PHP_INT_MAX );
            wp_set_auth_cookie( $user->ID, $remember );

            // Add filter for success login
            do_action( 'hmwp_user_auth_success', $user, 'two_factor_login' );

            // Must be global because that's how login_header() uses it.
            global $interim_login;
            $interim_login = HMWP_Classes_Tools::getValue( 'interim-login' );

            if ( $interim_login ) {
                $customize_login = isset( $_REQUEST['customize-login'] ); // phpcs:ignore WordPress.Security, isset check only
                if ( $customize_login ) {
                    wp_enqueue_script( 'customize-base' );
                }
                $message       = '<p class="message">' . __( 'You have logged in successfully.', 'hide-my-wp' ) . '</p>';
                $interim_login = 'success';
                login_header( '', $message );
                do_action( 'login_footer' );
                exit;
            }

            //redirect
            $redirect_to = apply_filters( 'login_redirect', $redirect_to, $redirect_to, $user );
            if ( wp_safe_redirect( esc_url_raw( $redirect_to ) ) ) {
                exit;
            }

        }

        do_action( 'wp_login_failed', $user->user_login, new WP_Error( 'hmwp_invalid_attempt', __( 'ERROR: Invalid verification code.', 'hide-my-wp' ) ) );

        // Store the last time a failed login occured.
        HMWP_Classes_Tools::saveUserMeta( self::USER_FAILURES, time(), $user->ID );

        // Store the number of failed login attempts.
        $attempts = HMWP_Classes_Tools::getUserMeta( self::USER_ATTEMPTS, $user->ID );
        HMWP_Classes_Tools::saveUserMeta( self::USER_ATTEMPTS, ( (int) $attempts + 1 ), $user->ID );

        // Create login nonce
        $login_nonce = $this->createLoginNonce( $user->ID );
        if ( empty( $login_nonce ) ) {
            wp_die( esc_html__( 'Failed to create a login nonce.', 'hide-my-wp' ) );
        }

        // Show the login form with error
        $this->loginHtml( $user, $login_nonce['key'], $redirect_to, esc_html__( 'ERROR: Invalid verification code.', 'hide-my-wp' ) );
        exit;

    }

    /**
     * Validates passkey login by verifying the user nonce and processing the passkey login request.
     *
     * @throws Exception
     */
    public function validatePasskeyLogin() {

        $user_id = (int) HMWP_Classes_Tools::getValue( 'user_id' );
        $nonce   = HMWP_Classes_Tools::getValue( '_ajax_nonce' );

        //check the current user nonce
        if ( true !== $this->verifyLoginNonce( $user_id, $nonce, false ) ) {
            wp_send_json_error( __( 'Invalid request.', 'hide-my-wp' ) );
        }

        /** @var HMWP_Models_Twofactor_Passkey $passkeyService */
        $passkeyService = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Twofactor_Passkey' );

        $options = $passkeyService->passkeyLogin( $user_id );

        wp_send_json_success( $options );
    }

    /**
     * Check the fail attempt limits and notify the user
     *
     * @param WP_User $user The WP_User instance representing the currently logged-in user.
     *
     * @return void
     * @throws Exception
     */
    public function checkUserAttemptsLimit( $user ) {
        $redirect_to = HMWP_Classes_Tools::getValue( 'redirect_to', '' );

        // Rate limit 2FA authentication attempts.
        if ( $this->rateLimitReached( $user ) ) {
            $time_delay = $this->getUserTimeDelay( $user );
            $last_login = $this->getLastUserLoginFail( $user );

            $error = str_replace( '{time}', human_time_diff( $last_login + $time_delay ), HMWP_Classes_Tools::getOption( 'hmwp_2falogin_message' ) );

            // Trigger the login fail hook from WP
            do_action( 'wp_login_failed', $user->user_login, $error );

            // Create login nonce
            $login_nonce = $this->createLoginNonce( $user->ID );
            if ( empty( $login_nonce ) ) {
                wp_die( esc_html__( 'Failed to create a login nonce.', 'hide-my-wp' ) );
            }

            // Show the login form with error
            $this->loginHtml( $user, $login_nonce['key'], $redirect_to, esc_html( $error ) );
            exit;
        }

    }

    /**
     * Show previous fail attempts for the current user
     *
     * @param WP_User $user The WP_User instance representing the currently logged-in user.
     */
    public function showNotices( $user ) {
        $user_failures = $this->getLastUserLoginFail( $user );
        $user_attempts = (int) HMWP_Classes_Tools::getUserMeta( self::USER_ATTEMPTS, $user->ID );

        if ( $user_failures && $user_attempts ) {
            echo '<div id="login_notice" class="message"><strong>';
            echo wp_kses_post( str_replace( array( '{count}',  '{time}' ), array(
                    number_format_i18n( $user_attempts ),
                    human_time_diff( $user_failures, time() )
            ), HMWP_Classes_Tools::getOption( 'hmwp_2falogin_fail_message' ) ) );
            echo '</strong></div>';
        }
    }

    /**
     * Create the login nonce.
     *
     * @param int $user_id User ID.
     *
     * @return array|false
     */
    private function createLoginNonce( $user_id ) {

        //create nonce for this user login
        $login_nonce = array(
                'user_id'    => (int) $user_id,
                'expiration' => time() + ( 10 * MINUTE_IN_SECONDS ),
                'key'        => wp_hash( $user_id . wp_rand( 11111, 99999 ) . microtime(), 'nonce' ),
        );

        // Store the nonce hashed to avoid leaking it via database access.
        if ( $hashed_key = $this->hashLoginNonce( $login_nonce ) ) {
            $login_nonce_stored = array(
                    'expiration' => $login_nonce['expiration'],
                    'key'        => $hashed_key,
            );
            HMWP_Classes_Tools::saveUserMeta( 'nonce_raw', $login_nonce, $user_id );

            if ( HMWP_Classes_Tools::saveUserMeta( self::USER_NONCE, $login_nonce_stored, $user_id ) ) {
                return $login_nonce;
            }
        }

        return false;
    }

    /**
     * Verify the user nonce.
     *
     * @param int $user_id User ID of the user who logged in.
     * @param string $nonce Login nonce from user meta.
     * @param bool $delete Delete the nonce after verification.
     *
     * @return bool
     */
    public function verifyLoginNonce( $user_id, $nonce, $delete = true ) {

        //get the current nonce from DB
        $login_nonce = HMWP_Classes_Tools::getUserMeta( self::USER_NONCE, $user_id );

        //check the integrity of the nonce
        if ( ! $login_nonce || empty( $login_nonce['key'] ) || empty( $login_nonce['expiration'] ) ) {
            return false;
        }

        $db_nonce = array(
                'user_id'    => (int) $user_id,
                'expiration' => $login_nonce['expiration'],
                'key'        => $nonce,
        );

        //check if the current hash matched the DB user nonce
        $db_hash      = $this->hashLoginNonce( $db_nonce );
        $hashes_match = hash_equals( $login_nonce['key'], $db_hash );

        //Check the nonce expiration
        if ( $hashes_match && time() < $login_nonce['expiration'] ) {
            return true;
        }

        // Require fresh nonce if valid, but the login fails.
        if ( $delete ) {
            $this->deleteLoginNonce( $user_id );
        }

        return false;
    }

    /**
     * Encode the login nonce for secure login
     *
     * @param array $nonce
     *
     * @return false|string
     */
    private function hashLoginNonce( $nonce ) {
        $message = wp_json_encode( $nonce );

        if ( ! $message ) {
            return false;
        }

        return wp_hash( $message, 'nonce' );
    }

    /**
     * Delete the login nonce.
     *
     * @param int $user_id User ID.
     *
     * @return bool
     */
    private function deleteLoginNonce( $user_id ) {
        return HMWP_Classes_Tools::deleteUserMeta( self::USER_NONCE, $user_id );
    }

    /**
     * Save timestamp for the current logged user
     *
     * @param WP_User $user The WP_User instance representing the currently logged-in user.
     *
     * @return WP_User|WP_Error
     */
    public function collectAuthLogin( $user ) {

        if ( ! is_wp_error( $user ) ) {
            HMWP_Classes_Tools::saveUserMeta( self::USER_SUCCESS, time(), $user->ID );
        }

        return $user;
    }

    /**
     * Save all user cookies for later management
     *
     * @param string $cookie
     *
     * @return void
     */
    public function collectAuthCookieTokens( $cookie ) {
        if ( function_exists( 'wp_parse_auth_cookie' ) ) {
            $parsed = wp_parse_auth_cookie( $cookie );

            if ( ! empty( $parsed['token'] ) ) {
                self::$tokens[] = $parsed['token'];
            }
        }
    }

    /**
     * Destroy the current cookies for the logged user
     *
     * @param WP_User $user The logged user
     *
     * @return void
     */
    public function destroyCurrentSession( $user ) {
        if ( class_exists( 'WP_Session_Tokens' ) ) {
            $session_manager = WP_Session_Tokens::get_instance( $user->ID );

            foreach ( self::$tokens as $auth_token ) {
                $session_manager->destroy( $auth_token );
            }
        }
    }

    /**
     * Check remember me option on login.
     *
     * @return boolean
     */
    private function remember() {
        $remember = false;

        if ( HMWP_Classes_Tools::getValue( 'rememberme' ) ) {
            $remember = true;
        }

        return $remember;
    }

    /**
     * Stores the current device information for two-factor authentication purposes.
     *
     * @param int $user_id The id of the current user
     *
     * @return void
     */
    private function rememberDevice( $user_id ) {

        if ( HMWP_Classes_Tools::getValue( 'remember_device' ) ) {

            if ( isset( $_SERVER['HTTP_USER_AGENT'] ) && $_SERVER['HTTP_USER_AGENT'] ) { //phpcs:ignore
                $devices   = (array) HMWP_Classes_Tools::getUserMeta( '_hmwp_2fa_devices', $user_id );
                $devices[] = md5( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) . ( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '' ) ); //phpcs:ignore
                $devices   = array_unique( $devices );
                HMWP_Classes_Tools::saveUserMeta( '_hmwp_2fa_devices', $devices, $user_id );
            }

        }

    }


    /**
     * Check if the current device is in the list of remembered devices for the user.
     *
     * This method verifies whether the current device, identified by a combination
     * of the HTTP User-Agent and IP address, has been previously remembered by the user.
     *
     * @param int $user_id The id of the current user
     *
     * @return bool Returns true if the device is remembered, otherwise false.
     */
    public function isRememberDevice( $user_id ) {

        if ( isset( $_SERVER['HTTP_USER_AGENT'] ) && $_SERVER['HTTP_USER_AGENT'] ) { //phpcs:ignore
            $devices = (array) HMWP_Classes_Tools::getUserMeta( '_hmwp_2fa_devices', $user_id );
            $devices = array_filter( $devices );

            if ( in_array( md5( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) . ( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '' ) ), $devices ) ) { //phpcs:ignore WordPress.Security.NonceVerification
                return true;
            }
        }

        return false;

    }

    /**
     * Deletes all remembered devices for the user by resetting the associated metadata.
     *
     * @param int $user_id The id of the current user
     *
     * @return void
     */
    public function deleteRememberDevices( $user_id ) {
        HMWP_Classes_Tools::saveUserMeta( '_hmwp_2fa_devices', array(), $user_id );
    }

    /**
     * Get the timestamp of the last user login fail
     *
     * @param WP_User $user The User.
     *
     * @return int
     */
    public function getLastUserLoginFail( $user ) {
        return (int) HMWP_Classes_Tools::getUserMeta( self::USER_FAILURES, $user->ID );
    }

    /**
     * Determine if a time delay between user two login attempts is reached.
     *
     * @param WP_User $user The User.
     *
     * @return bool True if rate limit is okay, false if not.
     */
    public function rateLimitReached( $user ) {

        $rate_limit  = $this->getUserTimeDelay( $user );
        $last_failed = $this->getLastUserLoginFail( $user );

        $attempt_limit_reached = false;
        if ( $last_failed && ( $last_failed + $rate_limit ) > time() ) {
            $attempt_limit_reached = true;
        }

        /**
         * Filter whether this login attempt limit is reached.
         *
         * @param bool $attempt_limit_reached Whether the user login is rate limited.
         * @param WP_User $user The user attempting to log in.
         */
        return apply_filters( 'hmwp_attempt_limit_reached', $attempt_limit_reached, $user );
    }

    /**
     * Determine the minimum wait between two login attempts for a user.
     *
     * @param WP_User $user The User.
     *
     * @return int Time delay in seconds between login attempts.
     */
    public function getUserTimeDelay( $user ) {

        /** @var int $rate_limit The number of seconds between two attempts. */
        $rate_limit = apply_filters( 'hmwp_min_attempt_seconds', 1 );

        //Number of fail attempts
        if ( $user_failed_logins = HMWP_Classes_Tools::getUserMeta( self::USER_ATTEMPTS, $user->ID ) ) {

            //Check if max attempts is reached
            if ( $user_failed_logins >= HMWP_Classes_Tools::getOption( 'hmwp_2falogin_max_attempts' ) ) {
                /** @var int $rate_limit The maximum number of seconds a user might be locked out for. Default 60 minutes. */
                $rate_limit = HMWP_Classes_Tools::getOption( 'hmwp_2falogin_max_timeout' );
            }

        }

        /**
         * Filters the per-user time duration between two fail login attempts.
         *
         * @param int $rate_limit The number of seconds between two attempts.
         * @param WP_User $user The user attempting to log in.
         */
        return apply_filters( 'hmwp_user_attempt_seconds', $rate_limit, $user );
    }

    /**
     * Get the redable time elapsed string
     *
     * @param int $time
     * @param bool $ago
     *
     * @return string
     * @since 1.0.0
     *
     */
    public function timeElapsed( $time, $ago = true ) {

        if ( is_numeric( $time ) ) {

            if ( $ago ) {
                $etime = time() - $time;
            }

            if ( $etime < 1 ) {
                return gmdate( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $time );
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

                    $time_string = '';
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
                                /* translators: %d: Number of days. */
                                $time_string = _n( '%d day remaining', '%d days remaining', $r, 'hide-my-wp' );
                                break;
                            case 'hour':
                                /* translators: %d: Number of hours. */
                                $time_string = _n( '%d hour remaining', '%d hours remaining', $r, 'hide-my-wp' );
                                break;
                            case 'minute':
                                /* translators: %d: Number of minutes. */
                                $time_string = _n( '%d minute remaining', '%d minutes remaining', $r, 'hide-my-wp' );
                                break;
                            case 'second':
                                /* translators: %d: Number of seconds. */
                                $time_string = _n( '%d second remaining', '%d seconds remaining', $r, 'hide-my-wp' );
                                break;
                        }
                    }

                    return esc_html( sprintf( $time_string, (int) $r ) );
                }
            }

            return gmdate( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $time );
        }

        return '';
    }

    /**
     * Displays admin notices for 2FA services based on the current user's status.
     *
     * This method checks the activation status of 2FA services (email and two-factor authentication)
     * for the current user and displays an error notice if the user is out of backup codes.
     *
     * @return void
     * @throws Exception
     */
    public function adminNotices() {
        $user = wp_get_current_user();

        /** @var HMWP_Models_Twofactor_Email $emailService */
        $emailService = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Twofactor_Email' );

        /** @var HMWP_Models_Twofactor_Tftotp $twoFactorService */
        $twoFactorService = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Twofactor_Tftotp' );

        /** @var HMWP_Models_Twofactor_Codes $backupService */
        $backupService = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Twofactor_Codes' );

        // If none of the services are active
        if ( ! $twoFactorService->isServiceActive( $user ) && ! $emailService->isServiceActive( $user ) ) {
            return;
        }

        // If the service is not active or there are still codes remained
        if ( ! $backupService->isServiceActive( $user ) ) {

            ?>
            <div class="error">
                <p>
				<span>
					<?php
                    /* translators: 1: URL to user profile 2FA section. */
                    echo wp_kses( sprintf( __( '2FA Codes: You are out of backup codes and need <a href="%1$s">new codes!</a>', 'hide-my-wp' ), esc_url( get_edit_user_link( $user->ID ) . '#hmwp_two_factor_options' ) ), array( 'a' => array( 'href' => true ) ) );
                    ?>
				<span>
                </p>
            </div>
            <?php
        }
    }


    /**
     * Sort the user's table by LastLogin
     *
     * @param $sortable_columns
     *
     * @return mixed
     */
    public function manageUsersColumnSort( $sortable_columns ) {
        return array_merge( $sortable_columns, array(
                'hmwp_last_login' => self::USER_SUCCESS,
        ) );
    }


    /**
     * Modifies the query arguments for managing users based on a specified orderby value.
     *
     * @param array $args The query arguments for retrieving user records.
     *                    Includes potential 'orderby' key to determine sorting logic.
     *
     * @return array Modified query arguments including updated meta key and orderby values, if applicable.
     */
    public function manageUsersColumnQuery( $args ) {

        if ( isset( $args['orderby'] ) ) {
            if ( is_string( $args['orderby'] ) ) {
                if ( $args['orderby'] == self::USER_SUCCESS ) {
                    $args['meta_key'] = self::USER_SUCCESS; //phpcs:ignore
                    $args['orderby']  = 'meta_value';
                }
            } elseif ( array_key_exists( self::USER_SUCCESS, $args['orderby'] ) ) {
                $args['meta_key']              = self::USER_SUCCESS; //phpcs:ignore
                $args['orderby']['meta_value'] = $args['orderby'][ self::USER_SUCCESS ]; //phpcs:ignore
                unset( $args['orderby'][ self::USER_SUCCESS ] );
            }
        }

        return $args;
    }


    /**
     * Filter the columns on the Users admin screen.
     *
     * @param array $columns Available columns.
     *
     * @return array          Updated array of columns.
     */
    public function manageUsersColumnHeader( array $columns ) {

        if ( HMWP_Classes_Tools::getOption( 'hmwp_2falogin_status' ) ) {
            $columns['hmwp_status'] = __( '2FA Settings', 'hide-my-wp' );
            if ( ! HMWP_Classes_Tools::isPluginActive( 'wordfence/wordfence.php' ) ) {
                $columns['hmwp_last_login'] = esc_html__( 'Last Login', 'hide-my-wp' );
            }
        }

        return $columns;
    }

    /**
     * Output the 2FA column data on the Users screen.
     *
     * @param string $output The column output.
     * @param string $column_name The column ID.
     * @param int $user_id The user ID.
     *
     * @return string              The column output.
     * @throws Exception
     */
    public function manageUsersColumn( $output, $column_name, $user_id ) {

        if ( 'hmwp_status' == $column_name ) {
            /** @var HMWP_Models_Twofactor_Tftotp $twoFactorService */
            $twoFactorService = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Twofactor_Tftotp' );

            /** @var HMWP_Models_Twofactor_Email $emailService */
            $emailService = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Twofactor_Email' );

            /** @var HMWP_Models_Twofactor_Passkey $passkeyService */
            $passkeyService = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Twofactor_Passkey' );

            $user = get_userdata( $user_id );

            if ( ! $twoFactorService->isServiceActive( $user ) && ! $emailService->isServiceActive( $user ) && ! $passkeyService->isServiceActive( $user ) ) {
                return sprintf( '<span style="color: darkgrey">%s</span>', esc_html__( 'Disabled', 'hide-my-wp' ) );
            } else {
                return sprintf( '<span style="color: darkgreen;" class="dashicons-before dashicons-yes-alt"> %s</span>', esc_html__( 'Active', 'hide-my-wp' ) );
            }
        } elseif ( 'hmwp_last_login' == $column_name ) {
            if ( $last_totp_login = HMWP_Classes_Tools::getUserMeta( self::USER_SUCCESS, $user_id ) ) {
                return sprintf( '<span>%s</span>', $this->timeElapsed( $last_totp_login ) );
            } else {
                return esc_html__( 'Not yet logged in', 'hide-my-wp' );
            }
        }

        return $output;
    }

    /**
     * Delete all 2FA logins
     *
     * @since 1.0.0
     */
    public function deleteTwoFactorLogins() {
        global $wpdb;

        $transient = '_hmwp_totp_%';
        $sql       = "DELETE FROM $wpdb->usermeta WHERE `meta_key` LIKE '%s'";
        $wpdb->query( $wpdb->prepare( $sql, $transient ) ); ///phpcs:ignore

        $transient = '_hmwp_email_%';
        $sql       = "DELETE FROM $wpdb->usermeta WHERE `meta_key` LIKE '%s'";
        $wpdb->query( $wpdb->prepare( $sql, $transient ) ); //phpcs:ignore

        $transient = '_hmwp_backup_%';
        $sql       = "DELETE FROM $wpdb->usermeta WHERE `meta_key` LIKE '%s'";
        $wpdb->query( $wpdb->prepare( $sql, $transient ) ); //phpcs:ignore

    }

}
