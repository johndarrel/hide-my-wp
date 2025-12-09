<?php defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' ); ?>
<?php if ( ! isset( $view ) ) { return; } ?>
<noscript>
    <style>#hmwp_wrap .tab-panel:not(.tab-panel-first) {
            display: block
        }</style>
</noscript>
<div id="hmwp_wrap" class="d-flex flex-row p-0 my-3">
	<?php echo $view->getAdminTabs( HMWP_Classes_Tools::getValue( 'page', 'hmwp_mapping' ) ); ?>
    <div class="hmwp_row d-flex flex-row p-0 m-0">
        <div class="hmwp_col flex-grow-1 p-0 pr-2 mr-2 mb-3">

            <form method="POST">
				<?php wp_nonce_field( 'hmwp_mappsettings', 'hmwp_nonce' ) ?>
                <input type="hidden" name="action" value="hmwp_mappsettings"/>
                <input type="hidden" name="hmwp_mapping_classes" value="1"/>

	            <?php do_action( 'hmwp_mapping_form_beginning' ) ?>

                <div id="text" class="card col-sm-12 p-0 m-0 tab-panel tab-panel-first">
                    <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__( 'Text Mapping', 'hide-my-wp' ); ?>
                        <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/text-mapping/' ) ?>" target="_blank" class="d-inline-block float-right mr-2" style="color: white"><i class="dashicons dashicons-editor-help"></i></a>
                    </h3>
					<?php if ( HMWP_Classes_Tools::getOption( 'hmwp_mode' ) == 'default' ) { ?>
                        <div class="card-body">
                            <div class="col-sm-12 border-0 py-3 mx-0 my-3 text-black-50 text-center">
								<?php echo sprintf( esc_html__( 'First, you need to activate the %sLite Mode%s', 'hide-my-wp' ), '<a href="' . HMWP_Classes_Tools::getSettingsUrl( 'hmwp_permalinks' ) . '">', '</a>' ) ?>
                            </div>
                        </div>
					<?php } else { ?>
                        <div class="card-body">
                            <div class="text-black-50"><?php echo esc_html__( 'Text Mapping only Classes, IDs, JS variables', 'hide-my-wp' ); ?></div>
                            <div class="text-black-50 py-2"><?php echo esc_html__( "After adding the classes, verify the frontend to ensure that your theme is not affected.", 'hide-my-wp' ); ?></div>

                            <div class="hmwp_text_mapping_group py-3">

                                <div class="border-bottom mb-2"></div>
								<?php
								$wpclasses                       = array();
								$wpclasses['wp-caption']         = 'caption';
								$wpclasses['wp-custom']          = 'custom';
								$wpclasses['wp-comment-cookies'] = 'comment-cookies';
								$wpclasses['wp-image']           = 'image';
								$wpclasses['wp-embed']           = 'embed';
								$wpclasses['wp-post']            = 'post';
								$wpclasses['wp-smiley']          = 'smiley';
								$wpclasses['wp-hooks']           = 'hooks';
								$wpclasses['wp-util']            = 'util';
								$wpclasses['wp-polyfill']        = 'polyfill';
								$wpclasses['wp-escape']          = 'escape';
								$wpclasses['wp-element']         = 'element';
								$wpclasses['wp-switch-editor']   = 'switch-editor';


								$hmwp_text_mapping = json_decode( HMWP_Classes_Tools::getOption( 'hmwp_text_mapping' ), true );
								if ( ! empty( $hmwp_text_mapping['from'] ) ) {
									foreach ( $hmwp_text_mapping['from'] as $index => $row ) {
										if ( isset( $wpclasses[ $hmwp_text_mapping['from'][ $index ] ] ) ) {
											unset( $wpclasses[ $hmwp_text_mapping['from'][ $index ] ] );
										}
										?>
                                        <div class="col-sm-12 hmwp_text_mapping row border-bottom border-light py-1 px-0 mx-0 my-0">
                                            <div class="hmwp_text_mapping_remove" onclick="jQuery(this).parent().remove()" title="<?php echo esc_html__( 'Remove' ) ?>">x</div>
                                            <div class="col-sm-6 py-0 px-0 input-group input-group">
                                                <input type="text" class="form-control" name="hmwp_text_mapping_from[]" value="<?php echo esc_attr( $hmwp_text_mapping['from'][ $index ] ) ?>" placeholder="Current Text ..."/>
                                                <div class="col-sm-1 py-2 px-0 text-center text-black-50" style="max-width: 30px"><?php echo '=>' ?></div>
                                            </div>
                                            <div class="col-sm-6 py-0 px-0 input-group input-group">
                                                <input type="text" class="form-control" name="hmwp_text_mapping_to[]" value="<?php echo esc_attr( $hmwp_text_mapping['to'][ $index ] ) ?>" placeholder="New Text ..."/>
                                            </div>
                                        </div>
										<?php
									}
								} ?>
                                <div class="col-sm-12 hmwp_text_mapping row border-bottom border-light py-1 px-0 mx-0 my-0">
                                    <div class="hmwp_text_mapping_remove" style="display: none" onclick="jQuery(this).parent().remove()" title="<?php echo esc_html__( 'Remove' ) ?>">x</div>
                                    <div class="col-sm-6 py-0 px-0 input-group input-group">
                                        <input type="text" class="form-control" name="hmwp_text_mapping_from[]" value="" placeholder="Current Text ..."/>
                                        <div class="col-sm-1 py-2 px-0 text-center text-black-50" style="max-width: 30px"><?php echo '=>' ?></div>
                                    </div>
                                    <div class="col-sm-6 py-0 px-0 input-group input-group">
                                        <input type="text" class="form-control" name="hmwp_text_mapping_to[]" value="" placeholder="New Text ..."/>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-12 row border-bottom border-light p-0 m-0">
                                <div class="col-sm-4 p-0 offset-4">
                                    <button type="button" class="col-sm-12 btn btn-default text-white add_text_mapping" onclick="jQuery('div.hmwp_text_mapping:last').clone().appendTo('div.hmwp_text_mapping_group'); jQuery('div.hmwp_text_mapping_remove').show(); jQuery('div.hmwp_text_mapping:last').find('div.hmwp_text_mapping_remove').hide(); jQuery('div.hmwp_text_mapping:last').find('input').val('')"><?php echo esc_html__( 'Add New Text', 'hide-my-wp' ) ?></button>
                                </div>
                            </div>

							<?php if ( ! empty( $wpclasses ) ) { ?>
                                <h5 class="text-black-50 text-center border-top pt-3 my-3"><?php echo esc_html__( 'Add common WordPress classes in text mapping', 'hide-my-wp' ); ?></h5>

                                <div class="col-sm-12 row p-0 m-0">
									<?php foreach ( $wpclasses as $from => $to ) { ?>
                                        <div class="col-4">
                                            <button type="button" class="btn btn-link btn-block btn-sm" style="min-width: 200px" onclick="jQuery('div.hmwp_text_mapping:last').find('input:first').val('<?php echo esc_attr( $from ) ?>'); jQuery('div.hmwp_text_mapping:last').find('input:last').val('<?php echo esc_attr( $to ) ?>'); jQuery(this).parent('div').hide(); jQuery('.add_text_mapping').trigger('click'); window.scrollTo(0, window.scrollY + 48)"><?php echo esc_html__( 'Add', 'hide-my-wp' ) ?> <?php echo esc_html( $from ) ?></button>
                                        </div>
									<?php } ?>
                                </div>

							<?php } ?>

							<?php
							//no rules made for windows and WP Engine is not loading htaccess anymore
							if ( ! HMWP_Classes_Tools::isWindows() && ! HMWP_Classes_Tools::isWpengine() ) { ?>
                                <div class="border-bottom mb-2 py-3"></div>

                                <div class="col-sm-12 row mb-1 ml-1 p-2">

                                    <div class="checker col-sm-12 row my-2 py-1">
                                        <div class="col-sm-12 p-0 switch switch-sm">
                                            <input type="hidden" name="hmwp_mapping_file" value="0"/>
                                            <input type="checkbox" id="hmwp_mapping_file" name="hmwp_mapping_file" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_mapping_file' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                            <label for="hmwp_mapping_file"><?php echo esc_html__( 'Text Mapping in CSS and JS files including cached files', 'hide-my-wp' ); ?>
                                                <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/text-mapping/#ghost-text-mapping-in-css-and-js-files' ) ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                                <span class="text-black-50 small">(<?php echo esc_html__( "not recommended", 'hide-my-wp' ); ?>)</span>
                                            </label>
                                            <div class="text-black-50 ml-5"><?php echo esc_html__( "Change the text in all CSS and JS files, including those in cached files generated by cache plugins.", 'hide-my-wp' ); ?></div>
                                            <div class="mt-1 ml-5 py-2 text-danger"><?php echo esc_html__( "Enabling this option may slow down the website, as CSS and JS files will load dynamically instead of through rewrites, allowing the text within them to be modified as needed.", 'hide-my-wp' ); ?></div>
                                        </div>
                                    </div>
                                </div>


								<?php if ( ! HMWP_Classes_Tools::isCachePlugin() ) { ?>
                                    <div class="col-sm-12 row mb-1 ml-1 p-2">
                                        <div class="checker col-sm-12 row my-2 py-1">
                                            <div class="col-sm-12 p-0 switch switch-sm">
                                                <input type="hidden" name="hmwp_file_cache" value="0"/>
                                                <input type="checkbox" id="hmwp_file_cache" name="hmwp_file_cache" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_file_cache' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                                <label for="hmwp_file_cache"><?php echo esc_html__( 'Optimize CSS and JS files', 'hide-my-wp' ); ?>
                                                    <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/text-mapping/#ghost-optimize-css-and-js-files' ) ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                                    <span class="text-black-50 small">(<?php echo esc_html__( "not recommended", 'hide-my-wp' ); ?>)</span>
                                                </label>
                                                <div class="text-black-50 ml-5"><?php echo esc_html__( 'Cache CSS, JS and Images to increase the frontend loading speed.', 'hide-my-wp' ); ?></div>
                                                <div class="text-black-50 ml-5"><?php echo sprintf( esc_html__( 'Check the website loading speed with %sPingdom Tool%s', 'hide-my-wp' ), '<a href="https://tools.pingdom.com/" target="_blank">', '</a>' ); ?></div>
                                            </div>
                                        </div>
                                    </div>
								<?php } ?>
							<?php } ?>

                        </div>
					<?php } ?>
                </div>

                <div id="url" class="card col-sm-12 p-0 m-0 tab-panel">
                    <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__( 'URL Mapping', 'hide-my-wp' ); ?>
                        <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/url-mapping/' ) ?>" target="_blank" class="d-inline-block float-right mr-2" style="color: white"><i class="dashicons dashicons-editor-help"></i></a>
                    </h3>
					<?php if ( HMWP_Classes_Tools::getOption( 'hmwp_mode' ) == 'default' ) { ?>
                        <div class="card-body">
                            <div class="col-sm-12 border-0 py-3 mx-0 my-3 text-black-50 text-center">
								<?php echo sprintf( esc_html__( 'First, you need to activate the %sLite Mode%s', 'hide-my-wp' ), '<a href="' . HMWP_Classes_Tools::getSettingsUrl( 'hmwp_permalinks' ) . '">', '</a>' ) ?>
                            </div>
                        </div>
					<?php } else { ?>
                        <div class="card-body">
                            <div class="text-black-50"><?php echo esc_html__( "Add a list of URLs you want to replace with new ones.", 'hide-my-wp' ); ?></div>
                            <div class="text-black-50 py-2"><?php echo esc_html__( "Be sure to include only internal URLs, and use relative paths whenever possible.", 'hide-my-wp' ); ?></div>

                            <div class="text-black-50 mt-4 font-weight-bold"><?php echo esc_html__( "Example:", 'hide-my-wp' ); ?></div>
                            <div class="text-black-50 row">
                                <div class="col-sm-1 font-weight-bold mr-0 pr-0" style="min-width: 70px;"><?php echo esc_html__( 'from', 'hide-my-wp' ) ?>:</div>
                                <div class="col-sm-10 m-0 p-0"><?php echo home_url() . '/' . HMWP_Classes_Tools::getOption( 'hmwp_themes_url' ) . '/' . substr( md5( str_replace( '%2F', '/', rawurlencode( get_template() ) ) ), 0, 10 ) . '/' . HMWP_Classes_Tools::getOption( 'hmwp_themes_style' ); ?></div>
                            </div>
                            <div class="text-black-50 row">
                                <div class="col-sm-1 font-weight-bold mr-0 pr-0" style="min-width: 70px;"><?php echo esc_html__( 'to', 'hide-my-wp' ) ?>:</div>
                                <div class="col-sm-10 m-0 p-0"><?php echo home_url( 'mystyle.css' ); ?></div>
                            </div>
                            <div class="text-black-50 my-2"><?php echo esc_html__( "or", 'hide-my-wp' ); ?></div>
                            <div class="text-black-50 row">
                                <div class="col-sm-1 font-weight-bold mr-0 pr-0" style="min-width: 70px;"><?php echo esc_html__( 'from', 'hide-my-wp' ) ?>:</div>
                                <div class="col-sm-10 m-0 p-0"><?php echo home_url() . '/' . HMWP_Classes_Tools::getOption( 'hmwp_themes_url' ) . '/'; ?></div>
                            </div>
                            <div class="text-black-50 row">
                                <div class="col-sm-1 font-weight-bold mr-0 pr-0" style="min-width: 70px;"><?php echo esc_html__( 'to', 'hide-my-wp' ) ?>:</div>
                                <div class="col-sm-10 m-0 p-0"><?php echo home_url( 'myassets/' ); ?></div>
                            </div>
                            <div class="hmwp_url_mapping_group py-3">
								<?php
								$hmwp_url_mapping = json_decode( HMWP_Classes_Tools::getOption( 'hmwp_url_mapping' ), true );
								if ( ! empty( $hmwp_url_mapping['from'] ) ) {
									foreach ( $hmwp_url_mapping['from'] as $index => $row ) {
										?>
                                        <div class="col-sm-12 hmwp_url_mapping row border-bottom border-light py-1 px-0 mx-0 my-0">
                                            <div class="hmwp_url_mapping_remove" onclick="jQuery(this).parent().remove()" title="<?php echo esc_html__( 'Remove' ) ?>">x</div>
                                            <div class="col-sm-6 py-0 px-0 input-group input-group">
                                                <input type="text" class="form-control" name="hmwp_url_mapping_from[]" value="<?php echo esc_attr( $hmwp_url_mapping['from'][ $index ] ) ?>" placeholder="Current URL ..."/>
                                                <div class="col-sm-1 py-2 px-0 text-center text-black-50" style="max-width: 30px"><?php echo '=>' ?></div>
                                            </div>
                                            <div class="col-sm-6 py-0 px-0 input-group input-group">
                                                <input type="text" class="form-control" name="hmwp_url_mapping_to[]" value="<?php echo esc_attr( $hmwp_url_mapping['to'][ $index ] ) ?>" placeholder="New URL ..."/>
                                            </div>
                                        </div>
										<?php
									}
								} ?>
                                <div class="col-sm-12 hmwp_url_mapping row border-bottom border-light py-1 px-0 mx-0 my-0">
                                    <div class="hmwp_url_mapping_remove" style="display: none" onclick="jQuery(this).parent().remove()" title="<?php echo esc_html__( 'Remove' ) ?>">x</div>
                                    <div class="col-sm-6 py-0 px-0 input-group input-group">
                                        <input type="text" class="form-control" name="hmwp_url_mapping_from[]" value="" placeholder="Current URL ..."/>
                                        <div class="col-sm-1 py-2 px-0 text-center text-black-50" style="max-width: 30px"><?php echo '=>' ?></div>
                                    </div>
                                    <div class="col-sm-6 py-0 px-0 input-group input-group">
                                        <input type="text" class="form-control" name="hmwp_url_mapping_to[]" value="" placeholder="New URL ..."/>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-12 row border-bottom border-light p-0 m-0">
                                <div class="col-sm-4 p-0 offset-4">
                                    <button type="button" class="col-sm-12 btn btn-default text-white" onclick="jQuery('div.hmwp_url_mapping:last').clone().appendTo('div.hmwp_url_mapping_group'); jQuery('div.hmwp_url_mapping_remove').show(); jQuery('div.hmwp_url_mapping:last').find('div.hmwp_url_mapping_remove').hide(); jQuery('div.hmwp_url_mapping:last').find('input').val('')"><?php echo esc_html__( 'Add New URL', 'hide-my-wp' ) ?></button>
                                </div>
                            </div>
                        </div>
					<?php } ?>
                </div>

                <div id="cdn" class="card col-sm-12 p-0 m-0 tab-panel">
                    <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__( 'CDN URL Mapping', 'hide-my-wp' ); ?>
                        <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/cdn-url-mapping/' ) ?>" target="_blank" class="d-inline-block float-right mr-2" style="color: white"><i class="dashicons dashicons-editor-help"></i></a>
                    </h3>
					<?php if ( HMWP_Classes_Tools::getOption( 'hmwp_mode' ) == 'default' ) { ?>
                        <div class="card-body">
                            <div class="col-sm-12 border-0 py-3 mx-0 my-3 text-black-50 text-center">
								<?php echo sprintf( esc_html__( 'First, you need to activate the %sLite Mode%s', 'hide-my-wp' ), '<a href="' . HMWP_Classes_Tools::getSettingsUrl( 'hmwp_permalinks' ) . '">', '</a>' ) ?>
                            </div>
                        </div>
					<?php } else { ?>
                        <div class="card-body">
                            <div class="text-black-50"><?php echo esc_html__( "Add the CDN URLs you're using in the cache plugin. ", 'hide-my-wp' ); ?></div>
                            <div class="text-black-50 py-2"><?php echo esc_html__( "Note that this option won't activate the CDN for your website, but it will update the custom paths if you've already set a CDN URL with another plugin.", 'hide-my-wp' ); ?></div>

                            <div class="hmwp_cdn_mapping_group py-3">
								<?php
								$hmwp_cdn_urls = json_decode( HMWP_Classes_Tools::getOption( 'hmwp_cdn_urls' ), true );
								if ( ! empty( $hmwp_cdn_urls ) ) {
									foreach ( $hmwp_cdn_urls as $index => $row ) {
										?>
                                        <div class="col-sm-12 hmwp_cdn_mapping row border-bottom border-light py-1 px-0 mx-0 my-0">
                                            <div class="hmwp_cdn_mapping_remove" onclick="jQuery(this).parent().remove()" title="<?php echo esc_html__( 'Remove' ) ?>">x</div>
                                            <div class="col-sm-12 py-0 px-0 input-group input-group">
                                                <input type="text" class="form-control" name="hmwp_cdn_urls[]" value="<?php echo esc_attr( $row ) ?>" placeholder="CDN URL ..."/>
                                            </div>
                                        </div>
										<?php
									}
								} ?>
                                <div class="col-sm-12 hmwp_cdn_mapping row border-bottom border-light py-1 px-0 mx-0 my-0">
                                    <div class="hmwp_cdn_mapping_remove" style="display: none" onclick="jQuery(this).parent().remove()" title="<?php echo esc_html__( 'Remove' ) ?>">x</div>
                                    <div class="col-sm-12 py-0 px-0 input-group input-group">
                                        <input type="text" class="form-control" name="hmwp_cdn_urls[]" value="" placeholder="CDN URL ..."/>
                                    </div>

                                </div>
                            </div>
                            <div class="col-sm-12 row border-bottom border-light p-0 m-0">
                                <div class="col-sm-4 p-0 offset-4">
                                    <button type="button" class="col-sm-12 btn btn-default text-white" onclick="jQuery('div.hmwp_cdn_mapping:last').clone().appendTo('div.hmwp_cdn_mapping_group'); jQuery('div.hmwp_cdn_mapping_remove').show(); jQuery('div.hmwp_cdn_mapping:last').find('div.hmwp_cdn_mapping_remove').hide(); jQuery('div.hmwp_cdn_mapping:last').find('input').val('')"><?php echo esc_html__( 'Add New CDN URL', 'hide-my-wp' ) ?></button>
                                </div>
                            </div>
                        </div>
					<?php } ?>
                </div>

	            <?php do_action( 'hmwp_mapping_form_end' ) ?>

                <?php if ( HMWP_Classes_Tools::getOption( 'test_frontend' ) || HMWP_Classes_Tools::getOption( 'logout' ) || HMWP_Classes_Tools::getOption( 'error' ) ) { ?>
                    <div class="col-sm-12 m-0 p-2">
                        <button type="submit" class="btn rounded-0 btn-success px-5 mr-5 save"><?php echo esc_html__( 'Save', 'hide-my-wp' ); ?></button>
                    </div>
				<?php } else { ?>
                    <div class="col-sm-12 m-0 p-2 bg-light text-center" style="position: fixed; bottom: 0; right: 0; z-index: 100; box-shadow: 0 0 8px -3px #444;">
                        <button type="submit" class="btn rounded-0 btn-success px-5 mr-5 save"><?php echo esc_html__( 'Save', 'hide-my-wp' ); ?></button>
                    </div>
				<?php } ?>

            </form>
        </div>
        <div class="hmwp_col hmwp_col_side p-0 pr-2 mr-2">
			<?php $view->show( 'blocks/ChangeCacheFiles' ); ?>
			<?php $view->show( 'blocks/SecurityCheck' ); ?>
        </div>

    </div>

</div>
