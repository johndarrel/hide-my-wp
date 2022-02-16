<?php

/**
 * Called on plugin uninstall
 */
if (!defined('ABSPATH') && !defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

//Set the plugin basename
define( 'HMWP_BASENAME',  plugin_basename(__FILE__) );

/* Call config files */
require(dirname(__FILE__) . '/config/config.php');

/* Delete the record from database */
delete_option(HMWP_OPTION);
delete_option(HMWP_OPTION_SAFE);
delete_option(HMWP_SECURITY_CHECK);
delete_option(HMWP_SECURITY_CHECK_IGNORE);
delete_option(HMWP_SECURITY_CHECK_TIME);