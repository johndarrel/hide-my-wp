<?php
/**
 * Unique Login Model
 *
 * @file  The Unique Login file
 * @package HMWPP/Unique Login
 * @since 1.3.0
 */
defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Models_Uniquelogin {

	/** @var string login success */
	const USER_NONCE = '_hmwp_nonce';

    /** @var string the user token param */
	const USER_TOKEN_PARAM = 'hmwpml_token';

	/** @var string the user session expire timestamp */
	const USER_SESSION_EXPIRE = '_hmwpml_expire';

    /** @var string the user session created timestamp */
	const USER_SESSION_CREATED = '_hmwpml_created';

    /** @var string the user token */
	const USER_TOKEN_KEY = '_hmwpml_token';

    /** @var string the user-last login timestamp */
	const USER_LAST_LOGIN = '_hmwpml_last_login';

    /** @var string the user redirect to */
	const USER_REDIRECT = '_hmwpml_redirect_to';

	/**
	 * Get valid temporary user based on token
	 *
	 * @param string $token
	 *
	 * @return array|bool
	 * @since 1.3.0
	 *
	 */
	public function findUserByToken( $token = '' ) {

		if ( empty( $token ) ) {
			return false;
		}

		$args = array(
			'fields'     => 'all',
			'meta_key'   => self::USER_SESSION_EXPIRE, //phpcs:ignore
			'order'      => 'DESC',
			'orderby'    => 'meta_value',
			'meta_query' => array( //phpcs:ignore
				0 => array(
					'key'     => self::USER_TOKEN_KEY,
					'value'   => sanitize_text_field( $token ),
					'compare' => '=',
				),
			),
		);

		$users = new WP_User_Query( $args );

		$users = $users->get_results();
		if ( empty( $users ) ) {
			return false;
		}

		foreach ( $users as $user ) {
			//check if the link is expired
			if ( $this->isExpired( $user->ID ) ) {
				return false;
			}

			//get user details
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
	 * @since 1.0.0
	 */
	public function getUserDetails( $user ) {
		$details = array();

		if ( ! $user instanceof WP_User ) {
			return json_decode( wp_json_encode( $details ) );
		}

		$details['redirect_to'] = HMWP_Classes_Tools::getUserMeta( self::USER_REDIRECT, $user->ID );
		$details['expire']      = HMWP_Classes_Tools::getUserMeta( self::USER_SESSION_EXPIRE, $user->ID );

		$details['last_login_time'] = HMWP_Classes_Tools::getUserMeta( self::USER_LAST_LOGIN, $user->ID );
		$details['last_login']      = esc_html__( 'Not yet logged in', 'hide-my-wp' );
		if ( ! empty( $details['last_login_time'] ) ) {
			$details['last_login'] = $this->timeElapsed( $details['last_login_time'], true );
		}

		return json_decode( wp_json_encode( $details ) );
	}

	/**
	 * Create a Unique login
	 *
	 * @return array
	 *
	 * @throws Exception
	 * @since 1.3.0
	 */
	public function createUniqueLogin( $data ) {

		$result = array(
			'error' => true
		);

		$expire      = ! empty( $data['expire'] ) ? $data['expire'] : HMWP_Classes_Tools::getOption( 'hmwp_uniquelogin_timeout' );
		$email       = isset( $data['user_email'] ) ? sanitize_email( $data['user_email'] ) : '';
		$redirect_to = ! empty( $data['redirect_to'] ) ? sanitize_text_field( $data['redirect_to'] ) : '';

		if ( empty( $data['user_email'] ) ) {
			$result['errcode'] = 'invalid_user';
			$result['message'] = esc_html__( 'Empty email address.', 'hide-my-wp' );
		} elseif ( ! is_email( $data['user_email'] ) ) {
			$result['errcode'] = 'invalid_user';
			$result['message'] = esc_html__( 'Invalid email address.', 'hide-my-wp' );
		} elseif ( $email <> '' && ! email_exists( $email ) ) {
			$result['errcode'] = 'invalid_user';
			$result['message'] = esc_html__( 'User does not exist.', 'hide-my-wp' );
		} else {
			/** @var WP_User|WP_Error $user */
			$user = get_user_by( 'email', $email );

			if ( is_wp_error( $user ) ) {
				$code = $user->get_error_code();

				$result['errcode'] = $code;
				$result['message'] = $user->get_error_message( $code );

			} else {

				$user_id = $user->ID;

				if ( HMWP_Classes_ObjController::getClass( 'HMWP_Models_Templogin' )->isValidTempLogin( $user_id ) ) {
					$result['errcode'] = 'invalid_user';
					$result['message'] = esc_html__( 'This user is a temporary user.', 'hide-my-wp' );

					return $result;
				}

				HMWP_Classes_Tools::saveUserMeta( self::USER_SESSION_CREATED, $this->gtmTimestamp(), $user_id );
				HMWP_Classes_Tools::saveUserMeta( self::USER_SESSION_EXPIRE, ( $this->gtmTimestamp() + $expire ), $user_id );
				HMWP_Classes_Tools::saveUserMeta( self::USER_TOKEN_KEY, $this->generateToken( $user_id ), $user_id );
				HMWP_Classes_Tools::saveUserMeta( self::USER_REDIRECT, $redirect_to, $user_id );

				$result['error']   = false;
				$result['user_id'] = $user_id;
			}
		}

		return $result;

	}

	/**
	 * Save the user last login time for the activity log
	 *
	 * @param $user
	 *
	 * @return void
	 */
	public function saveUserLastLogin( $user ) {
		HMWP_Classes_Tools::saveUserMeta( self::USER_LAST_LOGIN, $this->gtmTimestamp(), $user->ID );
	}

	/**
	 * Get current GMT date time
	 *
	 * @return false|int
	 * @since 1.0.0
	 *
	 */
	public function gtmTimestamp() {
		return strtotime( gmdate( 'Y-m-d H:i:s', time() ) );
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
        }

        return $time;
    }

	/**
	 * Check if unique login expired
	 *
	 * @param int $user_id
	 *
	 * @return bool
	 *
	 * @since 1.0.0
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
	 * Set the current unique login as expired
	 *
	 * @param int $user_id
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	public function setExpired( $user_id = 0 ) {

		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		if ( empty( $user_id ) ) {
			return false;
		}

		HMWP_Classes_Tools::saveUserMeta( self::USER_SESSION_EXPIRE, time() - 3600 * 24, $user_id );

		return true;

	}

	/**
	 * Get unique login url
	 *
	 * @param $user_id
	 *
	 * @return string
	 * @since 1.0.0
	 *
	 */
	public function getUniqueLoginUrl( $user_id ) {

		if ( empty( $user_id ) ) {
			return '';
		}

		$token = HMWP_Classes_Tools::getUserMeta( self::USER_TOKEN_KEY, $user_id );
		if ( empty( $token ) ) {
			return '';
		}

		$login_url = add_query_arg( self::USER_TOKEN_PARAM, $token, trailingslashit( home_url() ) );

		// Make it compatible with iThemes Security plugin with Custom URL Login enabled
		$login_url = apply_filters( 'itsec_notify_admin_page_url', $login_url );

		return apply_filters( 'hmwp_unique_login_link', $login_url, $user_id );

	}

	/**
	 * Generate and email the user unique login.
	 *
	 * @param WP_User $user The WP_User instance representing the currently logged-in user.
	 * @param string $url The URL of the unique login
	 *
	 * @return bool Whether the email contents were sent successfully.
	 */
	public function sendLoginUrl( $user, $url ) {

        $subject = HMWP_Classes_Tools::getOption( 'hmwp_uniquelogin_email_subject' );
        $message = HMWP_Classes_Tools::getOption( 'hmwp_uniquelogin_email_message' );

        if ( $subject && $message ) {

            /* translators: 1: Magic login URL wrapped with line breaks. */
            if ( strpos( $message, '%s' ) !== false ) {
                $message = wp_strip_all_tags( sprintf( $message,  PHP_EOL . esc_url( $url ) . PHP_EOL . PHP_EOL ) );
            }

            $subject = apply_filters( 'hmwp_unique_login_subject', $subject, $user->ID );
            $message = apply_filters( 'hmwp_unique_login_message', $message, $url, $user->ID );

            return wp_mail( $user->user_email, $subject, $message );

        }

        return false;

	}

	/**
	 * Generate unique Login Token
	 *
	 * @param $user_id
	 *
	 * @return false|string
	 *
	 * @since 1.0.0
	 */
	public function generateToken( $user_id ) {
		$byte_length = 32;

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
	 * Show Notice on the login page
	 *
	 * @param $message
	 * @param $error
	 *
	 * @return void
	 */
	public function showNotices( $message, $error = false ) {

		if ( ! function_exists( 'login_header' ) ) {
			// We really should migrate login_header() out of `wp-login.php` so it can be called from an includes file.
			include_once _HMWP_THEME_DIR_ . 'wplogin/header.php';
		}

        if ( function_exists( 'login_header' ) ) {
            login_header();
        }

		//Show errors on top
		?>
        <div id="login_notice" class="message <?php echo( $error ? 'notice-error' : '' ) ?>">
            <strong><?php echo wp_kses_post( $message ) ?></strong></div>
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
	 * Generates the html form for the second step of the authentication process.
	 *
	 * @param string $error_msg Optional. Login error message.
	 */
	public function loginHtml( $error_msg = '' ) {

		$redirect_to = HMWP_Classes_Tools::getValue( 'redirect_to', admin_url() );
        $button_text = HMWP_Classes_Tools::getOption( 'hmwp_uniquelogin_title' );

		if ( $page_name = $this->isWooCommerceLoginPage() ) {
			$redirect_to = wp_validate_redirect( apply_filters( 'woocommerce_login_redirect', $page_name ), admin_url() );
		}

		if ( HMWP_Classes_Tools::getValue( 'interim-login' ) ) {
			return;
		}

		if ( ! empty( $error_msg ) ) {
			echo '<div id="login_error"><strong>' . esc_html( $error_msg ) . '</strong><br /></div>';
		}
		?>
        <div id="unique_login_wrap">
            <div id="unique_login_separator">
                <hr>
            </div>
            <div id="unique_login">
                <input type="button" name="unique_login_button" id="unique_login_button" class="button button-secondary" value="<?php echo esc_attr( $button_text ); ?>">
            </div>
        </div>
        <div id="unique_login_form">
            <p style="font-size: 1rem; font-weight: 600; margin: 10px 0;">
                <?php echo esc_html( $button_text ); ?>
            </p>
            <p>
                <input type="hidden" name="action" value="validate_magic_link"/>
				<?php wp_nonce_field( 'validate_magic_link', self::USER_NONCE ); ?>
                <label for="user_email"><?php echo esc_html__( 'Email Address', 'hide-my-wp' ); ?></label>
                <input type="text" name="user_email" id="user_email" class="input" value="<?php echo esc_attr( HMWP_Classes_Tools::getValue( 'log' ) ); ?>" size="20" autocapitalize="off" autocomplete="username"/>
                <input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>"/>
            </p>
            <p class="submit">
                <input type="submit" id="unique_login_submit" class="button button-primary button-large" value="<?php echo esc_attr__( 'Send', 'hide-my-wp' ); ?>"/>
                <input type="button" id="unique_login_cancel" class="button button-secondary button-large" value="< <?php echo esc_attr__( 'Back', 'hide-my-wp' ); ?>"/>
            </p>
        </div>
        <style>
            form #unique_login_wrap {
                display: none;
            }

            form #unique_login_wrap #unique_login_separator {
                position: relative;
                margin-bottom: 1.5rem;
                margin-top: 1.5rem;
            }

            form #unique_login_wrap #unique_login_separator hr {
                border-width: 0;
                border-top: 1px solid #cbd5e1;
                color: #fff;
                height: 0;
            }

            form #unique_login_wrap #unique_login_separator span {
                position: absolute;
                font-size: .9rem;
                color: #cbd5e1;
                padding-right: .5rem;
                padding-left: .5rem;
                background-color: #ffffff;
                display: inline-block;
                top: -10px;
                left: 42%;
            }

            form #unique_login_wrap #unique_login {
                margin: 5px 0 20px;
                clear: both;
            }

            form #unique_login_wrap #unique_login input#unique_login_button {
                width: 100%;
            }

            #unique_login_form {
                display: none;
            }

            #unique_login_form #user_email {
                font-size: 24px;
                line-height: 1.33333333;
                width: 100%;
                border-width: 0.0625rem;
                padding: 0.1875rem 0.3125rem;
                margin: 0 6px 16px 0;
                min-height: 40px;
                max-height: none;
            }
        </style>
        <script>
            (function () {
                const unique_login_form = document.querySelector('#unique_login_form'),
                    wrap = document.querySelector('#unique_login_wrap'),
                    button = document.querySelector('#unique_login_button');

                var login_form = document.querySelector('#loginform');
                if (document.querySelector('.woocommerce-form') !== null) {
                    login_form = document.querySelector('.woocommerce-form');
                }

                if (login_form !== null) {
                    wrap.style.display = 'block';

                    login_form.parentElement.insertBefore(unique_login_form, login_form);
                    unique_login_form.innerHTML = '<form id="magicloginform" method="post">' + unique_login_form.innerHTML + '</form>';

                    button.addEventListener("click", function () {
                        login_form.style.display = 'none';
                        unique_login_form.style.display = 'block';

                        document.querySelector('#unique_login_cancel').addEventListener("click", function () {
                            unique_login_form.style.display = 'none';
                            login_form.style.display = 'block';
                        });
                    });
                }
            })();
        </script>
		<?php
	}

	/**
	 * Generate the two-factor login form URL.
	 *
	 * @param array $params List of query argument pairs to add to the URL.
	 * @param string $scheme URL scheme context.
	 *
	 * @return string
	 */
	public function loginUrl( $params = array(), $scheme = 'login' ) {
		if ( ! is_array( $params ) ) {
			$params = array();
		}

		$params = urlencode_deep( $params );

		if ( $myaccount = $this->isWooCommerceLoginPage() ) {
			return add_query_arg( $params, site_url( $myaccount, $scheme ) );
		}

		return add_query_arg( $params, site_url( 'wp-login.php', $scheme ) );
	}

	/**
	 * Check if the current page is a woocommerce account page
	 *
	 * @return false|string return the woocommerce account page
	 */
	public function isWooCommerceLoginPage() {

		if ( HMWP_Classes_Tools::isPluginActive( 'woocommerce/woocommerce.php' ) ) {
			global $wp;
			if ( isset( $wp->request ) && $post_id = get_option( 'woocommerce_myaccount_page_id' ) ) {
				if ( $post = get_post( $post_id ) ) {
					if ( basename( $wp->request ) == $post->post_name ) {
						return $post->post_name;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Set attempt as brute force
	 *
	 * @return void
	 * @throws Exception
	 */
	public function setFailAttempt() {
		if ( HMWP_Classes_Tools::getOption( 'hmwp_bruteforce' ) && class_exists( 'HMWP_Classes_ObjController') ) {
			$bruteForceModel = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Brute' );

			// Register the process as failed
            if( method_exists( $bruteForceModel, 'processIp' ) ) {
	            $bruteForceModel->processIp( 'failed_attempt' );
            }else{
                // Deprecated from version > 8.2
	            $bruteForceModel->brute_call( 'failed_attempt' );
            }
		}
	}

}
