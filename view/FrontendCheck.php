<?php if ( HMW_Classes_Tools::getOption( 'test_frontend' ) && HMW_Classes_Tools::getOption( 'hmw_mode' ) <> 'default' ) {
    add_action( 'home_url', array(HMW_Classes_ObjController::getClass( 'HMW_Models_Rewrite' ), 'home_url'), PHP_INT_MAX, 1 );
    ?>
    <div class="col-sm-12 border-bottom border-light py-3 mx-0 my-3">

        <div class="col-sm-12 border-warning bg-light border py-3 mx-0 my-0">
            <h4><?php _e( 'Next Steps', _HMW_PLUGIN_NAME_ ); ?></h4>
            <div class="col-sm-12 text-center my-2">
                <button type="button" class="btn btn-lg btn-success frontend_test" data-remote="<?php echo home_url() . '/' . HMW_Classes_Tools::getOption( 'hmw_login_url' ) ?>" data-target="#frontend_test_modal" data-toggle="modal"><?php _e( 'Frontend Login Test', _HMW_PLUGIN_NAME_ ); ?></button>
            </div>

            <ol>
                <li><?php echo sprintf( __( "Run %sFrontend Login Test%s and login inside the popup. ", _HMW_PLUGIN_NAME_ ), '<strong>', '</strong>' ); ?></li>
                <li><?php _e( 'Make sure you follow the Hide My WP Ghost instructions before moving forward.', _HMW_PLUGIN_NAME_ ); ?></li>
                <li><?php _e( "If you're able to login, you've set the new paths correctly.", _HMW_PLUGIN_NAME_ ); ?></li>
                <li><?php _e( 'Do not logout from this browser until you are confident that the Login Page is working and you will be able to login again.', _HMW_PLUGIN_NAME_ ); ?></li>
                <li><?php echo sprintf( __( "If you can't configure Hide My WP Ghost, switch to Default mode and %scontact us%s.", _HMW_PLUGIN_NAME_ ), '<a href="https://hidemywpghost.com/contact/" target="_blank" >', '</a>' ); ?></li>
            </ol>

            <div class="wp-admin_warning col-sm-12 my-2 mt-4 text-danger p-0 text-center">
                <div class="my-1"><?php echo sprintf( __( "%sWARNING:%s Use the custom login URL to login to admin.", _HMW_PLUGIN_NAME_ ), '<span class="font-weight-bold">', '</span>' ); ?></div>
                <div class="mb-3"><?php echo sprintf( __( "Your login URL will be: %s In case you can't re-login, use the safe URL: %s", _HMW_PLUGIN_NAME_ ), '<strong>' . home_url() . '/' . HMW_Classes_Tools::getOption( 'hmw_login_url' ) . '</strong><br /><br />', "<strong><br />" . site_url() . "/wp-login.php?" . HMW_Classes_Tools::getOption( 'hmw_disable_name' ) . "=" . HMW_Classes_Tools::getOption( 'hmw_disable' ) . "</strong>" ); ?></div>
            </div>

            <div class="hmw_logout">
                <form method="POST">
					<?php wp_nonce_field( 'hmw_confirm', 'hmw_nonce' ); ?>
                    <input type="hidden" name="action" value="hmw_confirm"/>
                    <input type="submit" class="hmw_btn hmw_btn-success" value="<?php echo __( "Yes, it's working", _HMW_PLUGIN_NAME_ ) ?>"/>
                </form>
            </div>
            <div class="hmw_abort" style="display: inline-block; margin-left: 5px;">
                <form method="POST">
					<?php wp_nonce_field( 'hmw_abort', 'hmw_nonce' ); ?>
                    <input type="hidden" name="action" value="hmw_abort"/>
                    <input type="submit" class="hmw_btn hmw_btn-warning" value="<?php echo __( "No, abort", _HMW_PLUGIN_NAME_ ) ?>"/>
                </form>
            </div>
        </div>
        <div class="modal fade" id="frontend_test_modal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel"><?php _e( 'Frontend login Test', _HMW_PLUGIN_NAME_ ); ?></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <iframe class="modal-body" style="min-height: 500px;"></iframe>
                </div>
            </div>
        </div>
        <script>
            (function ($) {
                $('button.frontend_test').on('click', function () {
                    $($(this).data("target") + ' .modal-body').attr('src', $(this).data("remote"));
                });
            })(jQuery);

        </script>

    </div>
<?php } ?>
