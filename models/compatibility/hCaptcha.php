<?php
/**
 * Compatibility Class
 *
 * @file The hCaptcha Model file
 * @package HMWP/Compatibility/hCaptcha
 * @since 7.1.0
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Models_Compatibility_hCaptcha extends HMWP_Models_Compatibility_Abstract
{

    public function hookFrontend()
    {

	    //Load hCaptcha on custom login path
	    add_action('hmwp_login_init', function ( ) {
            if (HMWP_Classes_Tools::getDefault('hmwp_login_url') <> HMWP_Classes_Tools::getOption('hmwp_login_url') ) {
                $_SERVER['REQUEST_URI'] = '/wp-login.php';
            }
	    });

	}

}
