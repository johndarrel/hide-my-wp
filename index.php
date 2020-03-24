<?php
/*
  Copyright (c) 2016 - 2020, WPPlugins.
  The copyrights to the software code in this file are licensed under the (revised) BSD open source license.

  Plugin Name: Hide My WP Ghost Lite
  Plugin URI:
  Description: The best solution for WordPress Security. Hide wp-admin, wp-login, wp-content, plugins, themes etc. Add Firewall, Brute Force protection & more. <br /> <a href="http://hidemywpghost.com/wordpress" target="_blank"><strong>Unlock all features</strong></a>
  Version: 3.5.03
  Author: WPPlugins - WordPress Security Plugins
  Author URI: https://wpplugins.tips
 */
if (!defined('HMW_VERSION')) {
    define('HMW_VERSION', '3.5.03');
    /* Call config files */
    require(dirname(__FILE__) . '/debug/index.php');
    require(dirname(__FILE__) . '/config/config.php');

    /* important to check the PHP version */
    if (PHP_VERSION_ID >= 5100) {
        /* inport main classes */
        require_once(_HMW_CLASSES_DIR_ . 'ObjController.php');
        HMW_Classes_ObjController::getClass('HMW_Classes_FrontController');

        if (defined('HMW_DISABLE') && HMW_DISABLE) {
            return;
        }

        //don't run cron hooks and update if there are installs
        if (!is_multisite() && defined('WP_INSTALLING') && WP_INSTALLING) {
            return;
        } elseif (is_multisite() && defined('WP_INSTALLING_NETWORK') && WP_INSTALLING_NETWORK) {
            return;
        }

        //If Brute Force is activated
        if (HMW_Classes_Tools::getOption('hmw_bruteforce')) {
            HMW_Classes_ObjController::getClass('HMW_Controllers_Brute');
        }


        //For auto updates
        add_action('upgrader_process_complete', array(HMW_Classes_ObjController::getClass('HMW_Classes_Tools'), 'checkWpUpdates'), 1);

        if (is_admin() || is_network_admin()) {
            register_activation_hook(__FILE__, array(HMW_Classes_ObjController::getClass('HMW_Classes_Tools'), 'hmw_activate'));
            register_deactivation_hook(__FILE__, array(HMW_Classes_ObjController::getClass('HMW_Classes_Tools'), 'hmw_deactivate'));

            //verify if there are updated and all plugins and themes are in the right list
            add_action('activated_plugin', array(HMW_Classes_ObjController::getClass('HMW_Classes_Tools'), 'checkWpUpdates'));
            //When a theme is changed
            add_action('after_switch_theme', array(HMW_Classes_ObjController::getClass('HMW_Classes_Tools'), 'checkWpUpdates'));

        }
    } else {
        /* Main class call */
        add_action('admin_notices', 'hmw_showError');
    }
    /**
     * Called in Notice Hook
     */
    function hmw_showError() {
        echo '<div class="update-nag"><span style="color:red; font-weight:bold;">' . __('For Hide My WordPress PRO to work, the PHP version has to be equal or greater then 5.1', _HMW_PLUGIN_NAME_) . '</span></div>';
    }

}