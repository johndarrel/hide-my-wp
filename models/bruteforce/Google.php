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

class HMWP_Models_Bruteforce_Google extends HMWP_Models_Bruteforce_Abstract {

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

		$captcha    = HMWP_Classes_Tools::getValue( 'g-recaptcha-response' );
		$project_id = HMWP_Classes_Tools::getOption( 'brute_google_project_id' );
		$apikey     = HMWP_Classes_Tools::getOption( 'brute_google_api_key' );
		$secret     = HMWP_Classes_Tools::getOption( 'brute_google_site_key' );

		if ( $secret <> '' && $project_id <> '' && $apikey <> '' ) {
			$params['event'] = array(
				'token'          => $captcha,
				'expectedAction' => "LOGIN",
				'siteKey'        => $secret,
			);

			$params                             = wp_json_encode( $params );
			$options['headers']['Content-Type'] = 'application/json';
			$response                           = json_decode( HMWP_Classes_Tools::hmwp_remote_post( "https://recaptchaenterprise.googleapis.com/v1/projects/$project_id/assessments?key=$apikey", $params, $options ), true );

			if ( ! isset( $response['tokenProperties']['valid'] ) || ! $response['tokenProperties']['valid'] ) {
				$error_message = sprintf( esc_html__( '%sIncorrect ReCaptcha%s. Please try again.', 'hide-my-wp' ), '<strong>', '</strong>' );
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

        if ( HMWP_Classes_Tools::getOption( 'brute_google_site_key' ) <> '' ) {
            if ( HMWP_Classes_Tools::getOption( 'brute_google_checkbox' ) ) {
                ?>
                <script src="https://www.google.com/recaptcha/enterprise.js?hl=<?php echo esc_attr( HMWP_Classes_Tools::getOption( 'brute_captcha_language' ) <> '' ? HMWP_Classes_Tools::getOption( 'brute_captcha_language' ) : get_locale() ) ?>" async defer></script>
                <style> #login { min-width: 354px; } </style>
                <?php
            } else {
                ?>
                <script src="https://www.google.com/recaptcha/enterprise.js?render=<?php echo esc_attr( HMWP_Classes_Tools::getOption( 'brute_google_site_key' ) ) ?>" async defer></script>
                <?php
            }

            $this->loaded = true;
        }

    }

	/**
	 * reCAPTCHA head and login form
	 */
	public function form() {
		if ( HMWP_Classes_Tools::getOption( 'brute_google_project_id' ) <> '' && HMWP_Classes_Tools::getOption( 'brute_google_api_key' ) <> '' && HMWP_Classes_Tools::getOption( 'brute_google_site_key' ) <> '' ) {
			global $hmwp_bruteforce;

			//load header first if not triggered
			if ( ! $hmwp_bruteforce && ! did_action( 'login_head' ) ) {
				$this->head();
			}

			?>

			<?php if ( HMWP_Classes_Tools::getOption( 'brute_google_checkbox' ) ) { ?>
                <div class="g-recaptcha" data-sitekey="<?php echo esc_attr( HMWP_Classes_Tools::getOption( 'brute_google_site_key' ) ) ?>" data-action="LOGIN" style="margin: 12px 0 24px 0;"></div>
			<?php } else { ?>
                <script>
                    function reCaptchaSubmit(e) {
                        var form = this;
                        e.preventDefault();
                        if( typeof grecaptcha !== 'undefined' ) {
                            grecaptcha.enterprise.ready(async () => {
                                try {
                                    const token = await grecaptcha.enterprise.execute('<?php echo esc_attr( HMWP_Classes_Tools::getOption( 'brute_google_site_key' ) ) ?>', {action: 'LOGIN'});
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

}
