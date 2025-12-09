<?php

// Called on plugin uninstall
if ( ! defined( 'ABSPATH' ) && ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

// Call config files
require dirname( __FILE__ ) . '/index.php';

// Uninstall the temporary logins on plugin uninstall
HMWP_Classes_ObjController::getClass( 'HMWP_Classes_Tools' );

//remove user capability
HMWP_Classes_ObjController::getClass( 'HMWP_Models_RoleManager' )->removeHMWPCaps();

// Delete the record from database
delete_option( HMWP_OPTION );
delete_option( HMWP_OPTION_SAFE );
delete_option( HMWP_SECURITY_CHECK );
delete_option( HMWP_SECURITY_CHECK_IGNORE );
delete_option( HMWP_SECURITY_CHECK_TIME );