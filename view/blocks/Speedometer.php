<?php defined( 'ABSPATH' ) || die( 'Cheating uh?' ); ?>
<?php
if ( ! isset( $view ) && ! isset( $do_check ) ) {
	return;
}

$hmwp_security_score = 0;

if ( ! $do_check ) {
	// Calculate security score (0-100, higher is better)
	$hmwp_risk_percent   = ( count( $view->risktasks ) > 0 ) ? round( ( count( $view->riskreport ) * 100 ) / count( $view->risktasks ) ) : 0;
    $hmwp_security_score = 100 - $hmwp_risk_percent;

	// Determine score level for styling
	if ( $hmwp_risk_percent > 90 ) {
		$hmwp_score_level   = 'danger';
        /* translators: 1: opening <strong> tag, 2: closing </strong> tag */
        $hmwp_score_message = sprintf( wp_kses_post( __( 'Your website security %1$sis extremely weak%2$s.', 'hide-my-wp' ) ), '<strong>', '</strong>' );
	} elseif ( $hmwp_risk_percent > 50 ) {
		$hmwp_score_level   = 'low';
        /* translators: 1: opening <strong> tag, 2: closing </strong> tag */
        $hmwp_score_message = sprintf( wp_kses_post( __( 'Your website security %1$sis very weak%2$s.', 'hide-my-wp' ) ), '<strong>', '</strong>' );
	} elseif ( $hmwp_risk_percent > 20 ) {
		$hmwp_score_level   = 'medium';
        /* translators: 1: opening <strong> tag, 2: closing </strong> tag */
        $hmwp_score_message = sprintf( wp_kses_post( __( 'Your website security %1$sis still weak%2$s.', 'hide-my-wp' ) ), '<strong>', '</strong>');
	} elseif ( $hmwp_risk_percent > 5 ) {
		$hmwp_score_level   = 'better';
        /* translators: 1: opening <strong> tag, 2: closing </strong> tag */
        $hmwp_score_message = sprintf( wp_kses_post( __( 'Your website security %1$sis getting better%2$s.', 'hide-my-wp' ) ), '<strong>', '</strong>');
	} else {
		$hmwp_score_level   = 'strong';
        $hmwp_score_message = wp_kses_post( __( "You've completed all free security tasks.", 'hide-my-wp' ) ) ;
    }
    /* translators: 1: Opening <strong><a> tag linking to premium page, 2: Closing </a></strong> tag. */
    $hmwp_score_message .= '<br />' . sprintf( wp_kses_post( __( '%1$sUnlock advanced protection with Premium >%2$s', 'hide-my-wp' ) ), '<strong><a href="https://wpghost.com/pricing/?coupon=HIDEMYWP70&utm_source=social&utm_medium=banner&utm_campaign=free&utm_id=offer#price" target="_blank" >', '</a></strong>' ) ;

}

// Needle angle: score 0 → 180° (far left), score 100 → 0° (far right)
$hmwp_needle_deg = 180 - ( $hmwp_security_score * 1.8 );
$hmwp_needle_rad = deg2rad( $hmwp_needle_deg );
$hmwp_gcx  = 200;
$hmwp_gcy  = 175;
$hmwp_nlen = 148;
$hmwp_ntx  = round( $hmwp_gcx + $hmwp_nlen * cos( $hmwp_needle_rad ), 1 );
$hmwp_nty  = round( $hmwp_gcy - $hmwp_nlen * sin( $hmwp_needle_rad ), 1 );
$hmwp_nmx  = round( $hmwp_gcx + ( $hmwp_nlen * 0.35 ) * cos( $hmwp_needle_rad ), 1 );
$hmwp_nmy  = round( $hmwp_gcy - ( $hmwp_nlen * 0.35 ) * sin( $hmwp_needle_rad ), 1 );
?>
<style>
    /* Score badge */
    .hmwp_score_badge {
        display: inline-flex;
        align-items: baseline;
        justify-content: center;
        gap: 2px;
        margin: 5px auto 0;
        padding: 6px 18px;
        border-radius: 8px;
        line-height: 1;
    }

    .hmwp_score_number {
        font-size: 2rem;
        font-weight: 700;
        letter-spacing: -1px;
    }

    .hmwp_score_total {
        font-size: 1rem;
        font-weight: 400;
        opacity: 0.7;
    }

    .hmwp_score_badge.hmwp_score_danger {
        color: #ef4444;
    }

    .hmwp_score_badge.hmwp_score_low {
        color: #f97316;
    }

    .hmwp_score_badge.hmwp_score_medium {
        color: #e0a020;
    }

    .hmwp_score_badge.hmwp_score_better {
        color: #e0a020;
    }

    .hmwp_score_badge.hmwp_score_strong {
        color: #22a558;
    }

    .hmwp_score_badge.hmwp_score_none {
        color: #a0aec0;
    }

    .hmwp_score_message {
        font-size: 1rem;
        font-style: italic;
        text-align: center;
        margin-top: 4px;
    }

    .hmwp_score_message.hmwp_score_danger,
    .hmwp_score_message.hmwp_score_low {
        color: #ef4444;
    }

    .hmwp_score_message.hmwp_score_medium,
    .hmwp_score_message.hmwp_score_better {
        color: #e08a20;
    }

    .hmwp_score_message.hmwp_score_strong {
        color: #22a558;
    }

    @container hmwpwrap (max-width: 599px) {

        td.hmwp_widget_gauge {
            width: 100% !important;
        }
    }
</style>
<?php // translators: %d: security score value (0–100) ?>
    <a href="<?php echo esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_securitycheck' ) ); ?>" class="hmwp_widget_gauge_link">
        <svg viewBox="0 0 400 195" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="<?php /* translators: %d: Security score number. */ echo esc_attr( sprintf( __( 'Security score: %d out of 100', 'hide-my-wp' ), $hmwp_security_score ) ); ?>" <?php echo $do_check ? 'style="filter: saturate(0.72); opacity: 0.5;"' : ''; ?>>
        <defs>
            <linearGradient id="hmwpNdlG" gradientUnits="userSpaceOnUse" x1="<?php echo esc_attr( $hmwp_gcx ); ?>" y1="<?php echo esc_attr( $hmwp_gcy ); ?>" x2="<?php echo esc_attr( $hmwp_ntx ); ?>"  y2="<?php echo esc_attr( $hmwp_nty ); ?>">
                <stop offset="0%" stop-color="#d9f1ff" stop-opacity="0.15"/>
                <stop offset="25%" stop-color="#9fd5f5" stop-opacity="0.5"/>
                <stop offset="50%" stop-color="#46b9fd" stop-opacity="1"/>
            </linearGradient>
            <filter id="hmwpNdlGlow">
                <feGaussianBlur in="SourceGraphic" stdDeviation="4" result="b"/>
                <feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge>
            </filter>
            <filter id="hmwpPivotGlow">
                <feGaussianBlur in="SourceGraphic" stdDeviation="1" result="b"/>
                <feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge>
            </filter>
        </defs>

        <!-- Colored arc segments -->
        <path d="M 20,190 A 180,180 0 0,1 54.38,84.24 L 106.96,122.38 A 115,115 0 0,0 85,190 Z" fill="#ef4444"/>
        <path d="M 54.38,84.24 A 180,180 0 0,1 144.38,18.82 L 164.47,80.63 A 115,115 0 0,0 106.96,122.38 Z" fill="#f59e0b"/>
        <path d="M 144.38,18.82 A 180,180 0 0,1 255.62,18.82 L 235.53,80.63 A 115,115 0 0,0 164.47,80.63 Z" fill="#facc15"/>
        <path d="M 255.62,18.82 A 180,180 0 0,1 345.62,84.24 L 293.04,122.38 A 115,115 0 0,0 235.53,80.63 Z" fill="#22c55e"/>
        <path d="M 345.62,84.24 A 180,180 0 0,1 380,190 L 315,190 A 115,115 0 0,0 293.04,122.38 Z" fill="#34d876"/>

        <?php if ( ! $do_check ) : ?>
            <!-- Needle glow -->
            <line x1="<?php echo esc_attr( $hmwp_nmx ); ?>" y1="<?php echo esc_attr( $hmwp_nmy ); ?>" x2="<?php echo esc_attr( $hmwp_ntx ); ?>" y2="<?php echo esc_attr( $hmwp_nty ); ?>" stroke="#46b9fd" stroke-width="6" stroke-linecap="round" opacity="0.35" filter="url(#hmwpNdlGlow)"/>

            <!-- Needle -->
            <line x1="<?php echo esc_attr( $hmwp_gcx ); ?>" y1="<?php echo esc_attr( $hmwp_gcy ); ?>" x2="<?php echo esc_attr( $hmwp_ntx ); ?>" y2="<?php echo esc_attr( $hmwp_nty ); ?>" stroke="url(#hmwpNdlG)" stroke-width="5" stroke-linecap="round"/>
        <?php endif; ?>

        <!-- Center pivot -->
        <circle cx="<?php echo esc_attr( $hmwp_gcx ); ?>" cy="<?php echo esc_attr( $hmwp_gcy ); ?>" r="16" fill="none" stroke="#00F0FF" stroke-width="3" filter="url(#hmwpPivotGlow)" />
        <circle cx="<?php echo esc_attr( $hmwp_gcx ); ?>" cy="<?php echo esc_attr( $hmwp_gcy ); ?>" r="18" fill="#111111"/>
        <circle cx="<?php echo esc_attr( $hmwp_gcx ); ?>" cy="<?php echo esc_attr( $hmwp_gcy ); ?>" r="14" fill="none" stroke="#00A3FF" stroke-width="2"/>
    </svg>
 </a>

<?php if ( ! $do_check ) : ?>
	<div class="hmwp_score_badge hmwp_score_<?php echo esc_attr( $hmwp_score_level ); ?>">
		<span class="hmwp_score_number"><?php echo esc_html( $hmwp_security_score ); ?></span>
		<span class="hmwp_score_total">/100</span>
	</div>

	<div class="hmwp_score_message hmwp_score_<?php echo esc_attr( $hmwp_score_level ); ?>">
		<?php echo wp_kses_post( $hmwp_score_message ); ?>
	</div>
<?php endif; ?>