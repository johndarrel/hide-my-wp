<?php if(!isset($view)) return; ?>
<div class="card col-sm-12 m-0 mb-2 p-0 rounded-0">
    <div class="card-body f-gray-dark text-center">
        <h3 class="card-title"><?php echo esc_html__('Check Your Website', 'hide-my-wp'); ?></h3>
        <div class="border-top mt-3 pt-3"></div>
        <div class="card-text text-muted">
            <?php echo esc_html__('Check if your website is secured with the current settings.', 'hide-my-wp') ?>
        </div>
        <div class="card-text text-info m-3">
            <a href="<?php echo HMWP_Classes_Tools::getSettingsUrl('hmwp_securitycheck', true) ?>" class="btn rounded-0 btn-sidebar text-white px-4 securitycheck"><?php echo esc_html__('Security Check', 'hide-my-wp'); ?></a>
        </div>
        <div class="card-text text-muted small">
            <?php echo esc_html__('Make sure you save the settings and empty the cache before checking your website with our tool.', 'hide-my-wp') ?>
        </div>

        <div class="card-text m-3 ">
            <a class="bigbutton text-center" href="<?php echo HMWP_Classes_Tools::getOption('hmwp_plugin_website') ?>" target="_blank"><?php echo esc_html__("Learn more about", 'hide-my-wp') . ' ' . HMWP_Classes_Tools::getOption('hmwp_plugin_name'); ?></a>
        </div>
    </div>
</div>
