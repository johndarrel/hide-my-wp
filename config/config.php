<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * The configuration file
 */
!defined('HMW_REQUEST_TIME') && define('HMW_REQUEST_TIME', microtime(true));
defined('_HMW_NONCE_ID_') || define('_HMW_NONCE_ID_', NONCE_KEY);

//force Hide My Wp to load right after initialization
defined('HMW_PRIORITY') || define('HMW_PRIORITY', false);
//force changing all the paths even in admin
defined('HMW_FORCEPATH') || define('HMW_FORCEPATH', false);
//force Safe Mode and redirects for all static files
defined('HMW_SAFEMODE') || define('HMW_SAFEMODE', false);
//add HMW Rules in WordPress rewrite definition in htaccess
defined('HMW_RULES_IN_WP_RULES') || define('HMW_RULES_IN_WP_RULES', true);

if (!defined('PHP_VERSION_ID')) {
    $version = explode('.', PHP_VERSION);
    define('PHP_VERSION_ID', ((int) @$version[0] * 1000 + (int) @$version[1] * 100 + ((isset($version[2])) ? ((int) $version[2] * 10) : 0)));
}
if (!defined('WP_VERSION_ID') && isset($wp_version)) {
    $version = explode('.', $wp_version);
    define('WP_VERSION_ID', ((int) @$version[0] * 1000 + (int) @$version[1] * 100 + ((isset($version[2])) ? ((int) $version[2] * 10) : 0)));
}else{
    !defined('WP_VERSION_ID') && define('WP_VERSION_ID', '3000');
}

if (!defined('HMW_VERSION_ID')) {
    $version = explode('.', HMW_VERSION);
    define('HMW_VERSION_ID', ((int) @$version[0] * 10000 + (int) @$version[1] * 1000 + ((isset($version[2])) ? ((int) $version[2] * 1) : 0)));
}

/* No path file? error ... */
require_once(dirname(__FILE__) . '/paths.php');

/* Define the record name in the Option and UserMeta tables */
define('HMW_OPTION', 'hmw_options');
define('HMW_OPTION_SAFE', 'hmw_options_safe');
