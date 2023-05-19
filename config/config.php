<?php
/**
 * List of plugin configurations. Database tables
 *
 * @file The configuration file
 *
 * @package HMWP\Config
 */

defined('ABSPATH') || die('Cheatin\' uh?');

//Force the plugin to load right after initialization.
defined('HMW_PRIORITY') || define('HMW_PRIORITY', false);
//Force not to write the rules in config file.
defined('HMW_RULES_IN_CONFIG') || define('HMW_RULES_IN_CONFIG', true);
//add HMW Rules in WordPress rewrite definition in htaccess.
defined('HMW_RULES_IN_WP_RULES') || define('HMW_RULES_IN_WP_RULES', false);
//Force all CSS and JS to load dynamically.
defined('HMW_DYNAMIC_FILES') || define('HMW_DYNAMIC_FILES', false);
//Force the plugin to rename the paths even in admin mode.
defined('HMW_ALWAYS_CHANGE_PATHS') || define('HMW_ALWAYS_CHANGE_PATHS', false);
//Hide also the images with the old paths.
defined('HMW_HIDE_OLD_IMAGES') || define('HMW_HIDE_OLD_IMAGES', false);
//Set a custom cookie while user logged in for path disable feature.
defined('HMWP_LOGGED_IN_COOKIE') || define('HMWP_LOGGED_IN_COOKIE', 'hmwp_logged_in_');

/**
 * No path file? error ...
 */
require_once dirname(__FILE__) . '/paths.php';

/**
 * Define the record name in the Option and UserMeta tables
 */
define('HMWP_OPTION', 'hmwp_options');
define('HMWP_OPTION_SAFE', 'hmwp_options_safe');
define('HMWP_SECURITY_CHECK', 'hmwp_securitycheck');
define('HMWP_SECURITY_CHECK_IGNORE', 'hmwp_securitycheck_ignore');
define('HMWP_SECURITY_CHECK_TIME', 'hmwp_securitycheck_time');
define('HMWP_CRON', 'hmwp_cron_process');
