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

class HMWP_Models_Bruteforce_Google extends HMWP_Models_Bruteforce_Abstract {

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

            /**
             * Catch Google API configuration errors (403, SERVICE_DISABLED, etc)
             */
            if ( isset( $response['error'] ) ) {

                $reason = '';

                if ( isset( $response['error']['details'][0]['reason'] ) ) {
                    $reason = $response['error']['details'][0]['reason'];
                }

                if ( $reason === 'SERVICE_DISABLED' || $response['error']['status'] === 'PERMISSION_DENIED' ) {

                    return wp_kses_post(
                            sprintf( '%1$sreCAPTCHA Enterprise is not properly configured.%2$s Please enable the reCAPTCHA Enterprise API and billing in your Google Cloud project' , '<strong>', '</strong>' )
                    );

                }

            }


            if ( ! isset( $response['tokenProperties']['valid'] ) || ! $response['tokenProperties']['valid'] ) {
                /* translators: 1: Opening <strong> tag, 2: Closing </strong> tag. */
                return wp_kses_post( sprintf( __( '%1$sIncorrect ReCaptcha%2$s. Please try again.', 'hide-my-wp' ), '<strong>', '</strong>' ) );
            }


        }

        return false;
    }


    /**
     * reCAPTCHA head and login form
     */
    public function head() {

        // Return is the header is already loaded
        if ( $this->loaded ) {
            return;
        }

        if ( HMWP_Classes_Tools::getOption( 'brute_google_site_key' ) <> '' ) {
            if ( HMWP_Classes_Tools::getOption( 'brute_google_checkbox' ) ) {
                ?>
                <script src="https://www.google.com/recaptcha/enterprise.js?hl=<?php echo esc_attr( HMWP_Classes_Tools::getOption( 'brute_captcha_language' ) <> '' ? HMWP_Classes_Tools::getOption( 'brute_captcha_language' ) : get_locale() ) //phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript ?>"
                        async defer></script>
                <style> #login {
                        min-width: 354px;
                    } </style>
                <?php
            } else {
                ?>
                <script src="https://www.google.com/recaptcha/enterprise.js?render=<?php echo esc_attr( HMWP_Classes_Tools::getOption( 'brute_google_site_key' ) ) //phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript ?>"
                        async defer></script>
                <?php
            }

            $this->loaded = true;
        }

    }

    /**
     * reCAPTCHA head and login form
     */
    public function form() {
        if ( HMWP_Classes_Tools::getOption( 'brute_google_project_id' ) <> '' &&
             HMWP_Classes_Tools::getOption( 'brute_google_api_key' ) <> '' &&
             HMWP_Classes_Tools::getOption( 'brute_google_site_key' ) <> '' ) {

            global $hmwp_bruteforce;

            // load header first if isn't triggered
            if ( ! $hmwp_bruteforce && ! did_action( 'login_head' ) ) {
                $this->head();
            }

            if ( HMWP_Classes_Tools::getOption( 'brute_google_checkbox' ) ) { ?>
                <div class="g-recaptcha" data-sitekey="<?php echo esc_attr( HMWP_Classes_Tools::getOption( 'brute_google_site_key' ) ) ?>" data-action="LOGIN" style="margin: 12px 0 24px 0;"></div>
            <?php } else { ?>
                <script>
                    function reCaptchaSubmit(e) {
                        var form = this;

                        // allow the second submit triggered after token injection
                        if (form.__hmwp_recaptcha_ent_ready) {
                            form.__hmwp_recaptcha_ent_ready = false;
                            return;
                        }

                        // If grecaptcha isn't available, do nothing (let ajax/non-ajax handlers work)
                        if (typeof grecaptcha === 'undefined' || !grecaptcha.enterprise || !grecaptcha.enterprise.execute) {
                            return;
                        }

                        e.preventDefault();
                        e.stopPropagation();
                        if (typeof e.stopImmediatePropagation === "function") e.stopImmediatePropagation();

                        grecaptcha.enterprise.ready(async () => {
                            try {
                                const token = await grecaptcha.enterprise.execute(
                                    '<?php echo esc_attr( HMWP_Classes_Tools::getOption( 'brute_google_site_key' ) ) ?>',
                                    {action: 'LOGIN'}
                                );

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
                            form.__hmwp_recaptcha_ent_ready = true;

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
                    }

                    if (document.getElementsByTagName("form").length > 0) {
                        var x = document.getElementsByTagName("form");
                        for (var i = 0; i < x.length; i++) {
                            // capture phase so token injection happens before most AJAX serializers
                            x[i].addEventListener("submit", reCaptchaSubmit, true);
                        }
                    }
                </script>
            <?php }
        }
    }


}
