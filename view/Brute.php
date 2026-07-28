<?php defined( 'ABSPATH' ) || die( 'Cheating uh?' );
if ( ! isset( $view ) ) {
    return;
} ?>
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

            <div id="blocked" style="<?php echo ( $current_tab === 'blocked' ? '' : 'display:none;' ); ?>" class="col-sm-12 p-0 m-0 tab-panel tab-panel-first">
                <div class="card col-sm-12 p-0 m-0">
                    <h3 class="card-title hmwp_header p-2 m-0 mb-3"><?php echo esc_html__( 'Blocked IPs', 'hide-my-wp' ); ?>
                        <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/brute-force-attack-protection/' ) ?>" target="_blank" class="d-inline-block float-right mr-2" style="color: white"><i class="dashicons dashicons-editor-help"></i></a>
                    </h3>
                    <div class="card-body p-2 m-0">
                        <?php if ( HMWP_Classes_Tools::getOption( 'hmwp_bruteforce' ) ) { ?>
                            <div class="col-sm-12 p-0">
                                <?php $view->show( 'blocks/BlockedIps' ); ?>
                            </div>
                        <?php } else { ?>
                            <div class="col-sm-12 p-1 my-2 text-center">
                                <div class="text-black-50 mb-2"><?php echo esc_html__( 'Activate the "Brute Force" option to see the user IP blocked report', 'hide-my-wp' ); ?></div>
                                <a href="<?php echo esc_url(HMWP_Classes_Tools::getSettingsUrl( 'hmwp_brute&tab=brute', true )) ?>" class="btn btn-default hmwp_nav_item" data-tab="brute"><?php echo esc_html__( 'Activate Brute Force Protection', 'hide-my-wp' ); ?></a>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <form method="POST">
                <?php wp_nonce_field( 'hmwp_brutesettings', 'hmwp_nonce' ) ?>
                <input type="hidden" name="action" value="hmwp_brutesettings"/>
                <input type="hidden" name="hmwp_bruteforce_login" value="1"/>

                <?php do_action( 'hmwp_brute_force_form_beginning' ) ?>

                <div id="brute" style="<?php echo ( $current_tab === 'brute' ? '' : 'display:none;' ); ?>" class="col-sm-12 p-0 m-0 tab-panel ">
                    <div class="card col-sm-12 p-0 m-0">
                        <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__( 'Brute Force', 'hide-my-wp' ); ?>
                            <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/brute-force-attack-protection/' ) ?>" target="_blank" class="d-inline-block float-right mr-2"><i class="dashicons dashicons-editor-help"></i></a>
                        </h3>
                        <div class="card-body">
                            <div class="col-sm-12 row mb-1 ml-1 p-2">
                                <div class="checker col-sm-12 row my-2 py-1">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="hidden" name="hmwp_bruteforce" value="0"/>
                                        <input type="checkbox" id="hmwp_bruteforce" name="hmwp_bruteforce" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_bruteforce' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                        <label for="hmwp_bruteforce"><?php echo esc_html__( 'Use Brute Force Protection', 'hide-my-wp' ); ?>
                                            <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/brute-force-attack-protection/#ghost-activate' ) ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                        </label>
                                        <div class="text-black-50 ml-5"><?php echo esc_html__( 'Stop repeated login attempts and block bots trying to guess usernames and passwords.', 'hide-my-wp' ); ?></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-12 row mb-1 ml-1 p-2 hmwp_bruteforce">
                                <div class="checker col-sm-12 row my-2 py-0">
                                    <div class="col-sm-12 p-0 switch switch-xxs pl-5">
                                        <input type="hidden" name="hmwp_bruteforce_lostpassword" value="0"/>
                                        <input type="checkbox" id="hmwp_bruteforce_lostpassword" name="hmwp_bruteforce_lostpassword" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_bruteforce_lostpassword' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                        <label for="hmwp_bruteforce_lostpassword"><?php echo esc_html__( 'Lost Password Form Protection', 'hide-my-wp' ); ?></label>
                                        <div class="text-black-50 ml-5"><?php echo esc_html__( 'Activate the Brute Force protection on lost password form.', 'hide-my-wp' ); ?></div>
                                    </div>
                                </div>
                            </div>

                            <?php if ( get_option( 'users_can_register' ) ) { ?>
                                <div class="col-sm-12 row mb-1 ml-1 p-2 hmwp_bruteforce">
                                    <div class="checker col-sm-12 row my-2 py-0">
                                        <div class="col-sm-12 p-0 switch switch-xxs pl-5">
                                            <input type="hidden" name="hmwp_bruteforce_register" value="0"/>
                                            <input type="checkbox" id="hmwp_bruteforce_register" name="hmwp_bruteforce_register" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_bruteforce_register' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                            <label for="hmwp_bruteforce_register"><?php echo esc_html__( 'Sign Up Form Protection', 'hide-my-wp' ); ?></label>
                                            <div class="text-black-50 ml-5"><?php echo esc_html__( 'Activate the Brute Force protection on sign up form.', 'hide-my-wp' ); ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>

                            <div class="col-sm-12 row mb-1 ml-1 p-2 hmwp_bruteforce">
                                <div class="checker col-sm-12 row my-2 py-0">
                                    <div class="col-sm-12 p-0 switch switch-xxs pl-5">
                                        <input type="hidden" name="hmwp_bruteforce_comments" value="0"/>
                                        <input type="checkbox" id="hmwp_bruteforce_comments" name="hmwp_bruteforce_comments" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_bruteforce_comments' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                        <label for="hmwp_bruteforce_comments"><?php echo esc_html__( 'Comment Form Protection', 'hide-my-wp' ); ?></label>
                                        <div class="text-black-50 ml-5"><?php echo esc_html__( 'Activate the Brute Force protection on website comment form.', 'hide-my-wp' ); ?></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-12 row mb-1 ml-1 p-2 hmwp_bruteforce">
                                <div class="checker col-sm-12 row my-2 py-0">
                                    <div class="col-sm-12 p-0 switch switch-xxs pl-5">
                                        <input type="hidden" name="hmwp_bruteforce_username" value="0"/>
                                        <input type="checkbox" id="hmwp_bruteforce_username" name="hmwp_bruteforce_username" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_bruteforce_username' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                        <label for="hmwp_bruteforce_username"><?php echo esc_html__( 'Wrong Username Protection', 'hide-my-wp' ); ?></label>
                                        <div class="text-black-50 ml-5"><?php echo esc_html__( 'Immediately block incorrect usernames on login form.', 'hide-my-wp' ); ?></div>
                                    </div>
                                </div>
                            </div>

                            <div class="hmwp_bruteforce">

                                <div class="border-top"></div>
                                <input type="hidden" value="<?php echo( HMWP_Classes_Tools::getOption( 'brute_use_math' ) ? '1' : '0' ) ?>" name="brute_use_math">
                                <?php if ( HMWP_Classes_Tools::getOption( 'brute_use_google_enterprise' ) ) { ?>
                                    <input type="hidden" name="brute_use_captcha" value="0"/>
                                    <input type="hidden" name="brute_use_captcha_v3" value="0"/>
                                    <input type="hidden" value="<?php echo( HMWP_Classes_Tools::getOption( 'brute_use_google' ) ? '1' : '0' ) ?>" name="brute_use_google">
                                <?php }else{ ?>
                                    <input type="hidden" name="brute_use_google" value="0"/>
                                    <input type="hidden" value="<?php echo( HMWP_Classes_Tools::getOption( 'brute_use_captcha' ) ? '1' : '0' ) ?>" name="brute_use_captcha">
                                    <?php if ( ! HMWP_Classes_Tools::isPluginActive( 'ultimate-member/ultimate-member.php' ) ) { ?>
                                        <input type="hidden" value="<?php echo( HMWP_Classes_Tools::getOption( 'brute_use_captcha_v3' ) ? '1' : '0' ) ?>" name="brute_use_captcha_v3">
                                    <?php } ?>
                                <?php } ?>

                                <div class="col-sm-12 group_autoload d-flex justify-content-center btn-group btn-group-lg mt-3 px-0" role="group">
                                    <button type="button" class="btn btn-outline-info brute_use_math mx-1 py-4 px-4 <?php echo( HMWP_Classes_Tools::getOption( 'brute_use_math' ) ? 'active' : '' ) ?>"><?php echo esc_html__( 'Math reCAPTCHA', 'hide-my-wp' ); ?></button>
                                    <?php if ( HMWP_Classes_Tools::getOption( 'brute_use_google_enterprise' ) ) { ?>
                                        <button type="button" class="btn btn-outline-info brute_use_google mx-1 py-4 px-4 <?php echo( HMWP_Classes_Tools::getOption( 'brute_use_google' ) ? 'active' : '' ) ?>"><?php echo esc_html__( 'Google reCAPTCHA', 'hide-my-wp' ) ?></button>
                                    <?php }else{ ?>
                                        <button type="button" class="btn btn-outline-info brute_use_captcha mx-1 py-4 px-4 <?php echo( HMWP_Classes_Tools::getOption( 'brute_use_captcha' ) ? 'active' : '' ) ?>"><?php echo esc_html__( 'Google reCAPTCHA V2', 'hide-my-wp' ) ?></button>
                                        <?php if ( ! HMWP_Classes_Tools::isPluginActive( 'ultimate-member/ultimate-member.php' ) ) { ?>
                                            <button type="button" class="btn btn-outline-info brute_use_captcha_v3 mx-1 py-4 px-4 <?php echo( HMWP_Classes_Tools::getOption( 'brute_use_captcha_v3' ) ? 'active' : '' ) ?>"><?php echo esc_html__( 'Google reCAPTCHA V3', 'hide-my-wp' ) ?></button>
                                        <?php } ?>
                                    <?php } ?>
                                </div>

                                <?php if ( HMWP_Classes_Tools::getOption( 'brute_use_google_enterprise' ) ) { ?>
                                    <div class="brute_use_google" <?php echo( ! HMWP_Classes_Tools::getOption( 'brute_use_google' ) ? 'style="display:none;"' : '' ) ?>>
                                        <div class="col-sm-12 text-center py-3 mx-0 my-3">
                                            <?php echo wp_kses_post( sprintf( /* translators: 1: Opening anchor tag. 2: Closing anchor tag. */ __( '%1$sClick here%2$s to create or view keys for Google reCAPTCHA.', 'hide-my-wp' ), '<a href="https://console.cloud.google.com/security/recaptcha/" class="mx-1 text-link font-weight-bold text-uppercase" target="_blank">', '</a>' ) ); ?>
                                        </div>
                                        <div class="col-sm-12 row py-3 mx-0 my-3">
                                            <div class="col-md-4 p-0 font-weight-bold">
                                                <?php echo esc_html__( 'Site Key', 'hide-my-wp' ); ?>:
                                                <div class="small text-black-50"><?php echo wp_kses_post( sprintf( /* translators: 1: Opening anchor tag. 2: Closing anchor tag. */ __( 'Site keys for %1$s Google reCaptcha %2$s.', 'hide-my-wp' ), '<a href="https://console.cloud.google.com/security/recaptcha" class="text-link" target="_blank">', '</a>' ) ); ?></div>
                                            </div>
                                            <div class="col-md-8 p-0 input-group">
                                                <input type="text" class="form-control " name="brute_google_site_key" value="<?php echo esc_attr( HMWP_Classes_Tools::getOption( 'brute_google_site_key' ) ) ?>"/>
                                            </div>
                                        </div>
                                        <div class="col-sm-12 row py-3 mx-0 my-3">
                                            <div class="col-md-4 p-0 font-weight-bold">
                                                <?php echo esc_html__( 'Project ID', 'hide-my-wp' ); ?>:
                                                <div class="small text-black-50"><?php echo wp_kses_post( sprintf( /* translators: 1: Opening anchor tag. 2: Closing anchor tag. */ __( 'Project ID for %1$s Google Enterprise %2$s.', 'hide-my-wp' ), '<a href="https://console.cloud.google.com/cloud-resource-manager" class="text-link" target="_blank">', '</a>' ) ); ?></div>
                                            </div>
                                            <div class="col-md-8 p-0 input-group">
                                                <input type="text" class="form-control " name="brute_google_project_id" value="<?php echo esc_attr( HMWP_Classes_Tools::getOption( 'brute_google_project_id' ) ) ?>"/>
                                            </div>
                                        </div>
                                        <div class="col-sm-12 row py-3 mx-0 my-3">
                                            <div class="col-md-4 p-0 font-weight-bold">
                                                <?php echo esc_html__( 'Api Key', 'hide-my-wp' ); ?>:
                                                <div class="small text-black-50"><?php echo wp_kses_post( sprintf( /* translators: 1: Opening anchor tag. 2: Closing anchor tag. */ __( 'Api key from %1$s Google API Console %2$s.', 'hide-my-wp' ), '<a href="https://console.cloud.google.com/apis/credentials" class="text-link" target="_blank">', '</a>' ) ); ?></div>
                                            </div>
                                            <div class="col-md-8 p-0 input-group">
                                                <input type="text" class="form-control " name="brute_google_api_key" value="<?php echo esc_attr( HMWP_Classes_Tools::getOption( 'brute_google_api_key' ) ) ?>" style="-webkit-text-security: disc;"/>
                                            </div>
                                        </div>

                                        <div class="col-sm-12 row mb-1 ml-1 p-2">
                                            <div class="checker col-sm-12 row my-2 py-1">
                                                <div class="col-sm-12 p-0 switch switch-sm">
                                                    <input type="hidden" name="brute_google_checkbox" value="0"/>
                                                    <input type="checkbox" id="brute_google_checkbox" name="brute_google_checkbox" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'brute_google_checkbox' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                                    <label for="brute_google_checkbox"><?php echo esc_html__( 'Use Checkbox Challenge', 'hide-my-wp' ); ?></label>
                                                    <div class="text-black-50 ml-5"><?php echo wp_kses_post( sprintf( /* translators: 1: Opening <strong> tag. 2: Closing </strong> tag. */ __( 'Verifies users by requiring them to check %1$s I\'m not a robot %2$s checkbox.', 'hide-my-wp' ), '<strong>', '</strong>' ) ); ?></div>
                                                    <div class="text-danger ml-5"><?php echo wp_kses_post( sprintf( /* translators: 1: Opening <strong> tag. 2: Closing </strong> tag. 3: Opening anchor tag. 4: Closing anchor tag. */ __( '%1$s Important! %2$s Enable this option only if it is already activated in the %3$s Google reCAPTCHA Key %4$s settings.', 'hide-my-wp' ), '<strong>', '</strong>', '<a href="https://console.cloud.google.com/security/recaptcha/'.esc_attr( HMWP_Classes_Tools::getOption( 'brute_google_site_key' ) ).'/edit" target="_blank">', '</a>' ) ); ?></div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-sm-12 row py-3 mx-0 my-3 brute_google_checkbox">
                                            <div class="col-sm-4 p-1">
                                                <div class="font-weight-bold"><?php echo esc_html__( 'reCaptcha Language', 'hide-my-wp' ); ?>:
                                                </div>
                                            </div>
                                            <div class="col-sm-8 p-0 input-group">
                                                <select name="brute_google_language" class="selectpicker form-control mb-1">
                                                    <?php
                                                    $languages = array(
                                                            esc_html__( 'Auto Detect', 'hide-my-wp' )         => '',
                                                            esc_html__( 'English', 'hide-my-wp' )             => 'en',
                                                            esc_html__( 'Arabic', 'hide-my-wp' )              => 'ar',
                                                            esc_html__( 'Bulgarian', 'hide-my-wp' )           => 'bg',
                                                            esc_html__( 'Catalan Valencian', 'hide-my-wp' )   => 'ca',
                                                            esc_html__( 'Czech', 'hide-my-wp' )               => 'cs',
                                                            esc_html__( 'Danish', 'hide-my-wp' )              => 'da',
                                                            esc_html__( 'German', 'hide-my-wp' )              => 'de',
                                                            esc_html__( 'Greek', 'hide-my-wp' )               => 'el',
                                                            esc_html__( 'British English', 'hide-my-wp' )     => 'en_gb',
                                                            esc_html__( 'Spanish', 'hide-my-wp' )             => 'es',
                                                            esc_html__( 'Persian', 'hide-my-wp' )             => 'fa',
                                                            esc_html__( 'French', 'hide-my-wp' )              => 'fr',
                                                            esc_html__( 'Canadian French', 'hide-my-wp' )     => 'fr_ca',
                                                            esc_html__( 'Hindi', 'hide-my-wp' )               => 'hi',
                                                            esc_html__( 'Croatian', 'hide-my-wp' )            => 'hr',
                                                            esc_html__( 'Hungarian', 'hide-my-wp' )           => 'hu',
                                                            esc_html__( 'Indonesian', 'hide-my-wp' )          => 'id',
                                                            esc_html__( 'Italian', 'hide-my-wp' )             => 'it',
                                                            esc_html__( 'Hebrew', 'hide-my-wp' )              => 'iw',
                                                            esc_html__( 'Jananese', 'hide-my-wp' )            => 'ja',
                                                            esc_html__( 'Korean', 'hide-my-wp' )              => 'ko',
                                                            esc_html__( 'Lithuanian', 'hide-my-wp' )          => 'lt',
                                                            esc_html__( 'Latvian', 'hide-my-wp' )             => 'lv',
                                                            esc_html__( 'Dutch', 'hide-my-wp' )               => 'nl',
                                                            esc_html__( 'Norwegian', 'hide-my-wp' )           => 'no',
                                                            esc_html__( 'Polish', 'hide-my-wp' )              => 'pl',
                                                            esc_html__( 'Portuguese', 'hide-my-wp' )          => 'pt',
                                                            esc_html__( 'Romanian', 'hide-my-wp' )            => 'ro',
                                                            esc_html__( 'Russian', 'hide-my-wp' )             => 'ru',
                                                            esc_html__( 'Slovak', 'hide-my-wp' )              => 'sk',
                                                            esc_html__( 'Slovene', 'hide-my-wp' )             => 'sl',
                                                            esc_html__( 'Serbian', 'hide-my-wp' )             => 'sr',
                                                            esc_html__( 'Swedish', 'hide-my-wp' )             => 'sv',
                                                            esc_html__( 'Thai', 'hide-my-wp' )                => 'th',
                                                            esc_html__( 'Turkish', 'hide-my-wp' )             => 'tr',
                                                            esc_html__( 'Ukrainian', 'hide-my-wp' )           => 'uk',
                                                            esc_html__( 'Vietnamese', 'hide-my-wp' )          => 'vi',
                                                            esc_html__( 'Simplified Chinese', 'hide-my-wp' )  => 'zh_cn',
                                                            esc_html__( 'Traditional Chinese', 'hide-my-wp' ) => 'zh_tw'
                                                    );
                                                    foreach ( $languages as $key => $language ) {
                                                        echo '<option value="' . esc_attr( $language ) . '"  ' . selected( $language, HMWP_Classes_Tools::getOption( 'brute_google_language' ) ) . '>' . esc_html( ucfirst( $key ) ) . '</option>';
                                                    } ?>
                                                </select>
                                            </div>
                                        </div>

                                        <?php if ( HMWP_Classes_Tools::getOption( 'brute_google_project_id' ) <> '' && HMWP_Classes_Tools::getOption( 'brute_google_api_key' ) <> '' && HMWP_Classes_Tools::getOption( 'brute_google_site_key' ) <> '' ) { ?>
                                            <div class="col-sm-12 py-3 mx-0 my-3">
                                                <button type="button" class="btn btn-lg btn-default brute_recaptcha_test hmwp_modal" data-remote="<?php echo esc_url( site_url( 'wp-login.php' ) . '?nordt=1&hmwp_preview=' . HMWP_Classes_Tools::getOption( 'hmwp_disable_name' ) ) ?>" data-target="#brute_recaptcha_modal"><?php echo esc_html__( 'reCAPTCHA Test', 'hide-my-wp' ); ?></button>

                                                <h4 class="mt-5 mb-3"><?php echo esc_html__( 'Next Steps', 'hide-my-wp' ); ?></h4>
                                                <ol>
                                                    <li><?php echo wp_kses_post( sprintf( /* translators: 1: Opening <strong> tag. 2: Closing </strong> tag. */ __( 'Run %1$sreCAPTCHA Test%2$s and login inside the popup.', 'hide-my-wp' ), '<strong>', '</strong>' ) ); ?></li>
                                                    <li><?php echo esc_html__( "If you're able to login, you've set reCAPTCHA correctly.", 'hide-my-wp' ); ?></li>
                                                    <li><?php echo esc_html__( 'If the reCAPTCHA displays any error, please make sure you fix them before moving forward.', 'hide-my-wp' ); ?></li>
                                                    <li><?php echo esc_html__( 'Do not logout from your account until you are confident that reCAPTCHA is working and you will be able to login again.', 'hide-my-wp' ); ?></li>
                                                    <li><?php echo esc_html__( "If you can't configure reCAPTCHA, switch to Math reCaptcha protection.", 'hide-my-wp' ); ?></li>
                                                </ol>
                                            </div>
                                        <?php } ?>

                                    </div>
                                <?php } else { ?>
                                    <div class="brute_use_captcha" <?php echo( ! HMWP_Classes_Tools::getOption( 'brute_use_captcha' ) ? 'style="display:none;"' : '' ) ?>>
                                        <div class="col-sm-12 text-center py-3 mx-0 my-3">
                                            <?php echo wp_kses_post( sprintf( /* translators: 1: Opening anchor tag. 2: Closing anchor tag. */ __( '%1$sClick here%2$s to create or view keys for Google reCAPTCHA v2.', 'hide-my-wp' ), '<a href="https://www.google.com/recaptcha/admin/create" class="mx-1 text-link font-weight-bold text-uppercase" target="_blank">', '</a>' ) ); ?>
                                        </div>
                                        <div class="col-sm-12 row py-3 mx-0 my-3">
                                            <div class="col-md-4 p-0 font-weight-bold">
                                                <?php echo esc_html__( 'Site Key', 'hide-my-wp' ); ?>:
                                                <div class="small text-black-50"><?php echo wp_kses_post( sprintf( /* translators: 1: Opening anchor tag. 2: Closing anchor tag. */ __( 'Site keys for %1$sGoogle reCaptcha%2$s.', 'hide-my-wp' ), '<a href="https://www.google.com/recaptcha/admin#list" class="text-link" target="_blank">', '</a>' ) ); ?></div>
                                            </div>
                                            <div class="col-md-8 p-0 input-group">
                                                <input type="text" class="form-control " name="brute_captcha_site_key" value="<?php echo esc_attr( HMWP_Classes_Tools::getOption( 'brute_captcha_site_key' ) ) ?>"/>
                                            </div>
                                        </div>
                                        <div class="col-sm-12 row py-3 mx-0 my-3">
                                            <div class="col-md-4 p-0 font-weight-bold">
                                                <?php echo esc_html__( 'Secret Key', 'hide-my-wp' ); ?>:
                                                <div class="small text-black-50"><?php echo wp_kses_post( sprintf( /* translators: 1: Opening anchor tag. 2: Closing anchor tag. */ __( 'Secret keys for %1$sGoogle reCAPTCHA%2$s.', 'hide-my-wp' ), '<a href="https://www.google.com/recaptcha/admin#list" class="text-link" target="_blank">', '</a>' ) ); ?></div>
                                            </div>
                                            <div class="col-md-8 p-0 input-group">
                                                <input type="password" class="form-control " name="brute_captcha_secret_key" value="<?php echo esc_attr( HMWP_Classes_Tools::getOption( 'brute_captcha_secret_key' ) ) ?>" style="-webkit-text-security: disc;" />
                                            </div>
                                        </div>
                                        <div class="col-sm-12 row py-3 mx-0 my-3">
                                            <div class="col-sm-4 p-1">
                                                <div class="font-weight-bold"><?php echo esc_html__( 'reCaptcha Theme', 'hide-my-wp' ); ?>:
                                                </div>
                                            </div>
                                            <div class="col-sm-8 p-0 input-group">
                                                <select name="brute_captcha_theme" class="selectpicker form-control mb-1">
                                                    <?php
                                                    $themes = array(
                                                            esc_html__( 'light', 'hide-my-wp' ),
                                                            esc_html__( 'dark', 'hide-my-wp' )
                                                    );
                                                    foreach ( $themes as $theme ) {
                                                        echo '<option value="' . esc_attr( $theme ) . '" ' . selected( $theme, HMWP_Classes_Tools::getOption( 'brute_captcha_theme' ) ) . '>' . esc_html( ucfirst( $theme ) ) . '</option>';
                                                    } ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-sm-12 row py-3 mx-0 my-3">
                                            <div class="col-sm-4 p-1">
                                                <div class="font-weight-bold"><?php echo esc_html__( 'reCaptcha Language', 'hide-my-wp' ); ?>:
                                                </div>
                                            </div>
                                            <div class="col-sm-8 p-0 input-group">
                                                <select name="brute_captcha_language" class="selectpicker form-control mb-1">
                                                    <?php
                                                    $languages = array(
                                                            esc_html__( 'Auto Detect', 'hide-my-wp' )         => '',
                                                            esc_html__( 'English', 'hide-my-wp' )             => 'en',
                                                            esc_html__( 'Arabic', 'hide-my-wp' )              => 'ar',
                                                            esc_html__( 'Bulgarian', 'hide-my-wp' )           => 'bg',
                                                            esc_html__( 'Catalan Valencian', 'hide-my-wp' )   => 'ca',
                                                            esc_html__( 'Czech', 'hide-my-wp' )               => 'cs',
                                                            esc_html__( 'Danish', 'hide-my-wp' )              => 'da',
                                                            esc_html__( 'German', 'hide-my-wp' )              => 'de',
                                                            esc_html__( 'Greek', 'hide-my-wp' )               => 'el',
                                                            esc_html__( 'British English', 'hide-my-wp' )     => 'en_gb',
                                                            esc_html__( 'Spanish', 'hide-my-wp' )             => 'es',
                                                            esc_html__( 'Persian', 'hide-my-wp' )             => 'fa',
                                                            esc_html__( 'French', 'hide-my-wp' )              => 'fr',
                                                            esc_html__( 'Canadian French', 'hide-my-wp' )     => 'fr_ca',
                                                            esc_html__( 'Hindi', 'hide-my-wp' )               => 'hi',
                                                            esc_html__( 'Croatian', 'hide-my-wp' )            => 'hr',
                                                            esc_html__( 'Hungarian', 'hide-my-wp' )           => 'hu',
                                                            esc_html__( 'Indonesian', 'hide-my-wp' )          => 'id',
                                                            esc_html__( 'Italian', 'hide-my-wp' )             => 'it',
                                                            esc_html__( 'Hebrew', 'hide-my-wp' )              => 'iw',
                                                            esc_html__( 'Jananese', 'hide-my-wp' )            => 'ja',
                                                            esc_html__( 'Korean', 'hide-my-wp' )              => 'ko',
                                                            esc_html__( 'Lithuanian', 'hide-my-wp' )          => 'lt',
                                                            esc_html__( 'Latvian', 'hide-my-wp' )             => 'lv',
                                                            esc_html__( 'Dutch', 'hide-my-wp' )               => 'nl',
                                                            esc_html__( 'Norwegian', 'hide-my-wp' )           => 'no',
                                                            esc_html__( 'Polish', 'hide-my-wp' )              => 'pl',
                                                            esc_html__( 'Portuguese', 'hide-my-wp' )          => 'pt',
                                                            esc_html__( 'Romanian', 'hide-my-wp' )            => 'ro',
                                                            esc_html__( 'Russian', 'hide-my-wp' )             => 'ru',
                                                            esc_html__( 'Slovak', 'hide-my-wp' )              => 'sk',
                                                            esc_html__( 'Slovene', 'hide-my-wp' )             => 'sl',
                                                            esc_html__( 'Serbian', 'hide-my-wp' )             => 'sr',
                                                            esc_html__( 'Swedish', 'hide-my-wp' )             => 'sv',
                                                            esc_html__( 'Thai', 'hide-my-wp' )                => 'th',
                                                            esc_html__( 'Turkish', 'hide-my-wp' )             => 'tr',
                                                            esc_html__( 'Ukrainian', 'hide-my-wp' )           => 'uk',
                                                            esc_html__( 'Vietnamese', 'hide-my-wp' )          => 'vi',
                                                            esc_html__( 'Simplified Chinese', 'hide-my-wp' )  => 'zh_cn',
                                                            esc_html__( 'Traditional Chinese', 'hide-my-wp' ) => 'zh_tw'
                                                    );
                                                    foreach ( $languages as $key => $language ) {
                                                        echo '<option value="' . esc_attr( $language ) . '"  ' . selected( $language, HMWP_Classes_Tools::getOption( 'brute_captcha_language' ) ) . '>' . esc_html( ucfirst( $key ) ) . '</option>';
                                                    } ?>
                                                </select>
                                            </div>
                                        </div>

                                        <?php if ( HMWP_Classes_Tools::getOption( 'brute_captcha_site_key' ) <> '' && HMWP_Classes_Tools::getOption( 'brute_captcha_secret_key' ) <> '' ) { ?>
                                            <div class="col-sm-12 py-3 mx-0 my-3">
                                                <button type="button" class="btn btn-lg btn-default brute_recaptcha_test hmwp_modal" data-remote="<?php echo esc_url( site_url( 'wp-login.php' ) . '?nordt=1&hmwp_preview=' . HMWP_Classes_Tools::getOption( 'hmwp_disable_name' ) ) ?>" data-target="#brute_recaptcha_modal"><?php echo esc_html__( 'reCAPTCHA V2 Test', 'hide-my-wp' ); ?></button>

                                                <h4 class="mt-5 mb-3"><?php echo esc_html__( 'Next Steps', 'hide-my-wp' ); ?></h4>
                                                <ol>
                                                    <li><?php echo wp_kses_post( sprintf( /* translators: 1: Opening <strong> tag. 2: Closing </strong> tag. */ __( 'Run %1$sreCAPTCHA Test%2$s and login inside the popup.', 'hide-my-wp' ), '<strong>', '</strong>' ) ); ?></li>
                                                    <li><?php echo esc_html__( "If you're able to login, you've set reCAPTCHA correctly.", 'hide-my-wp' ); ?></li>
                                                    <li><?php echo esc_html__( 'If the reCAPTCHA displays any error, please make sure you fix them before moving forward.', 'hide-my-wp' ); ?></li>
                                                    <li><?php echo esc_html__( 'Do not logout from your account until you are confident that reCAPTCHA is working and you will be able to login again.', 'hide-my-wp' ); ?></li>
                                                    <li><?php echo esc_html__( "If you can't configure reCAPTCHA, switch to Math reCaptcha protection.", 'hide-my-wp' ); ?></li>
                                                </ol>
                                            </div>
                                        <?php } ?>

                                    </div>
                                    <div class="brute_use_captcha_v3" <?php echo( ! HMWP_Classes_Tools::getOption( 'brute_use_captcha_v3' ) ? 'style="display:none"' : '' ) ?>>
                                        <div class="col-sm-12 text-center py-3 mx-0 my-3">
                                            <?php echo wp_kses_post( sprintf( /* translators: 1: Opening anchor tag. 2: Closing anchor tag. */ __( '%1$sClick here%2$s to create or view keys for Google reCAPTCHA v3.', 'hide-my-wp' ), '<a href="https://www.google.com/recaptcha/admin/create" class="mx-1 text-link font-weight-bold text-uppercase" target="_blank">', '</a>' ) ); ?>
                                        </div>
                                        <div class="col-sm-12 row py-3 mx-0 my-3">
                                            <div class="col-md-4 p-0 font-weight-bold">
                                                <?php echo esc_html__( 'Site Key', 'hide-my-wp' ); ?>:
                                                <div class="small text-black-50"><?php echo wp_kses_post( sprintf( /* translators: 1: Opening anchor tag. 2: Closing anchor tag. */ __( 'Site keys for %1$sGoogle reCaptcha%2$s.', 'hide-my-wp' ), '<a href="https://www.google.com/recaptcha/admin#list" class="text-link" target="_blank">', '</a>' ) ); ?></div>
                                            </div>
                                            <div class="col-md-8 p-0 input-group">
                                                <input type="text" class="form-control " name="brute_captcha_site_key_v3" value="<?php echo esc_attr( HMWP_Classes_Tools::getOption( 'brute_captcha_site_key_v3' ) ) ?>"/>
                                            </div>
                                        </div>
                                        <div class="col-sm-12 row py-3 mx-0 my-3">
                                            <div class="col-md-4 p-0 font-weight-bold">
                                                <?php echo esc_html__( 'Secret Key', 'hide-my-wp' ); ?>:
                                                <div class="small text-black-50"><?php echo wp_kses_post( sprintf( /* translators: 1: Opening anchor tag. 2: Closing anchor tag. */ __( 'Secret keys for %1$sGoogle reCAPTCHA%2$s.', 'hide-my-wp' ), '<a href="https://www.google.com/recaptcha/admin#list" class="text-link" target="_blank">', '</a>' ) ); ?></div>
                                            </div>
                                            <div class="col-md-8 p-0 input-group">
                                                <input type="password" class="form-control " name="brute_captcha_secret_key_v3" value="<?php echo esc_attr( HMWP_Classes_Tools::getOption( 'brute_captcha_secret_key_v3' ) ) ?>" style="-webkit-text-security: disc;" />
                                            </div>
                                        </div>

                                        <?php if ( HMWP_Classes_Tools::getOption( 'brute_captcha_site_key_v3' ) <> '' && HMWP_Classes_Tools::getOption( 'brute_captcha_secret_key_v3' ) <> '' ) { ?>
                                            <div class="col-sm-12 py-3 mx-0 my-3">
                                                <button type="button" class="btn btn-lg btn-default brute_recaptcha_test hmwp_modal" data-remote="<?php echo esc_url( site_url( 'wp-login.php' ) . '?nordt=1&hmwp_preview=' . HMWP_Classes_Tools::getOption( 'hmwp_disable_name' ) ) ?>" data-target="#brute_recaptcha_modal"><?php echo esc_html__( 'reCAPTCHA V3 Test', 'hide-my-wp' ); ?></button>

                                                <h4 class="mt-5 mb-3"><?php echo esc_html__( 'Next Steps', 'hide-my-wp' ); ?></h4>
                                                <ol>
                                                    <li><?php echo wp_kses_post( sprintf( /* translators: 1: Opening <strong> tag. 2: Closing </strong> tag. */ __( 'Run %1$sreCAPTCHA Test%2$s and login inside the popup.', 'hide-my-wp' ), '<strong>', '</strong>' ) ); ?></li>
                                                    <li><?php echo esc_html__( "If you're able to login, you've set reCAPTCHA correctly.", 'hide-my-wp' ); ?></li>
                                                    <li><?php echo esc_html__( 'If the reCAPTCHA displays any error, please make sure you fix them before moving forward.', 'hide-my-wp' ); ?></li>
                                                    <li><?php echo esc_html__( 'Do not logout from your account until you are confident that reCAPTCHA is working and you will be able to login again.', 'hide-my-wp' ); ?></li>
                                                    <li><?php echo esc_html__( "If you can't configure reCAPTCHA, switch to Math reCaptcha protection.", 'hide-my-wp' ); ?></li>
                                                </ol>
                                            </div>
                                        <?php } ?>

                                    </div>
                                <?php } ?>

                                <div>
                                    <div class="col-sm-12 row py-3 mx-0 my-3">
                                        <div class="col-md-4 p-0 font-weight-bold">
                                            <?php echo esc_html__( 'Max Failed Login Attempts', 'hide-my-wp' ); ?>:
                                            <div class="small text-black-50"><?php echo esc_html__( 'How many failed login attempts are allowed before the IP address is blocked.', 'hide-my-wp' ); ?></div>
                                        </div>
                                        <div class="col-md-2 p-0 input-group">
                                            <input type="text" class="form-control " name="brute_max_attempts" value="<?php echo esc_attr( HMWP_Classes_Tools::getOption( 'brute_max_attempts' ) ) ?>"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 row py-3 mx-0 my-3">
                                        <div class="col-md-4 p-0 font-weight-bold">
                                            <?php echo esc_html__( 'IP Lockout Duration', 'hide-my-wp' ); ?>:
                                            <div class="small text-black-50"><?php echo esc_html__( 'Duration of the IP block after failed login attempts.', 'hide-my-wp' ); ?></div>
                                        </div>
                                        <div class="col-md-2 p-0 input-group input-group">
                                            <select name="brute_max_timeout" id="brute_max_timeout" class="form-select form-select-sm mr-2" style="width: 240px;">
                                                <option value="900" <?php selected(900, HMWP_Classes_Tools::getOption('brute_max_timeout')); ?>>
                                                    <?php echo esc_html__('15 minutes', 'hide-my-wp'); ?>
                                                </option>
                                                <option value="1800" <?php selected(1800, HMWP_Classes_Tools::getOption('brute_max_timeout')); ?>>
                                                    <?php echo esc_html__('30 minutes', 'hide-my-wp'); ?>
                                                </option>
                                                <option value="<?php echo esc_attr( HOUR_IN_SECONDS ); ?>" <?php selected(HOUR_IN_SECONDS, HMWP_Classes_Tools::getOption('brute_max_timeout')); ?>>
                                                    <?php echo esc_html__('1 hour', 'hide-my-wp'); ?>
                                                </option>
                                                <option value="<?php echo esc_attr( DAY_IN_SECONDS ); ?>" <?php selected(DAY_IN_SECONDS, HMWP_Classes_Tools::getOption('brute_max_timeout')); ?>>
                                                    <?php echo esc_html__('1 day', 'hide-my-wp'); ?>
                                                </option>
                                                <option value="<?php echo esc_attr( WEEK_IN_SECONDS ); ?>" <?php selected(WEEK_IN_SECONDS, HMWP_Classes_Tools::getOption('brute_max_timeout')); ?>>
                                                    <?php echo esc_html__('1 week', 'hide-my-wp'); ?>
                                                </option>
                                                <option value="<?php echo esc_attr( MONTH_IN_SECONDS ); ?>" <?php selected(MONTH_IN_SECONDS, HMWP_Classes_Tools::getOption('brute_max_timeout')); ?>>
                                                    <?php echo esc_html__('1 month', 'hide-my-wp'); ?>
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 row py-3 mx-0 my-3">
                                        <div class="col-md-4 p-0 font-weight-bold">
                                            <?php echo esc_html__( 'Lockout Message', 'hide-my-wp' ); ?>:
                                            <div class="small text-black-50"><?php echo esc_html__( 'Display lockout message instead of the login form.', 'hide-my-wp' ); ?></div>
                                        </div>
                                        <div class="col-md-8 p-0 input-group input-group">
                                            <textarea type="text" class="form-control " name="hmwp_brute_message" style="height: 80px"><?php echo esc_html( HMWP_Classes_Tools::getOption( 'hmwp_brute_message' ) ) ?></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="modal" id="brute_recaptcha_modal" tabindex="-1" role="dialog" aria-hidden="true">
                                    <div class="modal-dialog modal-lg" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="exampleModalLabel"><?php echo esc_html__( 'reCAPTCHA Test', 'hide-my-wp' ); ?></h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <iframe class="modal-body" style="min-height: 500px;"></iframe>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <div class="col-sm-12 text-center border-light py-2 m-0">
                                <a href="<?php echo esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_firewall&tab=whitelist', true ) ) ?>" target="_blank">
                                    <?php echo esc_html__( 'Manage whitelist & blacklist IP addresses', 'hide-my-wp' ); ?>
                                </a>
                            </div>

                            <div class="col-sm-12 text-center border-top pt-4 my-4">
                                <h5><?php echo wp_kses_post( sprintf( /* translators: %s: Shortcode rendered in a <span> tag. */ __( 'Use the %s shortcode to integrate it with other login forms.', 'hide-my-wp' ), '<span class="text-danger">[hmwp_bruteforce]</span>' ) ); ?></h5>

                                <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/integrating-brute-force-protection-in-elementor-login-forms/' ) ?>" target="_blank">
                                    <?php echo esc_html__( 'Learn how to use the shortcode', 'hide-my-wp' ); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ( HMWP_Classes_Tools::isPluginActive( 'woocommerce/woocommerce.php' ) ) { ?>
                    <div id="woocommerce" style="<?php echo ( $current_tab === 'woocommerce' ? '' : 'display:none;' ); ?>" class="col-sm-12 p-0 m-0 tab-panel ">
                        <div class="card col-sm-12 p-0 m-0">
                            <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__( 'WooCommerce', 'hide-my-wp' ); ?>
                                <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/brute-force-attack-protection/#ghost-protected-forms' ) ?>" target="_blank" class="d-inline-block float-right mr-2"><i class="dashicons dashicons-editor-help"></i></a>
                            </h3>

                            <?php if ( HMWP_Classes_Tools::getOption( 'hmwp_mode' ) == 'default' ) { ?>
                                <div class="card-body">
                                    <div class="col-sm-12 border-0 py-3 mx-0 my-3 text-black-50 text-center">
                                        <?php echo wp_kses_post( sprintf( /* translators: 1: Opening anchor tag. 2: Closing anchor tag. 3: Opening anchor tag. 4: Closing anchor tag. */ __( 'First, you need to activate the %1$sLite Mode%2$s or %3$sGhost Mode%4$s', 'hide-my-wp' ), '<a href="' . esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_permalinks' ) ) . '">', '</a>', '<a href="' . esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_permalinks' ) ) . '">', '</a>' ) ) ?>
                                    </div>
                                </div>
                            <?php } else { ?>
                                <div class="card-body">

                                    <div class="col-sm-12 row mb-1 ml-1 p-2">
                                        <div class="checker col-sm-12 row my-2 py-1">
                                            <div class="col-sm-12 p-0 switch switch-sm">
                                                <input type="hidden" name="hmwp_bruteforce_woocommerce" value="0"/>
                                                <input type="checkbox" id="hmwp_bruteforce_woocommerce" name="hmwp_bruteforce_woocommerce" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_bruteforce_woocommerce' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                                <label for="hmwp_bruteforce_woocommerce"><?php echo esc_html__( 'WooCommerce Support', 'hide-my-wp' ); ?></label>
                                                <div class="text-black-50 ml-5"><?php echo esc_html__( 'Activate the Brute Force protection on WooCommerce login forms.', 'hide-my-wp' ); ?></div>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if ( 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) ) { ?>
                                        <div class="col-sm-12 row mb-1 py-1 mx-2 hmwp_bruteforce_woocommerce">
                                            <div class="checker col-sm-12 row my-2 py-1">
                                                <div class="col-sm-12 p-0 switch switch-xxs pl-5">
                                                    <input type="hidden" name="hmwp_bruteforce_register" value="0"/>
                                                    <input type="checkbox" id="hmwp_bruteforce_register" name="hmwp_bruteforce_register" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_bruteforce_register' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                                    <label for="hmwp_bruteforce_register"><?php echo esc_html__( 'Sign Up Form Protection', 'hide-my-wp' ); ?></label>
                                                    <div class="text-black-50 ml-5"><?php echo esc_html__( 'Activate the Brute Force protection on WooCommerce sign up forms.', 'hide-my-wp' ); ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php } ?>

                                </div>
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>

                <?php do_action( 'hmwp_brute_force_form_end' ) ?>

                 <div class="col-sm-12 m-0 p-2 card-footer save-button text-center">
                    <button type="submit" class="btn rounded-0 btn-success px-5 mr-5 save"><?php echo esc_html__( 'Save', 'hide-my-wp' ); ?></button>
                </div>
            </form>

        </div>

        <div class="hmwp_col hmwp_col_side p-0 pr-2 mr-2 mb-3">
            <?php
            if ( HMWP_Classes_Tools::getOption( 'hmwp_bruteforce' ) ) {
                $view->show('blocks/GoogleEnterprise');
            }
            ?>
            <div class="card col-sm-12 m-0 p-0 rounded-0">
                <div class="card-body f-gray-dark text-left">
                    <h3 class="card-title"><?php echo esc_html__( 'Brute Force Login Protection', 'hide-my-wp' ); ?></h3>
                    <div class="text-info"><?php echo wp_kses_post( sprintf( /* translators: %s: Plugin name followed by HTML line breaks. */ __( 'Protects your website against Brute Force login attacks using %s A common threat web developers face is a password-guessing attack known as a Brute Force attack. A Brute Force attack is an attempt to discover a password by systematically trying every possible combination of letters, numbers, and symbols until you discover the one correct combination that works.', 'hide-my-wp' ), esc_html( HMWP_Classes_Tools::getOption( 'hmwp_plugin_name' ) ) . '<br><br>' ) ); ?></div>
                </div>
            </div>
            <div class="card col-sm-12 p-0">
                <div class="card-body f-gray-dark text-left border-bottom">
                    <h3 class="card-title"><?php echo esc_html__( 'Features', 'hide-my-wp' ); ?></h3>
                    <ul class="text-info" style="margin-left: 16px; list-style: circle;">
                        <li><?php echo esc_html__( "Limit the number of allowed login attempts using normal login form.", 'hide-my-wp' ); ?></li>
                        <li><?php echo esc_html__( "Math & Google reCaptcha verification while logging in.", 'hide-my-wp' ); ?></li>
                        <li><?php echo esc_html__( "Manually block/unblock IP addresses.", 'hide-my-wp' ); ?></li>
                        <li><?php echo esc_html__( "Manually whitelist trusted IP addresses.", 'hide-my-wp' ); ?></li>
                        <li><?php echo esc_html__( "Option to inform user about remaining attempts on login page.", 'hide-my-wp' ); ?></li>
                        <li><?php echo esc_html__( "Custom message to show to blocked users.", 'hide-my-wp' ); ?></li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</div>
<noscript>
    <style>
        #hmwp_wrap .tab-panel:not(.tab-panel-first) {
            display: block;
        }
    </style>
</noscript>