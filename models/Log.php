<?php
/**
 * Events Log Model
 * Called to hook and log the users Events
 *
 * @file  The Events file
 * @package HMWP/EventsModel
 * @since 6.0.0
 * @deprecated since version 8.2
 */

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

HMWP_Classes_ObjController::getClass( 'HMWP_Controllers_EventsLog' );

class HMWP_Models_Log extends HMWP_Models_EventsLog {

	/**
	 * Log the known event
	 *
	 * @param $action
	 * @param $values
	 *
	 * @return void
	 * @throws Exception
	 * @deprecated since version 8.2
	 */
	public function hmwp_log_actions( $action = null, $values = array() ) {
		$this->save( $action, $values );
	}
}
