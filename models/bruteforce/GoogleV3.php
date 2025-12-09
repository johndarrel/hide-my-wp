<?php
/**
 * Brute Force Protection Model
 * Called from Brute Force Class
 *
 * @file  The Brute Force Google V3 Recaptcha file
 * @package HMWP/BruteForce/GoogleV3
 * @since 8.1
 */

defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

class HMWP_Models_Bruteforce_GoogleV3 extends HMWP_Models_Bruteforce_Abstract {


    /**
     * @var bool Prevent from loading Google script more than once
     */
    private $loaded = false;

	/**
	 * Verifies the Google Captcha while logging in.
	 *
	 * @param  mixed  $user
	 * @param  mixed  $response
	 *
	 * @return mixed $user Returns the user if the math is correct
	 * @throws WP_Error message if the math is wrong
	 */
	public function authenticate( $user, $response ) {

		$error_message = $this->call();

		if ( $error_message ) {
			$user = new WP_Error( 'authentication_failed', $error_message );
		}

		return $user;
	}


	/**
	 * Call the reCaptcha V2 from Google
	 */
	public function call() {
		$error_message = false;

		if ( ! HMWP_Classes_Tools::getOption( 'brute_use_captcha_v3' ) ) {
			return false;
		}

		$error_codes = array( 'missing-input-secret'   => esc_html__( 'The secret parameter is missing.', 'hide-my-wp' ),
		                      'invalid-input-secret'   => esc_html__( 'The secret parameter is invalid or malformed.', 'hide-my-wp' ),
		                      'timeout-or-duplicate'   => esc_html__( 'The response parameter is invalid or malformed.', 'hide-my-wp' ),
		                      'missing-input-response' => esc_html__( 'Empty ReCaptcha. Please complete reCaptcha.', 'hide-my-wp' ),
		                      'invalid-input-response' => esc_html__( 'Invalid ReCaptcha. Please complete reCaptcha.', 'hide-my-wp' ),
		                      'bad-request'            => esc_html__( 'The response parameter is invalid or malformed.', 'hide-my-wp' )
		);

		$captcha = HMWP_Classes_Tools::getValue( 'g-recaptcha-response' );
		$secret  = HMWP_Classes_Tools::getOption( 'brute_captcha_secret_key_v3' );

		if ( $secret <> '' ) {
			$response = json_decode( HMWP_Classes_Tools::hmwp_remote_get( "https://www.google.com/recaptcha/api/siteverify?secret=$secret&response=" . $captcha . "&remoteip=" . $_SERVER['REMOTE_ADDR'] ), true );

			if ( isset( $response['success'] ) && ! $response['success'] ) {
				//If captcha errors, let the user login and fix the error
				if ( isset( $response['error-codes'] ) && ! empty( $response['error-codes'] ) ) {
					foreach ( $response['error-codes'] as $error_code ) {
						if ( isset( $error_codes[ $error_code ] ) ) {
							$error_message = $error_codes[ $error_code ];
						}
					}
				}

				if ( ! $error_message ) {
					$error_message = sprintf( esc_html__( '%sIncorrect ReCaptcha%s. Please try again.', 'hide-my-wp' ), '<strong>', '</strong>' );
				}
			}

		}

		return $error_message;
	}


	/**
	 * reCAPTCHA head and login form
	 */
	public function head() {
        // Return is the header is already loaded
        if ($this->loaded) return;

        ?>
        <script src='https://www.google.com/recaptcha/api.js?render=<?php echo esc_attr( HMWP_Classes_Tools::getOption( 'brute_captcha_site_key_v3' ) ) ?>' async defer></script>
        <?php

        $this->loaded = true;
	}

	/**
	 * reCAPTCHA head and login form
	 */
	public function form() {
		if ( HMWP_Classes_Tools::getOption( 'brute_captcha_site_key_v3' ) <> '' && HMWP_Classes_Tools::getOption( 'brute_captcha_secret_key_v3' ) <> '' ) {
			global $hmwp_bruteforce;

			//load header first if not triggered
			if ( ! $hmwp_bruteforce && ! did_action( 'login_head' ) ) {
				$this->head();
			}

			?>
            <script>
                function reCaptchaSubmit(e) {
                    var form = this;
                    e.preventDefault();
                    if (typeof grecaptcha !== 'undefined') {
                        grecaptcha.ready(function () {
                            grecaptcha.execute('<?php echo esc_attr( HMWP_Classes_Tools::getOption( 'brute_captcha_site_key_v3' ) ) ?>', {action: 'submit'}).then(function (token) {
                                try {
                                    var input = document.createElement("input");
                                    input.type = "hidden";
                                    input.name = "g-recaptcha-response";
                                    input.value = token;
                                    form.appendChild(input);
                                    var input = document.createElement("input");
                                    input.type = "hidden";
                                    input.name = "login";
                                    form.appendChild(input);
                                } catch (err) {
                                    console.warn("reCAPTCHA error", err);
                                }
                                HTMLFormElement.prototype.submit.call(form);
                            });
                        });
                    } else {
                        HTMLFormElement.prototype.submit.call(form);
                    }
                }

                if (document.getElementsByTagName("form").length > 0) {
                    var x = document.getElementsByTagName("form");
                    for (var i = 0; i < x.length; i++) {
                        x[i].addEventListener("submit", reCaptchaSubmit);
                    }
                }
            </script>
			<?php
		}
	}

}
