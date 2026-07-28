<?php
/*
  Copyright (c) 2016 - 2026, WP Ghost
  The copyrights to the software code in this file are licensed under the (revised) BSD open source license.

  Plugin Name: WP Ghost Lite
  Plugin URI: https://wordpress.org/plugins/hide-my-wp/
  Description: Proactive WordPress Hack Prevention: Secure WP paths & login, firewall protection, brute force defense, 2FA, GEO security & bot blocking.
  Version: 7.0.08
  Author: WP Ghost
  Company: MINBO QRE SRL
  Author URI: https://wpghost.com
  License: GPLv2 or later
  License URI: https://www.gnu.org/licenses/gpl-2.0.html
  Text Domain: hide-my-wp
  Domain Path: /languages
  Network: true
  Requires at least: 5.8
  Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

if ( ! defined( 'HMW_VERSION' ) ) {

	//Set current plugin version
	define( 'HMWP_VERSION', '7.0.08' );

	// Set the last stable version of the plugin
	define( 'HMWP_STABLE_VERSION', '7.0.06' );

	//Set the type of plugin
	define( 'HMWP_CLASS_CTA', 'hmwp_pro' );

	//Set the plugin basename
	define( 'HMWP_BASENAME', plugin_basename( __FILE__ ) );

	//Set the PHP version ID for later use
	defined( 'PHP_VERSION_ID' ) || define( 'PHP_VERSION_ID', (int) str_replace( '.', '', PHP_VERSION ) );

	//Set the HMWP id for later verification
	defined( 'HMWP_VERSION_ID' ) || define( 'HMWP_VERSION_ID', (int) str_replace( '.', '', HMWP_VERSION ) );

	//Deactivate advanced pack as is not needed anymore
	define( 'HMWPP_DISABLE', true );

	//important to check the PHP version
	try {

		// Call config files
		include dirname( __FILE__ ) . '/config/config.php';

		// Import main classes
		include_once _HMWP_CLASSES_DIR_ . 'ObjController.php';

		if ( class_exists( 'HMWP_Classes_ObjController' ) ) {

			// Load Exception, Error and Tools class
			HMWP_Classes_ObjController::getClass( 'HMWP_Classes_Tools' );
			HMWP_Classes_ObjController::getClass( 'HMWP_Classes_Error' );

			// Load Front Controller
			HMWP_Classes_ObjController::getClass( 'HMWP_Classes_FrontController' );

			// If the disabled signal is on, return
			// Don't run cron hooks and update if there are installations
			if ( defined( 'HMWP_DISABLE' ) && HMWP_DISABLE ) {
				return;
			} elseif ( ! is_multisite() && defined( 'WP_INSTALLING' ) && WP_INSTALLING ) {
				return;
			} elseif ( is_multisite() && defined( 'WP_INSTALLING_NETWORK' ) && WP_INSTALLING_NETWORK ) {
				return;
			} elseif ( defined( 'WP_UNINSTALL_PLUGIN' ) && WP_UNINSTALL_PLUGIN <> '' ) {
				return;
			}

			// Don't load brute force, firewall and events on cron jobs
			if ( ! HMWP_Classes_Tools::isCron() ) {

				// Run the logs before the firewall to hook the threats
				if ( HMWP_Classes_Tools::getOption( 'hmwp_activity_log' ) ) {
					HMWP_Classes_ObjController::getClass( 'HMWP_Controllers_EventsLog' );
				}
				// Run the user event logs before firewall to hook the actions
				if ( HMWP_Classes_Tools::getOption( 'hmwp_threats_log' ) ) {
					HMWP_Classes_ObjController::getClass( 'HMWP_Controllers_ThreatsLog' );
				}

				// Run the firewall before brute force but after the logs
				HMWP_Classes_ObjController::getClass( 'HMWP_Controllers_Firewall' )->init();

				// If Brute Force is activated
				if ( HMWP_Classes_Tools::getOption( 'hmwp_bruteforce' ) ) {
					HMWP_Classes_ObjController::getClass( 'HMWP_Controllers_Brute' );
				}
				// If Temp Login is activated
				if ( HMWP_Classes_Tools::getOption( 'hmwp_templogin' ) ) {
					HMWP_Classes_ObjController::getClass( 'HMWP_Controllers_Templogin' );
				}

				// If the advanced pack is not installed, load the unique login and 2FA
				if ( ! HMWP_Classes_Tools::isAdvancedpackInstalled() ) {

					// If the unique login is activated
					if (HMWP_Classes_Tools::getOption('hmwp_uniquelogin') ) {
						HMWP_Classes_ObjController::getClass( 'HMWP_Controllers_Uniquelogin' );
					}

					// If the 2FA is activated
					if (HMWP_Classes_Tools::getOption('hmwp_2falogin') ) {
						HMWP_Classes_ObjController::getClass('HMWP_Controllers_Twofactor');
					}
				}

			}

			if ( is_admin() || is_network_admin() ) {

				// Check the user roles
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_RoleManager' );

				// Make sure to write the rewrites with other plugins
				add_action( 'rewrite_rules_array', array( HMWP_Classes_ObjController::getClass( 'HMWP_Classes_Tools' ), 'checkRewriteUpdate' ), 11, 1 );

				// Hook activation and deactivation
				register_activation_hook( __FILE__, array( HMWP_Classes_ObjController::getClass( 'HMWP_Classes_Tools' ), 'hmwp_activate' ) );
				register_deactivation_hook( __FILE__, array( HMWP_Classes_ObjController::getClass( 'HMWP_Classes_Tools' ), 'hmwp_deactivate' ) );

				// Verify if there are updated and all plugins and themes are in the right list
				add_action( 'activated_plugin', array( HMWP_Classes_ObjController::getClass( 'HMWP_Classes_Tools' ), 'checkPluginsThemesUpdates' ), 11, 0 );
				// When a theme is changed
				add_action( 'after_switch_theme', array( HMWP_Classes_ObjController::getClass( 'HMWP_Classes_Tools' ), 'checkPluginsThemesUpdates' ), 11, 0 );

			}

			// If not default mode
			if ( ( HMWP_Classes_Tools::getOption( 'hmwp_mode' ) <> 'default' ) ) {

                // Update rules in .htaccess on other plugins update to avoid rule deletion
                if(!HMWP_Classes_Tools::isApache() || HMWP_Classes_Tools::isLitespeed()){

					// When WordPress is automatically updated
                    add_action( 'automatic_updates_complete', function( $options ) {
                        if ( isset( $options['action'] ) && $options['action'] == 'update' ) {
                            set_transient( 'hmwp_update', 1 );
                        }
                    }, 10, 1 );

                    // When plugins are updated
                    add_action( 'upgrader_process_complete', function( $upgrader_object, $options ) {
                        $our_plugin = plugin_basename( __FILE__ );

                        if ( isset( $options['action'] ) && $options['action'] == 'update' ) {
                            if ( $options['type'] == 'plugin' && isset( $options['plugins'] ) ) {
                                foreach ( $options['plugins'] as $plugin ) {
                                    if ( $plugin <> $our_plugin ) {
                                        set_transient( 'hmwp_update', 1 );
                                    }
                                }
                            }
                        }
                    }, 10, 2 );

                }

				// Register the cron interval
				HMWP_Classes_ObjController::getClass( 'HMWP_Controllers_Cron' )->registerInterval();

			}

			// Request the plugin update when a new version is released
			if ( HMWP_Classes_Tools::getOption( 'hmwp_token' ) && WP_AUTO_UPDATE_HMWP  && file_exists( dirname( __FILE__ ) . '/update.php' )) {
				require dirname( __FILE__ ) . '/update.php';
			}

		}

	} catch ( Exception $e ) {

	}

}
