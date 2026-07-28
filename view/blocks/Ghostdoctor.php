<?php defined( 'ABSPATH' ) || die( 'Cheating uh?' ); ?>
<?php if ( ! isset( $view ) ) {
	return;
} ?>
<?php
$report   = isset( $view->report ) ? $view->report : array();
$pending  = ! empty( $view->pending );
$steps    = isset( $report['steps'] ) ? $report['steps'] : array();
$errors   = isset( $report['errors'] ) ? $report['errors'] : array();
$blockers = isset( $report['blockers'] ) ? $report['blockers'] : array();
$plan     = isset( $report['plan'] ) ? $report['plan'] : array();
$has_run  = ! empty( $report['time'] );

?>


		<?php
		// Same rhythm as the confirmation block above it: status on the left,
		// action on the right, one row. Centring this half while the half above
		// it was left aligned left a column of empty space down the middle and
		// made one card read as two unrelated ones.
		?>
        <div class="col-sm-12 px-4 py-4 d-flex flex-row justify-content-between align-items-center">

            <div class="pr-4">

				<?php if ( ! $has_run ) { ?>

                    <div class="text-muted" style="max-width: 62em;">
						<?php echo esc_html__( 'Ghost Doctor checks whether your paths, theme files and editor requests are being served correctly, then repairs what it can from the plugin.', 'hide-my-wp' ); ?>
                    </div>

				<?php } elseif ( ! empty( $report['success'] ) ) { ?>

                    <div class="text-success" style="font-size: 1.1rem;">
                        <i class="dashicons dashicons-yes mr-1" style="font-size: 1.4rem !important; vertical-align: text-bottom;"></i>
						<?php echo esc_html__( 'Every check passed. Your website paths are working.', 'hide-my-wp' ); ?>
                    </div>

				<?php } else { ?>

                    <div class="text-danger" style="font-size: 1.1rem;">
                        <i class="dashicons dashicons-no mr-1" style="font-size: 1.4rem !important; vertical-align: text-bottom;"></i>
						<?php
						echo esc_html(
							sprintf(
							/* translators: 1: Number of checks that are still failing. */
								_n( '%1$s check is still failing.', '%1$s checks are still failing.', (int) $report['remaining'], 'hide-my-wp' ),
								(int) $report['remaining']
							)
						);
						?>
                    </div>

				<?php } ?>

				<?php
				// One quiet line for everything that is context rather than status:
				// when it last ran, and what is left of the monthly allowance.
				$meta = array();

				if ( $has_run && isset( $report['time'] ) ) {
					$ran = (int) $report['time'];

					$meta[] = '<strong>' . esc_html__( 'Last check:', 'hide-my-wp' ) . '</strong> '
					          . '<span title="' . esc_attr( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $ran ) ) . '">'
					          . ( $ran > time() - 60
							? esc_html__( 'just now', 'hide-my-wp' )
							/* translators: %s: Length of time, for example "3 minutes". */
							: esc_html( sprintf( __( '%s ago', 'hide-my-wp' ), human_time_diff( $ran, time() ) ) ) )
					          . '</span>';
				}

				// Free has no allowance to report. The nudge sits in the same quiet
				// line as the timestamp rather than as a banner, so the diagnosis stays
				// the subject of the card and the offer is available without interrupting.
				$meta[] = '<a href="javascript:void(0)" onclick="jQuery(\'#hmwp_ghost_mode_modal\').modal(\'show\')">'
				          . esc_html__( 'Get these results explained for your website', 'hide-my-wp' )
				          . '</a>';

				if ( ! empty( $meta ) ) { ?>
                    <div class="text-muted small mt-2"><?php echo join( ' &middot; ', $meta ); //phpcs:ignore ?></div>
				<?php } ?>

            </div>

            <form method="POST" class="m-0">
				<?php wp_nonce_field( 'hmwp_ghostdoctor_diagnose', 'hmwp_nonce' ); ?>
                <input type="hidden" name="action" value="hmwp_ghostdoctor_diagnose"/>
				<?php // Default styling, not primary. While the confirmation above is
				      // open, keeping or undoing the path change is the decision that
				      // matters, and three filled buttons in one card compete for it. ?>
                <button type="submit" class="btn rounded-0 btn-default btn-lg px-5" style="white-space: nowrap;"><?php echo esc_html__( 'Start Diagnosis', 'hide-my-wp' ); ?></button>
            </form>

        </div>

		<?php if ( ! empty( $steps ) ) { ?>
            <div class="col-sm-12 p-0 input-group">
                <table class="table table_securitycheck border" style="width: 100%">
                    <thead>
                    <tr>
                        <th scope="col"><?php echo esc_html__( 'Repair', 'hide-my-wp' ); ?></th>
                        <th scope="col"><?php echo esc_html__( 'Value', 'hide-my-wp' ); ?></th>
                        <th scope="col" colspan="2"><?php echo esc_html__( 'Result', 'hide-my-wp' ); ?></th>
                    </tr>
                    </thead>
                    <tbody>
					<?php foreach ( $steps as $step ) {
						$kept = in_array( $step['outcome'], array( 'solved', 'improved' ) );
						?>
                        <tr class="<?php echo ( $kept ? 'task_failed' : 'task_passed' ); ?>">
                            <td style="width: 30%; word-break: break-word;">
								<?php echo esc_html( $step['label'] ); ?>
                                <div class="text-muted small"><?php echo esc_html( $step['why'] ); ?></div>
                            </td>
                            <td style="width: 20%; font-weight: bold;">
								<?php
								echo esc_html( sprintf(
								/* translators: 1: Checks failing before. 2: Checks failing after. */
									__( '%1$s to %2$s failing', 'hide-my-wp' ),
									$step['before'],
									$step['after']
								) );
								?>
                            </td>
                            <td style="width: 50%;" class="<?php echo ( $kept ? 'text-success' : 'text-muted' ); ?>" colspan="2">
								<?php if ( 'solved' === $step['outcome'] ) { ?>
                                    <i class="dashicons dashicons-yes mr-2" style="font-size: 1.6rem !important;"></i>
									<?php echo esc_html__( 'This repaired the website. Kept.', 'hide-my-wp' ); ?>
								<?php } elseif ( 'improved' === $step['outcome'] ) { ?>
                                    <i class="dashicons dashicons-yes mr-2" style="font-size: 1.6rem !important;"></i>
									<?php echo esc_html__( 'This helped. Kept.', 'hide-my-wp' ); ?>
								<?php } else { ?>
									<?php echo esc_html__( 'This made no difference, so it was put back.', 'hide-my-wp' ); ?>
								<?php } ?>
                            </td>
                        </tr>
					<?php } ?>
                    </tbody>
                </table>
            </div>
		<?php } ?>

		<?php if ( ! empty( $blockers ) ) { ?>
            <div class="col-sm-12 px-4 pb-3">
                <h4 class="card-title"><?php echo esc_html__( 'You need to do this yourself', 'hide-my-wp' ); ?></h4>
                <div class="border-top mt-2 pt-2"></div>
				<?php foreach ( $blockers as $blocker ) { ?>
                    <div class="my-2">
                        <div style="font-weight: 600;"><?php echo esc_html( $blocker['title'] ); ?></div>
                        <div class="text-muted"><?php echo esc_html( $blocker['body'] ); ?></div>
                        <a href="<?php echo esc_url( $blocker['link'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html__( 'Read the guide', 'hide-my-wp' ); ?></a>
                    </div>
				<?php } ?>
            </div>
		<?php } ?>

		<?php if ( ! empty( $errors ) ) { ?>
            <div class="col-sm-12 px-4 pb-3">
                <h4 class="card-title"><?php echo esc_html__( 'What is still failing', 'hide-my-wp' ); ?></h4>
                <div class="border-top mt-2 pt-2"></div>
				<?php foreach ( $errors as $error ) { ?>
                    <div class="my-1 text-muted small" style="word-break: break-all;">
                        <strong><?php echo esc_html( $error['badge'] ); ?></strong>
						<?php echo esc_html( $error['label'] ); ?>
                        <div style="font-family: Menlo, Consolas, monospace;"><?php echo esc_html( remove_query_arg( 'hmwp_preview', $error['url'] ) ); ?></div>
                    </div>
				<?php } ?>
            </div>
		<?php } ?>

		<?php if ( ! empty( $plan ) ) { ?>
            <div class="col-sm-12 px-4 pb-3">
                <h4 class="card-title"><?php echo esc_html__( 'Repairs Ghost Doctor can make', 'hide-my-wp' ); ?></h4>
                <div class="border-top mt-2 pt-2"></div>

                <form method="POST">
					<?php wp_nonce_field( 'hmwp_ghostdoctor_repair', 'hmwp_nonce' ); ?>
                    <input type="hidden" name="action" value="hmwp_ghostdoctor_repair"/>

					<?php foreach ( $plan as $fix ) { ?>
                        <div class="col-sm-12 p-0 mt-2 mb-4 switch switch-xxs" style="font-size: 0.9rem;">
                            <input type="checkbox" id="hmwp_gd_<?php echo esc_attr( $fix['id'] ); ?>" name="fixes[]" value="<?php echo esc_attr( $fix['id'] ); ?>" class="switch" checked="checked"/>
                            <label for="hmwp_gd_<?php echo esc_attr( $fix['id'] ); ?>">
								<?php echo esc_html( $fix['label'] ); ?>
								<?php if ( 'high' === $fix['risk'] ) { ?>
                                    <span class="text-danger"><?php echo esc_html__( 'reduces protection', 'hide-my-wp' ); ?></span>
								<?php } ?>
                                <div class="text-muted small"><?php echo esc_html( $fix['why'] ); ?></div>
                            </label>
                        </div>
					<?php } ?>

                    <div class="text-right mt-3">
                        <button type="submit" name="mode" value="selected" class="btn btn-light"><?php echo esc_html__( 'Fix Selected', 'hide-my-wp' ); ?></button>
                        <button type="submit" name="mode" value="all" class="btn rounded-0 btn-default px-4"><?php echo esc_html__( 'Fix Everything', 'hide-my-wp' ); ?></button>
                    </div>
                </form>
            </div>
		<?php } ?>

		<?php if ( $pending ) { ?>
            <div class="col-sm-12 px-4 py-3 d-flex flex-row justify-content-between align-items-center border-top">
                <div class="text-muted small">
					<?php echo esc_html__( 'Check your website now. If anything looks wrong, undo the changes.', 'hide-my-wp' ); ?>
                </div>
                <div>
                    <form method="POST" style="display: inline-block;">
						<?php wp_nonce_field( 'hmwp_ghostdoctor_undo', 'hmwp_nonce' ); ?>
                        <input type="hidden" name="action" value="hmwp_ghostdoctor_undo"/>
                        <button type="submit" class="btn btn-light"><?php echo esc_html__( 'Undo All Changes', 'hide-my-wp' ); ?></button>
                    </form>
                    <form method="POST" style="display: inline-block;">
						<?php wp_nonce_field( 'hmwp_ghostdoctor_keep', 'hmwp_nonce' ); ?>
                        <input type="hidden" name="action" value="hmwp_ghostdoctor_keep"/>
                        <button type="submit" class="btn btn-success rounded-0 px-4"><?php echo esc_html__( 'Keep Changes', 'hide-my-wp' ); ?></button>
                    </form>
                </div>
            </div>
		<?php } ?>
