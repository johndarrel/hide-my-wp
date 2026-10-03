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

		// Protect the WooCommerce checkout from card-testing by throttling attempts per IP
		if ( HMWP_Classes_Tools::getOption( 'hmwp_brute_checkout' ) ) {
			add_action( 'woocommerce_checkout_process', array( $this, 'checkoutProtection' ) );
			add_action( 'woocommerce_order_status_failed', array( $this, 'checkoutPaymentFailed' ) );
		}

		if ( HMWP_Classes_Tools::getValue( 'noredicts' ) ) {
			add_filter( 'woocommerce_is_rest_api_request', '__return_false' );
		}

		// If brute force is active
		if ( HMWP_Classes_Tools::getOption( 'hmwp_bruteforce' ) && HMWP_Classes_Tools::getOption( 'hmwp_bruteforce_woocommerce' ) ) {

			// Load the brute force for woocommerce login/register process
			$this->hookBruteForce();

		} else {

			// No captcha is printed on the WooCommerce form, so don't check one there.
			// Verified late: wp_verify_nonce() only exists once pluggable.php is loaded.
			add_filter( 'hmwp_preauth_captcha_check', function ( $check ) {
				global $pagenow;

				// The nonce is public, so it must never switch off the captcha on the
				// WordPress login endpoint or XML-RPC, where the captcha is printed.
				if ( in_array( $pagenow, array( 'wp-login.php', 'xmlrpc.php' ), true ) ) {
					return $check;
				}

				// WooCommerce posts its own submit field together with the nonce
				if ( HMWP_Classes_Tools::getValue( 'login' ) == '' ) {
					return $check;
				}

				// wp-login.php signs in with "log" on any login path, WooCommerce with "username"
				if ( HMWP_Classes_Tools::getValue( 'log' ) <> '' || HMWP_Classes_Tools::getValue( 'username' ) == '' ) {
					return $check;
				}

				//The nonce has to be valid, its presence alone proves nothing
				if ( wp_verify_nonce( HMWP_Classes_Tools::getValue( 'woocommerce-login-nonce' ), 'woocommerce-login' ) ) {
					return false;
				}

				return $check;
			} );

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

        // WooCommerce dedicated routes: REST, gateway callbacks and auth.
        // These live on their own paths, anchored at the site root, so the plugin stands fully aside.
        $is_route = HMWP_Classes_Tools::matchRootPath( $uri, array(
                HMWP_Classes_Tools::getOption( 'hmwp_wp-json' ) . '/wc',
                HMWP_Classes_Tools::getDefault( 'hmwp_wp-json' ) . '/wc',
                'wc-api',
                'wc-auth',
        ) );

        // WooCommerce REST gateway routes (IPNs, webhooks) carrying a known processor name
        $is_gateway_route = ( $uri &&
                HMWP_Classes_Tools::matchRootPath( $uri, array(
                        HMWP_Classes_Tools::getOption( 'hmwp_wp-json' ),
                        HMWP_Classes_Tools::getDefault( 'hmwp_wp-json' ),
                ) ) &&
                preg_match( '#/[^/]*(paypal|stripe|square|authorize[_-]?net|braintree)#i', $uri ) );

        // WooCommerce secure downloads, all four parameters have to carry a value
        $is_download = ( HMWP_Classes_Tools::getValue( 'download_file' ) <> '' &&
                         HMWP_Classes_Tools::getValue( 'order' ) <> '' &&
                         HMWP_Classes_Tools::getValue( 'uid' ) <> '' &&
                         HMWP_Classes_Tools::getValue( 'key' ) <> '' );

        // WooCommerce AJAX / legacy API by parameter. A genuine call names a real WooCommerce
        // action and always arrives on the WooCommerce endpoint at the site root, never on
        // wp-login.php or any hidden path.
        $is_ajax = ( $this->isWooRootRequest( $uri ) &&
                     ( $this->isWooAjaxAction( HMWP_Classes_Tools::getValue( 'wc-ajax' ) ) ||
                       $this->isWooApiHandler( HMWP_Classes_Tools::getValue( 'wc-api' ) ) ) );

        if ( $is_route || $is_gateway_route || $is_download ) {

            add_filter( 'hmwp_process_hide_urls', '__return_false' );
            add_filter( 'hmwp_process_firewall', '__return_false' );
            add_filter( 'hmwp_process_threats', '__return_false' );

        } elseif ( $is_ajax ) {

            // Real WooCommerce AJAX at the WooCommerce endpoint: relax payload scanning only
            add_filter( 'hmwp_process_firewall', '__return_false' );
            add_filter( 'hmwp_process_threats', '__return_false' );
        }

	}

	/**
	 * WooCommerce core AJAX actions, both the unauthenticated frontend set and the admin set
	 *
	 * Taken from WC_AJAX::add_ajax_events(). The plugin decides before WordPress init, when
	 * WooCommerce has not registered its wc_ajax_* handlers yet, so a static list is the only
	 * reliable proof that a value names a real WooCommerce action. Extendable with the
	 * hmwp_woocommerce_ajax_actions filter for custom endpoints.
	 *
	 * @return array
	 */
	private function getWooAjaxActions() {

		$actions = array(
			// Frontend, unauthenticated
			'get_refreshed_fragments', 'apply_coupon', 'remove_coupon', 'update_shipping_method',
			'get_cart_totals', 'update_order_review', 'add_to_cart', 'remove_from_cart', 'checkout',
			'get_variation', 'get_customer_location',
			// Admin
			'feature_product', 'mark_order_status', 'get_order_details', 'add_attribute',
			'add_new_attribute', 'remove_variations', 'save_attributes', 'add_attributes_and_variations',
			'add_variation', 'link_all_variations', 'revoke_access_to_download', 'grant_access_to_download',
			'get_customer_details', 'add_order_item', 'add_order_fee', 'add_order_shipping', 'add_order_tax',
			'add_coupon_discount', 'remove_order_coupon', 'remove_order_item', 'remove_order_tax',
			'calc_line_taxes', 'save_order_items', 'load_order_items', 'add_order_note', 'delete_order_note',
			'json_search_order_metakeys', 'json_search_products', 'json_search_products_and_variations',
			'json_search_downloadable_products_and_variations', 'json_search_customers', 'json_search_categories',
			'json_search_categories_tree', 'json_search_taxonomy_terms', 'json_search_product_attributes',
			'json_search_pages', 'term_ordering', 'product_ordering', 'refund_line_items', 'delete_refund',
			'rated', 'update_api_key', 'load_variations', 'save_variations', 'bulk_edit_variations',
			'tax_rates_save_changes', 'shipping_zones_save_changes', 'shipping_zone_add_method',
			'shipping_zone_remove_method', 'shipping_zone_methods_save_changes', 'shipping_zone_methods_save_settings',
			'shipping_classes_save_changes', 'shipping_providers_save_changes', 'toggle_gateway_enabled',
			'load_status_widget', 'load_recent_reviews_widget', 'order_add_meta', 'order_delete_meta',
		);

		return (array) apply_filters( 'hmwp_woocommerce_ajax_actions', $actions );
	}

	/**
	 * Check that a wc-ajax value names a real WooCommerce action
	 *
	 * A core action is proof on its own. A custom endpoint is accepted only when WooCommerce
	 * has actually registered it, never on the strength of the value alone.
	 *
	 * @param  mixed  $value  The wc-ajax parameter value
	 *
	 * @return bool
	 */
	private function isWooAjaxAction( $value ) {

		if ( ! is_string( $value ) || ! preg_match( '/^[A-Za-z0-9_-]{1,64}$/', $value ) ) {
			return false;
		}

		if ( in_array( $value, $this->getWooAjaxActions(), true ) ) {
			return true;
		}

		return ( function_exists( 'has_action' ) && has_action( 'wc_ajax_' . $value ) );
	}

	/**
	 * Check that a wc-api value names a real WooCommerce legacy API handler
	 *
	 * Accepted when WooCommerce has registered the handler, or when the value names a known
	 * payment processor, which is how a gateway IPN identifies itself. A value that is merely
	 * present is not enough.
	 *
	 * @param  mixed  $value  The wc-api parameter value
	 *
	 * @return bool
	 */
	private function isWooApiHandler( $value ) {

		if ( ! is_string( $value ) || ! preg_match( '/^[A-Za-z0-9_-]{1,64}$/', $value ) ) {
			return false;
		}

		if ( function_exists( 'has_action' ) && has_action( 'woocommerce_api_' . strtolower( $value ) ) ) {
			return true;
		}

		return (bool) preg_match( '/(paypal|stripe|square|authorize[_-]?net|braintree|klarna|mollie)/i', $value );
	}

	/**
	 * Check that the request targets the WooCommerce endpoint at the site root
	 *
	 * WooCommerce builds its AJAX endpoint from home_url( '/' ), so a genuine wc-ajax call
	 * always arrives at the site root, never on wp-login.php or any other entry point.
	 *
	 * @param  string  $uri  The request path, already parsed
	 *
	 * @return bool
	 */
	private function isWooRootRequest( $uri ) {

		if ( ! is_string( $uri ) || $uri == '' ) {
			return false;
		}

		$root = wp_parse_url( get_option( 'home' ), PHP_URL_PATH );
		$root = ( is_string( $root ) ? untrailingslashit( $root ) : '' );

		return ( untrailingslashit( $uri ) === $root );
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


	/**
	 * Throttle the WooCommerce checkout per IP so a scammer cannot test stolen
	 * cards by placing many orders in a row from the same address.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function checkoutProtection() {
		if ( ! function_exists( 'wc_add_notice' ) ) {
			return;
		}

		$ip = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Firewall_Server' )->getIp();
		if ( ! $ip ) {
			return;
		}

		$window  = (int) apply_filters( 'hmwp_checkout_window', 600 );
		$maxTry  = (int) apply_filters( 'hmwp_checkout_max_attempts', 8 );
		$maxFail = (int) apply_filters( 'hmwp_checkout_max_fails', 3 );

		$tries = (int) get_transient( 'hmwp_wc_try_' . md5( $ip ) ) + 1;
		set_transient( 'hmwp_wc_try_' . md5( $ip ), $tries, $window );

		$fails = (int) get_transient( 'hmwp_wc_fail_' . md5( $ip ) );

		if ( $tries > $maxTry || $fails >= $maxFail ) {
			do_action( 'hmwp_threat_detected', array( 'code' => 'WC_CHECKOUT_FLOOD', 'area' => 'checkout' ) );
			wc_add_notice( esc_html__( 'Too many checkout attempts. Please wait a few minutes and try again.', 'hide-my-wp' ), 'error' );
		}
	}

	/**
	 * Remember a failed payment per IP, the clearest sign of card testing.
	 *
	 * @param  int  $order_id  The order that failed to pay.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function checkoutPaymentFailed( $order_id ) {
		if ( ! function_exists( 'wc_get_order' ) || ! $order = wc_get_order( $order_id ) ) {
			return;
		}

		$ip = $order->get_customer_ip_address();
		if ( ! $ip ) {
			$ip = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Firewall_Server' )->getIp();
		}
		if ( ! $ip ) {
			return;
		}

		$window = (int) apply_filters( 'hmwp_checkout_window', 600 );
		$key    = 'hmwp_wc_fail_' . md5( $ip );
		set_transient( $key, (int) get_transient( $key ) + 1, $window );
	}

}
