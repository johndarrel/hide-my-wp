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



$gdreport = array();

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
						?>
                        <div class="col-sm-12 px-4 py-3 d-flex flex-row justify-content-between align-items-center border-top">
                            <div class="text-muted small" style="max-width: 62em;">
								<?php echo esc_html__( 'Get these findings explained for your website, ranked by what actually puts you at risk.', 'hide-my-wp' ); ?>
                            </div>
								<?php // btn-warning without the CTA class, matching the PRO buttons
								      // already on this screen. The class draws a corner ribbon, which
								      // collides with a button whose label is already PRO. ?>
                            <button type="button" class="btn btn-warning rounded-0 px-4" style="white-space: nowrap;" onclick="jQuery('#hmwp_ghost_mode_modal').modal('show')">
								<?php echo esc_html__( 'PRO', 'hide-my-wp' ); ?>
                            </button>
                        </div>

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
// before posting, and passes the values themselves as arguments. Four of those
// forms were never rendered anywhere, so the post went out with no action and no
// nonce, WordPress answered with 0, and jQuery reported it as "Ajax is not
// loading correctly. Clear all cache and try again." Every Fix it for security
// keys, the table prefix, the wp-config constants and the plugin updates failed
// that way. They are hidden because the button is the whole interface.
?>
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