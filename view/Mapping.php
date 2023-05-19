<?php if(!isset($view)) return; ?>
<noscript> <style>#hmwp_wrap .tab-panel:not(.tab-panel-first){display: block}</style> </noscript>
<div id="hmwp_wrap" class="d-flex flex-row p-0 my-3">
<?php echo $view->getAdminTabs(HMWP_Classes_Tools::getValue('page', 'hmwp_mapping')); ?>
    <div class="hmwp_row d-flex flex-row p-0 m-0">
        <div class="hmwp_col flex-grow-1 px-3 py-3 mr-2 mb-3 bg-white">

            <form method="POST">
                <?php wp_nonce_field('hmwp_mappsettings', 'hmwp_nonce') ?>
                <input type="hidden" name="action" value="hmwp_mappsettings"/>

                    <div id="text" class="card col-sm-12 p-0 m-0 tab-panel tab-panel-first">
                        <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__('Text Mapping', 'hide-my-wp'); ?>
                            <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/url-mapping-text-mapping/#text_mapping') ?>" target="_blank" class="d-inline-block ml-2" style="color: white"><i class="dashicons dashicons-editor-help"></i></a>
                        </h3>
                        <?php if (HMWP_Classes_Tools::getOption('hmwp_mode') == 'default' ) { ?>
                            <div class="card-body">
                                <div class="col-sm-12 border-0 py-3 mx-0 my-3 text-black-50 text-center">
                                    <?php echo sprintf(esc_html__('First, you need to activate the %sLite Mode%s', 'hide-my-wp'), '<a href="'.HMWP_Classes_Tools::getSettingsUrl('hmwp_permalinks').'">', '</a>') ?>
                                </div>
                            </div>
                        <?php } else { ?>
                            <div class="card-body">
                            <div class="col-sm-12border-bottom border-light py-3 mx-0 ">
                                <div class="p-0">
                                    <div class="text-black-50"><?php echo esc_html__('Replace the text in tags and classes to hide any WordPress footprint.', 'hide-my-wp'); ?></div>
                                    <div class="my-2 p-2 alert-danger">
                                        <?php echo esc_html__("Your plugins and themes may use these classes and it will affect the design and functionality.", 'hide-my-wp'); ?>
                                        <div class="py-1"><?php echo esc_html__('Read tutorial', 'hide-my-wp') ?>: <a href="https://hidemywpghost.com/hiding-plugins-like-woocommerce-and-elementor/" target="_blank">Hiding plugins like WooCommerce and Elementor</a></div>
                                    </div>
                                </div>

                            </div>

                            <div class="hmwp_text_mapping_group py-3">
                                <div class="col-sm-12 row mb-1 ml-1 p-2">
                                    <div class="checker col-sm-12 row my-2 py-1">
                                        <div class="col-sm-12 p-0 switch switch-sm">
                                            <input type="hidden" name="hmwp_mapping_classes" value="0"/>
                                            <input type="checkbox" id="hmwp_mapping_classes" name="hmwp_mapping_classes" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_mapping_classes') ? 'checked="checked"' : '') ?> value="1"/>
                                            <label for="hmwp_mapping_classes"><?php echo esc_html__('Text Mapping only Classes, IDs, JS variables', 'hide-my-wp'); ?>
                                                <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/url-mapping-text-mapping/#text_mapping_style') ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                                <span class="text-black-50 small">(<?php echo esc_html__("recommended", 'hide-my-wp'); ?>)</span>
                                            </label>
                                            <div class="offset-1 text-black-50"><?php echo esc_html__("Change the text only in classes, styles & scrips. (Recommended ON)", 'hide-my-wp'); ?></div>
                                            <div class="offset-1 text-black-50"><?php echo esc_html__("If this option is switched off, the text is changed brutally in source-code.", 'hide-my-wp'); ?></div>
                                            <div class="offset-1 text-danger my-2"><?php echo esc_html__("Avoid using text mapping for commonly used paths such as wp-content, wp-admin, wp-includes because it can cause errors. Instead, use the 'Change Paths' feature in the Hide My Wp plugin to safely hide these paths.", 'hide-my-wp'); ?></div>
                                        </div>
                                    </div>
                                </div>


                                <div class="border-bottom mb-2"></div>
                                <?php
                                $wpclasses = array();
                                $wpclasses['wp-caption'] = 'caption';
                                $wpclasses['wp-custom'] = 'custom';
                                $wpclasses['wp-comment-cookies'] = 'comment-cookies';
                                $wpclasses['wp-image'] = 'image';
                                $wpclasses['wp-embed'] = 'embed';
                                $wpclasses['wp-post'] = 'post';
                                $wpclasses['wp-smiley'] = 'smiley';
                                $wpclasses['wp-hooks'] = 'hooks';
                                $wpclasses['wp-util'] = 'util';
                                $wpclasses['wp-polyfill'] = 'polyfill';
                                $wpclasses['wp-escape'] = 'escape';
                                $wpclasses['wp-element'] = 'element';
                                $wpclasses['wp-switch-editor'] = 'switch-editor';


                                $hmwp_text_mapping = json_decode(HMWP_Classes_Tools::getOption('hmwp_text_mapping'), true);
                                if (isset($hmwp_text_mapping['from']) && !empty($hmwp_text_mapping['from']) ) {
                                    foreach ( $hmwp_text_mapping['from'] as $index => $row ) {
                                        if(isset($wpclasses[$hmwp_text_mapping['from'][$index]])) {
                                            unset($wpclasses[$hmwp_text_mapping['from'][$index]]);
                                        }
                                        ?>
                                        <div class="col-sm-12 hmwp_text_mapping row border-bottom border-light py-1 px-0 mx-0 my-0">
                                            <div class="hmwp_text_mapping_remove" onclick="jQuery(this).parent().remove()" title="<?php echo esc_html__('Remove') ?>">x</div>
                                            <div class="col-sm-6 py-0 px-0 input-group input-group">
                                                <input type="text" class="form-control bg-input" name="hmwp_text_mapping_from[]" value="<?php echo esc_attr($hmwp_text_mapping['from'][$index]) ?>" placeholder="Current Text ..."/>
                                                <div class="col-sm-1 py-2 px-0 text-center text-black-50" style="max-width: 30px"><?php echo '=>' ?></div>
                                            </div>
                                            <div class="col-sm-6 py-0 px-0 input-group input-group">
                                                <input type="text" class="form-control bg-input" name="hmwp_text_mapping_to[]" value="<?php echo esc_attr($hmwp_text_mapping['to'][$index]) ?>" placeholder="New Text ..."/>
                                            </div>
                                        </div>
                                        <?php
                                    }
                                } ?>
                                <div class="col-sm-12 hmwp_text_mapping row border-bottom border-light py-1 px-0 mx-0 my-0">
                                    <div class="hmwp_text_mapping_remove" style="display: none" onclick="jQuery(this).parent().remove()" title="<?php echo esc_html__('Remove') ?>">x</div>
                                    <div class="col-sm-6 py-0 px-0 input-group input-group">
                                        <input type="text" class="form-control bg-input" name="hmwp_text_mapping_from[]" value="" placeholder="Current Text ..."/>
                                        <div class="col-sm-1 py-2 px-0 text-center text-black-50" style="max-width: 30px"><?php echo '=>' ?></div>
                                    </div>
                                    <div class="col-sm-6 py-0 px-0 input-group input-group">
                                        <input type="text" class="form-control bg-input" name="hmwp_text_mapping_to[]" value="" placeholder="New Text ..."/>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-12 row border-bottom border-light p-0 m-0">
                                <div class="col-sm-4 p-0 offset-4">
                                    <button type="button" class="col-sm-12 btn btn-default text-white add_text_mapping" onclick="jQuery('div.hmwp_text_mapping:last').clone().appendTo('div.hmwp_text_mapping_group'); jQuery('div.hmwp_text_mapping_remove').show(); jQuery('div.hmwp_text_mapping:last').find('div.hmwp_text_mapping_remove').hide(); jQuery('div.hmwp_text_mapping:last').find('input').val('')"><?php echo esc_html__('Add another text', 'hide-my-wp') ?></button>
                                </div>
                            </div>


                                <?php if(!empty($wpclasses)) {?>
                                    <h5 class="text-black-50 text-center border-top pt-3 my-3"><?php echo esc_html__('Add common WordPress classes in text mapping', 'hide-my-wp'); ?></h5>

                                    <div class="col-sm-12 row p-0 m-0">
                                        <?php foreach ($wpclasses as $from => $to){ ?>
                                            <div class="col">
                                                <button type="button" class="btn btn-link btn-block btn-sm" style="min-width: 120px" onclick="jQuery('div.hmwp_text_mapping:last').find('input:first').val('<?php echo esc_attr($from) ?>'); jQuery('div.hmwp_text_mapping:last').find('input:last').val('<?php echo esc_attr($to) ?>'); jQuery(this).prop('disabled',true); jQuery('.add_text_mapping').trigger('click');"><?php echo esc_html__('Add', 'hide-my-wp') ?> <?php echo esc_html($from) ?></button>
                                            </div>
                                        <?php }?>
                                    </div>

                                    <div class="p-2 alert-danger text-center mt-3 small"><?php echo esc_html__("Verify the frontend after adding the classes to make sure the theme you're using is not affected.", 'hide-my-wp'); ?></div>
                                <?php }?>
                            </div>
                        <?php }?>
                    </div>
                    <div id="url" class="card col-sm-12 p-0 m-0 tab-panel">
                            <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__('URL Mapping', 'hide-my-wp'); ?>
                                <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/url-mapping-text-mapping/#url_mapping') ?>" target="_blank" class="d-inline-block ml-2" style="color: white"><i class="dashicons dashicons-editor-help"></i></a>
                            </h3>
                            <?php if (HMWP_Classes_Tools::getOption('hmwp_mode') == 'default' ) { ?>
                                <div class="card-body">
                                    <div class="col-sm-12 border-0 py-3 mx-0 my-3 text-black-50 text-center">
                                        <?php echo sprintf(esc_html__('First, you need to activate the %sLite Mode%s', 'hide-my-wp'), '<a href="'.HMWP_Classes_Tools::getSettingsUrl('hmwp_permalinks').'">', '</a>') ?>
                                    </div>
                                </div>
                            <?php } else { ?>
                                <div class="card-body">
                                <div class="text-black-50"><?php echo esc_html__("You can add a list of URLs you want to change into new ones. ", 'hide-my-wp'); ?></div>
                                <div class="my-2 p-2 alert-danger">
                                    <?php echo sprintf(esc_html__("It's important to only include internal URLs from Frontend source code after you activate the Lite Mode within %s.", 'hide-my-wp'), HMWP_Classes_Tools::getOption('hmwp_plugin_name')); ?>
                                </div>
                                <div class="text-black-50 mt-4 font-weight-bold"><?php echo esc_html__("Example:", 'hide-my-wp'); ?></div>
                                <div class="text-black-50 row">
                                    <div class="col-sm-1 font-weight-bold mr-0 pr-0" style="min-width: 70px;"><?php echo esc_html__('from', 'hide-my-wp') ?>:</div>
                                    <div class="col-sm-10 m-0 p-0"><?php echo home_url() . '/' . HMWP_Classes_Tools::getOption('hmwp_themes_url') . '/' . substr(md5(str_replace('%2F', '/', rawurlencode(get_template()))), 0, 10) . '/' . HMWP_Classes_Tools::getOption('hmwp_themes_style'); ?></div>
                                </div>
                                <div class="text-black-50 row">
                                    <div class="col-sm-1 font-weight-bold mr-0 pr-0" style="min-width: 70px;"><?php echo esc_html__('to', 'hide-my-wp') ?>:</div>
                                    <div class="col-sm-10 m-0 p-0"><?php echo home_url('mystyle.css'); ?></div>
                                </div>
                                <div class="text-black-50 my-2"><?php echo esc_html__("or", 'hide-my-wp'); ?></div>
                                <div class="text-black-50 row">
                                    <div class="col-sm-1 font-weight-bold mr-0 pr-0" style="min-width: 70px;"><?php echo esc_html__('from', 'hide-my-wp') ?>:</div>
                                    <div class="col-sm-10 m-0 p-0"><?php echo home_url() . '/' . HMWP_Classes_Tools::getOption('hmwp_themes_url') . '/'; ?></div>
                                </div>
                                <div class="text-black-50 row">
                                    <div class="col-sm-1 font-weight-bold mr-0 pr-0" style="min-width: 70px;"><?php echo esc_html__('to', 'hide-my-wp') ?>:</div>
                                    <div class="col-sm-10 m-0 p-0"><?php echo home_url('myassets/'); ?></div>
                                </div>
                                <div class="hmwp_url_mapping_group py-3">
                                    <?php
                                    $hmwp_url_mapping = json_decode(HMWP_Classes_Tools::getOption('hmwp_url_mapping'), true);
                                    if (isset($hmwp_url_mapping['from']) && !empty($hmwp_url_mapping['from']) ) {
                                        foreach ( $hmwp_url_mapping['from'] as $index => $row ) {
                                            ?>
                                            <div class="col-sm-12 hmwp_url_mapping row border-bottom border-light py-1 px-0 mx-0 my-0">
                                                <div class="hmwp_url_mapping_remove" onclick="jQuery(this).parent().remove()" title="<?php echo esc_html__('Remove') ?>">x</div>
                                                <div class="col-sm-6 py-0 px-0 input-group input-group">
                                                    <input type="text" class="form-control bg-input" name="hmwp_url_mapping_from[]" value="<?php echo esc_attr($hmwp_url_mapping['from'][$index]) ?>" placeholder="Current URL ..."/>
                                                    <div class="col-sm-1 py-2 px-0 text-center text-black-50" style="max-width: 30px"><?php echo '=>' ?></div>
                                                </div>
                                                <div class="col-sm-6 py-0 px-0 input-group input-group">
                                                    <input type="text" class="form-control bg-input" name="hmwp_url_mapping_to[]" value="<?php echo esc_attr($hmwp_url_mapping['to'][$index]) ?>" placeholder="New URL ..."/>
                                                </div>
                                            </div>
                                            <?php
                                        }
                                    } ?>
                                    <div class="col-sm-12 hmwp_url_mapping row border-bottom border-light py-1 px-0 mx-0 my-0">
                                        <div class="hmwp_url_mapping_remove" style="display: none" onclick="jQuery(this).parent().remove()" title="<?php echo esc_html__('Remove') ?>">x</div>
                                        <div class="col-sm-6 py-0 px-0 input-group input-group">
                                            <input type="text" class="form-control bg-input" name="hmwp_url_mapping_from[]" value="" placeholder="Current URL ..."/>
                                            <div class="col-sm-1 py-2 px-0 text-center text-black-50" style="max-width: 30px"><?php echo '=>' ?></div>
                                        </div>
                                        <div class="col-sm-6 py-0 px-0 input-group input-group">
                                            <input type="text" class="form-control bg-input" name="hmwp_url_mapping_to[]" value="" placeholder="New URL ..."/>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-12 row border-bottom border-light p-0 m-0">
                                    <div class="col-sm-4 p-0 offset-4">
                                        <button type="button" class="col-sm-12 btn btn-default text-white" onclick="jQuery('div.hmwp_url_mapping:last').clone().appendTo('div.hmwp_url_mapping_group'); jQuery('div.hmwp_url_mapping_remove').show(); jQuery('div.hmwp_url_mapping:last').find('div.hmwp_url_mapping_remove').hide(); jQuery('div.hmwp_url_mapping:last').find('input').val('')"><?php echo esc_html__('Add another URL', 'hide-my-wp') ?></button>
                                    </div>
                                </div>
                            </div>
                            <?php }?>
                        </div>
                    <div id="cdn" class="card col-sm-12 p-0 m-0 tab-panel">
                        <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__('CDN URLs', 'hide-my-wp'); ?>
                            <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/url-mapping-text-mapping/#cdn_urls') ?>" target="_blank" class="d-inline-block ml-2" style="color: white"><i class="dashicons dashicons-editor-help"></i></a>
                        </h3>
                        <?php if (HMWP_Classes_Tools::getOption('hmwp_mode') == 'default' ) { ?>
                            <div class="card-body">
                                <div class="col-sm-12 border-0 py-3 mx-0 my-3 text-black-50 text-center">
	                                <?php echo sprintf(esc_html__('First, you need to activate the %sLite Mode%s', 'hide-my-wp'), '<a href="'.HMWP_Classes_Tools::getSettingsUrl('hmwp_permalinks').'">', '</a>') ?>
                                </div>
                            </div>
                        <?php } else { ?>
                            <div class="card-body">
                            <div class="text-black-50"><?php echo esc_html__("You can add one or more CDN URLs you use.", 'hide-my-wp'); ?></div>
                            <div class="my-2 p-2 alert-danger"><?php echo esc_html__("This option will not activate the CDN option for your website but it will change the custom paths in case you already set a CDN URL with another plugin.", 'hide-my-wp'); ?></div>

                            <div class="hmwp_cdn_mapping_group py-3">
                                <?php
                                $hmwp_cdn_urls = json_decode(HMWP_Classes_Tools::getOption('hmwp_cdn_urls'), true);
                                if (!empty($hmwp_cdn_urls) ) {
                                    foreach ( $hmwp_cdn_urls as $index => $row ) {
                                        ?>
                                        <div class="col-sm-12 hmwp_cdn_mapping row border-bottom border-light py-1 px-0 mx-0 my-0">
                                            <div class="hmwp_cdn_mapping_remove" onclick="jQuery(this).parent().remove()" title="<?php echo esc_html__('Remove') ?>">x</div>
                                            <div class="col-sm-12 py-0 px-0 input-group input-group">
                                                <input type="text" class="form-control bg-input" name="hmwp_cdn_urls[]" value="<?php echo esc_attr($row) ?>" placeholder="CDN URL ..."/>
                                            </div>
                                        </div>
                                        <?php
                                    }
                                } ?>
                                <div class="col-sm-12 hmwp_cdn_mapping row border-bottom border-light py-1 px-0 mx-0 my-0">
                                    <div class="hmwp_cdn_mapping_remove" style="display: none" onclick="jQuery(this).parent().remove()" title="<?php echo esc_html__('Remove') ?>">x</div>
                                    <div class="col-sm-12 py-0 px-0 input-group input-group">
                                        <input type="text" class="form-control bg-input" name="hmwp_cdn_urls[]" value="" placeholder="CDN URL ..."/>
                                    </div>

                                </div>
                            </div>
                            <div class="col-sm-12 row border-bottom border-light p-0 m-0">
                                <div class="col-sm-4 p-0 offset-4">
                                    <button type="button" class="col-sm-12 btn btn-default text-white" onclick="jQuery('div.hmwp_cdn_mapping:last').clone().appendTo('div.hmwp_cdn_mapping_group'); jQuery('div.hmwp_cdn_mapping_remove').show(); jQuery('div.hmwp_cdn_mapping:last').find('div.hmwp_cdn_mapping_remove').hide(); jQuery('div.hmwp_cdn_mapping:last').find('input').val('')"><?php echo esc_html__('Add another CDN URL', 'hide-my-wp') ?></button>
                                </div>
                            </div>
                        </div>
                        <?php }?>
                    </div>
                    <div id="experimental" class="card col-sm-12 p-0 m-0 tab-panel">
                        <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__('Loading Speed Settings', 'hide-my-wp'); ?></h3>
                        <?php if (HMWP_Classes_Tools::getOption('hmwp_mode') == 'default' ) { ?>
                            <div class="card-body">
                                <div class="col-sm-12 border-0 py-3 mx-0 my-3 text-black-50 text-center">
	                                <?php echo sprintf(esc_html__('First, you need to activate the %sLite Mode%s', 'hide-my-wp'), '<a href="'.HMWP_Classes_Tools::getSettingsUrl('hmwp_permalinks').'">', '</a>') ?>
                                </div>
                            </div>
                        <?php } else { ?>
                         <div class="card-body">
                            <?php if (!HMWP_Classes_Tools::isIIS() ) { ?>
                                <div class="col-sm-12 row mb-1 ml-1 p-2">
                                    <div class="checker col-sm-12 row my-2 py-1">
                                        <div class="col-sm-12 p-0 switch switch-sm">
                                            <input type="hidden" name="hmwp_file_cache" value="0"/>
                                            <input type="checkbox" id="hmwp_file_cache" name="hmwp_file_cache" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_file_cache') ? 'checked="checked"' : '') ?> value="1"/>
                                            <label for="hmwp_file_cache"><?php echo esc_html__('Optimize CSS and JS files', 'hide-my-wp'); ?>
                                                <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/url-mapping-text-mapping/#optimize_css') ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                                <span class="text-black-50 small">(<?php echo esc_html__("not recommended", 'hide-my-wp'); ?>)</span> </label>
                                            </label>
                                            <div class="offset-1 text-black-50"><?php echo esc_html__('Cache CSS, JS and Images to increase the frontend loading speed.', 'hide-my-wp'); ?></div>
                                            <div class="offset-1 text-black-50"><?php echo sprintf(esc_html__('Check the website loading speed with %sPingdom Tool%s', 'hide-my-wp'), '<a href="https://tools.pingdom.com/" target="_blank">', '</a>'); ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>

                            <?php if (HMWP_Classes_Tools::isApache() || HMWP_Classes_Tools::isLitespeed() ) { ?>
                                <div class="col-sm-12 row mb-1 ml-1 p-2 hmwp_pro">
                                    <div class="box" >
                                        <div class="ribbon"><span><?php echo esc_html__( 'PRO', 'hide-my-wp' ) ?></span></div>
                                    </div>
                                    <div class="checker col-sm-12 row my-2 py-1" style="opacity: 0.3" onclick="jQuery('#hmwp_ghost_mode_modal').modal('show')">
                                       <div class="col-sm-12 p-0 switch switch-sm switch-red">
                                            <input type="hidden" name="hmwp_mapping_file" value="0"/>
                                            <input type="checkbox" id="hmwp_mapping_file" name="hmwp_mapping_file" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_mapping_file') ? 'checked="checked"' : '') ?> value="1"/>
                                            <label for="hmwp_mapping_file"><?php echo esc_html__('Text Mapping in CSS and JS files including caches', 'hide-my-wp'); ?>
                                               <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/url-mapping-text-mapping/#text_mapping_files') ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                               <span class="text-black-50 small">(<?php echo esc_html__("not recommended", 'hide-my-wp'); ?>)</span> </label>
                                            </label>
                                            <div class="offset-1 text-black-50"><?php echo esc_html__("Change the text in all CSS and JS files including cached files generated by cache plugins.", 'hide-my-wp'); ?></div>
                                            <div class="offset-1 mt-1 p-2 alert-danger"><?php echo esc_html__("If you switch this option on, it will significantly slow down the website as CSS and JS files are loaded dynamically and not through rewrites to be able to change the text within all of them", 'hide-my-wp'); ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                        <?php }?>
                    </div>
                    <?php if (HMWP_Classes_Tools::getOption('test_frontend') || HMWP_Classes_Tools::getOption('logout') || HMWP_Classes_Tools::getOption('error') ) { ?>
                        <div class="col-sm-12 m-0 p-2">
                            <button type="submit" class="btn rounded-0 btn-success px-5 mr-5 save"><?php echo esc_html__('Save', 'hide-my-wp'); ?></button>
                        </div>
                    <?php } else { ?>
                        <div class="col-sm-12 m-0 p-2 bg-light text-center" style="position: fixed; bottom: 0; right: 0; z-index: 100; box-shadow: 0 0 8px -3px #444;">
                            <button type="submit" class="btn rounded-0 btn-success px-5 mr-5 save"><?php echo esc_html__('Save', 'hide-my-wp'); ?></button>
                        </div>
                    <?php } ?>

            </form>
        </div>
        <div class="hmwp_col hmwp_col_side p-0 m-0 mr-2">
            <?php $view->show('blocks/ChangeCacheFiles'); ?>
            <?php $view->show('blocks/SecurityCheck'); ?>
        </div>

    </div>

</div>
