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
            $response = $this->assess( $project_id, $apikey, $secret, $captcha );

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
     * Ask reCAPTCHA Enterprise to assess a token. A plain request on purpose,
     * the licence headers added by hmwp_remote_* must not reach a third party.
     *
     * @param  string  $project_id  The Google Cloud project ID
     * @param  string  $apikey  The Google Cloud API key
     * @param  string  $sitekey  The reCAPTCHA Enterprise site key
     * @param  string  $token  The token sent by the form
     *
     * @return array The decoded assessment, empty on connection error
     */
    public function assess( $project_id, $apikey, $sitekey, $token ) {
        $url = 'https://recaptchaenterprise.googleapis.com/v1/projects/' . rawurlencode( (string) $project_id ) . '/assessments?key=' . rawurlencode( (string) $apikey );

        $response = wp_remote_post( $url, array(
                'timeout' => 10,
                'headers' => array( 'Content-Type' => 'application/json' ),
                'body'    => wp_json_encode( array(
                        'event' => array(
                                'token'          => (string) $token,
                                'expectedAction' => 'LOGIN',
                                'siteKey'        => (string) $sitekey,
                        ),
                ) ),
        ) );

        if ( is_wp_error( $response ) ) {
            return array();
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        return is_array( $body ) ? $body : array();
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

                    // Bind only to the form this script was printed inside, so no other
                    // form on the page is touched.
                    (function () {
                        var script = document.currentScript;
                        var owner  = (script && script.closest) ? script.closest("form") : null;

                        if (owner) {
                            if (!owner.__hmwpRecaptchaBound) {
                                owner.__hmwpRecaptchaBound = true;
                                // capture phase so token injection happens before most AJAX serializers
                                owner.addEventListener("submit", reCaptchaSubmit, true);
                            }
                            return;
                        }

                        // No owning form: printed outside it, or the form is added later.
                        // Listen on the document once, skipping forms already bound above.
                        if (!window.__hmwpRecaptchaDelegated) {
                            window.__hmwpRecaptchaDelegated = true;
                            document.addEventListener("submit", function (e) {
                                var form = e.target;
                                if (form && form.tagName === "FORM" && !form.__hmwpRecaptchaBound) {
                                    reCaptchaSubmit.call(form, e);
                                }
                            }, true);
                        }
                    })();
                </script>
            <?php }
        }
    }


}
