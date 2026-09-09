<?php defined( 'ABSPATH' ) || die( 'Cheating uh?' );
if ( ! isset( $view ) ) {
    return;
}

$page = HMWP_Classes_Tools::getValue( 'page' );

// The full tables are the evidence behind the summary, not the summary itself, so
// they start closed. Once the user opens or closes anything, WordPress stores the
// choice and this default stops applying.
$default_closed = ( false === get_user_option( "closedpostboxes_$page" ) ) ? ' closed' : '';

// Run the scan on arrival when this website has never been scanned, so a first
// visit shows results instead of an empty page and a button. A stale report is
// left alone on purpose: reloading the page under someone who came here to read
// their last results is worse than showing them a date and letting them decide.
$do_check = ( ! get_option( HMWP_SECURITY_CHECK ) );

// Totals for the summary row
$overview = array( 'success' => 0, 'warning' => 0, 'total' => 0 );
if ( ! empty( $view->report ) ) {
    foreach ( $view->report as $row ) {
        $overview['success'] += (int) ( $row['valid'] ?? '' );
        $overview['warning'] += (int) ( $row['warning'] ?? '' );
        $overview['total']   += 1;
    }
}
$failed = max( 0, $overview['total'] - $overview['success'] );

// Everything that needs attention, worst first, in one list. Computed up here
// because the status card renders before the list and needs to know what is on it.
$actions = $view->getActionItems();
$counts  = $view->countBySeverity( $actions );

// Identifies this exact set of problems, so the summary below can be dropped the
// moment the website stops having them, and to key the refresh so a call that
// keeps failing gives up instead of reloading forever.
$signature = $view->getFindingsSignature( $actions );

// The AI wording, when it has been asked for. Read up here because the summary
// renders above the list it describes.
//
// The summary describes the whole website at once, so it is tied to the set of
// findings it was written about. A different set means it no longer applies.
$explained = get_option( HMWP_AI_EXPLAIN );
$summary   = '';

if ( is_array( $explained ) && ! empty( $explained['summary'] )
     && isset( $explained['signature'] )
     && $explained['signature'] === $signature ) {
	$summary = $explained['summary'];
}

// Whether anything on screen is currently carrying AI wording. The rows keep
// theirs even when the summary goes, because each one is bound to a finding id.
// The footer needs the difference so it never offers a first explanation for
// findings that already have one.
$has_ai = false;
if ( is_array( $explained ) && ! empty( $explained['findings'] ) ) {
	foreach ( $actions as $item ) {
		if ( isset( $item['id'] ) && isset( $explained['findings'][ $item['id'] ] ) ) {
			$has_ai = true;
			break;
		}
	}
}

// Only offer the button when the account server says it can answer. On the free
// version that is the whole gate: nothing here checks the edition, so a website
// whose account carries an allowance gets the explanations and one that does not
// is told exactly what is missing instead of being shown a generic upgrade box.
/** @var HMWP_Models_Aiclient $aiclient */
$aiclient = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Aiclient' );
$ai_quota = $aiclient->getQuota();
$ai_ready = $aiclient->isReady( $ai_quota );

// A scan that turned up different problems leaves the stored summary describing
// the previous one. Rather than making someone find a second button, the page
// refreshes it itself once it has rendered.
//
// Not folded into the scan request on purpose. The scan already spends around
// nine seconds probing the website and the explanation another nine, and putting
// both in one form post ran the request past the time limit and had the worker
// killed mid-write. Asking for it afterwards keeps the scan as fast as it was.
$needs_explain = ( $ai_ready && $has_ai && '' === $summary );

// Running out for the month is not an error, so the quota call still succeeds and
// simply reports no allowance left. Without this the whole footer disappeared and
// nobody was told why the explanations had stopped.
$ai_used_up = ( ! $ai_ready
                && ! empty( $ai_quota['ok'] )
                && ! empty( $ai_quota['data']['allowed'] )
                && (int) $ai_quota['data']['used'] >= (int) $ai_quota['data']['allowed'] );

$ai_resets = ( $ai_used_up && ! empty( $ai_quota['data']['resets_at'] ) )
	? mysql2date( get_option( 'date_format' ), $ai_quota['data']['resets_at'] )
	: '';

// An account with no active subscription is the ordinary case here, so it gets a
// line that names the feature rather than the generic upgrade button. Anything
// else, including an account server that cannot be reached, falls back to that
// button: losing the offer entirely because a network call failed would be worse
// than showing it.
$ai_error    = ( ! empty( $ai_quota ) && empty( $ai_quota['ok'] ) ) ? $ai_quota['error'] : '';
$ai_inactive = in_array( $ai_error, array( 'subscription_inactive', 'subscription_expired' ), true );

// Ghost Doctor is a separate model, so the tile reads its report only when that
// model is present. Same guard the action list uses.
$gdreport = array();
if ( file_exists( _HMWP_MODEL_DIR_ . 'Ghostdoctor.php' ) ) {
	/** @var HMWP_Models_Ghostdoctor $gdmodel */
	$gdmodel  = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Ghostdoctor' );
	$gdreport = $gdmodel->getReport();
}

?>

<script>
    jQuery(document).ready(function ($) {
        if (typeof postboxes !== 'undefined') {
            postboxes.add_postbox_toggles('<?php echo esc_attr( $page ); ?>');
        }

		<?php
		// First visit with no report yet: run the scan without making anyone press
		// anything, then reload so the page renders the results. The flag is per
		// tab, so a scan that fails to write a report cannot loop the reload.
		if ( $do_check ) { ?>
        if (!window.sessionStorage || !sessionStorage.getItem('hmwp_autoscan')) {
            if (window.sessionStorage) {
                sessionStorage.setItem('hmwp_autoscan', '1');
            }

            var $scan = $('#hmwp_securitycheck');
            var $button = $scan.find('button');

            $button.prop('disabled', true).text('<?php echo esc_js( __( 'Scanning...', 'hide-my-wp' ) ); ?>');

            $.post(ajaxurl, $scan.serialize()).always(function () {
                window.location.reload();
            });
        }
		<?php } ?>

		<?php
		// The scan has already run and its results are on screen. What is missing
		// is the wording, so it is fetched now that nothing is blocking the page.
		// Keyed by the findings signature so a call that keeps failing gives up
		// instead of reloading forever.
		if ( $needs_explain ) { ?>
        var explainKey = 'hmwp_explain_<?php echo esc_js( $signature ); ?>';

        if (!window.sessionStorage || !sessionStorage.getItem(explainKey)) {
            if (window.sessionStorage) {
                sessionStorage.setItem(explainKey, '1');
            }

            $.post(ajaxurl, $('#hmwp_ai_explain').serialize()).always(function () {
                window.location.reload();
            });
        }
		<?php } ?>

    });
</script>

<div id="hmwp_wrap" class="d-flex flex-row p-0 my-3">
    <div class="hmwp_row d-flex flex-row p-0 m-0">
        <div class="hmwp_col flex-grow-1 px-2 py-0 mr-2 mb-3 meta-box-sortables">

			<?php do_action( 'hmwp_security_check_beginning' ); ?>

            <!-- Summary of where the website stands -->
            <div id="hmwp_status_widget" class="card col-sm-12 p-0 m-0 mb-3 postbox <?php echo esc_attr( postbox_classes( 'hmwp_status_widget', $page ) ); ?>">
                <div class="postbox-header hmwp_header">
                    <h3 class="card-title p-2 m-0 hndle"><?php echo esc_html__( 'WordPress Security Check', 'hide-my-wp' ); ?></h3>
                    <div class="handle-actions hide-if-no-js mr-2">
                        <button type="button" class="handlediv" aria-expanded="true">
                            <span class="toggle-indicator" aria-hidden="true"></span>
                        </button>
                    </div>
                </div>
                <div class="inside">
                    <div class="card-body p-0">

                        <div class="row row-cols-1 row-cols-md-2 m-0 p-0 pt-2 pb-4 align-items-center">

                            <div class="col m-0 p-0 text-center">
                                <div class="m-auto" style="width: 460px; max-width: 90%;">
									<?php include _HMWP_THEME_DIR_ . 'blocks/Speedometer.php'; ?>
                                </div>
                            </div>

                            <div class="col m-0 p-3">

                                <div class="row row-cols-2 m-0 p-0">

                                    <div class="col p-2">
                                        <div class="card h-100 shadow-sm rounded-0 text-center p-3">
                                            <div class="text-success" style="font-size: 2rem; line-height: 1;"><?php echo esc_html( $overview['success'] ); ?></div>
                                            <div class="text-muted small mt-1"><?php echo esc_html__( 'Tasks passed', 'hide-my-wp' ); ?></div>
                                        </div>
                                    </div>

                                    <div class="col p-2">
                                        <div class="card h-100 shadow-sm rounded-0 text-center p-3">
                                            <div class="<?php echo ( $failed ? 'text-danger' : 'text-success' ); ?>" style="font-size: 2rem; line-height: 1;"><?php echo esc_html( $failed ); ?></div>
                                            <div class="text-muted small mt-1"><?php echo esc_html__( 'Tasks failed', 'hide-my-wp' ); ?></div>
                                        </div>
                                    </div>

                                    <div class="col p-2">
										<?php // Not a locked panel. The tile says what the scan is for and
										      // opens the same upgrade dialog the rest of the plugin uses, so
										      // nothing on this screen looks broken or disabled. ?>
                                        <a href="javascript:void(0)" onclick="jQuery('#hmwp_ghost_mode_modal').modal('show')" class="card h-100 shadow-sm rounded-0 text-center p-3 <?php echo esc_attr( HMWP_CLASS_CTA ); ?>" style="text-decoration: none;">
                                            <div class="text-muted" style="font-size: 2rem; line-height: 1;">
                                                <i class="dashicons dashicons-shield-alt" style="font-size: 2rem !important; width: auto; height: auto;"></i>
                                            </div>
                                            <div class="text-muted small mt-1"><?php echo esc_html__( 'Vulnerability scan', 'hide-my-wp' ); ?></div>
                                        </a>
                                    </div>

                                    <div class="col p-2">
										<?php // Ghost Doctor itself lives on Change Paths, so this tile is the way there ?>
                                        <a href="<?php echo esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_permalinks' ) ); ?>" class="card h-100 shadow-sm rounded-0 text-center p-3" style="text-decoration: none;">
											<?php if ( empty( $gdreport['time'] ) ) { ?>
                                                <div class="text-muted" style="font-size: 2rem; line-height: 1;">&mdash;</div>
                                                <div class="text-muted small mt-1"><?php echo esc_html__( 'Paths not checked', 'hide-my-wp' ); ?></div>
											<?php } elseif ( ! empty( $gdreport['success'] ) ) { ?>
                                                <div class="text-success" style="font-size: 2rem; line-height: 1;">
                                                    <i class="dashicons dashicons-yes" style="font-size: 2rem !important; width: auto; height: auto;"></i>
                                                </div>
                                                <div class="text-muted small mt-1"><?php echo esc_html__( 'Paths working', 'hide-my-wp' ); ?></div>
											<?php } else { ?>
                                                <div class="text-danger" style="font-size: 2rem; line-height: 1;"><?php echo esc_html( (int) $gdreport['remaining'] ); ?></div>
                                                <div class="text-muted small mt-1"><?php echo esc_html__( 'Path checks failing', 'hide-my-wp' ); ?></div>
											<?php } ?>
                                        </a>
                                    </div>

                                </div>

                            </div>
                        </div>

						<?php
						// Score, then the sentence that explains the score, then the action.
						// The summary used to sit above the list it describes, where it read
						// as a stray paragraph. It belongs with the number it is talking about.
						?>
                        <div class="col-sm-12 px-4 py-4 border-top text-center">

							<?php // mx-auto, not m-auto. Both are !important in this Bootstrap
							      // build and m-auto is declared last, so it overwrites mb-4 and
							      // the button ends up touching the text. ?>
							<?php if ( $summary <> '' ) { ?>
                                <div class="card-text mx-auto mb-4" style="max-width: 52em;"><?php echo esc_html( $summary ); ?></div>
							<?php } elseif ( $needs_explain ) { ?>
								<?php // wp_loading_min is the spinner the rest of the plugin uses.
								      // Without it this line read as a statement about the website
								      // rather than as something still happening, and the wait can
								      // run close to a minute. ?>
                                <div class="card-text text-muted mx-auto mb-4" style="max-width: 52em;">
                                    <div class="wp_loading_min"></div>
                                    <div class="mt-2"><?php echo esc_html__( 'Working out what these results mean for your website. This can take up to a minute.', 'hide-my-wp' ); ?></div>
                                </div>
							<?php } ?>
                            <form id="hmwp_securitycheck" method="POST" class="m-0">
								<?php wp_nonce_field( 'hmwp_securitycheck', 'hmwp_nonce' ); ?>
                                <input type="hidden" name="action" value="hmwp_securitycheck"/>
                                <button type="submit" class="btn rounded-0 btn-success btn-lg px-5"><?php echo esc_html__( 'Start Scan', 'hide-my-wp' ); ?></button>
                            </form>

							<?php
							// Said as an age, not a clock time. "3 minutes ago" is what
							// someone who just pressed the button is looking for, and it
							// cannot be read wrong on a website whose timezone setting does
							// not match the person reading it. The exact time is on hover.
							?>
                            <div class="text-muted small mt-3">
								<?php if ( isset( $view->securitycheck_time['timestamp'] ) ) { ?>
									<?php $checked_at = (int) $view->securitycheck_time['timestamp']; ?>
                                    <strong><?php echo esc_html__( 'Last check:', 'hide-my-wp' ); ?></strong>
                                    <span title="<?php echo esc_attr( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $checked_at ) ); ?>">
										<?php
										if ( $checked_at > time() - 60 ) {
											echo esc_html__( 'just now', 'hide-my-wp' );
										} else {
											/* translators: %s: Length of time, for example "3 minutes". */
											echo esc_html( sprintf( __( '%s ago', 'hide-my-wp' ), human_time_diff( $checked_at, time() ) ) );
										}
										?>
                                    </span>
								<?php } else { ?>
									<?php echo esc_html__( 'This website has not been scanned yet.', 'hide-my-wp' ); ?>
								<?php } ?>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!--
                The list itself. Three sources are merged into it so nobody has to
                read three tables and work out which one matters most.
            -->
			<?php
			// One colour per level, used for the badge and the row accent so a
			// glance down the left edge tells you where the serious items are.
			$levels = array(
				'critical'  => array(
					'label' => esc_html__( 'Critical', 'hide-my-wp' ),
					'note'  => esc_html__( 'Someone could get in, or your website is broken right now.', 'hide-my-wp' ),
					'class' => 'text-danger',
					'color' => 'var(--hmwp-color-text-danger)',
				),
				'important' => array(
					'label' => esc_html__( 'Important', 'hide-my-wp' ),
					'note'  => esc_html__( 'A real weakness. Worth fixing this week.', 'hide-my-wp' ),
					'class' => 'text-danger',
					'color' => 'var(--hmwp-color-highlight)',
				),
				'suggested' => array(
					'label' => esc_html__( 'Suggested', 'hide-my-wp' ),
					'note'  => esc_html__( 'Hardening. Worth doing when you have time.', 'hide-my-wp' ),
					'class' => 'text-muted',
					'color' => 'var(--hmwp-color-text-muted)',
				),
			);
			?>

            <div id="hmwp_actions_widget" class="card col-sm-12 p-0 m-0 mb-3 postbox <?php echo esc_attr( postbox_classes( 'hmwp_actions_widget', $page ) ); ?>">
                <div class="postbox-header hmwp_header">
                    <h3 class="card-title p-2 m-0 hndle">
						<?php echo esc_html__( 'What To Do Next', 'hide-my-wp' ); ?>
						<?php if ( $counts['critical'] ) { ?>
                            <span class="text-danger">(<?php echo esc_html( $counts['critical'] ); ?> <?php echo esc_html__( 'critical', 'hide-my-wp' ); ?>)</span>
						<?php } ?>
                    </h3>
                    <div class="handle-actions hide-if-no-js mr-2">
                        <button type="button" class="handlediv" aria-expanded="true">
                            <span class="toggle-indicator" aria-hidden="true"></span>
                        </button>
                    </div>
                </div>
                <div class="inside">
                    <div class="card-body p-0">

						<?php if ( empty( $actions ) ) { ?>

                            <div class="p-4 text-center">
								<?php if ( empty( $view->report ) ) { ?>
                                    <div class="text-muted"><?php echo esc_html__( 'Run a scan to see what needs your attention.', 'hide-my-wp' ); ?></div>
								<?php } else { ?>
                                    <div class="text-success" style="font-size: 1.1rem;">
                                        <i class="dashicons dashicons-yes mr-2" style="font-size: 1.6rem !important;"></i>
										<?php echo esc_html__( 'Nothing needs your attention. Every check passed.', 'hide-my-wp' ); ?>
                                    </div>
								<?php } ?>
                            </div>

						<?php } else { ?>

							<?php foreach ( $levels as $key => $level ) {

								if ( empty( $counts[ $key ] ) ) {
									continue;
								}
								?>

                                <div class="col-sm-12 px-4 pt-4 pb-2">
                                    <h4 class="card-title m-0" style="font-size: .8rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: <?php echo esc_attr( $level['color'] ); ?>;">
										<?php echo esc_html( $level['label'] ); ?>
                                        <span class="text-muted">(<?php echo esc_html( $counts[ $key ] ); ?>)</span>
                                    </h4>
                                    <div class="text-muted small"><?php echo esc_html( $level['note'] ); ?></div>
                                </div>

								<?php
								// Layout follows the pattern Wordfence uses for scan results:
								// fixed table layout so the action column never moves, a 4px
								// left border carrying the severity colour, one divider per row,
								// and every control in a single right aligned row. The severity
								// word is not repeated on each row because the section heading
								// above already says it.
								?>
                                <table class="table table_securitycheck border-0 m-0" style="width: 100%; table-layout: fixed;">
                                    <tbody>
									<?php foreach ( $actions as $index => $item ) {
										if ( $item['severity'] <> $key ) {
											continue;
										}

										$oneline = ! empty( $item['action_text'] ) ? $item['action_text'] : $item['why'];

										// Everything that is not the one line above goes behind Details:
										// the reasoning when the model wrote one, and the built in
										// advice with its links to the settings screens and guides.
										$more = array();
										if ( ! empty( $item['action_text'] ) && ! empty( $item['why'] ) ) {
											$more[] = esc_html( $item['why'] );
										}
										if ( ! empty( $item['detail'] ) ) {
											$more[] = wp_kses_post( $item['detail'] );
										}
										$has_more = ! empty( $more );
										?>
                                        <tr>
                                            <td style="border-left: 4px solid <?php echo esc_attr( $level['color'] ); ?>; padding: 1.1rem 1.4rem; vertical-align: middle;">
                                                <div style="font-weight: 600; margin-bottom: 2px;"><?php echo esc_html( $item['title'] ); ?></div>
                                                <div class="text-muted small" style="max-width: 62em;"><?php echo wp_kses_post( $oneline ); ?></div>
												<?php if ( $has_more ) { ?>
                                                    <div id="hmwp_why_<?php echo esc_attr( $index ); ?>" class="text-muted small mt-2" style="display: none; max-width: 62em; padding-left: 12px; border-left: 2px solid var(--hmwp-color-border-light);">
														<?php foreach ( $more as $part ) { ?>
                                                            <div class="mb-2"><?php echo $part; //phpcs:ignore ?></div>
														<?php } ?>
                                                    </div>
												<?php } ?>
                                            </td>
                                            <td style="width: 250px; padding: 1.1rem 1.4rem; text-align: right; vertical-align: middle; white-space: nowrap;">
												<?php if ( $has_more ) { ?>
                                                    <button type="button" class="btn btn-light rounded-0 px-3"
                                                            onclick="jQuery('#hmwp_why_<?php echo esc_js( $index ); ?>').slideToggle(120);"><?php echo esc_html__( 'Details', 'hide-my-wp' ); ?></button>
												<?php } ?>
												<?php if ( ! empty( $item['link'] ) ) { ?>
                                                    <a href="<?php echo esc_url( $item['link'] ); ?>" class="btn btn-default rounded-0 px-3"><?php echo esc_html__( 'Open', 'hide-my-wp' ); ?></a>
												<?php } ?>
												<?php if ( ! empty( $item['javascript'] ) ) { ?>
													<?php // Suggested items get a plain button. A primary colour on something
													      // we just called optional pulls the eye to the wrong row. ?>
													<?php
													// Free marks a premium-only repair by setting javascript to
													// the string "pro". Rendering that straight into onclick
													// produced onclick="pro", a button that looked live and did
													// nothing. The row still explains the problem and how to fix
													// it by hand; only the one-click repair is premium.
													if ( 'pro' === $item['javascript'] ) { ?>
                                                        <button type="button" class="btn btn-warning rounded-0 px-3" onclick="jQuery('#hmwp_ghost_mode_modal').modal('show')"><?php echo esc_html__( 'PRO', 'hide-my-wp' ); ?></button>
													<?php } else { ?>
                                                        <button type="button" class="btn <?php echo ( 'suggested' === $key ? 'btn-default' : 'btn-success' ); ?> rounded-0 px-3" onclick="<?php echo esc_attr( $item['javascript'] ); ?>"><?php echo esc_html__( 'Fix it', 'hide-my-wp' ); ?></button>
													<?php } ?>
												<?php } ?>
                                            </td>
                                        </tr>
									<?php } ?>
                                    </tbody>
                                </table>

							<?php } ?>

						<?php
						// The upgrade sits where the explanation would be, at the bottom of
						// the list someone has just read. That is the moment they are looking
						// at findings they may not fully understand, which is when the offer
						// is useful rather than an interruption. Everything above it works, so
						// nothing here is a locked feature: the list, the scores and the task
						// details are all free. This row only adds the wording.
						//
						// Nothing here tests the edition. The account server decides who gets
						// an answer, so a website whose account carries an allowance gets the
						// real controls and everything else falls through to the offer.
						?>

						<?php if ( $ai_ready || $ai_used_up ) { ?>
							<?php
							// One call explains every finding above. Behind a button on
							// purpose, so using one of the monthly checks is a choice
							// rather than something that happens on every page load.
							?>
                            <div class="col-sm-12 px-4 py-3 d-flex flex-row justify-content-between align-items-center border-top">
                                <div class="text-muted small" style="max-width: 62em;">
									<?php if ( $ai_used_up ) { ?>
										<?php
										echo $ai_resets
											? esc_html(
												sprintf(
												/* translators: %s: Date the monthly allowance resets. */
													__( 'No AI checks left this month, they reset on %s.', 'hide-my-wp' ),
													$ai_resets
												)
											)
											: esc_html__( 'No AI checks left this month.', 'hide-my-wp' );
										?>
									<?php } elseif ( $summary <> '' ) { ?>
										<?php echo esc_html__( 'Explained for your website. Run it again after you make changes.', 'hide-my-wp' ); ?>
									<?php } else { ?>
										<?php echo esc_html__( 'Get these findings explained for your website, ranked by what actually puts you at risk.', 'hide-my-wp' ); ?>
									<?php } ?>
									<?php if ( ! $ai_used_up && ! empty( $ai_quota['data']['allowed'] ) ) { ?>
                                        <span class="ml-1">
                                            <?php
                                            $ai_left = max( 0, (int) $ai_quota['data']['allowed'] - (int) $ai_quota['data']['used'] );
                                            echo esc_html(
	                                            sprintf(
		                                            /* translators: 1: Checks left. 2: Checks allowed each month. */
		                                            _n( '%1$s of %2$s AI check left this month', '%1$s of %2$s AI checks left this month', $ai_left, 'hide-my-wp' ),
		                                            $ai_left,
		                                            (int) $ai_quota['data']['allowed']
	                                            )
                                            );
                                            ?>
                                        </span>
									<?php } ?>
                                </div>
								<?php if ( ! $ai_used_up ) { ?>
                                    <form method="POST" id="hmwp_ai_explain" class="m-0">
										<?php wp_nonce_field( 'hmwp_ai_explain', 'hmwp_nonce' ); ?>
                                        <input type="hidden" name="action" value="hmwp_ai_explain"/>
                                        <button type="submit" class="btn btn-success rounded-0 px-4" style="white-space: nowrap;">
											<?php echo ( $has_ai ? esc_html__( 'Explain Again', 'hide-my-wp' ) : esc_html__( 'Explain These Findings', 'hide-my-wp' ) ); ?>
                                        </button>
                                    </form>
								<?php } ?>
                            </div>

						<?php } else { ?>

                            <div class="col-sm-12 px-4 py-3 d-flex flex-row justify-content-between align-items-center border-top">
                                <div class="text-muted small" style="max-width: 62em;">
									<?php if ( $ai_inactive ) { ?>
										<?php // Naming the missing thing beats a bare PRO badge. The
										      // account answered, so this is a fact about the licence
										      // rather than a guess about the edition. ?>
										<?php echo esc_html__( 'AI Security Explanations need an active subscription. Everything else on this page keeps working.', 'hide-my-wp' ); ?>
									<?php } else { ?>
										<?php echo esc_html__( 'Get these findings explained for your website, ranked by what actually puts you at risk.', 'hide-my-wp' ); ?>
									<?php } ?>
                                </div>
									<?php // btn-warning without the CTA class, matching the PRO buttons
									      // already on this screen. The class draws a corner ribbon, which
									      // collides with a button whose label is already PRO. ?>
								<?php if ( $ai_inactive && ! empty( $ai_quota['renew'] ) ) { ?>
                                    <a href="<?php echo esc_url( $ai_quota['renew'] ); ?>" target="_blank" rel="noopener" class="btn btn-warning rounded-0 px-4" style="white-space: nowrap;">
										<?php echo esc_html__( 'Renew', 'hide-my-wp' ); ?>
                                    </a>
								<?php } else { ?>
                                    <button type="button" class="btn btn-warning rounded-0 px-4" style="white-space: nowrap;" onclick="jQuery('#hmwp_ghost_mode_modal').modal('show')">
										<?php echo esc_html__( 'PRO', 'hide-my-wp' ); ?>
                                    </button>
								<?php } ?>
                            </div>

						<?php } ?>

						<?php } ?>

                    </div>
                </div>
            </div>

            <!-- The security tasks -->
			<?php if ( ! empty( $view->report ) ) { ?>
                <div id="hmwp_tasks_widget" class="card col-sm-12 p-0 m-0 mb-3 postbox <?php echo esc_attr( postbox_classes( 'hmwp_tasks_widget', $page ) . $default_closed ); ?>">
                    <div class="postbox-header hmwp_header">
                        <h3 class="card-title p-2 m-0 hndle">
							<?php echo esc_html__( 'Security Tasks', 'hide-my-wp' ); ?>
							<?php if ( $failed ) { ?>
                                <span class="text-danger">(<?php echo esc_html( $failed ); ?>)</span>
							<?php } ?>
                        </h3>
                        <div class="handle-actions hide-if-no-js mr-2">
                            <button type="button" class="handlediv" aria-expanded="true">
                                <span class="toggle-indicator" aria-hidden="true"></span>
                            </button>
                        </div>
                    </div>
                    <div class="inside">
                        <div class="card-body p-0">

							<?php
							// Hiding the empty table left the card blank apart from a button
							// floating in white space. This says what that emptiness means, the
							// same way the panel above does, and names the count so the two
							// cards are not repeating one another word for word.
							if ( ! $failed && $overview['total'] ) { ?>
                                <div class="p-4 text-center">
                                    <div class="text-success" style="font-size: 1.1rem;">
                                        <i class="dashicons dashicons-yes mr-2" style="font-size: 1.6rem !important;"></i>
										<?php
										echo esc_html(
											sprintf(
											/* translators: %s: Number of security tasks that passed. */
												_n( 'All %s security task passed.', 'All %s security tasks passed.', (int) $overview['total'], 'hide-my-wp' ),
												number_format_i18n( $overview['total'] )
											)
										);
										?>
                                    </div>
                                </div>
							<?php } ?>

							<?php
							// With nothing failing, every row is hidden and all that was left
							// was a bordered box with column headings over no rows. The table
							// carries task_passed in that case, so the existing Show completed
							// tasks button reveals it along with the rows and Hide puts it away
							// again. No new toggle, and the button stays outside the table.
							?>
                            <table class="table table_securitycheck border m-0 <?php echo ( $failed ? '' : 'task_passed' ); ?>" style="width: 100%;<?php echo ( $failed ? '' : 'display:none;' ); ?>">
                                <thead>
                                <tr>
                                    <th scope="col"><?php echo esc_html__( 'Name', 'hide-my-wp' ); ?></th>
                                    <th scope="col"><?php echo esc_html__( 'Value', 'hide-my-wp' ); ?></th>
                                    <th scope="col"><?php echo esc_html__( 'Valid', 'hide-my-wp' ); ?></th>
                                    <th scope="col" colspan="2"><?php echo esc_html__( 'Action', 'hide-my-wp' ); ?></th>
                                </tr>
                                </thead>
                                <tbody>
								<?php foreach ( $view->report as $index => $row ) {
									if ( ! isset( $row['name'] ) ) {
										continue;
									}
									?>
                                    <tr class="<?php echo ( $row['valid'] ? 'task_passed' : 'task_failed' ); ?>" style="<?php echo ( $row['valid'] ? 'display:none' : '' ); ?>">
                                        <td style="width: 30%; word-break: break-word;"><?php echo wp_kses_post( $row['name'] ); ?></td>
                                        <td style="width: 20%; font-weight: bold; word-break: break-word;"><?php echo wp_kses_post( $row['value'] ); ?></td>
                                        <td style="width: 30%; word-break: break-word;" class="<?php echo ( $row['valid'] ? 'text-success' : 'text-danger' ); ?>"><?php echo ( $row['valid'] ? '<i class="dashicons dashicons-yes mr-2" style="font-size: 1.6rem !important;"></i>' : '<i class="dashicons dashicons-no mr-2"  style="font-size: 1.6rem !important;"></i>' . ( isset( $row['solution'] ) ? wp_kses_post( $row['solution'] ) : '' ) ); ?></td>
                                        <td style="width: 18%; min-width: 100px; padding-right: 0!important; position: relative">
                                            <div class="modal" id="hmwp_securitydetail<?php echo esc_attr( $index ); ?>" tabindex="-1" role="dialog" aria-hidden="true">
                                                <div class="modal-dialog modal-lg" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title"><?php echo wp_kses_post( $row['name'] ); ?></h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body"><?php echo wp_kses_post( $row['message'] ); ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <button class="btn btn-default rounded-0 px-3 float-right m-1" type="button" onclick="jQuery('#hmwp_securitydetail<?php echo esc_attr( $index ); ?>').modal('show');"><?php echo esc_html__( 'Info', 'hide-my-wp' ); ?></button>
											<?php
											if ( ! $row['valid'] && isset( $row['javascript'] ) && $row['javascript'] <> '' ) {
												?>
												<?php if ( 'pro' === $row['javascript'] ) { ?>
                                                <button type="button" class="btn btn-warning mx-0 my-1 rounded-0 float-right m-1" onclick="jQuery('#hmwp_ghost_mode_modal').modal('show')"><?php echo esc_html__( 'PRO', 'hide-my-wp' ); ?></button>
												<?php } else { ?>
                                                <button type="button" id="fix<?php echo esc_attr( $index ); ?>" class="btn btn-success mx-0 my-1 rounded-0 float-right  m-1" onclick="<?php echo esc_attr( $row['javascript'] ); ?>"><?php echo esc_html__( 'Fix it', 'hide-my-wp' ); ?></button>
												<?php } ?><?php
											} elseif ( $row['valid'] && isset( $row['javascript_undo'] ) && $row['javascript_undo'] <> '' ) {
												?>
                                                <button type="button" class="btn btn-link mx-0 my-1 rounded-0 float-right  m-1" onclick="<?php echo esc_attr( $row['javascript_undo'] ); ?>"><?php echo esc_html__( 'Undo', 'hide-my-wp' ); ?></button> <?php
											} elseif ( $row['valid'] && isset( $row['javascript_custom'] ) && isset( $row['javascript_button'] ) && $row['javascript_custom'] <> '' ) {
												?>
                                                <button type="button" class="btn btn-link mx-0 my-1 rounded-0 float-right  m-1" onclick="<?php echo esc_attr( $row['javascript_custom'] ); ?>"><?php echo wp_kses_post( $row['javascript_button'] ); ?></button> <?php
											}
											?>
                                        </td>
                                        <td class="px-3" style="width: 50px; position: relative">
                                            <form class="hmwp_securityexclude_form" method="POST" style="position: absolute; top: 13px; right: 0;">
												<?php wp_nonce_field( 'hmwp_securityexclude', 'hmwp_nonce' ); ?>
                                                <input type="hidden" name="action" value="hmwp_securityexclude"/>
                                                <input type="hidden" name="name" value="<?php echo esc_attr( $index ); ?>"/>
                                                <button type="submit" class="close my-2 mr-1" aria-label="Close" style="display: none" onclick="if (!confirm('<?php echo esc_html__( 'Are you sure you want to ignore this task in the future?', 'hide-my-wp' ); ?>')) {return false;}">
                                                    <span aria-hidden="true" title="<?php echo esc_attr__( 'Ignore security task', 'hide-my-wp' ); ?>">&times;</span>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
								<?php } ?>
                                </tbody>
                            </table>

                            <div class="col-sm-12 text-right py-2">
                                <form id="hmwp_resetexclude" method="POST">
									<?php wp_nonce_field( 'hmwp_resetexclude', 'hmwp_nonce' ); ?>
                                    <input type="hidden" name="action" value="hmwp_resetexclude"/>

                                    <button type="button" class="btn btn-light show_task_passed"><?php echo esc_html__( 'Show completed tasks', 'hide-my-wp' ); ?></button>
                                    <button type="button" class="btn btn-light hide_task_passed" style="display: none"><?php echo esc_html__( 'Hide completed tasks', 'hide-my-wp' ); ?></button>
									<?php if ( get_option( HMWP_SECURITY_CHECK_IGNORE ) ) { ?>
                                        <button type="submit" class="btn btn-light"><?php echo esc_html__( 'Show ignored tasks', 'hide-my-wp' ); ?></button>
									<?php } ?>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
			<?php } ?>

        </div>
    </div>
</div>

<div id="hmwp_security_mode_require_modal" class="modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger"><?php echo esc_html__( 'Ghost Mode', 'hide-my-wp' ); ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">

                <?php echo wp_kses_post( sprintf( /* translators: 1: Opening anchor tag. 2: Closing anchor tag. 3: Opening anchor tag. 4: Closing anchor tag. */ __( 'First, you need to activate the %1$sSafe Mode%2$s or %3$sGhost Mode%4$s', 'hide-my-wp' ), '<a href="' . esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_permalinks' ) ) . '">', '</a>', '<a href="' . esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_permalinks' ) ) . '">', '</a>' ) ) ?>

            </div>
            <div class="modal-footer">
                <div class="row w-100">
                    <div class="col text-right">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo esc_html__( 'Cancel', 'hide-my-wp' ); ?></button>
                        <a href="<?php echo esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_permalinks' ) ); ?>" type="button" class="btn btn-success"><?php echo esc_html__( 'Continue', 'hide-my-wp' ); ?> >></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="hmwp_fixadmin_modal" class="modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger"><?php echo esc_html__( 'Admin Username', 'hide-my-wp' ); ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p><?php echo esc_html__( "Change the user 'admin' or 'administrator' with another name to improve security.", 'hide-my-wp' ); ?></p>
                <p class="text-danger"><?php echo esc_html__( 'If you are connected with the admin user, you will have to re-login after the change.', 'hide-my-wp' ); ?></p>
                <form id="hmwp_fixadmin_form" method="POST">
                    <div class="input-group">
                        <?php wp_nonce_field( 'hmwp_fixadmin', 'hmwp_nonce' ); ?>
                        <input type="hidden" name="action" value="hmwp_fixadmin"/>
                        <label for="hmwp_username" class="lable m-2"><?php echo esc_html__( 'New Username', 'hide-my-wp' ); ?></label>
                        <input id="hmwp_username" class="form-control nopopup" type="text" name="hmwp_username" value=""/>
                    </div>
                    <button type="button" onclick="jQuery(this).hmwp_fixAdmin(true);" class="btn btn-success my-3 rounded-0 btn-sm form-control" name="hmwp_username" value=""><?php echo esc_html__( 'Change', 'hide-my-wp' ); ?></button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Nonce carriers for the Fix it buttons that have no dialog of their own.
//
// settings.js reads the action and the nonce out of a form with a fixed id
// before posting, and passes the values themselves as arguments. Those forms
// were never rendered anywhere, so the post went out with no action and no
// nonce, WordPress answered with 0, and jQuery reported it as "Ajax is not
// loading correctly. Clear all cache and try again." Every Fix it for a plugin
// setting, the security keys, the table prefix, the wp-config constants and the
// plugin updates failed that way. They are hidden because the button is the
// whole interface.
?>
<form id="hmwp_fixsettings_form" method="POST" class="d-none">
	<?php wp_nonce_field( 'hmwp_fixsettings', 'hmwp_nonce' ); ?>
    <input type="hidden" name="action" value="hmwp_fixsettings"/>
</form>

<form id="hmwp_fixsalts_form" method="POST" class="d-none">
	<?php wp_nonce_field( 'hmwp_fixsalts', 'hmwp_nonce' ); ?>
    <input type="hidden" name="action" value="hmwp_fixsalts"/>
</form>

<form id="hmwp_fixprefix_form" method="POST" class="d-none">
	<?php wp_nonce_field( 'hmwp_fixprefix', 'hmwp_nonce' ); ?>
    <input type="hidden" name="action" value="hmwp_fixprefix"/>
</form>

<form id="hmwp_fixconfig_form" method="POST" class="d-none">
	<?php wp_nonce_field( 'hmwp_fixconfig', 'hmwp_nonce' ); ?>
    <input type="hidden" name="action" value="hmwp_fixconfig"/>
</form>

<?php // hmwp_fixUpgrade also reads hmwp_username out of its form. The handler
      // upgrades every plugin and never looks at a name, so the field is only
      // here to keep the shared post shape intact. ?>
<form id="hmwp_fixupgrade_form" method="POST" class="d-none">
	<?php wp_nonce_field( 'hmwp_fixupgrade', 'hmwp_nonce' ); ?>
    <input type="hidden" name="action" value="hmwp_fixupgrade"/>
    <input type="hidden" name="hmwp_username" value=""/>
</form>

<div id="hmwp_fixpermissions_modal" class="modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger"><?php echo esc_html__( 'Fix Permissions', 'hide-my-wp' ); ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p><?php echo sprintf( /* translators: 1: Plugin name. 2: Opening link tag. 3: Closing link tag. */ esc_html__( 'Even if the default paths are protected by %1$s after customization, we recommend setting the correct permissions for all directories and files on your website, use File Manager or FTP to check and change the permissions. %2$sRead more%3$s', 'hide-my-wp' ), esc_html( HMWP_Classes_Tools::getOption( 'hmwp_plugin_name' ) ), '<a href="' . esc_url( HMWP_Classes_Tools::getOption( 'hmwp_plugin_website' ) . '/kb/how-to-change-file-permissions-in-wordpress/' ) . '" target="_blank">', '</a>' ); ?></p>

                <div class="mx-0 my-2">
                    <?php echo esc_html__( 'WordPress Default Permissions', 'hide-my-wp' ); ?>:
                    <ol class="my-2" style="list-style: disc">
                        <li class="small text-black-50 m-0"><?php echo esc_html__( 'Directories', 'hide-my-wp' ); ?>: <?php echo esc_attr( sprintf( '%o', HMW_DIR_PERMISSION ) ); ?></li>
                        <li class="small text-black-50 m-0"><?php echo esc_html__( 'Files', 'hide-my-wp' ); ?>: <?php echo esc_attr( sprintf( '%o', HMW_FILE_PERMISSION ) ); ?></li>
                        <li class="small text-black-50 m-0"><?php echo esc_html__( 'Config', 'hide-my-wp' ); ?>: <?php echo esc_attr( sprintf( '%o', HMW_CONFIG_PERMISSION ) ); ?></li>
                    </ol>
                </div>

                <div class="m-0 py-3 border-top">
                    <form id="hmwp_fixpermissions_form" method="POST">
                        <?php wp_nonce_field( 'hmwp_fixpermissions', 'hmwp_nonce' ); ?>
                        <input type="hidden" name="action" value="hmwp_fixpermissions"/>

                        <div class="form-group">
                            <div class="form-check">
                                <input class="form-input" type="radio" name="value" id="quick" value="quick" checked>
                                <label class="form-label" for="quick">
                                    <?php echo esc_html__( 'Quick Fix', 'hide-my-wp' ); ?>
                                    <div class="small text-black-50"><?php echo esc_html__( 'Fix permission for the main directories and files (~ 5 sec).', 'hide-my-wp' ); ?></div>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-input" type="radio" name="value" id="complete" value="complete">
                                <label class="form-label" for="complete">
                                    <?php echo esc_html__( 'Complete Fix', 'hide-my-wp' ); ?>
                                    <div class="small text-black-50"><?php echo esc_html__( 'Fix permission for all directories and files (~ 1 min).', 'hide-my-wp' ); ?></div>

                                </label>
                            </div>
                            <button type="button" onclick="jQuery(this).hmwp_fixPermissions(true);" class="btn btn-success my-3 rounded-0 btn-sm form-control" name="hmwp_username" value=""><?php echo esc_html__( 'Fix it', 'hide-my-wp' ); ?></button>


                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>