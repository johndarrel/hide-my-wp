<?php if(!isset($view)) return; ?>
<?php
wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false);
wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false);

$page = HMWP_Classes_Tools::getValue('page');
$sorted = get_user_option("meta-box-order_$page");
if(!$sorted) {
    $sorted = array('hmwp_securitycheck_widget,hmwp_features_widget');
}
?>

<script>

    jQuery(document).ready(function(){
        if(typeof postboxes !== 'undefined') {
            postboxes.add_postbox_toggles('<?php echo HMWP_Classes_Tools::getValue('page') ?>');
        }
    });

</script>

<style>
    .hmwp_feature .dashicons-before:before{
        font-size: 1.9rem;
    }
</style>

<div id="hmwp_wrap" class="d-flex flex-row my-2 ">
    <div class="hmwp_row d-flex flex-row px-0">
            <?php do_action('hmwp_notices'); ?>

            <div class="hmwp_col flex-grow-1 mr-2 meta-box-sortables">
                <?php
                foreach ( $sorted as $box_context => $ids ) {
                    foreach (explode(',', $ids) as $id) {
                        if ($id == 'hmwp_securitycheck_widget') {
                            ?> <div id="hmwp_securitycheck_widget" class="card col-sm-12 p-0 m-0 mb-3 border-0 bg-white postbox <?php echo postbox_classes('hmwp_securitycheck_widget', $page) ?>">
                                <div class="postbox-header hmwp_header">
                                    <h3 class="card-title p-2 m-0 hndle"><?php echo esc_html__('Security Status', 'hide-my-wp'); ?></h3>
                                    <div class="handle-actions hide-if-no-js">
                                        <button type="button" class="handlediv" aria-expanded="true">
                                            <span class="toggle-indicator" aria-hidden="true"></span>
                                        </button>
                                    </div>
                                </div>
                                <div class="inside">
                                    <div class="card-body p-0">
                                        <?php HMWP_Classes_ObjController::getClass('HMWP_Controllers_Widget')->dashboard(); ?>
                                    </div>
                                </div>
                            </div> <?php
                        } elseif ($id == 'hmwp_features_widget') {
                            ?> <div id="hmwp_features_widget"
                                 class="card col-sm-12 p-0 m-0 mb-3 border-0 bg-white postbox <?php echo postbox_classes('hmwp_features_widget', $page) ?>">
                                <div class="postbox-header hmwp_header">
                                    <h3 class="card-title p-2 m-0 hndle"><?php echo HMWP_Classes_Tools::getOption('hmwp_plugin_name') . ' ' . esc_html__('Features', 'hide-my-wp'); ?></h3>
                                    <div class="handle-actions hide-if-no-js">
                                        <button type="button" class="handlediv" aria-expanded="true">
                                            <span class="toggle-indicator" aria-hidden="true"></span>
                                        </button>
                                    </div>
                                </div>

                                <div id="features" class="inside">
                                    <div class="card-body p-0">
                                        <?php defined('ABSPATH') || die('Cheatin\' uh?'); ?>
                                        <?php $features = $view->getFeatures(); ?>
                                        <div class="hmwp_features m-0 p-0">

                                            <div class="row row-cols-1 row-cols-md-3 px-1 mx-1">
                                                <?php foreach ($features as $index => $feature) {

                                                    if (isset($feature['show']) && !$feature['show']) {
                                                        continue;
                                                    }

                                                    ?>
                                                    <div class="col px-2 py-0 mb-5">
                                                        <div id="hmwp_feature_<?php echo esc_attr($index) ?>" class="hmwp_feature card h-100 p-0 shadow-0 rounded-0 <?php echo ($feature['free'] ?  (!$feature['active'] ? 'bg-light' : 'active') :  'hmwp_pro') ?>">
                                                            <div class="card-body m-0 p-0">
                                                                <div class="m-0 p-0 text-center">
                                                                    <div class="m-0 py-4 <?php echo esc_attr($feature['logo']) ?>"  style="font-size: 1.9rem; line-height: 30px; color:#71512794; width: 30px; height: 80px; margin: 0 auto !important;"></div>
                                                                    <h5 class="py-0  m-0">
                                                                        <?php if($feature['link'] ) { ?>
                                                                            <a href="<?php echo esc_url($feature['link']) ?>" class="text-dark" style="text-decoration: none"><?php echo wp_kses_post($feature['title']) ?></a>
                                                                        <?php }else{ ?>
                                                                            <?php echo wp_kses_post($feature['title']) ?>
                                                                        <?php }?>
                                                                    </h5>
                                                                </div>
                                                                <div class="mx-3 my-3 p-0 text-black" style="min-height: 60px; font-size: 1.1rem;">
                                                                    <div class="pt-3 pb-1 small" style="color: #696868">
                                                                        <?php echo wp_kses_post($feature['description']) ?>
                                                                        <?php if ($feature['link']) { ?>
                                                                            <div class="col-12 p-0 pt-2">
                                                                                <?php if ($feature['free']) { ?>
                                                                                    <?php if ($feature['optional']) { ?>
                                                                                        <a href="<?php echo esc_url($feature['link']) ?>" class="small see_feature" <?php echo($feature['active'] ? '' : 'style="display:none;"') ?>>
                                                                                            <?php echo esc_html__("start feature setup", 'hide-my-wp') ?> >>
                                                                                        </a>
                                                                                    <?php } else { ?>
                                                                                        <a href="<?php echo esc_url($feature['link']) ?>" class="small see_feature">
                                                                                            <?php echo esc_html__("see feature", 'hide-my-wp') ?>  >>
                                                                                        </a>
                                                                                    <?php } ?>
                                                                                <?php } ?>

                                                                            </div>
                                                                        <?php } ?>

                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="card-footer p-0 m-0">
                                                                <div class="row m-0 p-0">
                                                                    <div class="col-7 px-2 py-1 m-0 align-middle text-left" style="line-height: 30px">
	                                                                    <?php if ($feature['free']) { ?>
                                                                            <?php if ($feature['optional']) { ?>
                                                                                <form class="ajax_submit" method="POST">
                                                                                    <?php wp_nonce_field('hmwp_feature_save', 'hmwp_nonce') ?>
                                                                                    <input type="hidden" name="action" value="hmwp_feature_save"/>
                                                                                    <input type="hidden" name="<?php echo esc_attr($feature['option']) ?>" value="0"/>
                                                                                    <div class="checker col-sm-3 row m-0 p-0 ">
                                                                                        <div class="p-0 switch switch-sm text-right">
                                                                                            <input type="checkbox" id="activate_<?php echo esc_attr($index) ?>" name="<?php echo esc_attr($feature['option']) ?>" <?php echo($feature['active'] ? 'checked="checked"' : '') ?> class="switch" value="1"/>
                                                                                            <label for="activate_<?php echo esc_attr($index) ?>" class="m-0"></label>
                                                                                        </div>
                                                                                    </div>
                                                                                </form>

                                                                            <?php } else { ?>

                                                                                <?php if ($feature['active']) { ?>
                                                                                    <div class="p-0 m-0 small align-middle text-left text-success">
                                                                                        <?php echo esc_html__("already active", 'hide-my-wp') ?>
                                                                                    </div>
                                                                                <?php } else { ?>
                                                                                    <div class="p-0 m-0 align-middle text-left">
                                                                                        <a href="<?php echo esc_url($feature['link']) ?>" class="btn btn-sm btn-default small"><?php echo esc_html__("activate feature", 'hide-my-wp') ?></a>
                                                                                    </div>
                                                                                <?php } ?>

                                                                            <?php } ?>
	                                                                    <?php }else{ ?>
                                                                            <div class="p-0 m-0 small align-middle text-left text-warning" onclick="jQuery('#hmwp_ghost_mode_modal').modal('show')" >
			                                                                    <?php echo esc_html__("PRO", 'hide-my-wp') ?>
                                                                            </div>
	                                                                    <?php } ?>
                                                                    </div>
                                                                    <div class="col-5 p-2 m-0 align-middle text-right">
                                                                        <?php if ($feature['details']) { ?>
                                                                            <a href="<?php echo esc_url($feature['details']) ?>" target="_blank">
                                                                                <?php echo esc_html__("help", 'hide-my-wp') ?>
                                                                                <i class="dashicons dashicons-editor-help m-0 px-2" style="display: inline; font-size: 1.1rem !important;"></i>
                                                                            </a>
                                                                        <?php } ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>


                                                    </div>
                                                <?php } ?>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div> <?php
                        }
                    }
                }?>

            </div>

            <div class="hmwp_col hmwp_col_side mr-2">

                <?php $view->show('blocks/ChangeCacheFiles'); ?>
                <?php $view->show('blocks/SecurityCheck'); ?>
                <?php $view->show('blocks/FrontendCheck'); ?>
                <?php
                    if (!HMWP_Classes_Tools::getOption('api_token')){
                        $view->show('blocks/Connect');
                    }
                ?>

            </div>
        </div>
</div>
