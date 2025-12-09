<?php
/**
 * Background Cron action
 *
 * @file The Cron file
 * @package HMWP/Cron
 * @since 4.0.0
 */

class HMWP_Controllers_Cron {

	/**
	 * HMWP_Controllers_Cron constructor.
	 */
	public function __construct() {
		add_filter( 'cron_schedules', array( $this, 'setInterval' ) );

		//Activate the cron job if not exists.
		if ( ! wp_next_scheduled( HMWP_CRON ) ) {
			wp_schedule_event( time(), 'hmwp_every_minute', HMWP_CRON );
		}
	}

	/**
	 * Add a custom schedule interval to the existing schedules.
	 *
	 * @param  array  $schedules  An array of the existing cron schedules.
	 *
	 * @return array The modified array of cron schedules with the new custom interval added.
	 */
	function setInterval( $schedules ) {

		$schedules['hmwp_every_minute'] = array(
			'display'  => 'every 1 minute',
			'interval' => 60
		);

		return $schedules;
	}

	/**
	 * Executes the scheduled cron job to verify and update cache plugins.
	 *
	 * This method checks the cache plugins and updates the paths in the cache files
	 * to ensure compatibility with the current configuration.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function processCron() {
		// Check the cache plugins and change the paths in the cache files.
		HMWP_Classes_ObjController::getClass( 'HMWP_Models_Compatibility' )->checkCacheFiles();
	}


}
