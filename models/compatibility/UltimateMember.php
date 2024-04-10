<?php
/**
 * Compatibility Class
 *
 * @file The UltimateMember Model file
 * @package HMWP/Compatibility/UltimateMember
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Models_Compatibility_UltimateMember extends HMWP_Models_Compatibility_Abstract
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

        add_filter('um_submit_form_login', array($this, 'checkReCaptcha'));


        if (HMWP_Classes_Tools::getOption('hmwp_bruteforce_register')) {
            add_filter('um_submit_form_register', array($this, 'checkReCaptcha'));
        }

        if (HMWP_Classes_Tools::getOption('hmwp_bruteforce_lostpassword')) {
            add_action('um_reset_password_errors_hook', array($this, 'checkReCaptcha'));
        }

        if (HMWP_Classes_Tools::getOption('brute_use_math')) { //math recaptcha

            add_filter('um_after_login_fields', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Brute'), 'brute_recaptcha_head'), 99);
            add_filter('um_after_login_fields', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Brute'), 'brute_math_form'), 99);

            if (HMWP_Classes_Tools::getOption('hmwp_bruteforce_register')) {
                add_filter('um_after_register_fields', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Brute'), 'brute_recaptcha_head'), 99);
                add_filter('um_after_register_fields', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Brute'), 'brute_math_form'), 99);
            }

            if (HMWP_Classes_Tools::getOption('hmwp_bruteforce_lostpassword')) {
                add_filter('um_after_password_reset_fields', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Brute'), 'brute_recaptcha_head'), 99);
                add_filter('um_after_password_reset_fields', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Brute'), 'brute_math_form'), 99);
            }

        } elseif (HMWP_Classes_Tools::getOption('brute_use_captcha')) { // recaptcha v2

            add_filter('um_after_login_fields', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Brute'), 'brute_recaptcha_head'), 99);
            add_filter('um_after_login_fields', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Brute'), 'brute_recaptcha_form'), 99);

            if (HMWP_Classes_Tools::getOption('hmwp_bruteforce_register')) {
                add_filter('um_after_register_fields', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Brute'), 'brute_recaptcha_head'), 99);
                add_filter('um_after_register_fields', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Brute'), 'brute_recaptcha_form'), 99);
            }

            if (HMWP_Classes_Tools::getOption('hmwp_bruteforce_lostpassword')) {
                add_filter('um_after_password_reset_fields', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Brute'), 'brute_recaptcha_head'), 99);
                add_filter('um_after_password_reset_fields', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Brute'), 'brute_recaptcha_form'), 99);
            }

        }

    }

    /**
     * Get the options
     * @return false|mixed|null
     */
    public function getOptions(){
        return get_option('um_options');
    }

    /**
     * Get the login path
     * @return false|string
     */
    public function getLoginPath(){

        $options = $this->getOptions();

        if(isset($options['core_login']) && (int)$options['core_login'] > 0){
            $post = get_post((int)$options['core_login']);
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
