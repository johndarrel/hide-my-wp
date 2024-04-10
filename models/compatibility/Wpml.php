<?php
/**
 * Compatibility Class
 *
 * @file The WPML Model file
 * @package HMWP/Compatibility/WPML
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Models_Compatibility_Wpml extends HMWP_Models_Compatibility_Abstract
{

    public function __construct() {
        parent::__construct();

        //WPML checks the HTTP_REFERER based on wp-admin and not the custom admin path
        if (isset($_SERVER['HTTP_REFERER'])) {
            if (HMWP_Classes_Tools::getDefault('hmwp_admin_url') <> HMWP_Classes_Tools::getOption('hmwp_admin_url')) {
                $_SERVER['HTTP_REFERER'] = HMWP_Classes_ObjController::getClass('HMWP_Models_Files')->getOriginalUrl($_SERVER['HTTP_REFERER']);
            }
        }
    }


}
