<?php
/**
 * Brute Force Protection Model
 * Called from Brute Force Class
 *
 * @file  The Brute Force Google V2 Recaptcha file
 * @package HMWP/BruteForce/GoogleV2
 * @since 8.1
 */

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Models_Bruteforce_GoogleV2 extends HMWP_Models_Bruteforce_Abstract {

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
		$error_codes   = array(
			'missing-input-secret'   => esc_html__( 'The secret parameter is missing.', 'hide-my-wp' ),
			'invalid-input-secret'   => esc_html__( 'The secret parameter is invalid or malformed.', 'hide-my-wp' ),
			'timeout-or-duplicate'   => esc_html__( 'The response parameter is invalid or malformed.', 'hide-my-wp' ),
			'missing-input-response' => esc_html__( 'Empty ReCaptcha. Please complete reCaptcha.', 'hide-my-wp' ),
			'invalid-input-response' => esc_html__( 'Invalid ReCaptcha. Please complete reCaptcha.', 'hide-my-wp' )
		);

		$captcha = HMWP_Classes_Tools::getValue( 'g-recaptcha-response', false );
		$secret  = HMWP_Classes_Tools::getOption( 'brute_captcha_secret_key' );

		if ( $secret <> '' ) {
			$response = json_decode( $this->siteVerify( $secret, $captcha ), true );

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
                    /* translators: 1: Opening <strong> tag, 2: Closing </strong> tag. */
                    $error_message = wp_kses_post( sprintf( __( '%1$sIncorrect ReCaptcha%2$s. Please try again.', 'hide-my-wp' ), '<strong>', '</strong>' ) );
                }
			}
		}

		return $error_message;
	}


	/**
	 * reCAPTCHA head and login form
	 */
	public function head() {
        ?><script src='https://www.google.com/recaptcha/api.js?hl=<?php echo esc_attr( HMWP_Classes_Tools::getOption( 'brute_captcha_language' ) <> '' ? HMWP_Classes_Tools::getOption( 'brute_captcha_language' ) : get_locale() ) ?>' async defer></script><?php //phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript ?>
        <style> #login {  min-width: 354px; } </style><?php
	}

	/**
	 * reCAPTCHA head and login form
	 */
	public function form() {
		if ( HMWP_Classes_Tools::getOption( 'brute_captcha_site_key' ) <> '' && HMWP_Classes_Tools::getOption( 'brute_captcha_secret_key' ) <> '' ) {
			global $hmwp_bruteforce;

			//load header first if not triggered
			if ( ! $hmwp_bruteforce && ! did_action( 'login_head' ) ) {
				$this->head();
			}

            ?><div class="g-recaptcha" data-sitekey="<?php echo esc_attr( HMWP_Classes_Tools::getOption( 'brute_captcha_site_key' ) ) ?>" data-theme="<?php echo esc_attr( HMWP_Classes_Tools::getOption( 'brute_captcha_theme' ) ) ?>" data-callback="hmwpRecaptchaSolved" data-expired-callback="hmwpRecaptchaReset" data-error-callback="hmwpRecaptchaReset" style="margin: 12px 0 24px 0;"></div>
            <script>
                (function () {
                    // Keep the login button disabled until the visitor solves the checkbox
                    function hmwpSubmitBtn() {
                        var w = document.querySelector('.g-recaptcha');
                        var f = w ? w.closest('form') : null;
                        return f ? f.querySelector('[type=submit]') : null;
                    }
                    function hmwpToggle(on) {
                        var b = hmwpSubmitBtn();
                        if (b) { b.disabled = !on; }
                    }
                    window.hmwpRecaptchaSolved = function () { hmwpToggle(true); };
                    window.hmwpRecaptchaReset = function () { hmwpToggle(false); };
                    function hmwpInit() {
                        hmwpToggle(false);
                        // Do not keep the button locked if the widget fails to load
                        setTimeout(function () {
                            if (!document.querySelector('.g-recaptcha iframe')) { hmwpToggle(true); }
                        }, 10000);
                    }
                    if (document.readyState !== 'loading') { hmwpInit(); }
                    else { document.addEventListener('DOMContentLoaded', hmwpInit); }
                })();
            </script>
            <?php
		}
	}

}
