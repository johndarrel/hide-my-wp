<?php
/*
  Copyright (c) 2016 - 2021, WPPlugins.
  The copyrights to the software code in this file are licensed under the (revised) BSD open source license.

  Plugin Name: Hide My WP Ghost Lite
  Plugin URI: https://wordpress.org/plugins/hide-my-wp/
  Description: The best solution for WordPress Security. Hide wp-admin, wp-login, wp-content, plugins, themes etc. Add Firewall, Brute Force protection & more. <br /> <a href="https://hidemywpghost.com/wordpress" target="_blank"><strong>Unlock all features</strong></a>
  Version: 4.1.05
  Author: WPPlugins - WordPress Security Plugins
  Author URI: https://hidemywp.co
  License: GPLv2 or later
  License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
  Text Domain: hide-my-wp
  Domain Path: /languages
 */

if (defined( 'NONCE_KEY' ) && defined( 'ABSPATH' ) && ! defined( 'HMW_VERSION' ) ) {
	define( 'HMW_VERSION', '4.1.05' );
	/* Call config files */
	require( dirname( __FILE__ ) . '/config/config.php' );

	/* important to check the PHP version */
	try  {
		/* inport main classes */
		require_once( _HMW_CLASSES_DIR_ . 'ObjController.php' );
		HMW_Classes_ObjController::getClass( 'HMW_Classes_FrontController' );

		require( dirname( __FILE__ ) . '/debug/index.php' );


		if ( defined( 'HMW_DISABLE' ) && HMW_DISABLE ) {
			return;
		}

		//don't run cron hooks and update if there are installs
		if ( ! is_multisite() && defined( 'WP_INSTALLING' ) && WP_INSTALLING ) {
			return;
		} elseif ( is_multisite() && defined( 'WP_INSTALLING_NETWORK' ) && WP_INSTALLING_NETWORK ) {
			return;
		}

		//If Brute Force is activated
		if ( HMW_Classes_Tools::getOption( 'hmw_bruteforce' ) ) {
			HMW_Classes_ObjController::getClass( 'HMW_Controllers_Brute' );
		}

		//For auto updates
		add_action( 'upgrader_process_complete', array(
			HMW_Classes_ObjController::getClass( 'HMW_Classes_Tools' ),
			'checkWpUpdates'
		), 1 );

		//Make sure to write the rewrites with other plugins
		add_action( 'rewrite_rules_array', array(
			HMW_Classes_ObjController::getClass( 'HMW_Classes_Tools' ),
			'checkRewriteUpdate'
		) );

		if ( is_admin() || is_network_admin() ) {
			register_activation_hook( __FILE__, array(
				HMW_Classes_ObjController::getClass( 'HMW_Classes_Tools' ),
				'hmw_activate'
			) );
			register_deactivation_hook( __FILE__, array(
				HMW_Classes_ObjController::getClass( 'HMW_Classes_Tools' ),
				'hmw_deactivate'
			) );

			//verify if there are updated and all plugins and themes are in the right list
			add_action( 'activated_plugin', array(
				HMW_Classes_ObjController::getClass( 'HMW_Classes_Tools' ),
				'checkWpUpdates'
			) );
			//When a theme is changed
			add_action( 'after_switch_theme', array(
				HMW_Classes_ObjController::getClass( 'HMW_Classes_Tools' ),
				'checkWpUpdates'
			) );

		}
	} catch(Exception $e) {
	}

}
