<?php
/**
 * Security Threats Log Class
 * Called on Traffic Log
 *
 * @file The Security Threats Log file
 * @package HMWP/ThreatsLog
 * @since 8.0.0
 */

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Controllers_ThreatsLog extends HMWP_Classes_FrontController {

	/** @var array Threat data */
	public $threat = array();

	/** @var bool Is the response an AJAX response? */
	public $isAjaxResponse = false;

	/** @var array Map points for the GeoMap content view */
	public $mapPoints = array();

	/** @var array Top points for the GeoMap content view */
	public $topPoints = array();

	/**
	 * @throws Exception
	 */
	public function __construct() {
		parent::__construct();

		if ( HMWP_Classes_Tools::getValue( 'hmwp_preview' ) == HMWP_Classes_Tools::getOption( 'hmwp_disable_name' ) ) {
			return;
		}

		add_action( 'hmwp_threat_detected', function ( $threat ) {
			$this->threat = $threat;
			add_action( 'shutdown', array( $this, 'run' ) );
		} );
	}

	/**
	 * Initializes the hook for setting up the database table.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function hookInit() {
		/** @var HMWP_Models_Firewall_Database $database */
		$database = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Firewall_Database' );
		$database->maybeCreateTable();
	}

	/**
	 * Executes the primary operation of saving the model.
	 *
	 * @return void
	 */
	public function run() {
		try {
			$this->model->save( $this->threat );
		} catch ( Exception $e ) {
		}
	}

	/**
	 * Handles various actions related to brute force protection and IP management.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function action() {
		parent::action();

		if ( HMWP_Classes_Tools::getValue( 'action' ) == 'hmwp_load_threat_map' ) {

			if ( ! HMWP_Classes_Tools::userCan( HMWP_CAPABILITY ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Permission denied.', 'hide-my-wp' ), ), 403 );
			}

			check_ajax_referer( 'hmwp_load_threat_map', 'hmwp_nonce' );

			$mapData = $this->model->getThreatMapPayload( 7 );
			$points  = array();

			if ( isset( $mapData['points'] ) && is_array( $mapData['points'] ) ) {
				$points = array_values( $mapData['points'] );
			}

			usort(
				$points,
				static function ( $a, $b ) {
					return ( (int) ( $b['total'] ?? 0 ) ) <=> ( (int) ( $a['total'] ?? 0 ) );
				}
			);

			$topPoints = array_slice(
				array_values(
					array_filter(
						$points,
						static function ( $row ) {
							return isset( $row['total'] ) && (int) $row['total'] > 0;
						}
					)
				), 0, 5
			);

			$this->isAjaxResponse = true;
			$this->mapPoints      = $points;
			$this->topPoints      = $topPoints;

			$html = $this->getView( 'blocks/GeoMapContent' );

			wp_send_json_success(
				array(
					'html'    => $html,
					'mapData' => $mapData,
				)
			);
		}
	}
}