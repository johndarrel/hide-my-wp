<?php defined( 'ABSPATH' ) || die( 'Cheating uh?' );
if ( ! isset( $view ) ) {
	return;
}
/**
 *
 * @var $wp_roles WP_Roles
 */
global $wp_roles;
?>
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

			<?php do_action( 'hmwp_temporary_login_beginning' ) ?>

            <div id="logins" style="<?php echo ( $current_tab === 'logins' ? '' : 'display:none;' ); ?>" class="col-sm-12 p-0 m-0 tab-panel tab-panel-first">
                <div class="card col-sm-12 p-0 m-0">
                    <h3 class="card-title hmwp_header p-2 m-0 mb-3"><?php echo esc_html__( 'Temporary Logins', 'hide-my-wp' ); ?>
                    <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/temporary-logins/' ) ?>" target="_blank" class="d-inline-block float-right mr-2" style="color: white"><i class="dashicons dashicons-editor-help"></i></a>
                </h3>
                    <div class="card-body p-2 m-0">
					<?php if ( HMWP_Classes_Tools::getOption( 'hmwp_templogin' ) ) {
                        $view->show( 'blocks/TempLoginUsers' );
					} else { ?>
                        <div class="col-sm-12 p-1 text-center">
                            <div class="text-black-50 mb-2"><?php echo esc_html__( 'Activate the "Temporary Logins" option to view the Temporary login report.', 'hide-my-wp' ); ?></div>
                            <a href="<?php echo esc_url(HMWP_Classes_Tools::getSettingsUrl( 'hmwp_templogin&tab=settings', true )) ?>" class="btn btn-default hmwp_nav_item" data-tab="settings"><?php echo esc_html__( 'Activate Temporary Logins', 'hide-my-wp' ); ?></a>
                        </div>
					<?php } ?>
                </div>
                </div>
            </div>

            <form method="POST">
				<?php wp_nonce_field( 'hmwp_temploginsettings', 'hmwp_nonce' ) ?>
                <input type="hidden" name="action" value="hmwp_temploginsettings"/>

				<?php do_action( 'hmwp_temporary_login_form_beginning' ) ?>

                <div id="settings" style="<?php echo ( $current_tab === 'settings' ? '' : 'display:none;' ); ?>" class="col-sm-12 p-0 m-0 tab-panel ">
                    <div class="card col-sm-12 p-0 m-0">
                        <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__( 'Temporary Login Settings', 'hide-my-wp' ); ?>
                            <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/temporary-logins/#ghost-activate' ) ?>" target="_blank" class="d-inline-block float-right mr-2" style="color: white"><i class="dashicons dashicons-editor-help"></i></a>
                        </h3>
                        <div class="card-body">
                        <div class="col-sm-12 row mb-1 ml-1 p-2">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmwp_templogin" value="0"/>
                                    <input type="checkbox" id="hmwp_templogin" name="hmwp_templogin" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_templogin' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                    <label for="hmwp_templogin"><?php echo esc_html__( 'Use Temporary Logins', 'hide-my-wp' ); ?>
                                        <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/temporary-logins/#ghost-default-settings' ) ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                    </label>
                                    <div class="text-black-50 ml-5"><?php echo esc_html__( 'Create secure, time-limited access links without sharing usernames or passwords for a limited period of time.', 'hide-my-wp' ); ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="hmwp_templogin">

                            <div class="border-top"></div>

                            <div class="col-sm-12 row py-3 mx-1 my-3">
                                <div class="col-sm-4 p-1">
                                    <div class="font-weight-bold"><?php echo esc_html__( 'Default User Role', 'hide-my-wp' ); ?>:</div>
                                    <div class="small text-black-50"><?php echo esc_html__( "Default user role for which the temporary login will be created.", 'hide-my-wp' ); ?></div>
                                </div>
                                <div class="col-sm-8 p-0 input-group">
                                    <select name="hmwp_templogin_role" class="selectpicker form-control mb-1">
										<?php

										$allroles = array();
										if ( function_exists( 'wp_roles' ) ) {
											$allroles = wp_roles()->get_names();
										}

										foreach ( $allroles as $role => $name ) {
											echo '<option value="' . esc_attr($role) . '" ' . selected( $role, HMWP_Classes_Tools::getOption( 'hmwp_templogin_role' ), true ) . '>' . esc_html( $name ) . '</option>';
										} ?>

                                    </select>
                                </div>

                            </div>

                            <div class="col-sm-12 row py-3 mx-1 my-3">
                                <div class="col-sm-4 p-1">
                                    <div class="font-weight-bold"><?php echo esc_html__( 'Default Redirect After Login', 'hide-my-wp' ); ?>:</div>
                                    <div class="small text-black-50"><?php echo esc_html__( "Redirect temporary users to a custom page after login.", 'hide-my-wp' ); ?></div>
                                </div>
                                <div class="col-sm-8 p-0 input-group mb-1">
                                    <select name="hmwp_templogin_redirect" class="selectpicker form-control mb-1">
                                        <option value=""><?php echo esc_html__( "Dashboard", 'hide-my-wp' ) ?></option>
										<?php
										$pages = get_pages( array( 'number' => 50 ) );
										foreach ( $pages as $page ) {
											if ( $page->post_title <> '' ) {
												?>
                                                <option value="<?php echo esc_attr( $page->post_name ) ?>" <?php echo selected( $page->post_name, HMWP_Classes_Tools::getOption( 'hmwp_templogin_redirect' ), true ) ?> ><?php echo esc_html( $page->post_title ) ?></option><?php
											}
										} ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-sm-12 row py-3 mx-1 my-3">
                                <div class="col-sm-4 p-1">
                                    <div class="font-weight-bold"><?php echo esc_html__( 'Default Temporary Expire Time', 'hide-my-wp' ); ?>:</div>
                                    <div class="small text-black-50"><?php echo esc_html__( "Select how long the temporary login will be available after the first user access.", 'hide-my-wp' ); ?></div>
                                </div>
                                <div class="col-sm-8 p-0 input-group mb-1">
                                    <select name="hmwp_templogin_expires" class="selectpicker form-control mb-1">
										<?php
										foreach ( HMWP_Classes_ObjController::getClass( 'HMWP_Models_Templogin' )->expires as $key => $expire ) {
											?>
                                            <option value="<?php echo esc_attr( $key ) ?>" <?php echo selected( $key, HMWP_Classes_Tools::getOption( 'hmwp_templogin_expires' ), true ) ?>><?php echo esc_html($expire['label']) ?></option><?php
										} ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-sm-12 row mb-1 py-1 mx-2 ">
                                <div class="checker col-sm-12 row my-2 py-1">
                                    <div class="col-sm-12 p-0 switch switch-sm switch-red">
                                        <input type="hidden" name="hmwp_templogin_delete_uninstal" value="0"/>
                                        <input type="checkbox" id="hmwp_templogin_delete_uninstal" name="hmwp_templogin_delete_uninstal" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_templogin_delete_uninstal' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                        <label for="hmwp_templogin_delete_uninstal"><?php echo esc_html__( 'Delete Temporary Users on Plugin Uninstall', 'hide-my-wp' ); ?></label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
                </div>

				<?php do_action( 'hmwp_temporary_login_form_end' ) ?>

                 <div class="col-sm-12 m-0 p-2 card-footer save-button text-center">
                    <button type="submit" class="btn rounded-0 btn-success px-5 mr-3 save"><?php echo esc_html__( 'Save', 'hide-my-wp' ); ?></button>
					<?php if ( HMWP_Classes_Tools::getOption( 'hmwp_templogin' ) ) { ?>
                        <button type="button" class="btn rounded-0 btn-success px-5 mr-5" onclick="jQuery('#hmwp_templogin_modal_new').modal('show');"><?php echo esc_html__( 'Add New Temporary Login', 'hide-my-wp' ); ?></button>
					<?php } ?>
                </div>
            </form>

        </div>

        <div class="hmwp_col hmwp_col_side p-0 pr-2 mr-2">
            <div class="card col-sm-12 m-0 p-0 rounded-0">
                <div class="card-body f-gray-dark text-left">
                    <h3 class="card-title"><?php echo esc_html__( 'Temporary Login', 'hide-my-wp' ); ?></h3>
                    <div class="text-info">
                        <?php
                        echo wp_kses_post(
                                sprintf(
                                /* translators: %s: HTML line breaks (<br><br>) between sentences. */
                                        __(
                                                'Create a temporary login URL with any user role to access the website dashboard without a username and password for a limited period of time.%sThis is useful when you need to give admin access to a developer for support or routine tasks.',
                                                'hide-my-wp'
                                        ),
                                        '<br><br>'
                                )
                        );
                        ?>
                    </div>
                </div>
            </div>

        </div>

    </div>

	<?php if ( isset( $view->user->ID ) ) { ?>
        <div id="hmwp_templogin_modal_edit" class="modal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><?php echo esc_html__( 'Edit User', 'hide-my-wp' ) ?>: <?php echo esc_html($view->user->user_email) ?></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" class="col-12 m-0 p-1">
							<?php wp_nonce_field( 'hmwp_templogin_update', 'hmwp_nonce' ) ?>
                            <input type="hidden" name="action" value="hmwp_templogin_update"/>
                            <input type="hidden" name="user_id" value="<?php echo esc_attr($view->user->ID) ?>"/>


                            <div class="col-sm-12 row py-3 mx-2 my-3">
                                <div class="col-sm-4 p-0 font-weight-bold">
									<?php echo esc_html__( 'First Name', 'hide-my-wp' ); ?>:
                                </div>
                                <div class="col-sm-8 p-0 input-group">
                                    <input type="text" class="form-control " name="hmwp_details[first_name]" value="<?php echo esc_attr($view->user->first_name) ?>"/>
                                </div>
                            </div>

                            <div class="col-sm-12 row py-3 mx-2 my-3">
                                <div class="col-sm-4 p-0 font-weight-bold">
									<?php echo esc_html__( 'Last Name', 'hide-my-wp' ); ?>:
                                </div>
                                <div class="col-sm-8 p-0 input-group">
                                    <input type="text" class="form-control " name="hmwp_details[last_name]" value="<?php echo esc_attr($view->user->last_name) ?>"/>
                                </div>
                            </div>

							<?php if ( HMWP_Classes_Tools::isMultisites() ) {
								$sites = get_sites( array( 'number' => 10000, 'public' => 1, 'deleted' => 0, ) );
								if ( ! empty( $sites ) && count( $sites ) > 0 ) { ?>

                                    <div class="col-sm-12 row py-3 mx-1 my-3">
                                        <div class="col-sm-4 p-1">
                                            <div class="font-weight-bold"><?php echo esc_html__( 'Website', 'hide-my-wp' ); ?>:</div>
                                            <div class="small text-black-50"><?php echo esc_html__( "Set the website you want this user to be created for.", 'hide-my-wp' ); ?></div>
                                        </div>
                                        <div class="col-sm-8 p-0 input-group">
                                            <select name="hmwp_details[blog_id]" class="selectpicker form-control mb-1">
												<?php foreach ( $sites as $site ) { ?>
                                                    <option value="<?php echo esc_attr($site->blog_id) ?>" <?php selected( true, ( $site->blog_id == get_user_meta( $view->user->ID, 'primary_blog', true ) ) ) ?>><?php echo esc_url($site->domain . $site->path) ?></option>
												<?php } ?>
                                            </select>
                                        </div>

                                    </div>


								<?php } ?>
							<?php } ?>

                            <div class="col-sm-12 row py-3 mx-1 my-3">
                                <div class="col-sm-4 p-1">
                                    <div class="font-weight-bold"><?php echo esc_html__( 'User Role', 'hide-my-wp' ); ?>:</div>
                                    <div class="small text-black-50"><?php echo esc_html__( "Set the current user role.", 'hide-my-wp' ); ?></div>
                                </div>
                                <div class="col-sm-8 p-0 input-group">
                                    <input type="hidden" name="hmwp_details[user_role]" value="<?php echo esc_attr($view->user->details->user_role) ?>" class="selectpicker form-control mb-1" />
                                    <select class="selectpicker form-control mb-1" disabled="disabled">
										<?php

										$allroles = array();
										if ( function_exists( 'wp_roles' ) ) {
											$allroles = wp_roles()->get_names();
										}

										foreach ( $allroles as $role => $name ) {
											echo '<option value="' . esc_attr($role) . '" ' . selected( $role, strtolower( $view->user->details->user_role ), true ) . '>' . esc_html( $name ) . '</option>';
										} ?>

                                    </select>

                                </div>

                            </div>

                            <div class="col-sm-12 row py-3 mx-1 my-3">
                                <div class="col-sm-4 p-1">
                                    <div class="font-weight-bold"><?php echo esc_html__( 'Redirect After Login', 'hide-my-wp' ); ?>:</div>
                                    <div class="small text-black-50"><?php echo esc_html__( "Redirect user to a custom page after login.", 'hide-my-wp' ); ?></div>
                                </div>
                                <div class="col-sm-8 p-0 input-group mb-1">
                                    <select name="hmwp_details[redirect_to]" class="selectpicker form-control mb-1">
                                        <option value=""><?php echo esc_html__( "Dashboard", 'hide-my-wp' ) ?></option>
										<?php
										$pages = get_pages( array( 'number' => 50 ) );
										foreach ( $pages as $page ) {
											if ( $page->post_title <> '' ) {
												?>
                                                <option value="<?php echo esc_attr( $page->post_name ) ?>" <?php echo selected( $page->post_name, $view->user->details->redirect_to, true ) ?> ><?php echo esc_html( $page->post_title ) ?></option><?php
											}
										} ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-sm-12 row py-3 mx-1 my-3">
                                <div class="col-sm-4 p-1">
                                    <div class="font-weight-bold"><?php echo esc_html__( 'Expire Time', 'hide-my-wp' ); ?>:</div>
                                    <div class="small text-black-50"><?php echo esc_html__( "How long the temporary login will be available after the user first access.", 'hide-my-wp' ); ?></div>
                                </div>
                                <div class="col-sm-8 p-0 input-group mb-1">
                                    <select name="hmwp_details[expire]" class="selectpicker form-control mb-1">
										<?php
										foreach ( HMWP_Classes_ObjController::getClass( 'HMWP_Models_Templogin' )->expires as $key => $expire ) {
											?>
                                            <option value="<?php echo esc_attr( $key ) ?>" <?php echo selected( $key, $view->user->details->expire, true ) ?>><?php echo esc_html($expire['label']) ?></option><?php
										} ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-sm-12 row py-3 mx-1 my-3">
                                <div class="col-sm-4 p-1">
                                    <div class="font-weight-bold"><?php echo esc_html__( 'Language', 'hide-my-wp' ); ?>:</div>
                                    <div class="small text-black-50"><?php echo esc_html__( "Load custom language is WordPress local language is installed.", 'hide-my-wp' ); ?></div>
                                </div>
                                <div class="col-sm-8 p-0 input-group mb-1 p-1 py-3">
									<?php
									wp_dropdown_languages( array( 'name'     => 'hmwp_details[locale]',
									                              'selected' => $view->user->details->locale
									) );
									?>
                                </div>
                            </div>

                            <button type="submit" class="btn rounded-0 btn-success px-5 mx-3 save"><?php echo esc_html__( 'Save User', 'hide-my-wp' ) ?></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <script>
            (function ($) {
                $(document).ready(function () {
                    $('#hmwp_templogin_modal_edit').modal('show');
                });
            })(jQuery);
        </script>
	<?php } ?>

    <div id="hmwp_templogin_modal_new" class="modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo esc_html__( 'Add New Temporary Login User', 'hide-my-wp' ) ?>:</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST" class="col-12 m-0 p-1">
						<?php wp_nonce_field( 'hmwp_templogin_new', 'hmwp_nonce' ) ?>
                        <input type="hidden" name="action" value="hmwp_templogin_new"/>

                        <div class="col-sm-12 row py-3 mx-2 my-3">
                            <div class="col-sm-4 p-0 font-weight-bold">
								<?php echo esc_html__( 'Email', 'hide-my-wp' ); ?>: *
                            </div>
                            <div class="col-sm-8 p-0 input-group">
                                <input type="text" class="form-control " name="hmwp_details[user_email]" required/>
                            </div>
                        </div>

                        <div class="col-sm-12 row py-3 mx-2 my-3">
                            <div class="col-sm-4 p-0 font-weight-bold">
								<?php echo esc_html__( 'First Name', 'hide-my-wp' ); ?>:
                            </div>
                            <div class="col-sm-8 p-0 input-group">
                                <input type="text" class="form-control " name="hmwp_details[first_name]"/>
                            </div>
                        </div>

                        <div class="col-sm-12 row py-3 mx-2 my-3">
                            <div class="col-sm-4 p-0 font-weight-bold">
								<?php echo esc_html__( 'Last Name', 'hide-my-wp' ); ?>:
                            </div>
                            <div class="col-sm-8 p-0 input-group">
                                <input type="text" class="form-control " name="hmwp_details[last_name]"/>
                            </div>
                        </div>

						<?php if ( HMWP_Classes_Tools::isMultisites() ) {
							$sites = get_sites( array( 'number' => 10000, 'public' => 1, 'deleted' => 0, ) );
							if ( ! empty( $sites ) && count( $sites ) > 0 ) { ?>

                                <div class="col-sm-12 row py-3 mx-1 my-3">
                                    <div class="col-sm-4 p-1">
                                        <div class="font-weight-bold"><?php echo esc_html__( 'Website', 'hide-my-wp' ); ?>:</div>
                                        <div class="small text-black-50"><?php echo esc_html__( "Set the website you want this user to be created for.", 'hide-my-wp' ); ?></div>
                                    </div>
                                    <div class="col-sm-8 p-0 input-group">
                                        <select name="hmwp_details[blog_id]" class="selectpicker form-control mb-1">
											<?php foreach ( $sites as $site ) { ?>
                                                <option value="<?php echo esc_attr($site->blog_id) ?>"><?php echo esc_url($site->domain . $site->path) ?></option>
											<?php } ?>
                                        </select>
                                    </div>

                                </div>


							<?php } ?>
						<?php } ?>

                        <div class="col-sm-12 row py-3 mx-1 my-3">
                            <div class="col-sm-4 p-1">
                                <div class="font-weight-bold"><?php echo esc_html__( 'User Role', 'hide-my-wp' ); ?>:</div>
                                <div class="small text-black-50"><?php echo esc_html__( "Set the current user role.", 'hide-my-wp' ); ?></div>
                            </div>
                            <div class="col-sm-8 p-0 input-group">
                                <select name="hmwp_details[user_role]" class="selectpicker form-control mb-1">
									<?php

									$allroles = array();
									if ( function_exists( 'wp_roles' ) ) {
										$allroles = wp_roles()->get_names();
									}

									foreach ( $allroles as $role => $name ) {
										echo '<option value="' . esc_attr($role) . '" ' . selected( $role, HMWP_Classes_Tools::getOption( 'hmwp_templogin_role' ), true ) . '>' . esc_html( $name ) . '</option>';
									} ?>

                                </select>
                            </div>

                        </div>

                        <div class="col-sm-12 row py-3 mx-1 my-3">
                            <div class="col-sm-4 p-1">
                                <div class="font-weight-bold"><?php echo esc_html__( 'Redirect After Login', 'hide-my-wp' ); ?>:</div>
                                <div class="small text-black-50"><?php echo esc_html__( "Redirect user to a custom page after login.", 'hide-my-wp' ); ?></div>
                            </div>
                            <div class="col-sm-8 p-0 input-group mb-1">
                                <select name="hmwp_details[redirect_to]" class="selectpicker form-control mb-1">
                                    <option value=""><?php echo esc_html__( "Dashboard", 'hide-my-wp' ) ?></option>
									<?php
									$pages = get_pages( array( 'number' => 50 ) );
									foreach ( $pages as $page ) {
										if ( $page->post_title <> '' ) {
											?>
                                            <option value="<?php echo esc_attr( $page->post_name ) ?>" <?php echo selected( $page->post_name, HMWP_Classes_Tools::getOption( 'hmwp_templogin_redirect' ), true ) ?> ><?php echo esc_html( $page->post_title ) ?></option><?php
										}
									} ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-sm-12 row py-3 mx-1 my-3">
                            <div class="col-sm-4 p-1">
                                <div class="font-weight-bold"><?php echo esc_html__( 'Expire Time', 'hide-my-wp' ); ?>:</div>
                                <div class="small text-black-50"><?php echo esc_html__( "How long the temporary login will be available after the user first access.", 'hide-my-wp' ); ?></div>
                            </div>
                            <div class="col-sm-8 p-0 input-group mb-1">
                                <select name="hmwp_details[expire]" class="selectpicker form-control mb-1">
									<?php
									foreach ( HMWP_Classes_ObjController::getClass( 'HMWP_Models_Templogin' )->expires as $key => $expire ) {
										?>
                                        <option value="<?php echo esc_attr( $key ) ?>" <?php echo selected( $key, HMWP_Classes_Tools::getOption( 'hmwp_templogin_expires' ), true ) ?>><?php echo esc_html($expire['label']) ?></option><?php
									} ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-sm-12 row py-3 mx-1 my-3">
                            <div class="col-sm-4 p-1">
                                <div class="font-weight-bold"><?php echo esc_html__( 'Language', 'hide-my-wp' ); ?>:</div>
                                <div class="small text-black-50"><?php echo esc_html__( "Load custom language is WordPress local language is installed.", 'hide-my-wp' ); ?></div>
                            </div>
                            <div class="col-sm-8 p-0 input-group mb-1 p-1 py-3">
								<?php
								wp_dropdown_languages( array( 'name'     => 'hmwp_details[locale]',
								                              'selected' => get_locale()
								) );
								?>
                            </div>
                        </div>

                        <button type="submit" class="btn rounded-0 btn-success px-5 mx-3 save"><?php echo esc_html__( 'Create', 'hide-my-wp' ) ?></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

