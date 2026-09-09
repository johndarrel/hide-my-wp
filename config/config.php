<?php
/**
 * List of plugin configurations. Database tables
 *
 * @file The configuration file
 *
 * @package HMWP\Config
 */

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

// Let plugin to automatically check if there is an update
defined( 'WP_AUTO_UPDATE_HMWP' ) || define( 'WP_AUTO_UPDATE_HMWP', true );
// Force the plugin to load right after initialization.
defined( 'HMW_PRIORITY' ) || define( 'HMW_PRIORITY', false );
// Force not to write the rules in config file htaccess|nginx|webconf.
defined( 'HMW_RULES_IN_CONFIG' ) || define( 'HMW_RULES_IN_CONFIG', true );
// Add HMW Rules in WordPress rewrite definition in htaccess.
defined( 'HMW_RULES_IN_WP_RULES' ) || define( 'HMW_RULES_IN_WP_RULES', false );
// Force all CSS and JS to load dynamically.
defined( 'HMW_DYNAMIC_FILES' ) || define( 'HMW_DYNAMIC_FILES', false );
// Force the plugin to rename the paths even in admin mode.
defined( 'HMW_ALWAYS_CHANGE_PATHS' ) || define( 'HMW_ALWAYS_CHANGE_PATHS', false );
// Hide also the images with the old paths.
defined( 'HMW_HIDE_OLD_IMAGES' ) || define( 'HMW_HIDE_OLD_IMAGES', false );
// Set a custom cookie while user logged in for path disable feature.
defined( 'HMWP_LOGGED_IN_COOKIE' ) || define( 'HMWP_LOGGED_IN_COOKIE', 'hmwp_logged_in_' );
// customize permissions
defined( 'HMW_FILE_PERMISSION' ) || define( 'HMW_FILE_PERMISSION', 0644 );
defined( 'HMW_DIR_PERMISSION' ) || define( 'HMW_DIR_PERMISSION', 0755 );
defined( 'HMW_CONFIG_PERMISSION' ) || define( 'HMW_CONFIG_PERMISSION', 0444 );

/**
 * No path file? error ...
 */
require_once dirname( __FILE__ ) . '/paths.php';

/**
 * Define the record name in the Option and UserMeta tables
 */
// Ghost Doctor stores its last diagnosis and the settings snapshot it takes
// before repairing, so a repair can always be undone.
define( 'HMWP_GHOSTDOCTOR_REPORT', 'hmwp_ghostdoctor_report' );
define( 'HMWP_GHOSTDOCTOR_SNAPSHOT', 'hmwp_ghostdoctor_snapshot' );

// The AI wording written for this website, kept so the findings keep their
// explanations across page loads instead of being fetched on every render.
define( 'HMWP_AI_EXPLAIN', 'hmwp_ai_explain' );

define( 'HMWP_OPTION', 'hmwp_options' );
define( 'HMWP_OPTION_SAFE', 'hmwp_options_safe' );
define( 'HMWP_SECURITY_CHECK', 'hmwp_securitycheck' );
define( 'HMWP_SECURITY_CHECK_IGNORE', 'hmwp_securitycheck_ignore' );
define( 'HMWP_SECURITY_CHECK_TIME', 'hmwp_securitycheck_time' );
define( 'HMWP_SALT_CHANGED', 'hmwp_saltchange_time' );
define( 'HMWP_THREATS_PURGE', 'hmwp_threats_purge_time' );
define( 'HMWP_CRON', 'hmwp_cron_process' );
define( 'HMWP_CRON_ONCE', 'hmwp_cron_process_once' );
// User capability
define( 'HMWP_CAPABILITY', 'hmwp_manage_settings' );
