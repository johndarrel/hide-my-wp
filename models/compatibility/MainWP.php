<?php
/**
 * Compatibility Class
 *
 * @file The MainWP Model file
 * @package HMWP/Compatibility/MainWP
 * @since 7.1.0
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Models_Compatibility_MainWP extends HMWP_Models_Compatibility_Abstract
{

	public function __construct() {

        if( isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
            if( isset($_SERVER['REQUEST_URI']) && isset($_SERVER['HTTP_USER_AGENT']) ) {

                $url = untrailingslashit(strtok($_SERVER["REQUEST_URI"], '?'));
                $agent = $_SERVER['HTTP_USER_AGENT'];

                $paths = array('wp-admin/admin-ajax.php');
                $agents = array('MainWP');

                if (HMWP_Classes_Tools::searchInString($url, $paths)) {
                    if(HMWP_Classes_Tools::searchInString($agent, $agents)){
                        add_filter('hmwp_process_hide_urls', '__return_false');
                        add_filter('hmwp_process_init', '__return_false');
                    }
                }
            }

        }

	}

}
