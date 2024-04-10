<?php
/**
 * Compatibility Class
 *
 * @file The MemberPress Model file
 * @package HMWP/Compatibility/MemberPress
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Models_Compatibility_MemberPress extends HMWP_Models_Compatibility_Abstract
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
        if (HMWP_Classes_Tools::getOption('hmwp_bruteforce') &&
            !HMWP_Classes_Tools::isPluginActive('memberpress-math-captcha/main.php')) {

            $this->hookBruteForce();
        }
    }

    public function hookBruteForce(){

        if (HMWP_Classes_Tools::getOption('brute_use_math')) { //math brute force

	        add_filter('mepr-validate-login', array(HMWP_Classes_ObjController::getClass('HMWP_Controllers_Brute'), 'hmwp_failed_attempt'));
	        add_action('mepr-login-form-before-submit', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Brute'), 'brute_math_form'));

//	        if(HMWP_Classes_Tools::getOption('hmwp_bruteforce_register')) {
//		        add_filter('mepr-validate-signup', array(HMWP_Classes_ObjController::getClass('HMWP_Controllers_Brute'), 'hmwp_failed_attempt'));
//	        }

	        if(HMWP_Classes_Tools::getOption('hmwp_bruteforce_lostpassword')) {
		        add_filter('mepr-validate-forgot-password', array(HMWP_Classes_ObjController::getClass('HMWP_Controllers_Brute'), 'hmwp_failed_attempt'));
		        add_action('mepr-forgot-password-form', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Brute'), 'brute_math_form'));
            }

        } elseif (HMWP_Classes_Tools::getOption('brute_use_captcha')) { //recaptcha V2

	        add_filter('mepr-validate-login', array(HMWP_Classes_ObjController::getClass('HMWP_Controllers_Brute'), 'hmwp_failed_attempt'));
	        add_action('mepr-login-form-before-submit', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Brute'), 'brute_recaptcha_head'));
	        add_action('mepr-login-form-before-submit', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Brute'), 'brute_recaptcha_form'));

//	        if(HMWP_Classes_Tools::getOption('hmwp_bruteforce_register')) {
//		        add_filter('mepr-validate-signup', array(HMWP_Classes_ObjController::getClass('HMWP_Controllers_Brute'), 'hmwp_failed_attempt'));
//	        }

	        if(HMWP_Classes_Tools::getOption('hmwp_bruteforce_lostpassword')) {
		        add_filter('mepr-validate-forgot-password', array(HMWP_Classes_ObjController::getClass('HMWP_Controllers_Brute'), 'hmwp_failed_attempt'));
		        add_action('mepr-forgot-password-form', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Brute'), 'brute_recaptcha_head'));
                add_action('mepr-forgot-password-form', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Brute'), 'brute_recaptcha_form'));
			}

        } elseif (HMWP_Classes_Tools::getOption('brute_use_captcha_v3')) { //recaptcha v3

	        add_filter('mepr-validate-login', array(HMWP_Classes_ObjController::getClass('HMWP_Controllers_Brute'), 'hmwp_failed_attempt'));
	        add_action('mepr-login-form-before-submit', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Brute'), 'brute_recaptcha_head_v3'));
	        add_action('mepr-login-form-before-submit', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Brute'), 'brute_recaptcha_form_v3'));

//	        if(HMWP_Classes_Tools::getOption('hmwp_bruteforce_register')) {
//		        add_filter('mepr-validate-signup', array(HMWP_Classes_ObjController::getClass('HMWP_Controllers_Brute'), 'hmwp_failed_attempt'));
//	        }

	        if(HMWP_Classes_Tools::getOption('hmwp_bruteforce_lostpassword')) {
		        add_filter('mepr-validate-forgot-password', array(HMWP_Classes_ObjController::getClass('HMWP_Controllers_Brute'), 'hmwp_failed_attempt'));
		        add_action('mepr-forgot-password-form', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Brute'), 'brute_recaptcha_head_v3'));
                add_action('mepr-forgot-password-form', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Brute'), 'brute_recaptcha_form_v3'));
            }

        }


    }

    /**
     * Get the options
     * @return false|mixed|null
     */
    public function getOptions(){
        return get_option('mepr_options');
    }

    /**
     * Get the login path
     * @return false|string
     */
    public function getLoginPath(){

        $options = $this->getOptions();
        if(isset($options['login_page_id']) && (int)$options['login_page_id'] > 0){
            $post = get_post((int)$options['login_page_id']);
            if(!is_wp_error($post)){
                return $post->post_name;
            }
        }

        return false;
    }

    /**
     * Check the reCaptcha on login, register and password reset
     * @param $args
     * @return void
     * @throws Exception
     */
    public function checkReCaptcha( $args ){
        if(class_exists('UM')){

            $errors = HMWP_Classes_ObjController::getClass('HMWP_Controllers_Brute')->hmwp_check_preauth(false);

            if ( is_wp_error($errors) ) {
                if(isset($args['mode'])){
                    switch ($args['mode']){
                        case 'login':
                            UM()->form()->add_error( 'username', strip_tags($errors->get_error_message()) );
                            break;
                        case 'register':
                            UM()->form()->add_error( 'user_login', strip_tags($errors->get_error_message()) );
                            break;
                        case 'password':
                            UM()->form()->add_error( 'username_b', strip_tags($errors->get_error_message()) );
                            break;

                    }
                }
            }

        }
    }
}
