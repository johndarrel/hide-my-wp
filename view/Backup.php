<div id="hmw_wrap" class="d-flex flex-row my-3 bg-light">
    <?php echo $view->getAdminTabs(HMW_Classes_Tools::getValue('tab', 'hmw_permalinks')); ?>
    <div class="hmw_row d-flex flex-row bg-white px-3">
        <div class="hmw_col flex-grow-1 mr-3">

            <div class="card p-0 col-sm-12 tab-panel">
                <h3 class="card-title bg-brown text-white p-2"><?php _e('Backup/Restore Settings', _HMW_PLUGIN_NAME_); ?>:</h3>
                <div class="card-body">
                    <div class="text-black-50 mb-2"><?php _e('Click Backup and the download will start automatically. You can use the Backup for all your websites.', _HMW_PLUGIN_NAME_); ?></div>

                    <div class="hmw_settings_backup">
                        <form action="" target="_blank" method="POST">
                            <?php wp_nonce_field('hmw_backup', 'hmw_nonce'); ?>
                            <input type="hidden" name="action" value="hmw_backup"/>
                            <input type="submit" class="btn rounded-0 btn-light" name="hmw_backup" value="<?php _e('Backup Settings', _HMW_PLUGIN_NAME_) ?>"/>
                            <input type="button" class="btn rounded-0 btn-light hmw_restore" onclick="jQuery('.hmw_settings_restore').modal()" name="hmw_restore" value="<?php _e('Restore Settings', _HMW_PLUGIN_NAME_) ?>"/>
                        </form>
                    </div>


                    <!-- Modal -->
                    <div class="modal hmw_settings_restore"  tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" ><?php _e('Hide My Wp Restore', _HMW_PLUGIN_NAME_) ?></h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div><?php _e('Upload the file with the saved Hide My Wp Settings', _HMW_PLUGIN_NAME_) ?></div>
                                    <form action="" method="POST" enctype="multipart/form-data">
                                        <?php wp_nonce_field('hmw_restore', 'hmw_nonce'); ?>
                                        <input type="hidden" name="action" value="hmw_restore"/>
                                        <div class="py-2">
                                            <input type="file" name="hmw_options" id="favicon"/>
                                        </div>

                                        <input type="submit" style="margin-top: 10px;" class="btn rounded-0 btn-success" name="hmw_restore" value="<?php _e('Restore Backup', _HMW_PLUGIN_NAME_) ?>"/>
                                    </form>
                                </div>

                            </div>
                        </div>
                    </div>


                </div>
            </div>
        </div>
        <div class="hmw_col hmw_col_side">
            <div class="card col-sm-12 p-0">
                <div class="card-body f-gray-dark text-left">
                    <h3 class="panel-title"><?php _e('Backup Settings', _HMW_PLUGIN_NAME_); ?></h3>
                    <div class="text-info mt-3"><?php echo sprintf(__("It's important to <strong>save your settings every time you change them</strong>. You can use the backup to configure other websites you own.", _HMW_PLUGIN_NAME_), site_url()); ?>
                    </div>
                </div>
            </div>

            <?php echo $view->getView('Support') ?>

        </div>
    </div>
</div>