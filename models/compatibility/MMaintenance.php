<?php
/**
 * Compatibility Class
 *
 * @file The Minimal Maintenance Model file
 * @package HMWP/Compatibility/Minimal Maintenance
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Models_Compatibility_MMaintenance extends HMWP_Models_Compatibility_Abstract
{

    public function hookFrontend()
    {

	    if (HMWP_Classes_Tools::$default['hmwp_login_url'] <> HMWP_Classes_Tools::getOption('hmwp_login_url') ) {
		    add_filter('csmm_get_options', function ($signals_csmm_options){
			    $signals_csmm_options['custom_login_url'] = HMWP_Classes_Tools::getOption('hmwp_login_url');

			    return $signals_csmm_options;
		    });

		    if(isset($_SERVER["REQUEST_URI"])) {
			    $url = untrailingslashit(strtok($_SERVER["REQUEST_URI"], '?'));

			    if (strpos($url , site_url('wp-login.php', 'relative')) !== false){
				    add_filter('csmm_force_display', "__return_false");
			    }

		    }
	    }

	    $headers = headers_list();

	    if(!empty($headers)) {
		    $iscontenttype = false;
		    foreach ($headers as $value) {
			    if (strpos($value, ':') !== false) {
				    if (stripos($value, 'Content-Type') !== false) {
					    $iscontenttype = true;
				    }
			    }
		    }

		    if(!$iscontenttype) {
			    header('Content-Type: text/html; charset=UTF-8');
			    add_filter('hmwp_priority_buffer', '__return_true');
		    }
	    }

	}

}
