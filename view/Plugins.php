<div id="hmw_wrap" class="d-flex flex-row my-3 bg-light">
    <?php echo $view->getAdminTabs(HMW_Classes_Tools::getValue('tab', 'hmw_permalinks')); ?>
    <div class="hmw_row d-flex flex-row bg-white px-3">
        <div class="hmw_col row justify-content-center flex-grow-1">
            <?php foreach ($view->plugins as $name => $plugin) { ?>
                <div class="card p-0 col-sm-5 mt-3 m-2">
                    <div class="card-body p-3">
                        <h3 class="card-title my-2"><a href="<?php echo $plugin['url']; ?>" class="text-link" target="_blank"><?php echo $plugin['title']; ?></a></h3>
                        <div class="card-text">
                            <a href="<?php echo $plugin['url']; ?>" target="_blank"><img class="col-sm-12 p-0" src="<?php echo $plugin['banner']; ?>"></a>
                        </div>
                        <div class="card-text small text-secondary my-2" style="min-height: 120px;"><?php echo $plugin['description']; ?></div>
                        <div class="card-footer text-right">
                            <a href="<?php echo $plugin['url']; ?>" class="btn rounded-0 btn-light" target="_blank"><?php _e('More details', _HMW_PLUGIN_NAME_) ?></a>
                            <?php if (!HMW_Classes_Tools::isPluginActive($plugin['path'])) { ?>
                                <button class="btn hmw_plugin_install rounded-0 btn-info wp-loading" data-plugin="<?php echo $name; ?>"><?php _e('Install Plugin', _HMW_PLUGIN_NAME_) ?></button>
                            <?php } else { ?>
                                <button class="btn rounded-0 plugin btn-default" disabled><?php _e('Plugin Installed', _HMW_PLUGIN_NAME_) ?></button>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            <?php } ?>

        </div>
        <div class="hmw_col hmw_col_side">
            <div class="card col-sm-12 p-0 mt-3">
                <div class="card-body f-gray-dark text-left">
                    <h3 class="panel-title"><?php _e('Plugins', _HMW_PLUGIN_NAME_); ?></h3>
                    <div class="text-info mt-3"><?php echo __("We are testing every week the latest version of these plugins and <strong>we make sure they are working with Hide My WP</strong> plugin.
                     <br /><br />You don't need to install all these plugin in your website. If you're already using a cache plugin you don't need to install another one. <strong>We recommend using only one cache plugin</strong>.
                     <br /><br />You can also install either <strong>iThemes Security</strong> plugin or <strong>Sucuri Security</strong> plugin to work with Hide My Wp plugin.
                     <br /><br />If your plugins directory is not writable you will need to install the plugins manually.", _HMW_PLUGIN_NAME_); ?>
                    </div>
                </div>
            </div>

            <?php echo $view->getView('Support') ?>

        </div>
    </div>
</div>