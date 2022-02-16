<?php if(!isset($view)) return; ?>
<div class="card col-sm-12 p-0 m-0">
    <div class="card-body f-gray-dark text-center">
        <h3 class="card-title p-0 m-0"><?php echo esc_html__('Debug Mode', 'hide-my-wp'); ?></h3>
        <div class="col-sm-12 row mb-1 mx-0 p-0">
            <div class="col-sm-12 p-0 switch switch-sm">
                <form id="hmwp_devsettings" method="POST">
                    <?php wp_nonce_field('hmwp_devsettings', 'hmwp_nonce') ?>
                    <input type="hidden" name="action" value="hmwp_devsettings"/>
                    <div class="border-top mt-3 pt-3"></div>
                    <input type="hidden" name="hmwp_debug" value="0"/>
                    <input type="checkbox" id="hmwp_debug" name="hmwp_debug" onchange="jQuery('form#hmwp_devsettings').submit()" class="switch nopopup" <?php echo(HMWP_Classes_Tools::getOption('hmwp_debug') ? 'checked="checked"' : '') ?> value="1"/>
                    <label for="hmwp_debug"><?php echo esc_html__('Save Debug Log', 'hide-my-wp'); ?></label>
                    <div class="text-black-50"><?php echo esc_html__("Activate info and logs for debugging.", 'hide-my-wp'); ?></div>
                </form>

                <?php if (HMWP_Classes_Tools::getOption('hmwp_debug') ) { ?>
                    <form id="hmwp_devsettings" method="POST">
                        <?php wp_nonce_field('hmwp_devdownload', 'hmwp_nonce') ?>
                        <input type="hidden" name="action" value="hmwp_devdownload"/>
                        <button type="submit" class="btn btn-link"><?php echo esc_html__('Download Debug', 'hide-my-wp'); ?></button>
                    </form>
                <?php } ?>

            </div>
        </div>
    </div>
</div>
