<?php if(!isset($view)) return; ?>
<div class="card col-sm-12 m-0 mb-2 p-0 rounded-0">
    <div class="card-body f-gray-dark text-center">
        <h4 class="card-title"><?php echo esc_html__('Connect to Cloud', 'hide-my-wp'); ?></h4>
        <div class="border-top mt-3 pt-3"></div>
        <div class="card-text text-muted">
            <?php echo esc_html__('Get connected to the Cloud to initiate website security monitoring and implement a backup for your customized login to ensure prevention.', 'hide-my-wp') ?>
        </div>

        <div class="text-center my-4">
            <form method="POST">
                <?php wp_nonce_field('hmwp_reconnect', 'hmwp_nonce') ?>
                <input type="hidden" name="action" value="hmwp_reconnect"/>

                <div class="col-sm-12 my-3 p-0">
                    <button type="submit" class="btn rounded-0 btn-default px-5 save"><?php echo esc_html__('Activate', 'hide-my-wp'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
