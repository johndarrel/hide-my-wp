<?php if(!isset($view)) return; ?>
<div id="hmwp_wrap" class="d-flex flex-row p-0 my-3">
    <div class="hmwp_row d-flex flex-row p-0 m-0">
        <div class="hmwp_col flex-grow-1 px-3 py-3 mr-2 mb-3 bg-white">

            <div class="card col-sm-12 p-0 m-0">
                <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__('Backup/Restore Settings', 'hide-my-wp'); ?></h3>
                <div class="card-body">
                    <div class="text-black-50 mb-2"><?php echo esc_html__('Click Backup and the download will start automatically. You can use the Backup for all your websites.', 'hide-my-wp'); ?></div>

                    <div class="hmwp_settings_backup">
                        <form action="" target="_blank" method="POST">
                            <?php wp_nonce_field('hmwp_backup', 'hmwp_nonce'); ?>
                            <input type="hidden" name="action" value="hmwp_backup"/>
                            <button type="submit" class="btn rounded-0 btn-default" name="hmwp_backup" ><?php echo esc_html__('Backup Settings', 'hide-my-wp') ?></button>
                            <button type="button" class="btn rounded-0 btn-light hmwp_restore hmwp_modal" onclick="jQuery('#hmwp_settings_restore').modal('show')" name="hmwp_restore"><?php echo esc_html__('Restore Settings', 'hide-my-wp') ?></button>
                        </form>
                    </div>

                    <!-- Modal -->
                    <div id="hmwp_settings_restore" class="modal hmwp_settings_restore"  tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" ><?php echo esc_html__('Restore Settings', 'hide-my-wp') ?></h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div><?php echo esc_html__('Upload the file with the saved plugin settings', 'hide-my-wp') ?></div>
                                    <form action="" method="POST" enctype="multipart/form-data">
                                        <?php wp_nonce_field('hmwp_restore', 'hmwp_nonce'); ?>
                                        <input type="hidden" name="action" value="hmwp_restore"/>
                                        <div class="py-2">
                                            <input type="file" name="hmwp_options" id="favicon"/>
                                        </div>

                                        <input type="submit" style="margin-top: 10px;" class="btn rounded-0 btn-default" name="hmwp_restore" value="<?php echo esc_html__('Restore Backup', 'hide-my-wp') ?>"/>
                                    </form>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <div class="card col-sm-12 p-0 m-0 mt-3">
                <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__('Reset Settings', 'hide-my-wp'); ?></h3>
                <div class="card-body">
                    <div class="hmwp_settings_rollback">
                        <div class="text-black-50 mb-2"><?php echo esc_html__('Rollback all the plugin settings to initial values.', 'hide-my-wp'); ?></div>
                        <form method="POST">
                            <?php wp_nonce_field('hmwp_rollback', 'hmwp_nonce'); ?>
                            <input type="hidden" name="action" value="hmwp_rollback"/>
                            <input type="submit" class="btn rounded-0 btn-default" name="hmwp_backup"  onclick="return confirm('<?php echo esc_html__('Are you sure you want to reset the settings to their initial values?','hide-my-wp') ?>');" value="<?php echo esc_html__('Reset Settings', 'hide-my-wp') ?>"/>
                        </form>
                    </div>
                </div>
            </div>

            <div class="card col-sm-12 p-0 m-0 mt-3">
                <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__('Rollback to version', 'hide-my-wp') . ' ' . HMWP_STABLE_VERSION ?></h3>
                <div class="card-body">
                    <div class="hmwp_settings_rollback">
                        <div class="text-black-50 mb-2"><?php echo esc_html__('Install the last stable version of the plugin.', 'hide-my-wp'); ?></div>
                        <form method="POST">
					        <?php wp_nonce_field('hmwp_rollback_stable', 'hmwp_nonce'); ?>
                            <input type="hidden" name="action" value="hmwp_rollback_stable"/>
                            <input type="submit" class="btn rounded-0 btn-default" name="hmwp_backup"  onclick="return confirm('<?php echo esc_html__('Are you sure you want to rollback to the previous version of the plugin?','hide-my-wp') ?>');" value="<?php echo esc_html__('Rollback Now', 'hide-my-wp') ?>"/>
                        </form>
                    </div>
                </div>
            </div>

        </div>
        <div class="hmwp_col hmwp_col_side p-0 m-0 mr-2">
            <div class="card col-sm-12 m-0 p-0 rounded-0">
                <div class="card-body f-gray-dark text-left">
                    <h3 class="panel-title"><?php echo esc_html__('Backup Settings', 'hide-my-wp'); ?></h3>
                    <div class="text-info mt-3"><?php echo sprintf(esc_html__("It's important to %s save your settings every time you change them %s. You can use the backup to configure other websites you own.", 'hide-my-wp'), '<strong>' , '</strong>'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
