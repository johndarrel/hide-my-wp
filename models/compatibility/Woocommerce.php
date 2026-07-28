<?php
/**
 * WooCommerce Class
 *
 * @file The WooCommerce Model file
 * @package HMWP/Compatibility/WooCommerce
 * @since 7.0.0
 */

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Models_Compatibility_Woocommerce extends HMWP_Models_Compatibility_Abstract {

	public function __construct() {
		parent::__construct();
		add_action( 'admin_url', array( $this, 'admin_url' ), PHP_INT_MAX, 3 );

		if ( HMWP_Classes_Tools::getValue( 'noredicts' ) ) {
			add_filter( 'woocommerce_is_rest_api_request', '__return_false' );
		}

		// If brute force is active
		if ( HMWP_Classes_Tools::getOption( 'hmwp_bruteforce' ) && HMWP_Classes_Tools::getOption( 'hmwp_bruteforce_woocommerce' ) ) {

			// Load the brute force for woocommerce login/register process
			$this->hookBruteForce();

		} else {

			//Check if WooCommerce login support is loaded
			if ( HMWP_Classes_Tools::getValue( 'woocommerce-login-nonce' ) ) {
				add_filter( 'hmwp_preauth_check', '__return_false' );
			}

		}

		//If Login/Signup Popup is active and logged in through it
		if ( HMWP_Classes_Tools::isPluginActive( 'easy-login-woocommerce/xoo-el-main.php' ) && ! HMWP_Classes_Tools::getOption( 'brute_use_math' ) && HMWP_Classes_Tools::isAjax() && HMWP_Classes_Tools::getValue( 'xoo-el-username' ) && HMWP_Classes_Tools::getValue( 'xoo-el-password' ) ) {

			add_filter( 'hmwp_preauth_check', '__return_false' );
		}

        //Deactivate path hidden security on woocommerce api
        $uri = false;
        if ( isset( $_SERVER['REQUEST_URI'] ) ) {
            $uri = wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        }

        // WooCommerce AJAX endpoints: skip firewall rules
        // WooCommerce AJAX actions
        if (
             // WooCommerce secure downloads: skip firewall rules
             ( HMWP_Classes_Tools::getIsset( 'download_file' ) &&
               HMWP_Classes_Tools::getIsset( 'order' ) &&
               HMWP_Classes_Tools::getIsset( 'uid' ) &&
               HMWP_Classes_Tools::getIsset( 'key' ) ) ||
             // WooCommerce REST API endpoints
             (
                     $uri &&
                     (
                             strpos( $uri, '/'.HMWP_Classes_Tools::getOption( 'hmwp_wp-json' ).'/wc/' ) !== false ||
                             strpos( $uri, '/wp-json/wc/' ) !== false ||
                             strpos( $uri, '/wc-api/' ) !== false ||
                             strpos( $uri, '/wc-auth/' ) !== false
                     )
             ) ||
             // WooCommerce payment gateways (IPNs, webhooks from payment processors)
             (
                     $uri &&
                     (
                             strpos( $uri, '/wc-api/v' ) !== false ||
                             preg_match( '/\/(paypal|stripe|square|authorize_net|braintree)/', $uri )
                     )
             )
        ) {

            add_filter( 'hmwp_process_hide_urls', '__return_false' );
            add_filter( 'hmwp_process_firewall', '__return_false' );
            add_filter( 'hmwp_process_threats', '__return_false' );
        }

	}

	public function hookBruteForce() {

		// Get the active brute force class
		$bruteforce = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Brute' )->getInstance();

		add_action( 'woocommerce_login_form', array( $bruteforce, 'head' ), 99 );
		add_action( 'woocommerce_login_form', array( $bruteforce, 'form' ), 99 );

		if ( HMWP_Classes_Tools::getOption( 'hmwp_bruteforce_register' ) ) {
			if ( ! HMWP_Classes_Tools::getOption( 'brute_use_captcha_v3' ) ) {

				add_filter( 'woocommerce_registration_errors', function ( $errors, $sanitizedLogin, $userEmail ) {

					//check if the registering process is on woocommerce checkout
					//if woocommerce nonce is correct return
					$nonce_value = HMWP_Classes_Tools::getValue( 'woocommerce-process-checkout-nonce', HMWP_Classes_Tools::getValue( '_wpnonce' ) );
					if ( wp_verify_nonce( $nonce_value, 'woocommerce-process_checkout' ) ) {
						return $errors;
					}

					// Get the brute force registration class
					return HMWP_Classes_ObjController::getClass( 'HMWP_Models_Bruteforce_Registration' )->call( $errors, $sanitizedLogin, $userEmail );
				}, 99, 3 );
			}
			add_action( 'woocommerce_register_form', array( $bruteforce, 'head' ), 99 );
			add_action( 'woocommerce_register_form', array( $bruteforce, 'form' ), 99 );
		}

		if ( HMWP_Classes_Tools::getOption( 'hmwp_bruteforce_lostpassword' ) ) {
			add_action( 'lostpassword_post', array( HMWP_Classes_ObjController::getClass( 'HMWP_Models_Bruteforce_LostPassword' ), 'call' ), 99, 2 );
			add_action( 'woocommerce_lostpassword_form', array( $bruteforce, 'head' ), 99 );
			add_action( 'woocommerce_lostpassword_form', array( $bruteforce, 'form' ), 99 );
		}

	}


	/**
	 * Fix the admin url if wrong redirect
	 *
	 * @param mixed $url
	 * @param mixed $path
	 * @param mixed $blog_id
	 */
	public function admin_url( $url, $path, $blog_id ) {
		if ( HMWP_Classes_Tools::getDefault( 'hmwp_admin_url' ) <> HMWP_Classes_Tools::getOption( 'hmwp_admin_url' ) ) {

			if ( strpos( $url, '/wp-admin/' . HMWP_Classes_Tools::getOption( 'hmwp_admin_url' ) . '/' ) !== false ) {
				$url = str_replace( '/' . HMWP_Classes_Tools::getOption( 'hmwp_admin_url' ) . '/', '/', $url );
			}

		}

		return $url;

	}

	/**
	 * Show the reCaptcha form on login/register
	 *
	 * @return void
	 */
	public function woocommerce_brute_recaptcha_form() {
		?>
        <script src='https://www.google.com/recaptcha/api.js?hl=<?php echo esc_attr( HMWP_Classes_Tools::getOption( 'brute_captcha_language' ) <> '' ? HMWP_Classes_Tools::getOption( 'brute_captcha_language' ) : get_locale() ) ?>' async defer></script><?php //phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript ?>
        <style>#login { min-width: 354px; }</style>
		<?php

		if ( HMWP_Classes_Tools::getOption( 'brute_captcha_site_key' ) <> '' && HMWP_Classes_Tools::getOption( 'brute_captcha_secret_key' ) <> '' ) {
			?>
            <div class="g-recaptcha" data-sitekey="<?php echo esc_attr(HMWP_Classes_Tools::getOption( 'brute_captcha_site_key' )) ?>" data-theme="<?php echo esc_attr(HMWP_Classes_Tools::getOption( 'brute_captcha_theme' )) ?>"></div>
			<?php
		}
	}

    /**
	 * reCaptcha V3 support for Woocommerce
	 *
	 * @return void
	 */
	public function woocommerce_brute_recaptcha_form_v3() {
        ?>
        <script src='https://www.google.com/recaptcha/api.js?render=<?php echo esc_attr(HMWP_Classes_Tools::getOption( 'brute_captcha_site_key_v3' )) ?>' async defer></script><?php //phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript ?>
        <style>#login {
                min-width: 354px;
            }</style>
		<?php

		if ( HMWP_Classes_Tools::getOption( 'brute_captcha_site_key_v3' ) <> '' && HMWP_Classes_Tools::getOption( 'brute_captcha_secret_key_v3' ) <> '' ) {
			?>
            <script>
                function reCaptchaSubmit(e) {
                    var form = this;
                    e.preventDefault();

                    grecaptcha.ready(function () {
                        grecaptcha.execute('<?php echo esc_attr( HMWP_Classes_Tools::getOption( 'brute_captcha_site_key_v3' ) ) ?>', {action: 'submit'}).then(function (token) {
                            //add google data
                            var input = document.createElement("input");
                            input.type = "hidden";
                            input.name = "g-recaptcha-response";
                            input.value = token;
                            form.appendChild(input);

                            //complete form integration
                            var submit = document.createElement("input");
                            submit.type = "hidden";
                            submit.name = "login";
                            form.appendChild(submit);

                            form.submit();
                        });
                    });
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
