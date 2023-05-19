<?php if(!isset($view)) return; ?>
<?php if (!HMWP_Classes_Tools::getOption('test_frontend') && HMWP_Classes_Tools::getOption('hmwp_mode') <> 'default' ) { ?>
<div class="card col-sm-12 m-0 mb-2 p-0 rounded-0">
    <div class="card-body f-gray-dark text-center">
        <h4 class="card-title"><?php echo esc_html__('Check Frontend Paths', 'hide-my-wp'); ?></h4>
        <div class="border-top mt-3 pt-3"></div>
        <div class="card-text text-muted">
            <?php echo esc_html__('Check if the website paths are working correctly.', 'hide-my-wp') ?>
        </div>

        <div class="text-center my-4">
            <div class="hmwp_confirm  my-2" style="display: inline-block; margin-right: 5px;">
                <form class="hmwp_frontendcheck_form" method="POST">
                    <?php wp_nonce_field('hmwp_frontendcheck', 'hmwp_nonce') ?>
                    <input type="hidden" name="action" value="hmwp_frontendcheck"/>
                    <button type="button" class="btn rounded-0 btn-default text-white px-4 frontend_test"><?php echo esc_html__('Frontend Test', 'hide-my-wp'); ?></button>
                </form>
            </div>
            <div id="hmwp_frontendcheck_content" class="my-3"></div>
        </div>
    </div>
</div>
<?php }?>