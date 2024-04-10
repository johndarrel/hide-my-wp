<?php
/**
 * Compatibility Class
 *
 * @file The LiteSpeed Model file
 * @package HMWP/Compatibility/LiteSpeed
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Models_Compatibility_LiteSpeed extends HMWP_Models_Compatibility_Abstract
{

    public function hookAdmin()
    {
	    add_action( 'wp_initialize_site', function ( $site_id ) {
		    HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rewrite' )->flushChanges();
	    }, PHP_INT_MAX, 1 );

	    add_action( 'create_term', function ( $term_id ) {
		    add_action( 'admin_footer', function () {
			    HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rewrite' )->flushChanges();
		    } );
	    }, PHP_INT_MAX, 1 );

	    //wait for the cache on litespeed servers and flush the changes
	    add_action( 'hmwp_apply_permalink_changes', function () {
		    sleep( 5 ); //wait 5 sec to clear the cache

		    add_action( 'admin_footer', function () {
			    HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rewrite' )->flushChanges();
		    } );
	    } );
	}

	public function hookFrontend() {

		add_action( 'litespeed_initing' , function (){
			if (! defined( 'LITESPEED_DISABLE_ALL' ) || ! defined( 'LITESPEED_GUEST_OPTM' )){
				add_filter('hmwp_process_buffer', '__return_false');
			}
		});
		add_filter( 'litespeed_buffer_finalize', array($this, 'findReplaceCache'), PHP_INT_MAX );
		add_filter('hmwp_priority_buffer', '__return_true');
		add_filter('litespeed_comment', '__return_false');

	}

}
