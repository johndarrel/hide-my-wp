<?php defined( 'ABSPATH' ) || die( 'Cheating uh?' );
if ( ! isset( $view ) ) {
    return;
}

$do_check = false;
//Set the alert if security wasn't check
if ( HMWP_Classes_Tools::getOption( 'hmwp_security_alert' ) ) {
    if ( ! get_option( HMWP_SECURITY_CHECK ) ) {
        $do_check = true;
    } elseif ( $securitycheck_time = get_option( HMWP_SECURITY_CHECK_TIME ) ) {
        if ( ( isset( $securitycheck_time['timestamp'] ) && time() - $securitycheck_time['timestamp'] > ( 3600 * 24 * 7 ) ) ) {
            $do_check = true;
        }
    } else {
        $do_check = true;
    }
}

// Calculate security score (0-100, higher is better)
$hmwp_risk_percent   = ( count( $view->risktasks ) > 0 ) ? round( ( count( $view->riskreport ) * 100 ) / count( $view->risktasks ) ) : 0;
$hmwp_security_score = 100 - $hmwp_risk_percent;

?>
<style>
    .hmwp_widget_content {
        container-type: inline-size;
        container-name: hmwpwrap;
        position: relative;
    }

    .hmwp_widget_table {
        margin: auto;
        width: 100%;
    }

    .hmwp_widget_gauge {
        width: 50%;
        margin-bottom: 20px;
        text-align: center;
    }

    .hmwp_widget_gauge_link {
        text-align: center;
        display: inline-block;
        width: 100%;
        text-decoration: none;
    }

    .hmwp_widget_gauge_img {
        max-width: 75%;
        height: auto;
        margin: 10px auto;
    }

    /* Actions section */
    .hmwp_actions_title {
        font-size: 1.15rem;
        padding-bottom: 20px;
        text-align: center;
    }

    .hmwp_actions_list {
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .hmwp_actions_item {
        margin: 10px 0;
        padding: 10px;
        line-height: 30px;
        border: 1px solid #f3ebd0;
        border-left: 4px solid #d63638;
    }

    .wp_button {
        display: block;
        font-weight: 400;
        text-align: center;
        white-space: nowrap;
        vertical-align: middle;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
        border-radius: 0;
        transition: color .15s ease-in-out, background-color .15s ease-in-out, border-color .15s ease-in-out, box-shadow .15s ease-in-out;
        padding: .9rem 1.5rem;
        font-size: 1rem;
        line-height: 1;
        color: #fff !important;
        background-color: #007cba;
        border-color: #405c7b;
        margin: 1rem auto;
        text-decoration: none;
        max-width: 300px;
        cursor: pointer;
    }

    @container hmwpwrap (max-width: 599px) {
        td.hmwp_widget_security,
        td.hmwp_widget_log {
            display: block !important;
            width: 100% !important;
            clear: both;
        }

        td.hmwp_widget_gauge {
            width: 100% !important;
        }
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>

<div class="hmwp_widget_content">
    <div style="text-align: center">

        <table class="hmwp_widget_table">
            <tr>
                <td class="hmwp_widget_security hmwp_widget_gauge">
                    <?php if ( ! $do_check ) { ?>

                        <a href="<?php echo esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_securitycheck' ) ); ?>" class="hmwp_widget_gauge_link">
                            <div class="hmwp_widget_gauge_img">
                                <?php include _HMWP_THEME_DIR_ . 'blocks/Speedometer.php'; ?>
                            </div>
                        </a>
                    <?php } else { ?>

                        <a href="<?php echo esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_securitycheck' ) ); ?>" class="hmwp_widget_gauge_link">
                            <div class="hmwp_widget_gauge_img">
                                <?php include _HMWP_THEME_DIR_ . 'blocks/Speedometer.php'; ?>
                            </div>
                        </a>

                        <a href="<?php echo esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_securitycheck', true ) ); ?>" class="wp_button">
                            <?php echo esc_html__( 'Run Full Security Check', 'hide-my-wp' ); ?>
                        </a>

                    <?php } ?>
                </td>

                <?php $view->show( 'blocks/ThreatsCount' ); ?>
            </tr>
        </table>
    </div>

    <?php if ( $hmwp_risk_percent > 0 ) { ?>
        <div style="padding: 40px 0 20px 0;">
            <div class="hmwp_actions_title"><?php echo esc_html__( 'Urgent Security Actions Required', 'hide-my-wp' ); ?></div>
            <ul class="hmwp_actions_list">
                <?php foreach ( $view->riskreport as $function => $row ) { ?>
                    <li class="hmwp_actions_item"><?php echo wp_kses_post( $row['solution'] ); ?></li>
                <?php } ?>
            </ul>
        </div>
    <?php } ?>

</div>