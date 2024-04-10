<?php
/**
 * Compatibility Class
 *
 * @file The WP Frontend Admin file
 * @package HMWP/Compatibility/WPFrontendAdmin
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Models_Compatibility_WPFrontendAdmin extends HMWP_Models_Compatibility_Abstract
{


    public function hookAdmin() {

        if(HMWP_Classes_Tools::getIsset('vgfa_source')){

            if(HMWP_Classes_Tools::getValue('page') == 'wu-my-account'){
                add_action('admin_head', function(){

                    if(HMWP_Classes_Tools::getOption('hmwp_disable_click')
                        || HMWP_Classes_Tools::getOption('hmwp_disable_inspect')
                        || HMWP_Classes_Tools::getOption('hmwp_disable_source')
                        || HMWP_Classes_Tools::getOption('hmwp_disable_copy_paste')
                        || HMWP_Classes_Tools::getOption('hmwp_disable_drag_drop')
                    ) {
                        if (! wp_script_is('jquery')) {
                            wp_deregister_script('jquery');
                            wp_register_script( 'jquery', includes_url( '/js/jquery/jquery.js' ), false, NULL, true );
                            wp_enqueue_script('jquery');
                        }
                        HMWP_Classes_ObjController::getClass('HMWP_Models_Clicks')->disableKeysAndClicks();
                    }

                }, PHP_INT_MAX);
            }else{
                add_action('admin_footer', function(){
                    if(HMWP_Classes_Tools::getOption('hmwp_disable_click')
                        || HMWP_Classes_Tools::getOption('hmwp_disable_inspect')
                        || HMWP_Classes_Tools::getOption('hmwp_disable_source')
                        || HMWP_Classes_Tools::getOption('hmwp_disable_copy_paste')
                        || HMWP_Classes_Tools::getOption('hmwp_disable_drag_drop')
                    ) {
                        if (! wp_script_is('jquery')) {
                            wp_deregister_script('jquery');
                            wp_register_script( 'jquery', includes_url( '/js/jquery/jquery.js' ), false, NULL, true );
                            wp_enqueue_script('jquery');
                        }
                        HMWP_Classes_ObjController::getClass('HMWP_Models_Clicks')->disableKeysAndClicks();
                    }

                }, PHP_INT_MAX);
            }

        }


    }

}
