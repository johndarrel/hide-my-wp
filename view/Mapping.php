<div id="hmw_wrap" class="d-flex flex-row my-3 bg-light">
	<?php echo $view->getAdminTabs( HMW_Classes_Tools::getValue( 'tab', 'hmw_permalinks' ) ); ?>
    <div class="hmw_row d-flex flex-row bg-white px-3">
        <div class="hmw_col flex-grow-1 mr-3">
	        <?php echo $view->getView( 'FrontendCheck' ); ?>

            <form method="POST">
				<?php wp_nonce_field( 'hmw_mappsettings', 'hmw_nonce' ) ?>
                <input type="hidden" name="action" value="hmw_mappsettings"/>

                <div class="card p-0 col-sm-12 tab-panel">
                    <h3 class="card-title bg-brown text-white p-2"><?php _e( 'Text Mapping', _HMW_PLUGIN_NAME_ ); ?>:
                        <a href="https://hidemywpghost.com/kb/url-mapping-text-mapping/#text_mapping" target="_blank" class="d-inline-block ml-2" style="color: white"><i class="fa fa-question-circle"></i></a>
                    </h3>
                    <div class="card-body">

						<?php if ( HMW_Classes_Tools::getOption( 'hmw_mode' ) == 'default' ) { ?>
                            <div class="col-sm-12 border-bottom border-light py-3 mx-0 my-3 text-black-50 text-center">
								<?php echo __( 'First, you need to switch Hide My Wp from Default mode to Safe Mode or Ghost Mode.', _HMW_PLUGIN_NAME_ ) ?>
                            </div>
						<?php } else { ?>
                            <div class="col-sm-12 row border-bottom border-light py-3 mx-0 ">
                                <div class="p-0">
                                    <div class="text-black-50"><?php _e( 'Replace the text in tags and classes to hide any WordPress footprint.', _HMW_PLUGIN_NAME_ ); ?>:</div>
                                    <div class="text-black-50"><?php _e( "Note! Your plugins and themes may use these and it will affect the design and functionality.", _HMW_PLUGIN_NAME_ ); ?></div>
                                </div>

                            </div>


                            <div class="hmw_text_mapping_group py-3">
                                <div class="col-sm-12 row mb-1 ml-1">
                                    <div class="checker col-sm-12 row my-2 py-1">
                                        <div class="col-sm-12 p-0 switch switch-sm">
                                            <input type="hidden" name="hmw_mapping_classes" value="0"/>
                                            <input type="checkbox" id="hmw_mapping_classes" name="hmw_mapping_classes" class="switch" <?php echo(HMW_Classes_Tools::getOption( 'hmw_mapping_classes' ) ? 'checked="checked"' : '') ?> value="1"/>
                                            <label for="hmw_mapping_classes"><?php _e( 'Text Mapping only Classes, IDs, JS variables', _HMW_PLUGIN_NAME_ ); ?></label>
                                            <a href="https://hidemywpghost.com/kb/url-mapping-text-mapping/#text_mapping_style" target="_blank" class="d-inline-block ml-2"><i class="fa fa-question-circle"></i></a>
                                            <div class="offset-1 text-black-50"><?php _e( "Change the text only in classes, styles & scrips. (Recommended ON)", _HMW_PLUGIN_NAME_ ); ?></div>
                                            <div class="offset-1 text-black-50"><?php _e( "If this option is switched off, the text is changed in all page", _HMW_PLUGIN_NAME_ ); ?></div>
                                        </div>
                                    </div>
                                </div>

								<?php
								$hmw_text_mapping = json_decode( HMW_Classes_Tools::getOption( 'hmw_text_mapping' ), true );
								if ( isset( $hmw_text_mapping['from'] ) && ! empty( $hmw_text_mapping['from'] ) ) {
									foreach ( $hmw_text_mapping['from'] as $index => $row ) {
										?>
                                        <div class="col-sm-12 hmw_text_mapping row border-bottom border-light py-1 px-0 mx-0 my-0">
                                            <div class="hmw_text_mapping_remove" onclick="jQuery(this).parent().remove()" title="<?php echo __( 'Remove Text Map', _HMW_PLUGIN_NAME_ ) ?>">x</div>
                                            <div class="col-sm-6 py-1 px-0 input-group input-group">
                                                <input type="text" class="form-control bg-input" name="hmw_text_mapping_from[]" value="<?php echo $hmw_text_mapping['from'][ $index ] ?>" placeholder="Current Text ..."/>
                                                <div class="col-sm-1 py-2 px-0 text-center text-black-50" style="max-width: 30px"><?php echo '=>' ?></div>
                                            </div>
                                            <div class="col-sm-6 py-1 px-0 input-group input-group">
                                                <input type="text" class="form-control bg-input" name="hmw_text_mapping_to[]" value="<?php echo $hmw_text_mapping['to'][ $index ] ?>" placeholder="New Text ..."/>
                                            </div>
                                        </div>
										<?php
									}
								} ?>
                                <div class="col-sm-12 hmw_text_mapping row border-bottom border-light py-1 px-0 mx-0 my-0">
                                    <div class="hmw_text_mapping_remove" style="display: none" onclick="jQuery(this).parent().remove()" title="<?php echo __( 'Remove Text Map', _HMW_PLUGIN_NAME_ ) ?>">x</div>
                                    <div class="col-sm-6 py-1 px-0 input-group input-group">
                                        <input type="text" class="form-control bg-input" name="hmw_text_mapping_from[]" value="" placeholder="Current Text ..."/>
                                        <div class="col-sm-1 py-2 px-0 text-center text-black-50" style="max-width: 30px"><?php echo '=>' ?></div>
                                    </div>
                                    <div class="col-sm-6 py-1 px-0 input-group input-group">
                                        <input type="text" class="form-control bg-input" name="hmw_text_mapping_to[]" value="" placeholder="New Text ..."/>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-12 row border-bottom border-light p-0 m-0">
                                <div class="col-sm-4 p-0 offset-4">
                                    <button type="button" class="col-sm-12 btn btn-sm btn-warning text-white" onclick="jQuery('div.hmw_text_mapping:last').clone().appendTo('div.hmw_text_mapping_group'); jQuery('div.hmw_text_mapping_remove').show(); jQuery('div.hmw_text_mapping:last').find('div.hmw_text_mapping_remove').hide()"><?php echo __( 'Add another text', _HMW_PLUGIN_NAME_ ) ?></button>
                                </div>
                            </div>

						<?php } ?>
                    </div>
                </div>
                <div class="card p-0 col-sm-12 tab-panel">
                    <h3 class="card-title bg-brown text-white p-2"><?php _e( 'URL Mapping', _HMW_PLUGIN_NAME_ ); ?>:
                        <a href="https://hidemywpghost.com/kb/url-mapping-text-mapping/#url_mapping" target="_blank" class="d-inline-block ml-2" style="color: white"><i class="fa fa-question-circle"></i></a>
                    </h3>
                    <div class="card-body">
                        <div class="box" data-toggle="popover" data-html="true" data-placement="top" data-content="<?php echo sprintf( __( 'This feature requires %sHide My WP Ghost%s.', _HMW_PLUGIN_NAME_ ), "<a href='https://hidemywpghost.com/hide-my-wp-pricing/' target='_blank'>", "</a>" ) ?>">
                            <div class="ribbon"><span><?php echo __( 'PRO', _HMW_PLUGIN_NAME_ ) ?></span></div>
                        </div>
                        <div style="opacity: 0.3">

							<?php if ( HMW_Classes_Tools::getOption( 'hmw_mode' ) == 'default' ) { ?>
                                <div class="col-sm-12 border-bottom border-light py-3 mx-0 my-3 text-black-50 text-center">
									<?php echo __( 'First, you need to switch Hide My Wp from Default mode to Safe Mode or Ghost Mode.', _HMW_PLUGIN_NAME_ ) ?>
                                </div>
							<?php } else { ?>
                                <div class="text-black-50"><?php echo __( "You can add a list of URLs you want to change into new ones. ", _HMW_PLUGIN_NAME_ ); ?></div>
                                <div class="text-black-50"><?php echo __( "It's important to include only internal URLs from Frontend source code after you activate the plugin in Safe Mode or Ghost Mode.", _HMW_PLUGIN_NAME_ ); ?></div>
                                <div class="text-black-50 mt-4 font-weight-bold"><?php echo __( "Example:", _HMW_PLUGIN_NAME_ ); ?></div>
                                <div class="text-black-50 row">
                                    <div class="col-sm-1 font-weight-bold mr-0 pr-0" style="min-width: 70px;"><?php echo __( 'from', _HMW_PLUGIN_NAME_ ) ?>:</div>
                                    <div class="col-sm-10 m-0 p-0"><?php echo home_url() . '/' . HMW_Classes_Tools::getOption( 'hmw_themes_url' ) . '/' . substr( md5( str_replace( '%2F', '/', rawurlencode( get_template() ) ) ), 0, 10 ) . '/' . HMW_Classes_Tools::getOption( 'hmw_themes_style' ); ?></div>
                                </div>
                                <div class="text-black-50 row">
                                    <div class="col-sm-1 font-weight-bold mr-0 pr-0" style="min-width: 70px;"><?php echo __( 'to', _HMW_PLUGIN_NAME_ ) ?>:</div>
                                    <div class="col-sm-10 m-0 p-0"><?php echo home_url( 'mystyle.css' ); ?></div>
                                </div>
                                <div class="text-black-50 my-2"><?php echo __( "or", _HMW_PLUGIN_NAME_ ); ?></div>
                                <div class="text-black-50 row">
                                    <div class="col-sm-1 font-weight-bold mr-0 pr-0" style="min-width: 70px;"><?php echo __( 'from', _HMW_PLUGIN_NAME_ ) ?>:</div>
                                    <div class="col-sm-10 m-0 p-0"><?php echo home_url() . '/' . HMW_Classes_Tools::getOption( 'hmw_themes_url' ) . '/'; ?></div>
                                </div>
                                <div class="text-black-50 row">
                                    <div class="col-sm-1 font-weight-bold mr-0 pr-0" style="min-width: 70px;"><?php echo __( 'to', _HMW_PLUGIN_NAME_ ) ?>:</div>
                                    <div class="col-sm-10 m-0 p-0"><?php echo home_url( 'myassets/' ); ?></div>
                                </div>
                                <div class="hmw_url_mapping_group py-3">
									<?php
									$hmw_url_mapping = json_decode( HMW_Classes_Tools::getOption( 'hmw_url_mapping' ), true );
									if ( isset( $hmw_url_mapping['from'] ) && ! empty( $hmw_url_mapping['from'] ) ) {
										foreach ( $hmw_url_mapping['from'] as $index => $row ) {
											?>
                                            <div class="col-sm-12 hmw_url_mapping row border-bottom border-light py-1 px-0 mx-0 my-0">
                                                <div class="hmw_url_mapping_remove" onclick="jQuery(this).parent().remove()" title="<?php echo __( 'Remove URL Map', _HMW_PLUGIN_NAME_ ) ?>">x</div>
                                                <div class="col-sm-6 py-1 px-0 input-group input-group">
                                                    <input type="text" class="form-control bg-input" name="hmw_url_mapping_from[]" value="<?php echo $hmw_url_mapping['from'][ $index ] ?>" placeholder="Current URL ..."/>
                                                    <div class="col-sm-1 py-2 px-0 text-center text-black-50" style="max-width: 30px"><?php echo '=>' ?></div>
                                                </div>
                                                <div class="col-sm-6 py-1 px-0 input-group input-group">
                                                    <input type="text" class="form-control bg-input" name="hmw_url_mapping_to[]" value="<?php echo $hmw_url_mapping['to'][ $index ] ?>" placeholder="New URL ..."/>
                                                </div>
                                            </div>
											<?php
										}
									} ?>
                                    <div class="col-sm-12 hmw_url_mapping row border-bottom border-light py-1 px-0 mx-0 my-0">
                                        <div class="hmw_url_mapping_remove" style="display: none" onclick="jQuery(this).parent().remove()" title="<?php echo __( 'Remove URL Map', _HMW_PLUGIN_NAME_ ) ?>">x</div>
                                        <div class="col-sm-6 py-1 px-0 input-group input-group">
                                            <input type="text" class="form-control bg-input" name="hmw_url_mapping_from[]" value="" placeholder="Current URL ..."/>
                                            <div class="col-sm-1 py-2 px-0 text-center text-black-50" style="max-width: 30px"><?php echo '=>' ?></div>
                                        </div>
                                        <div class="col-sm-6 py-1 px-0 input-group input-group">
                                            <input type="text" class="form-control bg-input" name="hmw_url_mapping_to[]" value="" placeholder="New URL ..."/>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-12 row border-bottom border-light p-0 m-0">
                                    <div class="col-sm-4 p-0 offset-4">
                                        <button type="button" class="col-sm-12 btn btn-sm btn-warning text-white" onclick="jQuery('div.hmw_url_mapping:last').clone().appendTo('div.hmw_url_mapping_group'); jQuery('div.hmw_url_mapping_remove').show(); jQuery('div.hmw_url_mapping:last').find('div.hmw_url_mapping_remove').hide()"><?php echo __( 'Add another URL', _HMW_PLUGIN_NAME_ ) ?></button>
                                    </div>
                                </div>
							<?php } ?>
                        </div>
                    </div>
                </div>
                <div class="card p-0 col-sm-12 tab-panel">
                    <h3 class="card-title bg-brown text-white p-2"><?php _e( 'CDN URLs', _HMW_PLUGIN_NAME_ ); ?>:
                        <a href="https://hidemywpghost.com/kb/url-mapping-text-mapping/#cdn_urls" target="_blank" class="d-inline-block ml-2" style="color: white"><i class="fa fa-question-circle"></i></a>
                    </h3>
                    <div class="card-body">
                        <div class="box" data-toggle="popover" data-html="true" data-placement="top" data-content="<?php echo sprintf( __( 'This feature requires %sHide My WP Ghost%s.', _HMW_PLUGIN_NAME_ ), "<a href='https://hidemywpghost.com/hide-my-wp-pricing/' target='_blank'>", "</a>" ) ?>">
                            <div class="ribbon"><span><?php echo __( 'PRO', _HMW_PLUGIN_NAME_ ) ?></span></div>
                        </div>
                        <div style="opacity: 0.3">
							<?php if ( HMW_Classes_Tools::getOption( 'hmw_mode' ) == 'default' ) { ?>
                                <div class="col-sm-12 border-bottom border-light py-3 mx-0 my-3 text-black-50 text-center">
									<?php echo __( 'First, you need to switch Hide My Wp from Default mode to Safe Mode or Ghost Mode.', _HMW_PLUGIN_NAME_ ) ?>
                                </div>
							<?php } else { ?>
                                <div class="text-black-50"><?php echo __( "You can add one or more CDN URLs you use.", _HMW_PLUGIN_NAME_ ); ?></div>
                                <div class="text-black-50"><?php echo __( "This option will not activate the CDN option for your website but it will change the custom paths in case you already set a CDN URL with another plugin.", _HMW_PLUGIN_NAME_ ); ?></div>

                                <div class="hmw_cdn_mapping_group py-3">
									<?php
									$hmw_cdn_urls = json_decode( HMW_Classes_Tools::getOption( 'hmw_cdn_urls' ), true );
									if ( ! empty( $hmw_cdn_urls ) ) {
										foreach ( $hmw_cdn_urls as $index => $row ) {
											?>
                                            <div class="col-sm-12 hmw_cdn_mapping row border-bottom border-light py-1 px-0 mx-0 my-0">
                                                <div class="hmw_cdn_mapping_remove" onclick="jQuery(this).parent().remove()" title="<?php echo __( 'Remove CDN', _HMW_PLUGIN_NAME_ ) ?>">x</div>
                                                <div class="col-sm-12 py-1 px-0 input-group input-group">
                                                    <input type="text" class="form-control bg-input" name="hmw_cdn_urls[]" value="<?php echo $row ?>" placeholder="CDN URL ..."/>
                                                </div>
                                            </div>
											<?php
										}
									} ?>
                                    <div class="col-sm-12 hmw_cdn_mapping row border-bottom border-light py-1 px-0 mx-0 my-0">
                                        <div class="hmw_cdn_mapping_remove" style="display: none" onclick="jQuery(this).parent().remove()" title="<?php echo __( 'Remove CDN', _HMW_PLUGIN_NAME_ ) ?>">x</div>
                                        <div class="col-sm-12 py-1 px-0 input-group input-group">
                                            <input type="text" class="form-control bg-input" name="hmw_cdn_urls[]" value="" placeholder="CDN URL ..."/>
                                        </div>

                                    </div>
                                </div>
                                <div class="col-sm-12 row border-bottom border-light p-0 m-0">
                                    <div class="col-sm-4 p-0 offset-4">
                                        <button type="button" class="col-sm-12 btn btn-sm btn-warning text-white" onclick="jQuery('div.hmw_cdn_mapping:last').clone().appendTo('div.hmw_cdn_mapping_group'); jQuery('div.hmw_cdn_mapping_remove').show(); jQuery('div.hmw_cdn_mapping:last').find('div.hmw_cdn_mapping_remove').hide()"><?php echo __( 'Add another CDN URL', _HMW_PLUGIN_NAME_ ) ?></button>
                                    </div>
                                </div>
							<?php } ?>
                        </div>
                    </div>
                </div>


	            <?php if ( HMW_Classes_Tools::getOption( 'test_frontend' ) || HMW_Classes_Tools::getOption( 'logout' ) || HMW_Classes_Tools::getOption( 'error' ) ) { ?>
                    <div class="col-sm-12 m-0 p-2">
                        <button type="submit" class="btn rounded-0 btn-success btn-lg px-5 mr-5 save"><?php _e( 'Save', _HMW_PLUGIN_NAME_ ); ?></button>
                        <a href="https://wordpress.org/support/plugin/hide-my-wp/reviews/?rate=5#new-post" target="_blank" style="color: #ff005e;"><?php echo sprintf( __( 'Love Hide My WP %s? Show us ;)', _HMW_PLUGIN_NAME_ ), _HMW_VER_NAME_ ); ?></a>
                    </div>
	            <?php } else { ?>
                    <div class="col-sm-12 m-0 p-2 bg-light text-center" style="position: fixed; bottom: 0; right: 0; z-index: 100; box-shadow: 0px 0px 8px -3px #444;">
                        <button type="submit" class="btn rounded-0 btn-success btn-lg px-5 mr-5 save"><?php _e('Save', _HMW_PLUGIN_NAME_); ?></button>
                        <a href="https://wordpress.org/support/plugin/hide-my-wp/reviews/?rate=5#new-post" target="_blank" style="color: #ff005e;"><?php echo sprintf( __( 'Love Hide My WP %s? Show us ;)', _HMW_PLUGIN_NAME_ ), _HMW_VER_NAME_ ); ?></a>
                    </div>
	            <?php } ?>
            </form>
        </div>
        <div class="hmw_col hmw_col_side">
            <div class="card col-sm-12 p-0">
                <div class="card-body f-gray-dark text-center">
                    <h3 class="card-title"><?php _e( 'Check Your Website', _HMW_PLUGIN_NAME_ ); ?></h3>
                    <div class="card-text text-muted">
						<?php echo __( 'Check if your website is secured with the current settings.', _HMW_PLUGIN_NAME_ ) ?>
                    </div>
                    <div class="card-text text-info m-3">
                        <a href="<?php echo HMW_Classes_Tools::getSettingsUrl( 'hmw_securitycheck' ) ?>" class="btn rounded-0 btn-warning btn-lg text-white px-4 securitycheck"><?php _e( 'Security Check', _HMW_PLUGIN_NAME_ ); ?></a>
                    </div>
                    <div class="card-text text-muted small">
						<?php echo __( 'Make sure you save the settings and empty the cache before checking your website with our tool.', _HMW_PLUGIN_NAME_ ) ?>
                    </div>

                    <div class="card-text m-3 ">
                        <a class="bigbutton text-center" href="https://hidemywpghost.com/" target="_blank"><?php echo __( "Learn more about Hide My WP", _HMW_PLUGIN_NAME_ ); ?></a>
                    </div>
                </div>
            </div>

            <div class="card col-sm-12 p-0">
                <div class="card-body f-gray-dark text-center">
                    <h3 class="card-title"><?php echo __( 'Love Hide My WP?', _HMW_PLUGIN_NAME_ ); ?></h3>
                    <div class="card-text text-muted">
                        <h1><i class="fa fa-heart text-danger"></i></h1>
						<?php echo __( 'Give us 5 stars on WordPress.org', _HMW_PLUGIN_NAME_ ) ?>
                    </div>
                    <div class="card-text text-info m-3">
                        <a href="https://wordpress.org/support/plugin/hide-my-wp/reviews/?rate=5#new-post" target="_blank" class="btn rounded-0 btn-success btn-lg px-4"><?php echo __( 'Rate Hide My WP', _HMW_PLUGIN_NAME_ ); ?></a>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>