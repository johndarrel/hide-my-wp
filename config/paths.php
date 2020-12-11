<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

$currentDir = dirname( __FILE__ );

define( '_HMW_NAMESPACE_', 'HMW' );
define( '_HMW_VER_NAME_', 'Lite' );
define( '_HMW_PLUGIN_NAME_', 'hide-my-wp' );
defined( '_HMW_SUPPORT_SITE_' ) || define( '_HMW_SUPPORT_SITE_', 'https://wpplugins.tips' );
defined( '_HMW_ACCOUNT_SITE_' ) || define( '_HMW_ACCOUNT_SITE_', 'https://account.wpplugins.tips' );
defined( '_HMW_API_SITE_' ) || define( '_HMW_API_SITE_', _HMW_ACCOUNT_SITE_ );
define( '_HMW_SUPPORT_EMAIL_', 'contact@wpplugins.tips' );

/* Directories */
define( '_HMW_ROOT_DIR_', realpath( $currentDir . '/..' ) );
define( '_HMW_CLASSES_DIR_', _HMW_ROOT_DIR_ . '/classes/' );
define( '_HMW_CONTROLLER_DIR_', _HMW_ROOT_DIR_ . '/controllers/' );
define( '_HMW_MODEL_DIR_', _HMW_ROOT_DIR_ . '/models/' );
define( '_HMW_TRANSLATIONS_DIR_', _HMW_ROOT_DIR_ . '/languages/' );
define( '_HMW_THEME_DIR_', _HMW_ROOT_DIR_ . '/view/' );

/* URLS */
define( '_HMW_URL_', plugins_url() . '/' . _HMW_PLUGIN_NAME_ );
define( '_HMW_THEME_URL_', _HMW_URL_ . '/view/' );