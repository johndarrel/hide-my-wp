<?php
/**
 * Woocommerce Class
 *
 * @file The Woocommerce Model file
 * @package HMWP/Compatibility/Woocommerce
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Models_Compatibility_Woocommerce extends HMWP_Models_Compatibility_Abstract
{

    public function __construct()
    {
	    parent::__construct();

	    add_action('admin_url', array($this, 'admin_url'), PHP_INT_MAX, 3);

        if(HMWP_Classes_Tools::getValue('noredicts')) {
            add_filter('woocommerce_is_rest_api_request', '__return_false');
        }

		//if brute force is active
	    if (HMWP_Classes_Tools::getOption('hmwp_bruteforce') &&  HMWP_Classes_Tools::getOption('hmwp_bruteforce_woocommerce')) {

			//load the brute force
		    $this->hookBruteForce();

	    }else{

		    //Check if Woocommerce login support is loaded
		    if(HMWP_Classes_Tools::getValue('woocommerce-login-nonce')) {
			    add_filter('hmwp_preauth_check', '__return_false');
		    }

	    }

	    //If Login/Signup Popup is active and logged in through it
	    if(HMWP_Classes_Tools::isPluginActive('easy-login-woocommerce/xoo-el-main.php')
	       && !HMWP_Classes_Tools::getOption('brute_use_math')
	       && HMWP_Classes_Tools::isAjax()
	       && HMWP_Classes_Tools::getValue('xoo-el-username')
	       && HMWP_Classes_Tools::getValue('xoo-el-password') ) {

		    add_filter('hmwp_preauth_check', '__return_false');
	    }
    }

	public function hookBruteForce(){

		if(!HMWP_Classes_Tools::getOption('brute_use_captcha_v3')) {
			add_filter('woocommerce_registration_errors', array(HMWP_Classes_ObjController::getClass('HMWP_Controllers_Brute'), 'hmwp_check_registration'), 99, 3);
		}
		if(HMWP_Classes_Tools::getOption('hmwp_bruteforce_lostpassword')) {
			add_action('lostpassword_post', array(HMWP_Classes_ObjController::getClass('HMWP_Controllers_Brute'), 'hmwp_check_lpassword'), 99, 2);
		}

		if (HMWP_Classes_Tools::getOption('brute_use_math')) { //math recaptcha

			add_filter('woocommerce_login_form', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Brute'), 'brute_recaptcha_head'), 99);
			add_filter('woocommerce_login_form', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Brute'), 'brute_math_form'), 99);

			if(HMWP_Classes_Tools::getOption('hmwp_bruteforce_register')) {
				add_filter('woocommerce_register_form', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Brute'), 'brute_recaptcha_head'), 99);
				add_filter('woocommerce_register_form', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Brute'), 'brute_math_form'), 99);
			}

			if(HMWP_Classes_Tools::getOption('hmwp_bruteforce_lostpassword')) {
				add_filter('woocommerce_lostpassword_form', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Brute'), 'brute_recaptcha_head'), 99);
				add_filter('woocommerce_lostpassword_form', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Brute'), 'brute_math_form'), 99);
			}

		}elseif (HMWP_Classes_Tools::getOption('brute_use_captcha')) { // recaptcha v2

			add_filter('woocommerce_login_form', array($this, 'woocommerce_brute_recaptcha_form'), 99);

			if(HMWP_Classes_Tools::getOption('hmwp_bruteforce_register')) {
				add_filter('woocommerce_register_form', array($this, 'woocommerce_brute_recaptcha_form'), 99);
			}

			if(HMWP_Classes_Tools::getOption('hmwp_bruteforce_lostpassword')) {
				add_filter('woocommerce_lostpassword_form', array($this, 'woocommerce_brute_recaptcha_form'), 99);
			}

		}elseif (HMWP_Classes_Tools::getOption('brute_use_captcha_v3')) { //recaptcha v3

			add_filter('woocommerce_login_form', array($this, 'woocommerce_brute_recaptcha_form_v3'), 99);

			if(HMWP_Classes_Tools::getOption('hmwp_bruteforce_lostpassword')) {
				add_filter('woocommerce_lostpassword_form', array($this, 'woocommerce_brute_recaptcha_form_v3'), 99);
			}
		}


	}


	/**
     * Fix the admin url if wrong redirect
     *
     * @param mixed $url
     * @param mixed $path
     * @param mixed $blog_id
     */
    public function admin_url( $url, $path, $blog_id )
    {
        if (HMWP_Classes_Tools::getDefault('hmwp_admin_url') <> HMWP_Classes_Tools::getOption('hmwp_admin_url')) {

            if (strpos( $url, '/wp-admin/' . HMWP_Classes_Tools::getOption('hmwp_admin_url') . '/' ) !== false ) {
                $url = str_replace('/' . HMWP_Classes_Tools::getOption('hmwp_admin_url') . '/', '/', $url);
            }

        }

        return $url;

    }

	/**
	 * Show the reCaptcha form on login/register
	 *
	 * @return void
	 */
	public function woocommerce_brute_recaptcha_form()
	{
		?>
		<script src='https://www.google.com/recaptcha/api.js?hl=<?php echo(HMWP_Classes_Tools::getOption('brute_captcha_language') <> '' ? HMWP_Classes_Tools::getOption('brute_captcha_language') : get_locale()) ?>' async defer></script>
		<style>#login{min-width: 354px;}</style>
		<?php

		if (HMWP_Classes_Tools::getOption('brute_captcha_site_key') <> '' && HMWP_Classes_Tools::getOption('brute_captcha_secret_key') <> '') {
			?>
			<div class="g-recaptcha" data-sitekey="<?php echo HMWP_Classes_Tools::getOption('brute_captcha_site_key') ?>" data-theme="<?php echo HMWP_Classes_Tools::getOption('brute_captcha_theme') ?>"></div>
			<?php
		}
	}

	/**
	 * reCaptcha V3 support for Woocommerce
	 * @return void
	 */
	public function woocommerce_brute_recaptcha_form_v3()
	{
		?>
		<script src='https://www.google.com/recaptcha/api.js?render=<?php echo HMWP_Classes_Tools::getOption('brute_captcha_site_key_v3') ?>' async defer></script>
		<style>#login{min-width: 354px;}</style>
		<?php

		if (HMWP_Classes_Tools::getOption('brute_captcha_site_key_v3') <> '' && HMWP_Classes_Tools::getOption('brute_captcha_secret_key_v3') <> '') {
			?>
			<script>
                function reCaptchaSubmit(e) {
                    var form = this;
                    e.preventDefault();

                    grecaptcha.ready(function() {
                        grecaptcha.execute('<?php echo HMWP_Classes_Tools::getOption('brute_captcha_site_key_v3') ?>', {action: 'submit'}).then(function(token) {
                            //add google data
                            var input = document.createElement("input");
                            input.type = "hidden";
                            input.name = "g-recaptcha-response" ;
                            input.value = token ;
                            form.appendChild(input);

                            //complete form integration
                            var submit = document.createElement("input");
                            submit.type = "hidden";
                            submit.name = "login" ;
                            form.appendChild(submit);

                            form.submit();
                        });
                    });
                }

                if(document.getElementsByTagName("form").length > 0) {
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
