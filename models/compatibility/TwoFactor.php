<?php
/**
 * Compatibility Class
 *
 * @file The TwoFactor Model file
 * @package HMWP/Compatibility/TwoFactor
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Models_Compatibility_TwoFactor extends HMWP_Models_Compatibility_Abstract
{

	public function hookFrontend() {

		add_filter('hmwp_files_handle_login', function($url){

            if(HMWP_Classes_Tools::getvalue('action') === 'validate_2fa'){

                if(isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
                    set_transient('hmwp_disable_hide_urls', 1, 60);
                    $response = HMWP_Classes_ObjController::getClass('HMWP_Models_Files')->postRequest($url);

                    if($response){
                        header("HTTP/1.1 200 OK");
                        if (!empty($response['headers']) ) {
                            foreach ( $response['headers'] as $header ) {
                                header($header);
                            }
                        }

                        //Echo the html file content
                        echo $response['body'];
                        exit();
                    }
                }
            }

        }, PHP_INT_MAX, 1);

	}


}
