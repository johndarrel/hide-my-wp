<?php
/**
 * Ghost Doctor Class
 * Diagnoses why the website paths stopped working and repairs what it can
 *
 * @file The Ghost Doctor controller file
 * @package HMWP/Ghostdoctor
 * @since 9.2.0
 */

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Controllers_Ghostdoctor extends HMWP_Classes_FrontController {

	/**
	 * The last diagnosis and repair run, shown in the view.
	 *
	 * @var array
	 */
	public $report = array();

	/**
	 * True while a repair is waiting to be kept or undone.
	 *
	 * @var bool
	 */
	public $pending = false;

	/**
	 * What the account server says about the AI allowance.
	 *
	 * @var array
	 */
	public $quota = array();

	/**
	 * How many AI checks are left this month.
	 *
	 * @var int
	 */
	public $remaining = 0;

	/**
	 * Print the panel on the Security Check page.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function showPanel() {

		/** @var HMWP_Models_Ghostdoctor $model */
		$model = $this->model;

		$this->report  = $model->getReport();
		$this->pending = $model->hasSnapshot();

		// Cached, so drawing this page never waits on the network. If the account
		// server cannot be reached the panel simply says nothing about the AI and
		// everything else on the page still works.
		/** @var HMWP_Models_Aiclient $client */
		$client          = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Aiclient' );
		$this->quota     = $client->getQuota();
		$this->remaining = $client->getRemaining( $this->quota );

		$this->show( 'blocks/Ghostdoctor' );
	}

	/**
	 * Ask the account server to explain the current findings and store the result.
	 *
	 * Shared by the Explain button and by the security scan, which calls this
	 * itself whenever a scan turns up a different set of findings. Without that,
	 * running a scan would leave the wording describing the previous scan and the
	 * only way back would be a second button press, which is not a flow anyone
	 * should have to work out.
	 *
	 * @return bool|string True on success, 'nothing' when there is nothing to
	 *                     explain, or an error message.
	 */
	public function runExplain() {

		/** @var HMWP_Controllers_SecurityCheck $securitycheck */
		$securitycheck = HMWP_Classes_ObjController::getClass( 'HMWP_Controllers_SecurityCheck' );
		$securitycheck->initSecurity();

		$findings = $securitycheck->getActionItems();

		if ( empty( $findings ) ) {
			return 'nothing';
		}

		// Only what the model needs to judge urgency. No page content, no
		// user data, no licence details beyond the headers already sent.
		$send = array();
		foreach ( $findings as $finding ) {
			$send[] = array(
				'id'       => $finding['id'],
				'severity' => $finding['severity'],
				'source'   => $finding['source'],
				'title'    => $finding['title'],
				'detail'   => wp_strip_all_tags( $finding['why'] ),
			);
		}

		/** @var HMWP_Models_Aiclient $client */
		$client = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Aiclient' );

		/** @var HMWP_Models_Ghostdoctor $model */
		$model = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Ghostdoctor' );

		$result = $client->explain( $send, $model->readSiteState(), '' );

		if ( empty( $result['ok'] ) ) {
			return ( $result['message'] <> '' ? $result['message'] : false );
		}

		// Keyed by finding id so the wording can only ever appear next to
		// the finding it was written for
		$byid = array();
		foreach ( (array) $result['data']['findings'] as $finding ) {
			if ( ! empty( $finding['id'] ) ) {
				$byid[ $finding['id'] ] = $finding;
			}
		}

		update_option( HMWP_AI_EXPLAIN, array(
			'time'      => time(),
			'signature' => $securitycheck->getFindingsSignature( $findings ),
			'summary'   => isset( $result['data']['summary'] ) ? $result['data']['summary'] : '',
			'findings'  => $byid,
		), false );

		return true;
	}

	/**
	 * Run the repairs and report what happened.
	 *
	 * Every repair is checked straight after it is made and undone when it did
	 * not help, so a run never leaves a setting changed for nothing. The loop
	 * stops as soon as the website is working, which is why the repairs that
	 * give up protection sit at the end of the list and are usually never
	 * reached.
	 *
	 * @param array $plan  The ordered repairs to try.
	 * @param array $probe The state of the site before the run.
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function runRepairs( $plan, $probe ) {

		/** @var HMWP_Models_Ghostdoctor $model */
		$model = $this->model;

		$steps  = array();
		$before = count( $probe['errors'] );

		foreach ( $plan as $fix ) {

			$step   = $model->repairStep( $fix['id'], $before );
			$before = $step['after'];

			// The probe is large and is not needed in the stored report
			$probe = $step['probe'];
			unset( $step['probe'] );
			$steps[] = $step;

			if ( 'solved' === $step['outcome'] ) {
				break;
			}
		}

		return array( 'steps' => $steps, 'probe' => $probe );
	}

	/**
	 * Build the report shown after a run.
	 *
	 * @param array $diagnosis The diagnosis the run started from.
	 * @param array $result    The outcome of runRepairs().
	 * @param string $mode     Which repair mode was used.
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function buildReport( $diagnosis, $result, $mode ) {

		/** @var HMWP_Models_Ghostdoctor $model */
		$model = $this->model;

		$state = $model->readSiteState();

		return array(
			'time'      => time(),
			'mode'      => $mode,
			'started'   => count( $diagnosis['probe']['errors'] ),
			'remaining' => count( $result['probe']['errors'] ),
			'success'   => ! empty( $result['probe']['success'] ),
			'steps'     => $result['steps'],
			'errors'    => $result['probe']['errors'],
			'blockers'  => $model->getBlockers( $state, $result['probe'] ),
			'plan'      => $model->getPlan( $result['probe'], $state ),
			'state'     => $state,
		);
	}


	/**
	 * Run the actions on submit
	 *
	 * @throws Exception
	 */
	public function action() {
		parent::action();

		// Check if the current user has the 'hmwp_manage_settings' capability
		if ( ! HMWP_Classes_Tools::userCan( HMWP_CAPABILITY ) ) {
			return;
		}

		/** @var HMWP_Models_Ghostdoctor $model */
		$model = $this->model;

		switch ( HMWP_Classes_Tools::getValue( 'action' ) ) {

			case 'hmwp_ghostdoctor_diagnose':

				$diagnosis = $model->diagnose();

				$model->saveReport( array(
					'time'      => $diagnosis['time'],
					'mode'      => 'diagnose',
					'started'   => count( $diagnosis['probe']['errors'] ),
					'remaining' => count( $diagnosis['probe']['errors'] ),
					'success'   => ! empty( $diagnosis['probe']['success'] ),
					'steps'     => array(),
					'errors'    => $diagnosis['probe']['errors'],
					'blockers'  => $diagnosis['blockers'],
					'plan'      => $diagnosis['plan'],
					'state'     => $diagnosis['state'],
				) );

				if ( ! empty( $diagnosis['probe']['success'] ) ) {
					HMWP_Classes_Error::setNotification( esc_html__( 'Your website paths are all working. Nothing needs to be repaired.', 'hide-my-wp' ), 'success' );
				} else {
					HMWP_Classes_Error::setNotification(
						sprintf(
						/* translators: 1: Number of checks that failed. */
							esc_html( _n( '%1$s check failed. Ghost Doctor has listed what it can repair.', '%1$s checks failed. Ghost Doctor has listed what it can repair.', count( $diagnosis['probe']['errors'] ), 'hide-my-wp' ) ),
							count( $diagnosis['probe']['errors'] )
						)
					);
				}

				break;

			case 'hmwp_ghostdoctor_repair':

				$mode      = HMWP_Classes_Tools::getValue( 'mode', 'all' );
				$diagnosis = $model->diagnose();

				if ( ! empty( $diagnosis['probe']['success'] ) ) {
					HMWP_Classes_Error::setNotification( esc_html__( 'Your website paths are all working. Nothing needs to be repaired.', 'hide-my-wp' ), 'success' );
					break;
				}

				$plan = $diagnosis['plan'];

				// Only the repairs the user ticked
				if ( 'selected' === $mode ) {
					$chosen = (array) HMWP_Classes_Tools::getValue( 'fixes', array() );
					$keep   = array();
					foreach ( $plan as $fix ) {
						if ( in_array( $fix['id'], $chosen ) ) {
							$keep[] = $fix;
						}
					}
					$plan = $keep;
				}

				// One repair per click
				if ( 'one' === $mode ) {
					$chosen = HMWP_Classes_Tools::getValue( 'fix' );
					$keep   = array();
					foreach ( $plan as $fix ) {
						if ( $fix['id'] === $chosen ) {
							$keep[] = $fix;
							break;
						}
					}
					$plan = $keep;
				}

				if ( empty( $plan ) ) {
					HMWP_Classes_Error::setNotification( esc_html__( 'No repair was selected.', 'hide-my-wp' ) );
					break;
				}

				// Keep the settings as they are now so the run can be undone.
				// Only taken once, so several runs still undo back to the start.
				if ( ! $model->hasSnapshot() ) {
					$model->snapshot();
				}

				$result = $this->runRepairs( $plan, $diagnosis['probe'] );
				$report = $this->buildReport( $diagnosis, $result, $mode );

				$model->saveReport( $report );

				if ( $report['success'] ) {
					HMWP_Classes_Error::setNotification( esc_html__( 'Your website paths are working again. Check your site, then keep or undo the changes below.', 'hide-my-wp' ), 'success' );
				} elseif ( $report['remaining'] < $report['started'] ) {
					HMWP_Classes_Error::setNotification(
						sprintf(
						/* translators: 1: Checks failing before. 2: Checks failing now. */
							esc_html__( 'Some progress: %1$s checks were failing, now %2$s. The rest needs a change on your server.', 'hide-my-wp' ),
							esc_html( $report['started'] ),
							esc_html( $report['remaining'] )
						)
					);
				} else {
					HMWP_Classes_Error::setNotification( esc_html__( 'Ghost Doctor could not repair this from the plugin. Every setting it changed has been put back. See the steps below for what your server needs.', 'hide-my-wp' ) );
				}

				break;


			case 'hmwp_ghostdoctor_keep':

				$model->clearSnapshot();
				HMWP_Classes_Error::setNotification( esc_html__( 'The changes have been kept.', 'hide-my-wp' ), 'success' );

				break;

			case 'hmwp_ai_explain':

				$explained = $this->runExplain();

				// The page refreshes this by itself after a scan, so it has to be
				// answerable over ajax as well as by the button.
				if ( HMWP_Classes_Tools::isAjax() ) {
					if ( true === $explained ) {
						wp_send_json_success( esc_html__( 'Done!', 'hide-my-wp' ) );
					}

					wp_send_json_error(
						( is_string( $explained ) && $explained <> '' && 'nothing' <> $explained
							? $explained
							: esc_html__( 'The explanations could not be fetched right now.', 'hide-my-wp' ) )
					);
				}

				if ( 'nothing' === $explained ) {
					HMWP_Classes_Error::setNotification( esc_html__( 'There is nothing to explain. Every check passed.', 'hide-my-wp' ), 'success' );
				} elseif ( true === $explained ) {
					HMWP_Classes_Error::setNotification( esc_html__( 'The findings below have been explained for your website.', 'hide-my-wp' ), 'success' );
				} else {
					HMWP_Classes_Error::setNotification(
						( is_string( $explained ) && $explained <> ''
							? $explained
							: esc_html__( 'The explanations could not be fetched right now.', 'hide-my-wp' ) )
					);
				}

				break;

			case 'hmwp_ghostdoctor_undo':

				if ( $model->restore() ) {
					$model->saveReport( array() );
					HMWP_Classes_Error::setNotification( esc_html__( 'Every setting Ghost Doctor changed has been put back the way it was.', 'hide-my-wp' ), 'success' );
				} else {
					HMWP_Classes_Error::setNotification( esc_html__( 'There was nothing to undo.', 'hide-my-wp' ) );
				}

				break;
		}
	}

}
