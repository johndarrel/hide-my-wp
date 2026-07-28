<?php defined( 'ABSPATH' ) || die( 'Cheating uh?' ); ?>
<?php if ( ! isset( $view ) ) {
    return;
} ?>
<?php
$block_ips = $alerts = $threats = $blocked = '-';

if ( HMWP_Classes_Tools::getOption( 'hmwp_threats_log' ) ) {
    /** @var HMWP_Models_ThreatsLog $threatsLog */
    $threatsLog = HMWP_Classes_ObjController::getClass( 'HMWP_Models_ThreatsLog' );
    $data       = $threatsLog->getThreatStatsByDay( 7 );

    // Sum totals across the full 7-day period
    if ( ! empty( $data ) && isset( $data['threats'] ) && isset( $data['blocked'] ) ) {
        $threats_total = (int) array_sum( $data['threats'] );
        $blocked_total = (int) array_sum( $data['blocked'] );
        $threats       = number_format( $threats_total );
        $blocked       = number_format( $blocked_total );
    }

}
?>
<style>
    .hmwp-widget-log {
        width: 50%;
    }

    .hmwp-widget-table {
        width: 100%;
    }

    .hmwp-widget-chart-cell {
        padding: 0;
        margin: 0;
    }

    .hmwp-widget-col {
        width: 50%;
        vertical-align: top;
        text-align: center;
        margin: 0;
        padding: 0;
    }

    .hmwp-widget-card {
        font-size: 1.2rem;
        font-weight: bold;
        padding: 10px;
        background-color: #eeeeee4d;
        box-shadow: 0 0 3px -1px #aaa;
        border: 1px solid #fff;
    }

    .hmwp-widget-card-left {
        margin: 10px 10px 0 0;
    }

    .hmwp-widget-card-right {
        margin: 10px 0 0 10px;
    }

    .hmwp-widget-card-title {
        font-size: 0.83rem;
        font-weight: normal;
        line-height: 23px;
    }

    .hmwp-widget-link {
        text-decoration: none;
    }

    .hmwp-widget-link-accent {
        color: #FFA500 !important;
    }

    .hmwp-widget-empty {
        padding: 20px 0 0 0;
    }

    .hmwp-widget-empty-button {
        margin: 10px auto;
    }

    .hmwp-widget-note {
        font-size: 0.82rem;
        color: darkgrey;
    }
</style>
<td class="hmwp_widget_log hmwp-widget-log">
    <table class="hmwp-widget-table">
        <?php if ( HMWP_Classes_Tools::getOption( 'hmwp_threats_log' ) ) { ?>
            <tr>
                <td colspan="2" class="hmwp-widget-chart-cell">
                    <?php $view->show( 'blocks/ThreatsChart' ); ?>
                </td>
            </tr>
            <tr>
                <td class="hmwp-widget-col">
                    <div class="hmwp-widget-card hmwp-widget-card-left">
                        <div class="hmwp-widget-card-title"><?php echo esc_html__( 'Threats Prevented (7d)', 'hide-my-wp' ); ?></div>
                        <a href="<?php echo esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_log&thr_blocked=1&thr_range=7&tab=threats', true ) ); ?>" class="hmwp-widget-link"><?php echo esc_html( $blocked ); ?></a>
                    </div>
                </td>
                <td class="hmwp-widget-col">
                    <div class="hmwp-widget-card hmwp-widget-card-right">
                        <div class="hmwp-widget-card-title"><?php echo esc_html__( 'Threats Passed (7d)', 'hide-my-wp' ); ?></div>
                        <a href="<?php echo esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_log&thr_blocked=0&thr_range=7&tab=threats', true ) ); ?>" class="hmwp-widget-link hmwp-widget-link-accent"><?php echo esc_html( $threats ); ?></a>
                    </div>
                </td>
            </tr>
            <?php if ( isset( $threats_total ) && $threats_total > 0 && ( ! HMWP_Classes_Tools::getOption( 'hmwp_sqlinjection' ) || (int) HMWP_Classes_Tools::getOption( 'hmwp_sqlinjection_level' ) < 4 ) ) { ?>
            <tr>
                <td colspan="2" style="padding: 6px 0 2px;">
                    <a href="<?php echo esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_firewall&tab=firewall', true ) ); ?>" style="display:block;background:#F59E0B;color:#fff;text-align:center;padding:7px 10px;border-radius:0;text-decoration:none;font-size:0.82rem;font-weight:600;">
                        &#9888; <?php echo esc_html__( 'Activate 7G or 8G Firewall to block these threats', 'hide-my-wp' ); ?>
                    </a>
                </td>
            </tr>
            <?php } ?>
        <?php } else { ?>
            <tr>
                <td colspan="2" class="hmwp-widget-empty">
                    <a href="<?php echo esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_log&tab=settings', true ) ); ?>" class="wp_button hmwp-widget-empty-button"><?php echo esc_html__( 'Enable Security Threats Log', 'hide-my-wp' ); ?></a>
                    <div class="hmwp-widget-note"><?php echo esc_html__( 'Start monitoring suspicious requests and attack patterns', 'hide-my-wp' ); ?></div>
                </td>
            </tr>
        <?php } ?>

        <?php if ( HMWP_Classes_Tools::getOption( 'hmwp_activity_log' ) ) { ?>
            <tr>
                <td class="hmwp-widget-col">
                    <div class="<?php echo esc_attr( HMWP_CLASS_CTA . ' hmwp-widget-card hmwp-widget-card-left' ); ?>">
                        <div class="hmwp-widget-card-title"><?php echo esc_html__( 'Brute Force IPs Blocked (7d)', 'hide-my-wp' ); ?></div>
                        <a href="<?php echo esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_log&ev_action=block_ip&ev_range=7&tab=events', true ) ); ?>" class="hmwp-widget-link"><?php echo esc_html( $block_ips ); ?></a>
                    </div>
                </td>
                <td class="hmwp-widget-col">
                    <div class="<?php echo esc_attr( HMWP_CLASS_CTA . ' hmwp-widget-card hmwp-widget-card-right' ); ?>">
                        <div class="hmwp-widget-card-title"><?php echo esc_html__( 'Alert Emails Sent (7d)', 'hide-my-wp' ); ?></div>
                        <a href="<?php echo esc_url( HMWP_Classes_Tools::getCloudUrl( 'emails' ) ); ?>" target="_blank" class="hmwp-widget-link"><?php echo esc_html( $alerts ); ?></a>
                    </div>
                </td>
            </tr>
        <?php } else { ?>
            <tr>
                <td colspan="2" class="hmwp-widget-empty">
                    <a href="<?php echo esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_log&tab=settings', true ) ); ?>" class="wp_button hmwp-widget-empty-button"><?php echo esc_html__( 'Enable User Events Log', 'hide-my-wp' ); ?></a>
                    <div class="hmwp-widget-note"><?php echo esc_html__( 'Track key users actions and sign-in events on your site', 'hide-my-wp' ); ?></div>
                </td>
            </tr>
        <?php } ?>

    </table>
</td>
