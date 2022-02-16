<?php
/**
 * Definition of all the paths from the plugin
 *
 * @file The paths configuration file
 *
 * @package HMWP\Paths
 */

defined('ABSPATH') || die('Cheatin\' uh?');

$currentDir = dirname(__FILE__);

define('_HMWP_NAMESPACE_', 'HMWP');
define('_HMWP_PLUGIN_FULL_NAME_', 'Hide My WP Ghost');
define('_HMWP_ACCOUNT_SITE_', 'https://account.hidemywpghost.com');
define('_HMWP_API_SITE_', _HMWP_ACCOUNT_SITE_);
define('_HMWP_SUPPORT_EMAIL_', 'contact@hidemywpghost.com');
define('_HMWP_CHECK_SSL_', (((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === "on") || (defined('FORCE_SSL_ADMIN') && FORCE_SSL_ADMIN) || (function_exists('is_ssl') && is_ssl())) ? true : false));

/**
 * Directories
 */
define('_HMWP_ROOT_DIR_', realpath($currentDir . '/..'));
define('_HMWP_CLASSES_DIR_', _HMWP_ROOT_DIR_ . '/classes/');
define('_HMWP_CONTROLLER_DIR_', _HMWP_ROOT_DIR_ . '/controllers/');
define('_HMWP_MODEL_DIR_', _HMWP_ROOT_DIR_ . '/models/');
define('_HMWP_TRANSLATIONS_DIR_', _HMWP_ROOT_DIR_ . '/languages/');
define('_HMWP_THEME_DIR_', _HMWP_ROOT_DIR_ . '/view/');
define('_HMWP_ASSETS_DIR_', _HMWP_THEME_DIR_ . 'assets/');

/**
 * URLS paths
 */
define('_HMWP_URL_', plugins_url() . '/' . plugin_basename(_HMWP_ROOT_DIR_));
define('_HMWP_THEME_URL_', _HMWP_URL_ . '/view/');
define('_HMWP_ASSETS_URL_', _HMWP_THEME_URL_ . 'assets/');
define('_HMWP_WPLOGIN_URL_', _HMWP_THEME_URL_ . 'wplogin/');
