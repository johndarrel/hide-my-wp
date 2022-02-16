<?php if(!isset($view)) return; ?>
<div id="hmwp_wrap" class="d-flex flex-row p-0 my-3">
    <div class="hmwp_row d-flex flex-row p-0 m-0">
        <div class="hmwp_col flex-grow-1 px-0 py-0 mr-3 mb-3 bg-white">
            <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__('Recommended Plugins', 'hide-my-wp'); ?></h3>

            <div class="row row-cols-1 row-cols-md-3 px-1 mx-1" style="max-width: 1200px;">
                <?php foreach ($view->plugins as $name => $plugin) { ?>
                    <div class="col px-2 py-0 mb-5">
                        <div class="card h-100 p-0 shadow-0 rounded-0">
                            <div class="card-body m-0 p-0">
                                <h3 class="card-title my-2 p-2"><a href="<?php echo esc_url($plugin['url']); ?>" class="text-link" target="_blank"><?php echo esc_html($plugin['title']); ?></a></h3>
                                <div class="card-text">
                                    <a href="<?php echo esc_url($plugin['url']); ?>" target="_blank">
                                        <img class="col-sm-12 p-0" src="<?php echo esc_url($plugin['banner']); ?>" alt="" style="min-height: 100px;">
                                    </a>
                                </div>
                                <div class="card-text small text-secondary my-2  p-2" style="min-height: 120px;"><?php echo wp_kses_post($plugin['description']); ?></div>

                            </div>
                            <div class="card-footer text-right">
                                <a href="<?php echo esc_url($plugin['url']); ?>" class="btn rounded-0 btn-light" target="_blank"><?php echo esc_html__('More details', 'hide-my-wp') ?></a>
                                <?php if (!HMWP_Classes_Tools::isPluginActive($plugin['path'])) { ?>
                                    <a href="<?php echo esc_url($plugin['url']); ?>" target="_blank" class="btn rounded-0 btn-default text-white"><?php echo esc_html__('Go To Plugin', 'hide-my-wp') ?></a>
                                <?php } else { ?>
                                    <button class="btn rounded-0 plugin btn-light" disabled><?php echo esc_html__('Plugin Active', 'hide-my-wp') ?></button>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
        <div class="hmwp_col hmwp_col_side p-0 m-0 mr-2">
            <div class="card col-sm-12 m-0 p-0 rounded-0">
                <div class="card-body f-gray-dark text-left">
                    <h3 class="panel-title"><?php echo esc_html__('Plugins', 'hide-my-wp'); ?></h3>
                    <div class="text-info mt-3"><?php echo sprintf(
                        esc_html__("We test the latest versions of the plugins listed here every week to %s ensure they work with the %s plugin perfectly %s.
                     %s You don't need to add all these plugins to your website. If you're already using a cache plugin, you don't need to install another one. %s We recommend only using one cache plugin %s.
                     %s You can also install either the %s iThemes Security %s plugin or the %s Sucuri Security %s plugin to work with the %s plugin.", 'hide-my-wp'
                        ), '<strong>', HMWP_Classes_Tools::getOption('hmwp_plugin_name'), '</strong>', '<br /><br />', '<strong>', '</strong>', '<br /><br />', '<strong>', '</strong>', '<strong>', '</strong>', HMWP_Classes_Tools::getOption('hmwp_plugin_name')
                    ); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
