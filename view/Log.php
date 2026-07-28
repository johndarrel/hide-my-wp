<?php defined( 'ABSPATH' ) || die( 'Cheating uh?' );
if ( ! isset( $view ) ) {
	return;
} ?>
<noscript>
    <style>#hmwp_wrap .tab-panel:not(.tab-panel-first) {
            display: block
        }</style>
</noscript>
<div id="hmwp_wrap" class="d-flex flex-row p-0 my-3">
    <?php
    $view->getAdminTabs( HMWP_Classes_Tools::getValue( 'page' ) );

    $current_tab = HMWP_Classes_Tools::getValue( 'tab' );
    $subtabs = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Menu' )->getSubMenu( HMWP_Classes_Tools::getValue( 'page' ) );
    $tabs = array_column( $subtabs, 'tab' );

    if ( ! $current_tab || ( ! empty( $tabs ) && ! in_array( $current_tab, $tabs, true ) ) ) {
        $current_tab = reset( $tabs );
    }
    ?>
    <div class="hmwp_row d-flex flex-row p-0 m-0">
        <div class="hmwp_col flex-grow-1 p-0 pr-2 mr-2 mb-3">

            <div id="threats" style="<?php echo ( $current_tab === 'threats' ? '' : 'display:none;' ); ?>" class="col-sm-12 p-0 m-0 tab-panel tab-panel-first" >
                <div class="card col-sm-12 p-0 m-0">
                    <h3 class="card-title hmwp_header p-2 m-0 mb-3">
                        <?php echo esc_html__( 'Security Threats Log', 'hide-my-wp' ); ?>
                        <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/security-threats-log/' ) ?>" target="_blank" class="d-inline-block float-right mr-2" style="color: white"><i class="dashicons dashicons-editor-help"></i></a>
                    </h3>

                    <div class="card-body p-2 m-0">

                        <?php if ( HMWP_Classes_Tools::getOption( 'hmwp_threats_log' ) ) { ?>

                            <?php if ( apply_filters( 'hmwp_showtrafficlogs', true ) ) { ?>

                                <div class="card-body p-1 m-0">
                                    <?php $view->trafficListTable->loadPageTable(); ?>
                                </div>

                            <?php } ?>

                        <?php } else { ?>
                            <div class="col-sm-12 p-1 text-center">
                                <div class="text-black-50 mb-2"><?php echo esc_html__( 'Activate "Log Security Threats" to see suspicious requests detected on your website.', 'hide-my-wp' ); ?></div>
                                <a href="<?php echo esc_url(HMWP_Classes_Tools::getSettingsUrl( 'hmwp_log&tab=settings', true )) ?>" class="btn btn-default hmwp_nav_item" data-tab="settings"><?php echo esc_html__( 'Activate Log Security Threats', 'hide-my-wp' ); ?></a>
                            </div>
                        <?php } ?>

                    </div>
                </div>
            </div>

            <div id="events" style="<?php echo ( $current_tab === 'events' ? '' : 'display:none;' ); ?>" class="col-sm-12 p-0 m-0 tab-panel"  >
                <div class="card col-sm-12 p-0 m-0">
                    <h3 class="card-title hmwp_header p-2 m-0 mb-3">
                        <?php echo esc_html__( 'Events Log', 'hide-my-wp' ); ?>
                        <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/events-log-report/#ghost-reading-report' ) ?>" target="_blank" class="d-inline-block float-right mr-2" style="color: white"><i class="dashicons dashicons-editor-help"></i></a>
                    </h3>

                    <div class="card-body p-2 m-0">

                        <?php if ( HMWP_Classes_Tools::getOption( 'hmwp_activity_log' ) ) { ?>

                            <?php if ( apply_filters( 'hmwp_showeventlogs', true ) ) { ?>

                                <div class="card-body p-1 m-0">
                                    <?php $view->eventsListTable->loadPageTable(); ?>
                                </div>

                                <?php if ( apply_filters( 'hmwp_showaccount', true ) && HMWP_Classes_Tools::getOption( 'hmwp_activity_log_cloud') ) { ?>
                                    <div class="mt-3 pt-2 border-top"></div>
                                    <div class="col-sm-12 text-center my-3">
                                        <a href="<?php echo esc_url(HMWP_Classes_Tools::getCloudUrl( 'events' )) ?>" class="btn rounded-0 btn-default btn-lg px-4 securitycheck" target="_blank">
                                            <?php echo esc_html__( 'Go to User Events Cloud Log', 'hide-my-wp' ); ?>
                                        </a>
                                        <div class="text-black-50 small"><?php echo esc_html__( 'Search in user events log and manage the email alerts', 'hide-my-wp' ); ?></div>
                                    </div>
                                <?php } ?>

                            <?php } ?>

                        <?php } else { ?>
                            <div class="col-sm-12 p-1 text-center">
                                <div class="text-black-50 mb-2"><?php echo esc_html__( 'Activate the "Log Users Events" option to see the user activity log for this website', 'hide-my-wp' ); ?></div>
                                <a href="<?php echo esc_url(HMWP_Classes_Tools::getSettingsUrl( 'hmwp_log&tab=settings', true )) ?>" class="btn btn-default hmwp_nav_item" data-tab="settings"><?php echo esc_html__( 'Activate Log Users Events', 'hide-my-wp' ); ?></a>
                            </div>
                        <?php } ?>

                    </div>
                </div>
            </div>

            <form method="POST">
				<?php wp_nonce_field( 'hmwp_logsettings', 'hmwp_nonce' ) ?>
                <input type="hidden" name="action" value="hmwp_logsettings"/>

				<?php do_action( 'hmwp_event_log_form_beginning' ) ?>

                <div id="settings" style="<?php echo ( $current_tab === 'settings' ? '' : 'display:none;' ); ?>" class="col-sm-12 p-0 m-0 tab-panel" >
                    <div class="card col-sm-12 p-0 m-0">
                        <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__( 'Logs Settings', 'hide-my-wp' ); ?>
                        <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/events-log-report/#ghost-how-to-activate' ) ?>" target="_blank" class="d-inline-block float-right mr-2" style="color: white"><i class="dashicons dashicons-editor-help"></i></a>
                    </h3>
                        <div class="card-body">

                            <div class="col-sm-12 row mb-1 ml-1 p-2">
                                <div class="checker col-sm-12 row my-2 py-1">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="checkbox" id="hmwp_activity_log" name="hmwp_activity_log" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_activity_log' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                        <label for="hmwp_activity_log"><?php echo esc_html__( 'Log Users Events', 'hide-my-wp' ); ?>
                                            <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/events-log-report/#ghost-how-to-activate' ) ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                        </label>
                                        <div class="text-black-50 ml-5"><?php echo esc_html__( 'Track and log events that happen on your WordPress site.', 'hide-my-wp' ); ?></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-12 row mb-1 ml-1 p-2 hmwp_activity_log <?php echo esc_attr(HMWP_CLASS_CTA) ?>">
                                <div class="checker col-sm-12 row my-2 py-0">
                                    <div class="col-sm-12 p-0 switch switch-xxs pl-5">
                                        <input type="hidden" name="hmwp_activity_log_cloud" value="0"/>
                                        <input type="checkbox" id="hmwp_activity_log_cloud" name="hmwp_activity_log_cloud" class="switch" <?php echo ( HMWP_Classes_Tools::getOption( 'hmwp_activity_log_cloud' ) ? 'checked="checked"' : '' ); ?> value="1"/>
                                        <label for="hmwp_activity_log_cloud"><?php echo esc_html__( 'Enable Cloud Storage for Events Log', 'hide-my-wp' ); ?></label>
                                        <div class="text-black-50 ml-5"><?php echo esc_html__( 'Keeps events in the Cloud for 30 days for audits and incident reports.', 'hide-my-wp' ); ?></div>
                                        <div class="text-black-50 ml-5"><?php echo esc_html__( 'Enable only if you consent to sending log data to the Cloud.', 'hide-my-wp' ); ?></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-12 row py-3 ml-1 my-3 pl-5 hmwp_activity_log <?php echo esc_attr(HMWP_CLASS_CTA) ?>">
                                <div class="col-sm-4 p-1">
                                    <div class="font-weight-bold"><?php echo esc_html__( 'Log User Roles', 'hide-my-wp' ); ?>:</div>
                                    <div class="small text-black-50"><?php echo esc_html__( "Don't select any role if you want to log all user roles.", 'hide-my-wp' ); ?></div>
                                </div>
                                <div class="col-sm-8 p-0 input-group">
                                    <select multiple name="hmwp_activity_log_roles[]" class="selectpicker form-control mb-1">
                                        <?php
                                        global $wp_roles;
                                        $roles = $wp_roles->get_names();
                                        foreach ( $roles as $key => $role ) {
                                            echo '<option value="' . esc_attr($key) . '" ' . ( in_array( $key, (array) HMWP_Classes_Tools::getOption( 'hmwp_activity_log_roles' ) ) ? 'selected="selected"' : '' ) . '>' . esc_html($role) . '</option>';
                                        } ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-sm-12 row mb-1 ml-1 p-2">
                                <div class="checker col-sm-12 row my-2 py-1">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="checkbox" id="hmwp_threats_log" name="hmwp_threats_log" class="switch" <?php echo ( HMWP_Classes_Tools::getOption( 'hmwp_threats_log' ) ? 'checked="checked"' : '' ); ?> value="1"/>
                                        <label for="hmwp_threats_log"><?php echo esc_html__( 'Log Security Threats', 'hide-my-wp' ); ?>
                                            <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/security-threats-log/#ghost-how-to-activate' ) ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                        </label>
                                        <div class="text-black-50 ml-5"><?php echo esc_html__( 'Log only suspicious frontend requests that match known attack patterns.', 'hide-my-wp' ); ?></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-12 row py-3 mx-0 my-3 <?php echo esc_attr(HMWP_CLASS_CTA) ?>">
                                <div class="col-sm-4 p-1">
                                    <div class="font-weight-bold"><?php echo esc_html__( 'Retention', 'hide-my-wp' ); ?>:</div>
                                    <div class="small text-black-50"><?php echo esc_html__( 'How long to keep threat logs in the database.', 'hide-my-wp' ); ?></div>
                                </div>
                                <div class="col-sm-8 p-0 input-group">
                                    <?php $retention = 7 ?>
                                    <select name="hmwp_threats_log_retention" class="form-control mb-1" style="max-width: 260px;">
                                        <?php foreach ( array( 7, 14, 30 ) as $days ) { ?>
                                            <option value="<?php echo esc_attr( (string) $days ); ?>" <?php selected( $retention, $days ); ?>>
                                                <?php echo esc_html( sprintf( /* translators: %1$d: Number of days. */ __( '%1$d days', 'hide-my-wp' ), $days ) ); ?>                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>


                        </div>

                         <div class="col-sm-12 m-0 p-2 card-footer save-button text-center">
                            <button type="submit" class="btn rounded-0 btn-success px-5 mr-5 save"><?php echo esc_html__( 'Save', 'hide-my-wp' ); ?></button>
                        </div>
                    </div>
                </div>

                <script>
                    (function () {
                        var h = window.location.hash || '';
                        var m = h.match(/(?:^|[?&#])tab=([^&#]+)/);
                        if (m && m[1]) {
                            document.documentElement.setAttribute('data-hmwp-tab', decodeURIComponent(m[1]));
                        }
                    })();
                </script>

                <style>
                    /* Hide everything by default to prevent flash */
                    #hmwp_wrap .tab-panel { display: none; }

                    /* Default tab if no hash */
                    html:not([data-hmwp-tab]) #hmwp_wrap .tab-panel-first { display: block; }

                    /* Show the requested tab immediately */
                    html[data-hmwp-tab="events"]  #hmwp_wrap #events  { display: block; }
                    html[data-hmwp-tab="threats"] #hmwp_wrap #threats { display: block; }
                </style>

				<?php do_action( 'hmwp_event_log_form_end' ) ?>

            </form>
        </div>

        <div class="hmwp_col hmwp_col_side p-0 pr-2 mr-2">
            <div class="card col-sm-12 m-0 p-0 rounded-0 mb-2">
                <div class="card-body f-gray-dark text-left">
                    <h3 class="card-title"><?php echo esc_html__( 'Security Threats Log', 'hide-my-wp' ); ?></h3>
                    <div class="text-info mb-3"><?php echo esc_html__( 'Monitor suspicious requests and identify attack patterns hitting your website.', 'hide-my-wp' ); ?></div>
                    <div class="text-info mb-3"><?php echo esc_html__( 'Only threats are logged to reduce database load on high-traffic websites.', 'hide-my-wp' ); ?></div>
                    <div class="text-warning mb-3"><?php echo esc_html__( 'Server-level blocks (Nginx/htaccess) may not reach WordPress, so they cannot be logged here.', 'hide-my-wp' ); ?></div>
                    <hr />
                    <h3 class="card-title"><?php echo esc_html__( 'What you see', 'hide-my-wp' ); ?></h3>
                    <ul class="text-info" style="margin-left: 16px; list-style: circle;">
                        <li class="mb-2"><?php echo esc_html__( 'Threat type and reason (SQLi, XSS, probing, traversal, and more).', 'hide-my-wp' ); ?></li>
                        <li class="mb-2"><?php echo esc_html__( 'Request path, method, and response status code.', 'hide-my-wp' ); ?></li>
                        <li class="mb-2"><?php echo esc_html__( 'Whether the request was blocked by the firewall.', 'hide-my-wp' ); ?></li>
                        <li><?php echo esc_html__( 'Useful context: referrer and user-agent when available.', 'hide-my-wp' ); ?></li>
                    </ul>
                </div>
            </div>

            <div class="card col-sm-12 m-0 p-0 rounded-0 mb-2">
                <div class="card-body f-gray-dark text-left">
                    <h3 class="card-title"><?php echo esc_html__( 'Events Log', 'hide-my-wp' ); ?></h3>
                    <div class="text-info mb-3"><?php echo esc_html__( 'Track key admin actions and sign-in events on your WordPress site.', 'hide-my-wp' ); ?></div>
                    <div class="text-info mb-3"><?php echo esc_html__( 'Events are saved locally on this website for quick review.', 'hide-my-wp' ); ?></div>
                    <div class="text-warning mb-3"><strong><?php echo esc_html__( 'Optional:', 'hide-my-wp' ); ?></strong> <?php echo esc_html__( 'Enable Cloud storage to keep logs for 30 days and generate incident reports. Turn this off if you do not consent to sending log data to the Cloud.', 'hide-my-wp' ); ?></div>

                    <hr />

                    <h3 class="card-title"><?php echo esc_html__( 'What you see', 'hide-my-wp' ); ?></h3>
                    <ul class="text-info" style="margin-left: 16px; list-style: circle;">
                        <li class="mb-2"><?php echo esc_html__( 'User actions in the dashboard (updates, changes, key settings actions).', 'hide-my-wp' ); ?></li>
                        <li class="mb-2"><?php echo esc_html__( 'Authentication events (logins, failed logins, password-related actions).', 'hide-my-wp' ); ?></li>
                        <li class="mb-2"><?php echo esc_html__( 'Optional security alerts to help you respond quickly to suspicious activity.', 'hide-my-wp' ); ?></li>
                        <li><?php echo esc_html__( 'Works alongside your theme and plugins without requiring special configuration.', 'hide-my-wp' ); ?></li>
                    </ul>
                </div>
            </div>


        </div>
    </div>
</div>
