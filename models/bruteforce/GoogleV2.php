<?php
/**
 * Brute Force Protection Model
 * Called from Brute Force Class
 *
 * @file  The Brute Force Google V2 Recaptcha file
 * @package HMWP/BruteForce/GoogleV2
 * @since 8.1
 */

defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

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
		?>
        <script src='https://www.google.com/recaptcha/api.js?hl=<?php echo esc_attr( HMWP_Classes_Tools::getOption( 'brute_captcha_language' ) <> '' ? HMWP_Classes_Tools::getOption( 'brute_captcha_language' ) : get_locale() ) ?>' async defer></script>
        <style> #login {  min-width: 354px; } </style>
		<?php
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

			?>
            <div class="g-recaptcha" data-sitekey="<?php echo esc_attr( HMWP_Classes_Tools::getOption( 'brute_captcha_site_key' ) ) ?>" data-theme="<?php echo esc_attr( HMWP_Classes_Tools::getOption( 'brute_captcha_theme' ) ) ?>" style="margin: 12px 0 24px 0;"></div>
			<?php
		}
	}

}
