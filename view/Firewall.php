<?php
defined( 'ABSPATH' ) || die( 'Cheating uh?' );
if ( ! isset( $view ) ) {
	return;
} ?>
<noscript>
    <style>#hmwp_wrap .tab-panel:not(.tab-panel-first) {
            display: block
        }</style>
</noscript>
<div id="hmwp_wrap" class="d-flex flex-row p-0 my-3">
    <?php
    $view->getAdminTabs( HMWP_Classes_Tools::getValue( 'page' ) );

    $current_tab = HMWP_Classes_Tools::getValue( 'tab' );
    $subtabs = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Menu' )->getSubMenu( HMWP_Classes_Tools::getValue( 'page' ) );
    $tabs = array_column( $subtabs, 'tab' );

    if ( ! $current_tab || ( ! empty( $tabs ) && ! in_array( $current_tab, $tabs, true ) ) ) {
        $current_tab = reset( $tabs );
    }

    ?>
    <div class="hmwp_row d-flex flex-row p-0 m-0">
        <div class="hmwp_col flex-grow-1 p-0 pr-2 mr-2 mb-3">
            <form method="POST">
				<?php wp_nonce_field( 'hmwp_firewall', 'hmwp_nonce' ) ?>
                <input type="hidden" name="action" value="hmwp_firewall"/>

				<?php do_action( 'hmwp_firewall_form_beginning' ) ?>

                <div id="firewall" style="<?php echo ( $current_tab === 'firewall' ? '' : 'display:none;' ); ?>" class="col-sm-12 p-0 m-0 tab-panel tab-panel-first">
                    <div class="card col-sm-12 p-0 m-0">
                        <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__( 'Firewall', 'hide-my-wp' ); ?>
                            <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/firewall-security/' ) ?>" target="_blank" class="d-inline-block float-right mr-2" style="color: white"><i class="dashicons dashicons-editor-help"></i></a>
                        </h3>
                        <div class="card-body">

                        <div class="col-sm-12 row mb-1 ml-1 p-2">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmwp_sqlinjection" value="0"/>
                                    <input type="checkbox" id="hmwp_sqlinjection" name="hmwp_sqlinjection" class="switch"<?php echo( HMWP_Classes_Tools::getOption( 'hmwp_sqlinjection' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                    <label for="hmwp_sqlinjection"><?php echo esc_html__( 'Firewall Against Script Injection', 'hide-my-wp' ); ?>
                                        <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/firewall-security/#ghost-activate-firewall' ) ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                    </label>
                                    <div class="text-black-50 ml-5"><?php echo esc_html__( 'Intelligent rules that block exploit attempts and malicious bots while keeping search engines and AI crawlers accessible.', 'hide-my-wp' ); ?></div>
                                    <div class="text-black-50 font-italic ml-5"><?php echo esc_html__( 'Designed to stop attacks without impacting SEO or AI indexing.', 'hide-my-wp' ); ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-12 row px-2 py-2 mx-0 my-3 hmwp_sqlinjection">
                            <div class="col-sm-4 p-1">
                                <div class="font-weight-bold"><?php echo esc_html__( 'Firewall Strength', 'hide-my-wp' ); ?>:</div>
                                <div class="text-black-50 small">
                                    <?php echo wp_kses_post(
                                            sprintf(
                                                    /* translators: 1: Opening anchor tag. 2: Closing anchor tag. */
                                                    __( 'Learn more about %1$s7G firewall%2$s.', 'hide-my-wp' ),
                                                    '<a href="https://perishablepress.com/7g-firewall/" target="_blank" rel="noopener">',
                                                    '</a>'
                                            )
                                        );
                                    ?>
                                </div>
                                <div class="text-black-50 small">
                                    <?php
                                    echo wp_kses_post(
                                            sprintf(
                                                    /* translators: 1: Opening anchor tag. 2: Closing anchor tag. */
                                                    __( 'Learn more about the %1$s8G firewall%2$s.', 'hide-my-wp' ),
                                                    '<a href="https://perishablepress.com/8g-firewall/" target="_blank" rel="noopener">',
                                                    '</a>'
                                            )
                                    );
                                    ?>
                                </div>                            </div>
                            <div class="col-sm-8 p-0 input-group mb-1">
                                <select name="hmwp_sqlinjection_level" class="selectpicker form-control">
                                    <option value="1" <?php echo selected( 1, HMWP_Classes_Tools::getOption( 'hmwp_sqlinjection_level' ) ) ?>><?php echo esc_html__( 'Minimal', 'hide-my-wp' ); ?></option>
                                    <option value="2" <?php echo selected( 2, HMWP_Classes_Tools::getOption( 'hmwp_sqlinjection_level' ) ) ?>><?php echo esc_html__( 'Medium', 'hide-my-wp' ); ?></option>
                                    <option value="3" <?php echo selected( 3, HMWP_Classes_Tools::getOption( 'hmwp_sqlinjection_level' ) ) ?>><?php echo esc_html__( '7G Firewall', 'hide-my-wp' ); ?></option>
                                    <option value="4" <?php echo selected( 4, HMWP_Classes_Tools::getOption( 'hmwp_sqlinjection_level' ) ) ?>><?php echo esc_html__( '8G Firewall', 'hide-my-wp' ); ?> (<?php echo esc_html__( 'recommended', 'hide-my-wp' ); ?>)</option>
                                </select>
                                <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/firewall-security/#ghost-8g-firewall' ) ?>" target="_blank" class="position-absolute float-right" style="right: 27px;top: 12%;"><i class="dashicons dashicons-editor-help"></i></a>
                            </div>

                        </div>

						<?php if ( HMWP_Classes_Tools::isApache() || HMWP_Classes_Tools::isLitespeed() ) { ?>
                            <div class="col-sm-12 row px-2 py-2 mx-0 my-3 hmwp_sqlinjection">
                                <div class="col-sm-4 p-1">
                                    <div class="font-weight-bold"><?php echo esc_html__( 'Firewall Location', 'hide-my-wp' ); ?>:</div>
                                    <div class="text-black-50 small"><?php echo esc_html__( 'Where to add the firewall rules.', 'hide-my-wp' ); ?></div>
                                </div>
                                <div class="col-sm-8 p-0 input-group mb-1">
                                    <select name="hmwp_sqlinjection_location" class="selectpicker form-control">
                                        <option value="onload" <?php echo selected( 'onload', HMWP_Classes_Tools::getOption( 'hmwp_sqlinjection_location' ) ) ?>><?php echo esc_html__( 'On website initialization', 'hide-my-wp' ); ?> (<?php echo esc_html__( 'recommended', 'hide-my-wp' ); ?>)</option>
                                        <option value="file" <?php echo selected( 'file', HMWP_Classes_Tools::getOption( 'hmwp_sqlinjection_location' ) ) ?>><?php echo esc_html__( 'In .htaccess file', 'hide-my-wp' ); ?></option>
                                    </select>
                                </div>

                            </div>
						<?php } else { ?>
                            <input type="hidden" name="hmwp_sqlinjection_location" value="onload"/>
						<?php } ?>

                        <div class="<?php echo esc_attr(HMWP_CLASS_CTA) ?>">
                            <div class="col-sm-12 row mb-1 ml-1 p-2">
                                <div class="checker col-sm-12 row my-2 py-1">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="checkbox" id="hmwp_threats_auto" name="hmwp_threats_auto" class="switch" <?php echo ( HMWP_Classes_Tools::getOption( 'hmwp_threats_auto' ) ? 'checked="checked"' : '' ); ?> value="1"/>
                                        <label for="hmwp_threats_auto"><?php echo esc_html__( 'Automate IP Blocking', 'hide-my-wp' ); ?>
                                            <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/firewall-security/#ghost-automate-ip-blocking' ) ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                        </label>
                                        <div class="text-black-50 ml-5"><?php echo esc_html__( 'Automatically block IP addresses that trigger repeated security threats.', 'hide-my-wp' ); ?></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-12 row mb-1 ml-1 p-2" >
                            <div class="card-body pt-0">
                                <div class="border rounded-3 p-3 bg-white">
                                    <div class="d-flex flex-wrap align-items-center my-2 py-1">
                                        <span class="font-weight-bold mr-2"><?php echo esc_html__('When', 'hide-my-wp'); ?></span>

                                        <input type="number" min="1" class="form-control form-control-sm mr-2" style="width: 90px;" id="hmwp_threats_auto_tries" name="hmwp_threats_auto_tries" value="<?php echo esc_attr( (int) HMWP_Classes_Tools::getOption('hmwp_threats_auto_tries') ); ?>"/>

                                        <select name="hmwp_threats_auto_type" id="hmwp_threats_auto_type" class="form-select form-select-sm mr-2" style="width: 180px;">
                                            <option value="ip" <?php selected('ip', HMWP_Classes_Tools::getOption('hmwp_threats_auto_type')); ?>>
                                                <?php echo esc_html__('Similar attacks', 'hide-my-wp'); ?>
                                            </option>
                                            <option value="rip" <?php selected('rip', HMWP_Classes_Tools::getOption('hmwp_threats_auto_type')); ?>>
                                                <?php echo esc_html__('Identical attacks', 'hide-my-wp'); ?>
                                            </option>
                                        </select>

                                        <span class="font-weight-bold mr-2"><?php echo esc_html__('within', 'hide-my-wp'); ?></span>

                                        <select name="hmwp_threats_auto_interval" id="hmwp_threats_auto_interval" class="form-select form-select-sm mr-2" style="width: 170px;">
                                            <option value="<?php echo esc_attr(MINUTE_IN_SECONDS); ?>" <?php selected(MINUTE_IN_SECONDS, HMWP_Classes_Tools::getOption('hmwp_threats_auto_interval')); ?>>
                                                <?php echo esc_html__('1 minute', 'hide-my-wp'); ?>
                                            </option>
                                            <option value="1800" <?php selected(1800, HMWP_Classes_Tools::getOption('hmwp_threats_auto_interval')); ?>>
                                                <?php echo esc_html__('30 minutes', 'hide-my-wp'); ?>
                                            </option>
                                            <option value="<?php echo esc_attr(HOUR_IN_SECONDS); ?>" <?php selected(HOUR_IN_SECONDS, HMWP_Classes_Tools::getOption('hmwp_threats_auto_interval')); ?>>
                                                <?php echo esc_html__('1 hour', 'hide-my-wp'); ?>
                                            </option>
                                            <option value="<?php echo esc_attr(DAY_IN_SECONDS); ?>" <?php selected(DAY_IN_SECONDS, HMWP_Classes_Tools::getOption('hmwp_threats_auto_interval')); ?>>
                                                <?php echo esc_html__('1 day', 'hide-my-wp'); ?>
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Action line -->
                                    <div class="d-flex flex-wrap align-items-center my-2 py-1">
                                        <span class="font-weight-bold mr-2"><?php echo esc_html__('Then', 'hide-my-wp'); ?></span>

                                        <select name="hmwp_threats_auto_timeout" id="hmwp_threats_auto_timeout" class="form-select form-select-sm mr-2" style="width: 240px;">
                                            <option value="0" <?php selected(0, HMWP_Classes_Tools::getOption('hmwp_threats_auto_timeout')); ?>>
                                                <?php echo esc_html__('Permanently blacklist IP', 'hide-my-wp'); ?>
                                            </option>
                                            <option value="<?php echo esc_attr(HOUR_IN_SECONDS); ?>" <?php selected(HOUR_IN_SECONDS, HMWP_Classes_Tools::getOption('hmwp_threats_auto_timeout')); ?>>
                                                <?php echo esc_html__('Temporarily block for 1 hour', 'hide-my-wp'); ?>
                                            </option>
                                            <option value="<?php echo esc_attr(DAY_IN_SECONDS); ?>" <?php selected(DAY_IN_SECONDS, HMWP_Classes_Tools::getOption('hmwp_threats_auto_timeout')); ?>>
                                                <?php echo esc_html__('Temporarily block for 1 day', 'hide-my-wp'); ?>
                                            </option>
                                            <option value="<?php echo esc_attr(WEEK_IN_SECONDS); ?>" <?php selected(WEEK_IN_SECONDS, HMWP_Classes_Tools::getOption('hmwp_threats_auto_timeout')); ?>>
                                                <?php echo esc_html__('Temporarily block for 1 week', 'hide-my-wp'); ?>
                                            </option>
                                            <option value="<?php echo esc_attr(MONTH_IN_SECONDS); ?>" <?php selected(MONTH_IN_SECONDS, HMWP_Classes_Tools::getOption('hmwp_threats_auto_timeout')); ?>>
                                                <?php echo esc_html__('Temporarily block for 1 month', 'hide-my-wp'); ?>
                                            </option>
                                        </select>

                                    </div>

                                    <div class="d-flex flex-wrap align-items-center text-black-50 my-2 py-1">
                                        <?php echo esc_html__('Whitelist IP addresses are never blocked.', 'hide-my-wp'); ?>
                                    </div>

                                </div>
                            </div>
                        </div>
                        </div>

                        <div class="col-sm-12 row mb-1 ml-1 p-2">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmwp_hide_unsafe_headers" value="0"/>
                                    <input type="checkbox" id="hmwp_hide_unsafe_headers" name="hmwp_hide_unsafe_headers" class="switch"<?php echo( HMWP_Classes_Tools::getOption( 'hmwp_hide_unsafe_headers' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                    <label for="hmwp_hide_unsafe_headers"><?php echo esc_html__( 'Remove Unsafe Headers', 'hide-my-wp' ); ?>
                                        <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/firewall-security/#ghost-remove-unsafe-headers' ) ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                    </label>
                                    <div class="text-black-50 ml-5"><?php echo esc_html__( 'Remove PHP version, Server info, Server Signature from header.', 'hide-my-wp' ); ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-12 row mb-1 ml-1 p-2">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmwp_detectors_block" value="0"/>
                                    <input type="checkbox" id="hmwp_detectors_block" name="hmwp_detectors_block" class="switch"<?php echo( HMWP_Classes_Tools::getOption( 'hmwp_detectors_block' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                    <label for="hmwp_detectors_block"><?php echo esc_html__( 'Block Theme Detectors Crawlers', 'hide-my-wp' ); ?>
                                        <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/firewall-security/#ghost-block-theme-detectors' ) ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                    </label>
                                    <div class="text-black-50 ml-5"><?php echo esc_html__( 'Block known Users-Agents from popular Theme Detectors.', 'hide-my-wp' ); ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-12 row mb-1 ml-1 p-2 <?php echo esc_attr(HMWP_CLASS_CTA) ?>">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="block_ai_bots" value="0"/>
                                    <input type="checkbox" id="block_ai_bots" name="block_ai_bots" class="switch"<?php echo( HMWP_Classes_Tools::getOption( 'block_ai_bots' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                    <label for="block_ai_bots"><?php echo esc_html__( 'Block AI Crawler Bots', 'hide-my-wp' ); ?>
                                        <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/firewall-security/#ghost-block-ai-crawlers' ) ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                    </label>
                                    <div class="text-black-50 ml-5"><?php echo esc_html__( 'Block AI training and scraping bots at the firewall level and add them to robots.txt automatically.', 'hide-my-wp' ); ?></div>
                                    <div class="text-black-50 font-italic ml-5"><?php echo esc_html__( 'Covers GPTBot, ClaudeBot, PerplexityBot, CCBot, Bytespider, and 30+ other known AI crawlers.', 'hide-my-wp' ); ?></div>
                                    <div class="ml-5 mt-2" style="font-size: 12px; color: #1e7e34;">
                                        <span class="dashicons dashicons-update" style="font-size: 14px; width: 14px; height: 14px; vertical-align: middle;"></span>
                                        <?php echo esc_html__( 'The AI crawler list is automatically updated with each plugin release as new bots appear.', 'hide-my-wp' ); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-12 p-3 m-0 mt-1 bg-warning">
                                <strong><?php echo esc_html__( 'Important:', 'hide-my-wp' ); ?></strong>
                                <?php echo esc_html__( 'Only block AI bots if your content is sensitive or you want to protect it from being used for AI training without permission. If you rely on AI search visibility (ChatGPT, Perplexity, etc.), blocking these bots will remove your content from those ecosystems. AI crawlers are how your content gets cited in AI-generated answers.', 'hide-my-wp' ); ?>
                            </div>
                        </div>

                    </div>
                    </div>
                </div>

                <div id="header" style="<?php echo ( $current_tab === 'header' ? '' : 'display:none;' ); ?>" class="col-sm-12 p-0 m-0 tab-panel">
                    <div class="card col-sm-12 p-0 m-0">
                        <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__( 'Header Security', 'hide-my-wp' ); ?>
                            <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/header-security/' ) ?>" target="_blank" class="d-inline-block float-right mr-2" style="color: white"><i class="dashicons dashicons-editor-help"></i></a>
                        </h3>
                        <div class="card-body">

                        <div class="col-sm-12 row mb-1 ml-1 p-2">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmwp_security_header" value="0"/>
                                    <input type="checkbox" id="hmwp_security_header" name="hmwp_security_header" class="switch"<?php echo( HMWP_Classes_Tools::getOption( 'hmwp_security_header' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                    <label for="hmwp_security_header"><?php echo esc_html__( 'Add Security Headers against XSS and Injection Attacks', 'hide-my-wp' ); ?>
                                        <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/header-security/#ghost-how-to-enable' ) ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                    </label>
                                </div>
                                <div class="text-black-50 col-sm-12 p-0 ml-5"><?php echo esc_html__( "Add Strict-Transport-Security header", 'hide-my-wp' ); ?>
                                    <a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Strict-Transport-Security" target="_blank"><?php echo esc_html__( 'more details', 'hide-my-wp' ) ?></a>
                                </div>
                                <div class="text-black-50 col-sm-12 p-0 ml-5"><?php echo esc_html__( "Add Content-Security-Policy header", 'hide-my-wp' ); ?>
                                    <a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP" target="_blank"><?php echo esc_html__( 'more details', 'hide-my-wp' ) ?></a>
                                </div>
                                <div class="text-black-50 col-sm-12 p-0 ml-5"><?php echo esc_html__( "Add X-XSS-Protection header", 'hide-my-wp' ); ?>
                                    <a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-XSS-Protection" target="_blank"><?php echo esc_html__( 'more details', 'hide-my-wp' ) ?></a>
                                </div>
                                <div class="text-black-50 col-sm-12 p-0 ml-5"><?php echo esc_html__( "Add X-Content-Type-Options header", 'hide-my-wp' ); ?>
                                    <a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Content-Type-Options" target="_blank"><?php echo esc_html__( 'more details', 'hide-my-wp' ) ?></a>
                                </div>

                            </div>

                            <div class="col-sm-12 row py-4 border-bottom hmwp_security_header">
                                <input type="hidden" class="form-control w-100" name="hmwp_security_headers[]" value=""/>
								<?php
								$headers = (array) HMWP_Classes_Tools::getOption( 'hmwp_security_headers' );
								$help    = array(
									"Strict-Transport-Security"       => array(
										"title"   => "Tells browsers that it should only be accessed using HTTPS, instead of using HTTP.",
										"link"    => "https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Strict-Transport-Security",
										"default" => "max-age=63072000"
									), "Content-Security-Policy"      => array(
										"title"   => "Adds layer of security that helps to detect and mitigate certain types of attacks, including Cross-Site Scripting (XSS) and data injection attacks.",
										"link"    => "https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP",
										"default" => "object-src 'none'"
									), "X-XSS-Protection"             => array(
										"title"   => "Stops pages from loading when they detect reflected cross-site scripting (XSS) attacks.",
										"link"    => "https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-XSS-Protection",
										"default" => "1; mode=block"
									), "X-Content-Type-Options"       => array(
										"title"   => "Blocks content sniffing that could transform non-executable MIME types into executable MIME types.",
										"link"    => "https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Content-Type-Options",
										"default" => "nosniff"
									), "Cross-Origin-Embedder-Policy" => array(
										"title"   => "Prevents a document from loading any cross-origin resources that don't explicitly grant the document permission.",
										"link"    => "https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cross-Origin-Embedder-Policy",
										"default" => "unsafe-none"
									), "Cross-Origin-Opener-Policy"   => array(
										"title"   => "Allows you to ensure a top-level document does not share a browsing context group with cross-origin documents.",
										"link"    => "https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cross-Origin-Opener-Policy",
										"default" => "unsafe-none"
									), "X-Frame-Options"              => array(
										"title"   => "Can be used to indicate whether or not a browser should be allowed to render a page in a frame, iframe, embed, object.",
										"link"    => "https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Frame-Options",
										"default" => "SAMEORIGIN"
									), "Permissions-Policy"           => array(
										"title"   => "Provides a mechanism to allow and deny the use of browser features in its own frame, and in content within any iframe elements in the document.",
										"link"    => "https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Feature-Policy",
										"default" => "interest-cohort=(), accelerometer=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=(), interest-cohort=()"
									), "Referrer-Policy"              => array(
										"title"   => "HTTP header controls how much referrer information (sent with the Referer header) should be included with requests..",
										"link"    => "https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Referrer-Policy",
										"default" => "origin-when-cross-origin"
									),
								); ?>

                                <div class="col-sm-12 m-0 p-0 hmwp_security_headers">
									<?php foreach ( $headers as $name => $value ) {
										if ( $value == '' ) {
											continue;
										}
										?>
                                        <div class="col-sm-12 row pb-3 m-0 my-1 border-0">
                                            <div class="hmwp_security_header_remove" onclick="jQuery(this).parent().remove()" title="<?php echo esc_attr__( 'Remove', 'hide-my-wp' ) ?>">x</div>
                                            <div class="col-sm-4 p-0 my-2 font-weight-bold">
												<?php echo esc_html( $name ) ?>:
												<?php if ( isset( $help[ $name ]['default'] ) ) { ?>
                                                    <div class="text-black-50 small"><?php echo esc_html__( 'default', 'hide-my-wp' ) . ': ' . esc_html( $help[ $name ]['default'] ); ?></div>
												<?php } ?>
                                            </div>
                                            <div class="col-sm-8 p-0">
                                                <div class=" input-group">
                                                    <input type="text" class="form-control w-100" name="hmwp_security_headers[<?php echo esc_attr( $name ) ?>]" value="<?php echo esc_attr( $value ) ?>"/>
													<?php if ( isset( $help[ $name ]['link'] ) ) { ?>
                                                        <a href="<?php echo esc_url( $help[ $name ]['link'] ) ?>" target="_blank" class="position-absolute float-right" style="right: 7px;top: 10px;"><i class="dashicons dashicons-editor-help"></i></a>
													<?php } ?>
                                                </div>
												<?php if ( isset( $help[ $name ]['title'] ) ) { ?>
                                                    <div class="text-black-50 small"><?php echo esc_html( $help[ $name ]['title'] ); ?></div>
												<?php } ?>
                                            </div>

                                        </div>
									<?php } ?>
                                </div>

								<?php if ( count( $help ) > ( count( $headers ) - 1 ) ) { ?>
                                    <div class="col-sm-12 row pb-3 m-0 my-1 border-0 hmwp_security_headers_new">

										<?php foreach ( $help as $name => $value ) {
											if ( ! in_array( $name, array_keys( $headers ), true ) ) {
												?>
                                                <div class="col-sm-12 row pb-3 m-0 my-1 border-0 <?php echo esc_attr( $name ) ?>" style="display: none">
                                                    <div class="hmwp_security_header_remove" onclick="jQuery(this).parent().remove()" title="<?php echo esc_attr__( 'Remove', 'hide-my-wp' ) ?>">x</div>
                                                    <div class="col-sm-4 p-0 my-2 font-weight-bold">
														<?php echo esc_html( $name ) ?>:
														<?php if ( isset( $value['default'] ) ) { ?>
                                                            <div class="text-black-50 small"><?php echo esc_html__( 'default', 'hide-my-wp' ) . ': ' . esc_html( $value['default'] ); ?></div>
														<?php } ?>
                                                    </div>
                                                    <div class="col-sm-8 p-0 input-group">
                                                        <input type="text" class="form-control w-100"/>
														<?php if ( isset( $value['link'] ) ) { ?>
                                                            <a href="<?php echo esc_url( $value['link'] ) ?>" target="_blank" class="position-absolute float-right" style="right: 7px;top: 10px;"><i class="dashicons dashicons-editor-help"></i></a>
														<?php } ?>

														<?php if ( isset( $value['title'] ) ) { ?>
                                                            <div class="text-black-50 small"><?php echo esc_html( $value['title'] ); ?></div>
														<?php } ?>
                                                    </div>

                                                </div>
											<?php }
										} ?>


                                        <div class="col-sm-4 p-0 my-2 font-weight-bold">
											<?php echo esc_html__( 'Add Security Header', 'hide-my-wp' ); ?>
                                        </div>
                                        <div class="col-sm-8 p-0 input-group">
                                            <select id="hmwp_security_headers_new" class=" form-control mb-1">
                                                <option value=""></option>
												<?php
												foreach ( $help as $name => $value ) {
													if ( ! in_array( $name, array_keys( $headers ), true ) ) {
														echo '<option value="' . esc_attr( $value['default'] ) . '" >' . esc_html( $name ) . '</option>';
													}
												}
												?>
                                            </select>
                                        </div>

                                    </div>
								<?php } ?>
                                <div class="col-sm-12 p-3 m-0 mt-1 bg-warning">
                                    <strong><?php echo esc_html__( 'Important:', 'hide-my-wp' ); ?></strong>
                                    <?php echo esc_html__( 'Changing the predefined security headers may affect the website functionality. Make sure you know what you are doing when modifying these values.', 'hide-my-wp' ); ?>
                                </div>
                                <div class="col-sm-12 text-center mt-3 small"><?php echo esc_html__( "Test your website headers with", 'hide-my-wp' ); ?>
                                    <a href="https://securityheaders.com/?q=<?php echo esc_url(home_url()) ?>" target="_blank">securityheaders.com</a>
                                </div>

                            </div>

                        </div>

                    </div>
                    </div>
                </div>

                <div id="geoblock" style="<?php echo ( $current_tab === 'geoblock' ? '' : 'display:none;' ); ?>" class="col-sm-12 p-0 m-0 tab-panel">
                    <div class="card col-sm-12 p-0 m-0">
                        <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__( 'Geo Security', 'hide-my-wp' ); ?>
                            <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/geo-security-country-blocking/' ) ?>" target="_blank" class="d-inline-block float-right mr-2" style="color: white"><i class="dashicons dashicons-editor-help"></i></a>
                        </h3>
                        <div class="card-body">

                        <div class="col-sm-12 row px-2 py-2 mx-0 my-3">
                            <?php
                            if ( HMWP_Classes_Tools::getOption( 'hmwp_threats_log' ) ) {
                                $threats_total = 0;

                                /** @var HMWP_Models_ThreatsLog $threatsLog */
                                $threatsLog = HMWP_Classes_ObjController::getClass( 'HMWP_Models_ThreatsLog' );
                                $data       = $threatsLog->getThreatStatsByDay( 7 );

                                // Sum totals across the full 7-day period
                                if ( ! empty( $data ) && isset( $data['threats'] ) && isset( $data['blocked'] ) ) {
                                    $threats_total = (int) array_sum( $data['threats'] ) + (int) array_sum( $data['blocked'] );
                                }

                                // If threats, show the threats map
                                if ( $threats_total ) {
                                    $view->show( 'GeoMap' );
                                }
                            }
                            ?>
                        </div>

                        <div class="col-sm-12 row mb-1 ml-1 p-2 <?php echo esc_attr(HMWP_CLASS_CTA) ?>">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmwp_geoblock" value="0"/>
                                    <input type="checkbox" id="hmwp_geoblock" name="hmwp_geoblock" class="switch"<?php echo( HMWP_Classes_Tools::getOption( 'hmwp_geoblock' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                    <label for="hmwp_geoblock"><?php echo esc_html__( 'Country Blocking', 'hide-my-wp' ); ?>
                                        <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/geo-security-country-blocking/#ghost-activate' ) ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                    </label>
                                    <div class="text-black-50 ml-5"><?php echo esc_html__( 'Block traffic from high-risk countries to reduce hacking attempts, spam, and unwanted access.', 'hide-my-wp' ); ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-12 row px-2 py-2 mx-0 my-3 border-bottom hmwp_geoblock <?php echo esc_attr(HMWP_CLASS_CTA) ?>">
                            <div class="col-sm-4 p-1">
                                <div class="font-weight-bold"><?php echo esc_html__( 'Block Countries', 'hide-my-wp' ); ?>:</div>
                                <div class="text-black-50 small"><?php echo esc_html__( 'Choose the countries where access to the website should be restricted.', 'hide-my-wp' ); ?></div>
                            </div>
                            <div class="col-sm-8 p-0 mb-1">
                                <select name="hmwp_geoblock_countries[]" class="form-control  selectpicker" multiple data-live-search="true">
									<?php
									//get all countries and codes
									$countries = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Geoip_GeoLocator' )->getCountryCodes();

									//get blocked countries
									$blocked_countries = HMWP_Classes_Tools::getOption( 'hmwp_geoblock_countries' );
									if ( ! empty( $blocked_countries ) && ! is_array( $blocked_countries ) ) {
										$blocked_countries = json_decode( $blocked_countries, true );
									}

									//show all countries and block countries
									foreach ( $countries as $code => $country ) {
										echo '<option value="' . esc_attr($code) . '" ' . selected( true, in_array( $code, (array) $blocked_countries ) ) . '>' . esc_html($country) . '</option>';
									}
									?>
                                </select>
                                <div class="col-sm-12 m-0 px-0">
                                    <input type="checkbox" id="hmwp_geoblock_selectall">
                                    <label for="hmwp_geoblock_selectall"><?php echo esc_html__( 'Select all', 'hide-my-wp' ); ?></label>
                                </div>

                            </div>
                        </div>

                        <div class="col-sm-12 row py-3 px-2 mx-1 my-3 hmwp_geoblock <?php echo esc_attr(HMWP_CLASS_CTA) ?>">
                            <div class="col-md-4 p-0 font-weight-bold ">
								<?php echo esc_html__( 'Block Specific Paths', 'hide-my-wp' ); ?>:
                                <div class="small text-black-50"><?php echo esc_html__( 'Add paths that will be blocked for the selected countries.', 'hide-my-wp' ) ?></div>
                                <div class="small text-black-50"><?php echo esc_html__( 'Leave it blank to block all paths for the selected countries.', 'hide-my-wp' ) ?></div>
                            </div>
                            <div class="col-md-8 p-0 input-group input-group">
								<?php
								$geoblock_urls = HMWP_Classes_Tools::getOption( 'hmwp_geoblock_urls' );
								if ( ! empty( $geoblock_urls ) ) {
									$geoblock_urls = json_decode( $geoblock_urls, true );
								}
								?>
                                <textarea type="text" class="form-control " name="hmwp_geoblock_urls" style="height: 100px"><?php echo esc_html( ! empty( $geoblock_urls ) ? implode( PHP_EOL, $geoblock_urls ) : '' ) ?></textarea>
                                <div class="small text-black-50 col-md-12 py-2 px-0"><?php echo esc_html__( 'e.g. /post-type/ will block all path starting with /post-type/', 'hide-my-wp' ) ?></div>
                            </div>
                        </div>


						<?php if ( ! empty( $blocked_countries ) ) { ?>
                            <div class="col-sm-12 row px-2 py-2 mx-0 my-3 border-bottom hmwp_geoblock <?php echo esc_attr(HMWP_CLASS_CTA) ?>">
                                <div class="col-sm-4 p-1">
                                    <div class="font-weight-bold"><?php echo esc_html__( 'Selected Countries', 'hide-my-wp' ); ?>:</div>
                                    <div class="text-black-50 small"><?php echo esc_html__( 'Here is the list of select counties where your website will be restricted..', 'hide-my-wp' ); ?></div>
                                </div>
                                <div class="col-sm-8 p-0 mb-1">
                                    <ul class="row">
										<?php

										//show all countries and block countries
										foreach ( $countries as $code => $country ) {
											if ( in_array( $code, $blocked_countries ) ) {
												echo '<li class="font-weight-bold ' . ( count( (array) $blocked_countries ) > 6 ? 'col-4' : 'col-12' ) . '">' . esc_html($country) . '</li>';
											}
										}
										?>
                                    </ul>
                                </div>
                            </div>
						<?php } ?>
                    </div>
                    </div>
                </div>

                <div id="whitelist" style="<?php echo ( $current_tab === 'whitelist' ? '' : 'display:none;' ); ?>" class="col-sm-12 p-0 m-0 tab-panel">
                    <div class="card col-sm-12 p-0 m-0">
                        <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__( 'Whitelist', 'hide-my-wp' ); ?>
                            <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/whitelist-ips-paths/' ) ?>" target="_blank" class="d-inline-block float-right mr-2" style="color: white"><i class="dashicons dashicons-editor-help"></i></a>
                        </h3>
                        <div class="card-body">
                        <div class="col-sm-12 row py-3 mx-0 my-3">
                            <div class="col-md-4 p-0 font-weight-bold">
								<?php echo esc_html__( 'Whitelist IPs', 'hide-my-wp' ); ?>:
                                <div class="small text-black-50"><?php echo esc_html__( 'Whitelist trusted IP addresses that should bypass firewall and security checks.', 'hide-my-wp' ) ?></div>
                                <div class="small text-black-50 pt-2 px-0">
                                    <?php echo esc_html__( 'Examples:', 'hide-my-wp' ) ?><br />
                                    10.1.1.1<br />
                                    11.1.1.*<br />
                                    12.1.*.*<br />
                                    67cf:f5da:48ff:a02a:77dc:7f93:85f1:86b3
                                </div>
                            </div>
                            <div class="col-md-8 p-0 input-group input-group pl-2">
								<?php
								$whitelist_ip = HMWP_Classes_Tools::getOption( 'whitelist_ip' );
								if ( ! empty( $whitelist_ip ) ) {
									$whitelist_ip = json_decode( $whitelist_ip, true );
								}
								?>
                                <textarea type="text" class="form-control " name="whitelist_ip" style="height: 150px"><?php echo esc_html( ! empty( $whitelist_ip ) ? implode( PHP_EOL, $whitelist_ip ) : '' ) ?></textarea>
                                <div class="small text-black-50 col-md-12 pt-2 px-0">
                                    <?php
                                    echo wp_kses_post(
                                            sprintf(
                                            /* translators: %s: Link to a website where the user can find their public IP address. */
                                                    __( 'You can white-list a single IP address like 192.168.0.1 or a range of 245 IPs like 192.168.0.*. Find your IP with %s', 'hide-my-wp' ),
                                                    '<a href="https://whatismyipaddress.com/" target="_blank" rel="noopener">whatismyipaddress.com</a>'
                                            )
                                    );
                                    ?>
                                </div>
                                <?php
	                            $domain = ( HMWP_Classes_Tools::isMultisites() && defined( 'BLOG_ID_CURRENT_SITE' ) ) ? get_home_url( BLOG_ID_CURRENT_SITE ) : site_url();
	                            if( $ip = @gethostbyname( wp_parse_url($domain, PHP_URL_HOST) ) ) { ?>
                                    <div class="small text-black-50 col-md-12 pt-1 px-0">
                                        <?php
                                        echo wp_kses_post(
                                                sprintf(
                                                /* translators: %s: IP address wrapped in <strong> tags. */
                                                        __( 'To whitelist your website IP address, add: %s', 'hide-my-wp' ),
                                                        '<strong>' . esc_html( $ip ) . '</strong>'
                                                )
                                        );
                                        ?>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="col-sm-12 row py-3 mx-0 my-3">
                            <div class="col-md-4 p-0 font-weight-bold ">
								<?php echo esc_html__( 'Whitelist Paths', 'hide-my-wp' ); ?>:
                                <div class="small text-black-50"><?php echo esc_html__( 'Add URL paths that should never be blocked by the firewall.', 'hide-my-wp' ) ?></div>
                                <div class="small text-black-50 pt-2 px-0">
                                    <?php echo esc_html__( 'Examples:', 'hide-my-wp' ) ?><br />
                                    /cart/<br />
                                    /shop/<br />
                                    /checkout/
                                </div>
                            </div>
                            <div class="col-md-8 p-0 input-group input-group pl-2">
								<?php
								$whitelist_urls = HMWP_Classes_Tools::getOption( 'whitelist_urls' );
                                if ( ! empty( $whitelist_urls ) ) {
									$whitelist_urls = json_decode( $whitelist_urls, true );
                                    $whitelist_urls = array_filter( $whitelist_urls );
                                }
								?>
                                <textarea type="text" class="form-control " name="whitelist_urls" style="height: 150px"><?php echo esc_html( ! empty( $whitelist_urls ) ? implode( PHP_EOL, $whitelist_urls ) : '' ) ?></textarea>
                                <div class="small text-black-50 col-md-12 py-2 px-0"><?php echo esc_html__( 'Example: /cart/ will exempt all requests beginning with /cart/ from firewall rules.', 'hide-my-wp' ) ?></div>
                            </div>
                        </div>

                        <?php $whitelist_rules = HMWP_Classes_Tools::getOption( 'whitelist_rules' );
                        if ( ! empty( $whitelist_rules ) ) {
                            $whitelist_rules = json_decode( $whitelist_rules, true );
                            $whitelist_rules = array_filter( $whitelist_rules );
                            if ( ! empty( $whitelist_rules ) ) {
                            ?>
                            <div class="col-sm-12 row py-3 mx-0 my-3">
                                <div class="col-md-4 p-0 font-weight-bold ">
                                    <?php echo esc_html__( 'Whitelist Rules', 'hide-my-wp' ); ?>:
                                    <div class="small text-black-50"><?php echo esc_html__( 'Add firewall rules codes that can pass plugin security.', 'hide-my-wp' ) ?></div>
                                </div>
                                <div class="col-md-8 p-0 input-group input-group pl-2">
                                    <textarea type="text" class="form-control " name="whitelist_rules" style="height: 100px"><?php echo esc_html( ! empty( $whitelist_rules ) ? implode( PHP_EOL, $whitelist_rules ) : '' ) ?></textarea>
                                    <div class="small text-black-50 col-md-12 py-2 px-0"><?php echo esc_html__( 'e.g.', 'hide-my-wp' ) ?> FW_URI_BAD_EXTS_1</div>
                                </div>
                            </div>
                        <?php } } ?>

                        <div class="col-sm-12 row py-3 mx-0 my-3 border-bottom">
                            <div class="col-sm-4 p-1">
                                <div class="font-weight-bold"><?php echo esc_html__( 'Whitelist Options', 'hide-my-wp' ); ?></div>
                                <div class="text-black-50 small">
                                    <?php echo esc_html__( 'Choose how the plugin should behave for whitelisted IP addresses and paths.', 'hide-my-wp' ); ?>
                                </div>
                            </div>
                            <div class="col-sm-8 p-0 input-group mb-1 pl-2">
                                <select name="whitelist_level" class="selectpicker form-control">
                                    <option value="0" <?php echo selected( 0, HMWP_Classes_Tools::getOption( 'whitelist_level' ) ) ?> >
                                        <?php echo esc_html__( 'Allow access to hidden paths only', 'hide-my-wp' ); ?>
                                    </option>
                                    <option value="1" <?php echo selected( 1, HMWP_Classes_Tools::getOption( 'whitelist_level' ) ) ?> >
                                        <?php echo esc_html__( 'Show default WordPress paths and allow hidden paths', 'hide-my-wp' ); ?>
                                    </option>
                                    <option value="2" <?php echo selected( 2, HMWP_Classes_Tools::getOption( 'whitelist_level' ) ) ?> >
                                        <?php echo esc_html__( 'Show default WordPress paths and disable all hiding', 'hide-my-wp' ); ?>
                                    </option>
                                </select>
                            </div>
                        </div>

                    </div>
                    </div>
                </div>

                <div id="blacklist" style="<?php echo ( $current_tab === 'blacklist' ? '' : 'display:none;' ); ?>" class="col-sm-12 p-0 m-0 tab-panel">
                    <div class="card col-sm-12 p-0 m-0">
                        <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__( 'Blacklist', 'hide-my-wp' ); ?>
                            <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/blacklist/' ) ?>" target="_blank" class="d-inline-block float-right mr-2" style="color: white"><i class="dashicons dashicons-editor-help"></i></a>
                        </h3>
                        <div class="card-body">
                        <div class="col-sm-12 row py-3 mx-0 my-3">
                            <div class="col-md-4 p-0 font-weight-bold">
                                <?php echo esc_html__( 'Blacklist IPs', 'hide-my-wp' ); ?>:
                                <div class="small text-black-50">
                                    <?php echo esc_html__( 'Add IP addresses that should always be blocked from accessing your website.', 'hide-my-wp' ) ?>
                                </div>
                                <div class="small text-black-50 pt-2 px-0">
                                    <?php echo esc_html__( 'Examples:', 'hide-my-wp' ) ?><br />
                                    10.1.1.1<br />
                                    11.1.1.*<br />
                                    12.1.*.*<br />
                                    67cf:f5da:48ff:a02a:77dc:7f93:85f1:86b3
                                </div>
                            </div>
                            <div class="col-md-8 p-0 input-group input-group pl-2">
								<?php
								$banlist_ip = HMWP_Classes_Tools::getOption( 'banlist_ip' );
								if ( ! empty( $banlist_ip ) ) {
									$banlist_ip = json_decode( $banlist_ip, true );
								}
								?>
                                <textarea type="text" class="form-control " name="banlist_ip" style="min-height: 150px;"><?php echo esc_html( ! empty( $banlist_ip ) ? implode( PHP_EOL, $banlist_ip ) : '' ) ?></textarea>
                                <div class="small text-black-50 col-md-12 py-2 px-0"><?php echo esc_html__( 'You can ban a single IP address like 192.168.0.1 or a range of 245 IPs like 192.168.0.*. These IPs will not be able to access the login page.', 'hide-my-wp' ) ?></div>
                            </div>
                        </div>

                        <div class="col-sm-12 row py-3 mx-0 my-3">
                            <div class="col-md-4 p-0 font-weight-bold">
								<?php echo esc_html__( 'Block User-Agents', 'hide-my-wp' ); ?>:
                                <div class="small text-black-50 pt-2 px-0">
                                    <?php echo esc_html__( 'Examples:', 'hide-my-wp' ) ?><br />
                                    acapbot<br />
                                    alexibot<br />
                                    badbot
                                </div>
                            </div>
                            <div class="col-md-8 p-0 input-group input-group pl-2">
								<?php
                                $banlist = HMWP_Classes_Tools::getOption( 'banlist_user_agent' );

                                if ( ! is_array( $banlist ) ) {
                                    $banlist = json_decode( $banlist, true );
                                    $banlist = is_array($banlist) ? $banlist : array();
                                }

								?>
                                <textarea type="text" class="form-control " name="banlist_user_agent"><?php echo esc_html( ! empty( $banlist ) ? implode( PHP_EOL, $banlist ) : '' ) ?></textarea>
                            </div>
                        </div>

                        <div class="col-sm-12 row py-3 mx-0 my-3">
                            <div class="col-md-4 p-0 font-weight-bold">
								<?php echo esc_html__( 'Block Referrer', 'hide-my-wp' ); ?>:
                                <div class="small text-black-50 pt-2 px-0">
                                    <?php echo esc_html__( 'Examples:', 'hide-my-wp' ) ?><br />
                                    xanax.com<br />
                                    badsite.com
                                </div>
                            </div>
                            <div class="col-md-8 p-0 input-group input-group pl-2">
								<?php
								$referrers = HMWP_Classes_Tools::getOption( 'banlist_referrer' );
								if ( ! empty( $referrers ) ) {
									$referrers = json_decode( $referrers, true );
								}
								?>
                                <textarea type="text" class="form-control " name="banlist_referrer"><?php echo esc_html( ! empty( $referrers ) ? implode( PHP_EOL, $referrers ) : '' ) ?></textarea>
                            </div>
                        </div>

                        <div class="col-sm-12 row py-3 mx-0 my-3">
                            <div class="col-md-4 p-0 font-weight-bold">
								<?php echo esc_html__( 'Block Hostnames', 'hide-my-wp' ); ?>:
                                <div class="small text-black-50 pt-2 px-0">
                                    <?php echo esc_html__( 'Examples:', 'hide-my-wp' ) ?><br />
                                    *.colocrossing.com<br />
                                    kanagawa.com
                                </div>
                            </div>
                            <div class="col-md-8 p-0 input-group input-group pl-2">
								<?php
								$hostnames = HMWP_Classes_Tools::getOption( 'banlist_hostname' );
								if ( ! empty( $hostnames ) ) {
									$hostnames = json_decode( $hostnames, true );
								}
								?>
                                <textarea type="text" class="form-control " name="banlist_hostname"><?php echo esc_html( ! empty( $hostnames ) ? implode( PHP_EOL, $hostnames ) : '' ) ?></textarea>
                                <div class="col-12 px-0 py-2 small text-danger"><?php echo esc_html__( 'Resolving hostnames may affect the website loading speed.', 'hide-my-wp' ); ?></div>
                            </div>
                        </div>

                    </div>
                    </div>
                </div>

				<?php do_action( 'hmwp_firewall_form_end' ) ?>

                 <div class="col-sm-12 m-0 p-2 card-footer save-button text-center">
                    <button type="submit" class="btn rounded-0 btn-success px-5 mr-5 save"><?php echo esc_html__( 'Save', 'hide-my-wp' ); ?></button>
                </div>
            </form>

        </div>

        <div class="hmwp_col hmwp_col_side p-0 pr-2 mr-2">
			<?php $view->show( 'blocks/SecurityCheck' ); ?>
        </div>
    </div>
