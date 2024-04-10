<?php
/**
 * Compatibility Class
 *
 * @file The WordPress User Manager Model file
 * @package HMWP/Compatibility/WPum
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Models_Compatibility_Wpum extends HMWP_Models_Compatibility_Abstract
{

    public function __construct()
    {
		parent::__construct();

        $login = $this->getLoginPath();
        if($login){
            defined('HMWP_DEFAULT_LOGIN') || define('HMWP_DEFAULT_LOGIN', $login);

	        if(HMWP_DEFAULT_LOGIN == 'login'){
		        add_filter('hmwp_option_hmwp_hide_login', '__return_false');
	        }

			add_filter('hmwp_option_hmwp_lostpassword_url', '__return_false');
            add_filter('hmwp_option_hmwp_register_url', '__return_false');
            add_filter('hmwp_option_hmwp_logout_url', '__return_false');
        }

        //load the brute force
        if (HMWP_Classes_Tools::getOption('hmwp_bruteforce') ) {

	        $this->hookBruteForce();
        }
    }

    public function hookBruteForce(){

	    //remove default check
	    remove_action('authenticate', array(HMWP_Classes_ObjController::getClass('HMWP_Controllers_Brute'),'hmwp_check_preauth'), 99);

	    if (!HMWP_Classes_Tools::getOption('brute_use_captcha_v3')){
		    add_action('authenticate', array( $this, 'checkLoginReCaptcha'), 99, 1);
	    }

	    if (HMWP_Classes_Tools::getOption('brute_use_math')) { //math brute force

		    add_action('wpum_before_submit_button_login_form', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Brute'), 'brute_math_form'));

		    if(HMWP_Classes_Tools::getOption('hmwp_bruteforce_lostpassword')) {
			    add_filter('submit_wpum_form_validate_fields', array($this, 'checkLPasswordReCaptcha'), 99, 3);
			    add_filter('wpum_before_submit_button_password_recovery_form', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Brute'), 'brute_math_form'));
		    }

		    if(HMWP_Classes_Tools::getOption('hmwp_bruteforce_register')) {
		        add_filter('submit_wpum_form_validate_fields', array($this, 'checkRegisterReCaptcha'), 99, 3);
		        add_filter('wpum_before_submit_button_registration_form', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Brute'), 'brute_math_form'));
	        }

	    } elseif (HMWP_Classes_Tools::getOption('brute_use_captcha')) { //recaptcha V2

		    add_action('wpum_before_submit_button_login_form', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Brute'), 'brute_recaptcha_head'));
		    add_action('wpum_before_submit_button_login_form', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Brute'), 'brute_recaptcha_form'));

		    if(HMWP_Classes_Tools::getOption('hmwp_bruteforce_lostpassword')) {
			    add_filter('submit_wpum_form_validate_fields', array($this, 'checkLPasswordReCaptcha'), 99, 3);
			    add_filter('wpum_before_submit_button_password_recovery_form', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Brute'), 'brute_recaptcha_head'));
			    add_filter('wpum_before_submit_button_password_recovery_form', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Brute'), 'brute_recaptcha_form'));
		    }

	        if(HMWP_Classes_Tools::getOption('hmwp_bruteforce_register')) {
		        add_filter('submit_wpum_form_validate_fields', array($this, 'checkRegisterReCaptcha'), 99, 3);
		        add_filter('wpum_before_submit_button_registration_form', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Brute'), 'brute_recaptcha_head'));
		        add_filter('wpum_before_submit_button_registration_form', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Brute'), 'brute_recaptcha_form'));
	        }


	    }
    }

    /**
     * Get the login path
     * @return false|string
     */
    public function getLoginPath(){
	    $settings = get_option( 'wpum_settings' );
	    if ( isset( $settings['login_page'][0] ) && (int) $settings['login_page'][0] > 0 ) {
		    $post = get_post( (int) $settings['login_page'][0] );
		    if ( ! is_wp_error( $post ) ) {
			    return $post->post_name;
		    }
	    }

        return false;
    }

	/**
	 * Check the reCaptcha on login
	 *
	 * @param $validate
	 * @param $fields
	 * @param $values
	 *
	 * @return void
	 * @throws Exception
	 */
	public function checkLoginReCaptcha( $user ){
		return HMWP_Classes_ObjController::getClass('HMWP_Controllers_Brute')->hmwp_check_preauth($user);
	}


	/**
	 * Check the reCaptcha on register
	 *
	 * @param $args
	 * @return void
	 * @throws Exception
	 */
	public function checkRegisterReCaptcha( $validate, $fields, $values ){

		//check the user
		if(isset($values['register']['user_password']) && isset($values['register']['user_email'])){
			$validate =  HMWP_Classes_ObjController::getClass('HMWP_Controllers_Brute')->hmwp_check_registration($validate, $fields, $values);
		}

		return $validate;

	}


	/**
	 * Check the reCaptcha on password reset
	 *
	 * @param $validate
	 * @param $fields
	 * @param $values
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function checkLPasswordReCaptcha( $validate, $fields, $values ){

		//check the user
		if(isset($values['user']['username_email'])){
			$validate =  HMWP_Classes_ObjController::getClass('HMWP_Controllers_Brute')->hmwp_check_registration($validate, $fields, $values);
		}

		return $validate;

	}


}
