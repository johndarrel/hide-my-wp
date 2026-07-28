<?php
defined( 'ABSPATH' ) || die( 'Cheating uh?' );
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

	        <?php do_action( 'hmwp_twofactor_beginning' ) ?>

            <div id="logins" style="<?php echo ( $current_tab === 'logins' ? '' : 'display:none;' ); ?>" class="col-sm-12 p-0 m-0 tab-panel tab-panel-first">
                <div class="card col-sm-12 p-0 m-0">
                    <h3 class="card-title hmwp_header p-2 m-0 mb-3"><?php echo esc_html__( 'Two-Factor Authentication', 'hide-my-wp' ); ?>
                    <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/two-factor-authentication/' ) ?>" target="_blank" class="d-inline-block ml-2" style="color: white"><i class="dashicons dashicons-editor-help"></i></a>
                </h3>
                    <div class="card-body p-2 m-0">
					<?php if ( HMWP_Classes_Tools::getOption( 'hmwp_2falogin' ) ) {
                        $view->show('blocks/TwofactorUsers');
                    } else { ?>
                        <div class="col-sm-12 p-1 my-2 text-center">
                            <div class="text-black-50 mb-2"><?php echo esc_html__( 'Activate the "Two-Factor Authentication" option to view the 2FA login report.', 'hide-my-wp' ); ?></div>
                            <a href="#settings" class="btn btn-default hmwp_nav_item" data-tab="settings"><?php echo esc_html__( 'Activate Two-Factor Authentication', 'hide-my-wp' ); ?></a>
                        </div>
					<?php } ?>
                </div>
                </div>
            </div>

            <form method="POST">
				<?php wp_nonce_field( 'hmwp_2fasettings', 'hmwp_nonce' ) ?>
                <input type="hidden" name="action" value="hmwp_2fasettings"/>

				<?php do_action( 'hmwp_two_factor_form_beginning' ) ?>

                <div id="settings" style="<?php echo ( $current_tab === 'settings' ? '' : 'display:none;' ); ?>" class="col-sm-12 p-0 m-0 tab-panel ">
                    <div class="card col-sm-12 p-0 m-0 mb-3">
                        <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__( 'Two-factor Authentication Settings', 'hide-my-wp' ); ?>
                            <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/two-factor-authentication/#ghost-how-to-enable' ) ?>" target="_blank" class="d-inline-block float-right mr-2" style="color: white"><i class="dashicons dashicons-editor-help"></i></a>
                        </h3>
                        <div class="card-body">
                            <div class="col-sm-12 row mb-1 ml-1 p-2">
                                <div class="checker col-sm-12 row my-2 py-1">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="hidden" name="hmwp_2falogin" value="0"/>
                                        <input type="checkbox" id="hmwp_2falogin" name="hmwp_2falogin" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_2falogin' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                        <label for="hmwp_2falogin"><?php echo esc_html__( 'Use 2FA Authentication', 'hide-my-wp' ); ?>
                                            <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/two-factor-authentication/#ghost-activate' ) ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                        </label>
                                        <div class="text-black-50 ml-5"><?php echo esc_html__( "Add a second verification step via authenticator app, email code, or passkey (Face ID, Touch ID), so a stolen password alone can't unlock your site.", 'hide-my-wp' ); ?></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-12 row mb-1 ml-1 p-2 hmwp_2falogin">
                                <div class="checker col-sm-12 row my-2 py-0">
                                    <div class="col-sm-12 p-0 switch switch-xxs pl-5">
                                        <input type="hidden" name="hmwp_2fa_forced" value="0"/>
                                        <input type="checkbox" id="hmwp_2fa_forced" name="hmwp_2fa_forced" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_2fa_forced' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                        <label for="hmwp_2fa_forced"><?php echo esc_html__( 'Force 2FA Setup', 'hide-my-wp' ); ?></label>
                                        <div class="text-black-50 ml-5"><?php echo esc_html__( "Require users to set up Two-Factor Authentication before they can use the dashboard. Users without 2FA are redirected to a setup screen after login.", 'hide-my-wp' ); ?></div>
                                    </div>
                                </div>
                                <div class="hmwp_2fa_forced col-sm-12 row py-3 mx-1 my-3">
                                    <div class="col-sm-4 p-1 pl-5">
                                        <div class="font-weight-bold"><?php echo esc_html__( 'Apply to Roles', 'hide-my-wp' ); ?>:</div>
                                        <div class="small text-black-50"><?php echo esc_html__( "Leave empty to require 2FA for all user roles.", 'hide-my-wp' ); ?></div>
                                    </div>
                                    <div class="col-sm-8 p-0 input-group">
                                        <select multiple name="hmwp_2fa_forced_roles[]" class="selectpicker form-control mb-1">
											<?php
											$hmwp_forced_roles = (array) HMWP_Classes_Tools::getOption( 'hmwp_2fa_forced_roles' );
											$hmwp_allroles     = array();
											if ( function_exists( 'wp_roles' ) ) {
												$hmwp_allroles = wp_roles()->get_names();
											}

											foreach ( $hmwp_allroles as $hmwp_role => $hmwp_role_name ) {
												echo '<option value="' . esc_attr( $hmwp_role ) . '" ' . ( in_array( $hmwp_role, $hmwp_forced_roles ) ? 'selected="selected"' : '' ) . '>' . esc_html( $hmwp_role_name ) . '</option>';
											} ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-12 row mb-1 ml-1 p-2 hmwp_2fa_user_select hmwp_2falogin">
                                <div class="checker col-sm-12 row my-2 py-0">
                                    <div class="col-sm-12 p-0 switch switch-xxs pl-5">
                                        <input type="hidden" name="hmwp_2fa_user" value="0"/>
                                        <input type="checkbox" id="hmwp_2fa_user" name="hmwp_2fa_user" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_2fa_user' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                        <label for="hmwp_2fa_user"><?php echo esc_html__( 'User Choice for 2FA', 'hide-my-wp' ); ?></label>
                                        <div class="text-black-50 ml-5"><?php echo esc_html__( 'Let the user choose their preferred 2FA method in their account settings.', 'hide-my-wp' ); ?></div>
                                    </div>
                                </div>
                            </div>

                            <div class="hmwp_2falogin">

                            <div class="border-top"></div>

                            <div class="hmwp_2fa_default" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_2fa_user' ) ? 'style="display:none"' : '' ) ?>>
                                <input type="hidden" value="<?php echo( HMWP_Classes_Tools::getOption( 'hmwp_2fa_totp' ) ? '1' : '0' ) ?>" name="hmwp_2fa_totp">
                                <input type="hidden" value="<?php echo( HMWP_Classes_Tools::getOption( 'hmwp_2fa_email' ) ? '1' : '0' ) ?>" name="hmwp_2fa_email">
                                <input type="hidden" value="<?php echo( HMWP_Classes_Tools::getOption( 'hmwp_2fa_passkey' ) ? '1' : '0' ) ?>" name="hmwp_2fa_passkey">

                                <div class="col-sm-12 group_autoload d-flex justify-content-center btn-group btn-group-lg mt-3 px-0" role="group">
                                    <button type="button" class="btn btn-outline-info hmwp_2fa_totp mx-1 py-4 px-4 <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_2fa_totp' ) ? 'active' : '' ) ?>"><?php echo esc_html__( '2FA Code', 'hide-my-wp' ); ?></button>
                                    <button type="button" class="btn btn-outline-info hmwp_2fa_email mx-1 py-4 px-4 <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_2fa_email' ) ? 'active' : '' ) ?>"><?php echo esc_html__( "Email Code", 'hide-my-wp' ); ?></button>
                                    <button type="button" class="btn btn-outline-info hmwp_2fa_passkey mx-1 py-4 px-4 <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_2fa_passkey' ) ? 'active' : '' ) ?>"><?php echo esc_html__( 'Passkey', 'hide-my-wp' ); ?></button>
                                </div>
                            </div>



                            <div class="hmwp_2fa_email" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_2fa_totp' ) || HMWP_Classes_Tools::getOption( 'hmwp_2fa_passkey' ) ? 'style="display:none"' : '' ) ?>>
                                <div class="col-12 py-4 px-2 text-danger"><?php /* translators: 1: Opening <a> tag to Easy WP SMTP plugin page, 2: Closing </a> tag. */ echo wp_kses_post( sprintf( __( 'Guarantee email delivery using the complimentary email plugin like %1$sEasy WP SMTP%2$s', 'hide-my-wp' ), '<a href="' . esc_url( 'https://wordpress.org/plugins/easy-wp-smtp/' ) . '" target="_blank">', '</a>' ) ); ?></div>                            </div>

                            <div class="hmwp_2falogin_limits">

                                <div class="col-sm-12 row py-3 mx-0 my-3">
                                    <div class="col-md-4 p-0 font-weight-bold">
                                        <?php echo esc_html__( 'Max Failed Login Attempts', 'hide-my-wp' ); ?>:
                                        <div class="small text-black-50"><?php echo esc_html__( 'How many failed login attempts are allowed before the IP address is blocked.', 'hide-my-wp' ); ?></div>
                                    </div>
                                    <div class="col-md-2 p-0 input-group">
                                        <input type="text" class="form-control" name="hmwp_2falogin_max_attempts" value="<?php echo esc_attr(HMWP_Classes_Tools::getOption( 'hmwp_2falogin_max_attempts' )) ?>"/>
                                    </div>
                                </div>

                                <div class="col-sm-12 row py-3 mx-0 my-3">
                                    <div class="col-md-4 p-0 font-weight-bold">
										<?php echo esc_html__( 'IP Lockout Duration', 'hide-my-wp' ); ?>:
                                        <div class="small text-black-50"><?php echo esc_html__( 'Duration of the IP block after failed login attempts.', 'hide-my-wp' ); ?></div>
                                    </div>
                                    <div class="col-md-2 p-0 input-group input-group">
                                        <div class="col-md-2 p-0 input-group input-group">
                                            <select name="hmwp_2falogin_max_timeout" id="hmwp_2falogin_max_timeout" class="form-select form-select-sm mr-2" style="width: 240px;">
                                                <option value="900" <?php selected(900, HMWP_Classes_Tools::getOption('hmwp_2falogin_max_timeout')); ?>>
                                                    <?php echo esc_html__('15 minutes', 'hide-my-wp'); ?>
                                                </option>
                                                <option value="1800" <?php selected(1800, HMWP_Classes_Tools::getOption('hmwp_2falogin_max_timeout')); ?>>
                                                    <?php echo esc_html__('30 minutes', 'hide-my-wp'); ?>
                                                </option>
                                                <option value="<?php echo esc_attr( HOUR_IN_SECONDS ); ?>" <?php selected(HOUR_IN_SECONDS, HMWP_Classes_Tools::getOption('hmwp_2falogin_max_timeout')); ?>>
                                                    <?php echo esc_html__('1 hour', 'hide-my-wp'); ?>
                                                </option>
                                                <option value="<?php echo esc_attr( DAY_IN_SECONDS ); ?>" <?php selected(DAY_IN_SECONDS, HMWP_Classes_Tools::getOption('hmwp_2falogin_max_timeout')); ?>>
                                                    <?php echo esc_html__('1 day', 'hide-my-wp'); ?>
                                                </option>
                                                <option value="<?php echo esc_attr( WEEK_IN_SECONDS ); ?>" <?php selected(WEEK_IN_SECONDS, HMWP_Classes_Tools::getOption('hmwp_2falogin_max_timeout')); ?>>
                                                    <?php echo esc_html__('1 week', 'hide-my-wp'); ?>
                                                </option>
                                                <option value="<?php echo esc_attr( MONTH_IN_SECONDS ); ?>" <?php selected(MONTH_IN_SECONDS, HMWP_Classes_Tools::getOption('hmwp_2falogin_max_timeout')); ?>>
                                                    <?php echo esc_html__('1 month', 'hide-my-wp'); ?>
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <?php if ( ! HMWP_Classes_ObjController::getClass( 'HMWP_Models_Translate' )->isMultilingualPluginActive() ) { ?>
                                    <div class="col-sm-12 row py-3 mx-0 my-3">
                                        <div class="col-md-4 p-0 font-weight-bold">
                                            <?php echo esc_html__( 'Failed Attempts Message', 'hide-my-wp' ); ?>:
                                            <div class="small text-black-50"><?php echo esc_html__( 'Show alert message for a specific user when there were fail attempts on his account.', 'hide-my-wp' ); ?></div>
                                            <div class="small text-black-50"><?php echo esc_html__( 'Variables: {count} - no. of tries, {time} - time since last fail.', 'hide-my-wp' ); ?></div>
                                        </div>
                                        <div class="col-md-8 p-0 input-group input-group">
                                            <textarea type="text" class="form-control" name="hmwp_2falogin_fail_message" style="height: 120px" ><?php echo esc_attr(HMWP_Classes_Tools::getOption( 'hmwp_2falogin_fail_message' )) ?></textarea>
                                        </div>
                                    </div>

                                    <div class="col-sm-12 row py-3 mx-0 my-3">
                                        <div class="col-md-4 p-0 font-weight-bold">
                                            <?php echo esc_html__( 'Lockout Message', 'hide-my-wp' ); ?>:
                                            <div class="small text-black-50"><?php echo esc_html__( 'Display lockout message instead of the login form', 'hide-my-wp' ); ?></div>
                                            <div class="small text-black-50"><?php echo esc_html__( 'Variables: {time} - time since available again.', 'hide-my-wp' ); ?></div>
                                        </div>
                                        <div class="col-md-8 p-0 input-group input-group">
                                            <textarea type="text" class="form-control" name="hmwp_2falogin_message" style="height: 80px"><?php echo esc_attr(HMWP_Classes_Tools::getOption( 'hmwp_2falogin_message' )) ?></textarea>
                                        </div>
                                    </div>
                                <?php } else { ?>
                                    <div class="col-sm-12 text-center py-3 mx-0 my-3">
                                        <div class="text-info"><?php echo esc_html__( 'Translate notification messages using WPML or Polylang String Translation.', 'hide-my-wp' ); ?></div>
                                    </div>
                                <?php } ?>

                                <div class="col-sm-12 row mb-1 py-1 mx-2 ">
                                    <div class="checker col-sm-12 row my-2 py-1">
                                        <div class="col-sm-12 p-0 switch switch-sm switch-red">
                                            <input type="hidden" name="hmwp_2falogin_delete_uninstal" value="0"/>
                                            <input type="checkbox" id="hmwp_2falogin_delete_uninstal" name="hmwp_2falogin_delete_uninstal" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_2falogin_delete_uninstal' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                            <label for="hmwp_2falogin_delete_uninstal"><?php echo esc_html__( 'Delete 2FA Data on Plugin Uninstall', 'hide-my-wp' ); ?></label>
                                        </div>
                                    </div>
                                </div>

                            </div>

                        </div>

                    </div>
                    </div>
                </div>

				<?php do_action( 'hmwp_two_factor_form_end' ) ?>

                <div class="col-sm-12 m-0 p-2 bg-light text-center" style="position: fixed; bottom: 0; right: 0; z-index: 100; box-shadow: 0 0 8px -3px #444;">
                    <button type="submit" class="btn rounded-0 btn-success px-5 mr-3 save"><?php echo esc_html__( 'Save', 'hide-my-wp' ); ?></button>
					<?php if ( HMWP_Classes_Tools::getOption( 'hmwp_2falogin' ) ) { ?>
                        <a href="<?php echo esc_url(admin_url( 'profile.php' ) . '#hmwp_two_factor_options') ?>" class="btn rounded-0 btn-success px-5 mr-5"><?php echo esc_html__( 'Add Two Factor Authentication', 'hide-my-wp' ); ?></a>
					<?php } ?>
                </div>
            </form>

	        <?php do_action( 'hmwp_twofactor_end' ) ?>

        </div>

        <div class="hmwp_col hmwp_col_side p-0 pr-2 mr-2">
            <div class="card col-sm-12 m-0 p-0 rounded-0">
                <div class="card-body f-gray-dark text-left">
                    <h3 class="card-title"><?php echo esc_html__( '2FA Logins', 'hide-my-wp' ); ?></h3>
                    <div class="text-info"><?php echo sprintf( esc_html__( "Add an extra layer of security to your online accounts by requiring both a password and a second verification method, such as a text message or app-generated code, to log in.", 'hide-my-wp' ), '<br><br>' ); ?>
                    </div>
                </div>
            </div>

        </div>

    </div>


</div>

