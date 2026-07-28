<?php
/**
 * Background Cron action
 *
 * @file The Cron file
 * @package HMWP/Cron
 * @since 4.0.0
 */

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Controllers_Cron {


	public function __construct() {
		add_action( HMWP_CRON_ONCE, array( $this, 'processCronOnce' ) );

		add_action( HMWP_CRON, array( $this, 'processCron' ) );
	}

	/**
	 * Register the cron at once.
	 */
	public function registerOnce() {

		// Clear any pending once event so you can re-schedule reliably
		wp_clear_scheduled_hook( HMWP_CRON_ONCE );

		// Give it a small delay to avoid edge cases with "time()"
		wp_schedule_single_event( time(), HMWP_CRON_ONCE );
	}

	/**
	 * Register the cron interval.
	 */
	public function registerInterval() {

		add_filter( 'cron_schedules', function ( $schedules ) {
			if ( ! isset( $schedules['hmwp_every_minute'] ) ) {
				$schedules['hmwp_every_minute'] = [
					'interval' => 60,
					'display'  => 'Every Minute',
				];
			}

			return $schedules;
		} );

		// Activate the cron job if not exists.
		if ( ! wp_next_scheduled( HMWP_CRON ) ) {
			wp_schedule_event( time(), 'hmwp_every_minute', HMWP_CRON );
		}
	}


	/**
	 * Executes the scheduled cron job.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function processCron() {
		// Cache plugin compatibility checks (existing behavior)
		$this->maybeChangeCache();

		// Daily maintenance tasks (throttled)
		$this->maybePurgeThreatLogs();

		// Batch-resolve missing country codes in the threats log
		$this->maybeResolveCountryCodes();
	}

	/**
	 * Executes a single instance of the scheduled cron job to perform security checks.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function processCronOnce() {
		HMWP_Classes_ObjController::getClass( 'HMWP_Controllers_SecurityCheck' )->doSecurityCheck();
	}

	/**
	 * Checks whether cache changes are enabled and processes the cache files to update paths if required.
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function maybeChangeCache() {

		if ( ! HMWP_Classes_Tools::getOption( 'hmwp_change_in_cache' ) &&
		     ! HMWP_Classes_Tools::getOption( 'hmwp_mapping_file' ) ) {
			return;
		}

		// Change paths in cache files
		HMWP_Classes_ObjController::getClass( 'HMWP_Models_Compatibility' )->checkCacheFiles();

	}

	/**
	 * Batch-resolve missing country codes in the threats log (runs every cron tick).
	 * Processes up to 200 distinct IPs per run to stay fast.
	 *
	 * @return void
	 */
	protected function maybeResolveCountryCodes() {

		if ( ! HMWP_Classes_Tools::getOption( 'hmwp_threats_log' ) ) {
			return;
		}

		/** @var HMWP_Models_ThreatsLog $threatsLog */
		$threatsLog = HMWP_Classes_ObjController::getClass( 'HMWP_Models_ThreatsLog' );
		$threatsLog->resolveCountryCodes();
	}

	/**
	 * Purge old threat logs once per day (even though cron runs every minute).
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function maybePurgeThreatLogs() {

		// Only if the feature is enabled
		if ( ! HMWP_Classes_Tools::getOption( 'hmwp_threats_log' ) ) {
			return;
		}

		$now     = time();
		$lastRun = (int) get_option( HMWP_THREATS_PURGE, 0 );

		// Run at most once every 24h
		if ( $lastRun > 0 && ( $now - $lastRun ) < DAY_IN_SECONDS ) {
			return;
		}

		// Record last run first to avoid multiple processes doing the same work
		update_option( HMWP_THREATS_PURGE, $now, false );

		// If connected to the API
		if ( HMWP_Classes_Tools::getOption( 'api_token' ) ) {
			// Log threats for the last 7 days
			/** @var HMWP_Models_ThreatsLog $threatsLog */
			$threatsLog = HMWP_Classes_ObjController::getClass( 'HMWP_Models_ThreatsLog' );
			$data       = $threatsLog->getThreatStatsByDay( 7 );
			HMWP_Classes_Tools::hmwp_remote_post( _HMWP_ACCOUNT_SITE_ . '/api/settings', array( 'threats' => $data ), array( 'timeout' => 5 ) );
		}

		// Save the total threats for each day
		if ( ! empty( $data ) && isset( $data['date'] ) && isset( $data['blocked'] ) ) {
			$threats_total = HMWP_Classes_Tools::getOption( 'hmwp_threats_total' );
			$index         = array_search( wp_date( 'Y-m-d', strtotime( '-1 days' ) ), $data['date'] );
			if ( $index && isset( $data['blocked'][ $index ] ) ) {
				$yesterday = (int) $data['blocked'][ $index ];
				HMWP_Classes_Tools::saveOptions( 'hmwp_threats_total', ( $threats_total + $yesterday ) );
			}
		}

		/** @var HMWP_Models_ThreatsLog $threatsLog */
		$threatsLog = HMWP_Classes_ObjController::getClass( 'HMWP_Models_ThreatsLog' );
		$threatsLog->purgeOldThreatLogs();
	}

}
