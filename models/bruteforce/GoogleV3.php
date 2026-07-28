<?php
/**
 * Brute Force Protection Model
 * Called from Brute Force Class
 *
 * @file  The Brute Force Google V3 Recaptcha file
 * @package HMWP/BruteForce/GoogleV3
 * @since 8.1
 */

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Models_Bruteforce_GoogleV3 extends HMWP_Models_Bruteforce_Abstract {

    /**
     * @var bool Prevent from loading Google script more than once
     */
    private $loaded = false;

    /**
     * Verifies the Google Captcha while logging in.
     *
     * @param mixed $user
     * @param mixed $response
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

        $error_codes = array(
                'missing-input-secret'   => esc_html__( 'The secret parameter is missing.', 'hide-my-wp' ),
                'invalid-input-secret'   => esc_html__( 'The secret parameter is invalid or malformed.', 'hide-my-wp' ),
                'timeout-or-duplicate'   => esc_html__( 'The response parameter is invalid or malformed.', 'hide-my-wp' ),
                'missing-input-response' => esc_html__( 'Empty ReCaptcha. Please complete reCaptcha.', 'hide-my-wp' ),
                'invalid-input-response' => esc_html__( 'Invalid ReCaptcha. Please complete reCaptcha.', 'hide-my-wp' ),
                'bad-request'            => esc_html__( 'The response parameter is invalid or malformed.', 'hide-my-wp' )
        );

        $captcha = HMWP_Classes_Tools::getValue( 'g-recaptcha-response' );
        $secret  = HMWP_Classes_Tools::getOption( 'brute_captcha_secret_key_v3' );

        if ( $secret <> '' ) {
            $response = json_decode( HMWP_Classes_Tools::hmwp_remote_get( "https://www.google.com/recaptcha/api/siteverify?secret=$secret&response=" . $captcha . "&remoteip=" . wp_unslash( ( $_SERVER['REMOTE_ADDR'] ?? '') ) ), true ); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

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

        // Return is the header is already loaded
        if ( $this->loaded ) {
            return;
        }

        //phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript
        ?><script src='https://www.google.com/recaptcha/api.js?render=<?php echo esc_attr( HMWP_Classes_Tools::getOption( 'brute_captcha_site_key_v3' ) ) ?>' async defer></script><?php

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

                    // allow the second submit triggered after token injection
                    if (form.__hmwp_recaptcha_v3_ready) {
                        form.__hmwp_recaptcha_v3_ready = false;
                        return;
                    }

                    // If grecaptcha isn't available, do nothing (let ajax/non-ajax handlers work)
                    if (typeof grecaptcha === 'undefined') {
                        return;
                    }

                    e.preventDefault();
                    e.stopPropagation();
                    if (typeof e.stopImmediatePropagation === "function") e.stopImmediatePropagation();

                    grecaptcha.ready(function () {
                        grecaptcha.execute('<?php echo esc_attr( HMWP_Classes_Tools::getOption( 'brute_captcha_site_key_v3' ) ) ?>', {action: 'submit'})
                            .then(function (token) {
                                try {
                                    // upsert g-recaptcha-response (avoid duplicates on repeated submits)
                                    var input = form.querySelector('input[name="g-recaptcha-response"]');
                                    if (!input) {
                                        input = document.createElement("input");
                                        input.type = "hidden";
                                        input.name = "g-recaptcha-response";
                                        form.appendChild(input);
                                    }
                                    input.value = token;

                                    // upsert login (avoid duplicates)
                                    var login = form.querySelector('input[name="login"]');
                                    if (!login) {
                                        login = document.createElement("input");
                                        login.type = "hidden";
                                        login.name = "login";
                                        form.appendChild(login);
                                    }
                                    if (login.value === "") login.value = "1";
                                } catch (err) {
                                    console.warn("reCAPTCHA error", err);
                                }

                                // mark as ready, then re-trigger submit through the normal path (keeps AJAX handlers)
                                form.__hmwp_recaptcha_v3_ready = true;

                                if (typeof form.requestSubmit === "function") {
                                    form.requestSubmit();
                                } else {
                                    // fallback: dispatch submit; if nobody cancels it, do native submit
                                    var ev = new Event("submit", {bubbles: true, cancelable: true});
                                    if (form.dispatchEvent(ev)) {
                                        HTMLFormElement.prototype.submit.call(form);
                                    }
                                }
                            });
                    });
                }

                if (document.getElementsByTagName("form").length > 0) {
                    var x = document.getElementsByTagName("form");
                    for (var i = 0; i < x.length; i++) {
                        // capture phase so token injection happens before most AJAX serializers
                        x[i].addEventListener("submit", reCaptchaSubmit, true);
                    }
                }
            </script>
            <?php
        }
    }

}
