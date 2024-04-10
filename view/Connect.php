<?php if(!isset($view)) return; ?>
<div id="hmwp_wrap" class="d-flex flex-row p-0 my-3">
    <div class="hmwp_row d-flex flex-row p-0 m-0">
        <?php do_action('hmwp_notices'); ?>
        <div class="hmwp_col flex-grow-1 px-3 py-3 mr-2 mb-3 bg-white">
            <form method="POST">
                <?php wp_nonce_field('hmwp_connect', 'hmwp_nonce') ?>
                <input type="hidden" name="action" value="hmwp_connect"/>

                <?php do_action('hmwp_form_notices'); ?>
                <div class="card p-0 col-sm-12">
                    <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__('Activate Free Token', 'hide-my-wp'); ?></h3>
                    <div class="card-body">

                        <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                            <div class="col-sm-6 p-1 font-weight-bold">
                                <?php echo esc_html__('Email Address', 'hide-my-wp'); ?>:
                                <div class="small text-black-50"><?php echo sprintf(esc_html__('Enter the 32 chars token from Order/Licence on %s', 'hide-my-wp'), '<a href="' . _HMWP_ACCOUNT_SITE_ . '/user/auth/orders" target="_blank" style="font-weight: bold">' . _HMWP_ACCOUNT_SITE_ . '</a>'); ?></div>
                            </div>
                            <div class="col-sm-6 p-0 input-group ">
	                            <?php
                                $email = HMWP_Classes_Tools::getOption( 'hmwp_email_address' );
                                if ( $email == '' ) {
                                    global $current_user;
                                    $email = $current_user->user_email;
                                }
                                ?>
                                <input type="text" class="form-control" name="hmwp_email" value="<?php echo $email ?>" placeholder="<?php echo $email ?>"/>
                            </div>
                        </div>

                        <div class="col-sm-12 row mb-1 ml-1 hmwp_howtolessons_div">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmwp_monitor" value="0"/>
                                    <input type="checkbox" id="hmwp_monitor" name="hmwp_monitor" class="switch" checked="checked" value="1"/>
                                    <label for="hmwp_monitor"><?php _e( 'Monitor my website, send me security alerts and vulnerability reports', 'hide-my-wp' ); ?></label>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="col-sm-12 my-3 p-0">
                    <button type="submit" class="btn rounded-0 btn-success btn-lg px-5 save"><?php echo esc_html__('Activate', 'hide-my-wp'); ?></button>
                </div>
            </form>

            <form method="POST">
		        <?php wp_nonce_field( 'hmwp_dont_connect', 'hmwp_nonce' ) ?>
                <input type="hidden" name="action" value="hmwp_dont_connect"/>
                <button type="submit" class="btn rounded-0 float-right btn-link btn-lg px-3" style="position: relative;margin-top: -65px; color: gray;"><?php _e( 'Skip Activation', 'hide-my-wp' ); ?></button>
            </form>

        </div>
        <div class="hmwp_col hmwp_col_side p-0 m-0 mr-2">
            <div class="card col-sm-12 m-0 p-0 rounded-0">
                <div class="card-body f-gray-dark text-left border-bottom">
                    <h3 class="card-title"><?php echo esc_html__('Activation Help', 'hide-my-wp'); ?></h3>
                    <div class="text-info">
		                <?php echo sprintf( esc_html__( "By activating the Free version of %s you agree with our %sTerms of Use%s and %sPrivacy Policy%s", 'hide-my-wp' ), HMWP_Classes_Tools::getOption('hmwp_plugin_name'), '<a href="https://hidemywpghost.com/terms-of-use/" target="_blank">', '</a>', '<a href="https://hidemywpghost.com/privacy-policy/" target="_blank">', '</a>' ); ?>
                    </div>
                    <div class="text-info mt-3">
		                <?php echo esc_html__( 'Note! If you add your email you will receive a free token which will activate the plugin.', 'hide-my-wp' ); ?>
                    </div>
                    <div class="text-danger mt-2">
		                <?php echo sprintf( esc_html__( "If you bought %s please remove this plugin and install the one from %sYour Account%s", 'hide-my-wp' ), HMWP_Classes_Tools::getOption('hmwp_plugin_name'), '<a href="https://account.hidemywpghost.com/user/" target="_blank">', '</a>' ); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
