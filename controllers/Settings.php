<?php
/**
 * Settings Class
 * Called when the plugin setting is loaded
 *
 * @file The Settings file
 * @package HMWP/Settings
 * @since 4.0.0
 */

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Controllers_Settings extends HMWP_Classes_FrontController {

    /**
     * @var $user WP_User
     */
    public $user;

	/**
	 * List of events/actions
	 *
	 * @var $eventsListTable HMWP_Models_EventsListTable
	 */
	public $eventsListTable;

    /**
     * List of events/actions
     *
     * @var $trafficListTable HMWP_Models_ThreatsListTable
     */
	public $trafficListTable;

	public function __construct() {
		parent::__construct();

		// If save settings is required, show the alert
		if ( HMWP_Classes_Tools::getOption( 'changes' ) ) {
			add_action( 'admin_notices', array( $this, 'showSaveRequires' ) );
			HMWP_Classes_Tools::saveOptions( 'changes', false );
		}

		if ( ! HMWP_Classes_Tools::getOption( 'hmwp_valid' ) ) {
			add_action( 'admin_notices', array( $this, 'showPurchaseRequires' ) );
		}

		// Add the Settings class only for the plugin settings page
		add_filter( 'admin_body_class', array( HMWP_Classes_ObjController::getClass( 'HMWP_Models_Menu' ), 'addSettingsClass' ) );

		// If the option to prevent broken layout is on
		if ( HMWP_Classes_Tools::getOption( 'prevent_slow_loading' ) ) {

			//check the frontend on settings successfully saved
			add_action( 'hmwp_confirmed_settings', function() {
				//check the frontend and prevent from showing brake websites
				$url      = _HMWP_URL_ . '/view/assets/img/logo.svg?hmwp_preview=' . HMWP_Classes_Tools::getOption( 'hmwp_disable_name' );
				$url      = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rewrite' )->find_replace_url( $url );
				$response = HMWP_Classes_Tools::hmwp_localcall( $url, array( 'redirection' => 0, 'cookies' => false ) );

				//If the plugin logo is not loading correctly, switch off the path changes
				if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) == 404 ) {
					HMWP_Classes_Tools::saveOptions( 'file_mappings', array( home_url() ) );
				}
			} );
		}

		// Save the login path on Cloud
		add_action( 'hmwp_apply_permalink_changes', function() {
			HMWP_Classes_Tools::sendLoginPathsApi();
		} );

	}

	/**
	 * Called on Menu hook
	 * Init the Settings page
	 *
	 * @return void
	 * @throws Exception
	 */
	public function init() {
        /////////////////////////////////////////////////
		// Get the current Page
		$page = HMWP_Classes_Tools::getValue( 'page' );

		if ( strpos( $page, '_' ) !== false ) {
			$tab = substr( $page, ( strpos( $page, '_' ) + 1 ) );

			if ( method_exists( $this, $tab ) ) {
				call_user_func( array( $this, $tab ) );
			}
		}
		/////////////////////////////////////////////////

		// We need that function so make sure is loaded
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			include_once ABSPATH . '/wp-admin/includes/plugin.php';
		}

		if ( HMWP_Classes_Tools::isNginx() && HMWP_Classes_Tools::getOption( 'test_frontend' ) && HMWP_Classes_Tools::getOption( 'hmwp_mode' ) <> 'default' ) {
			$config_file = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rules' )->getConfFile();
			if ( HMWP_Classes_Tools::isLocalFlywheel() ) {
				if ( strpos( $config_file, '/includes/' ) !== false ) {
					$config_file = substr( $config_file, strpos( $config_file, '/includes/' ) + 1 );
				}
                /* translators: 1: Code snippet and tutorial link HTML. */
                HMWP_Classes_Error::setNotification( wp_kses_post( sprintf( __( 'Local & NGINX detected. In case you didn\'t add the code in the NGINX config already, please add the following line. %1$s', 'hide-my-wp' ), '<br /><br /><code><strong>include ' . esc_html( $config_file ) . ';</strong></code> <br /><strong><br /><a href="' . esc_url( HMWP_Classes_Tools::getOption( 'hmwp_plugin_website' ) . '/kb/local-flywheel-wp-ghost-setup/' ) ) . '" target="_blank">' . esc_html__( 'Learn how to setup on Local & Nginx', 'hide-my-wp' ) . ' >></a></strong>' ) , 'notice', false );
            } else {
                /* translators: 1: Code snippet and tutorial link HTML. */
                HMWP_Classes_Error::setNotification( wp_kses_post( sprintf( __( 'NGINX detected. In case you didn\'t add the code in the NGINX config already, please add the following line. %1$s', 'hide-my-wp' ), '<br /><br /><code><strong>include ' . esc_html( $config_file ) . ';</strong></code> <br /><strong><br /><a href="' . esc_url( HMWP_Classes_Tools::getOption( 'hmwp_plugin_website' ) . '/kb/setup-wp-ghost-on-nginx-server/' ) ) . '" target="_blank">' . esc_html__( 'Learn how to setup on Nginx server', 'hide-my-wp' ) . ' >></a></strong>' ) , 'notice', false );
            }
		}

		// Setting Alerts based on Logout and Error statements
		if ( get_transient( 'hmwp_restore' ) == 1 ) {
			$restoreLink = '<a href="' . esc_url( add_query_arg( array( 'hmwp_nonce' => wp_create_nonce( 'hmwp_restore_settings' ), 'action'     => 'hmwp_restore_settings' ) ) ) . '" class="btn btn-default btn-sm ml-3" />' . esc_html__( "Restore Settings", 'hide-my-wp' ) . '</a>';
			HMWP_Classes_Error::setNotification( esc_html__( 'Do you want to restore the last saved settings?', 'hide-my-wp' ) . $restoreLink );
		}

		// Show the config rules to make sure they are okay
		if ( HMWP_Classes_Tools::getValue( 'hmwp_config' ) ) {
			//Initialize WordPress Filesystem
			$wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

			$config_file = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rules' )->getConfFile();
			if ( $config_file <> '' && $wp_filesystem->exists( $config_file ) ) {
				$rules = $wp_filesystem->get_contents( HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rules' )->getConfFile() );
				HMWP_Classes_Error::setNotification( '<pre>' . $rules . '</pre>' );
			}
		}

		// Load the css for Settings
		HMWP_Classes_ObjController::getClass( 'HMWP_Classes_DisplayController' )->loadMedia( 'popper' );

		if ( is_rtl() ) {
			HMWP_Classes_ObjController::getClass( 'HMWP_Classes_DisplayController' )->loadMedia( 'bootstrap.rtl' );
			HMWP_Classes_ObjController::getClass( 'HMWP_Classes_DisplayController' )->loadMedia( 'rtl' );
		} else {
			HMWP_Classes_ObjController::getClass( 'HMWP_Classes_DisplayController' )->loadMedia( 'bootstrap' );
		}

		HMWP_Classes_ObjController::getClass( 'HMWP_Classes_DisplayController' )->loadMedia( 'bootstrap-select' );
		HMWP_Classes_ObjController::getClass( 'HMWP_Classes_DisplayController' )->loadMedia( 'font-awesome' );
		HMWP_Classes_ObjController::getClass( 'HMWP_Classes_DisplayController' )->loadMedia( 'switchery' );
		HMWP_Classes_ObjController::getClass( 'HMWP_Classes_DisplayController' )->loadMedia( 'alert' );
		HMWP_Classes_ObjController::getClass( 'HMWP_Classes_DisplayController' )->loadMedia( 'settings' );

        wp_enqueue_script( 'clipboard' );

		// Show connect for activation
		if ( ! HMWP_Classes_Tools::getOption( 'hmwp_token' ) ) {
			$this->show( 'Connect' );

			return;
		}

		if ( HMWP_Classes_Tools::getOption( 'error' ) ) {
			HMWP_Classes_Error::setNotification( esc_html__( 'There is a configuration error in the plugin. Please Save the settings again and follow the instruction.', 'hide-my-wp' ) );
		}

		if ( HMWP_Classes_Tools::isWpengine() ) {
			add_filter( 'hmwp_option_hmwp_mapping_url_show', "__return_false" );
		}

		// Check compatibilities with other plugins
		HMWP_Classes_ObjController::getClass( 'HMWP_Models_Compatibility' )->getAlerts();

		// Show errors on top
		HMWP_Classes_ObjController::getClass( 'HMWP_Classes_Error' )->hookNotices();

		echo '<meta name="viewport" content="width=640">';
        /* translators: 1: Plugin name. */
        echo '<noscript><div class="alert-danger text-center py-3">' . sprintf( esc_html__( 'Javascript is disabled on your browser! You need to activate the javascript in order to use %1$s plugin.', 'hide-my-wp' ), esc_html( HMWP_Classes_Tools::getOption( 'hmwp_plugin_name' ) ) ) . '</div></noscript>';
        $this->show( ucfirst( str_replace( 'hmwp_', '', $page ) ) );
    }

    /**
     * Load Media for the tweaks page
     * @return void
     */
    public function tweaks() {
        wp_enqueue_media();
    }

	/**
	 * Log the user event
	 *
	 * @throws Exception
	 */
	public function log() {

        if ( apply_filters( 'hmwp_showtrafficlogs', true ) ) {
            $this->trafficListTable = HMWP_Classes_ObjController::getClass( 'HMWP_Models_ThreatsListTable' );
        }

        if ( apply_filters( 'hmwp_showeventlogs', true ) ) {
            $this->eventsListTable = HMWP_Classes_ObjController::getClass( 'HMWP_Models_EventsListTable' );
		}

        if ( HMWP_Classes_Tools::getValue( 'hmwp_message' ) ) {
            HMWP_Classes_Error::setNotification( HMWP_Classes_Tools::getValue( 'hmwp_message', false, true ), 'success' );
        }

	}

	/**
	 * Log the user event
	 *
	 * @throws Exception
	 */
	public function templogin() {
		if ( ! HMWP_Classes_Tools::getOption( 'hmwp_token' ) ) {
			return;
		}

		// Clear previous alerts
		HMWP_Classes_Error::clearErrors();

		if ( HMWP_Classes_Tools::getValue( 'action' ) == 'hmwp_update' && HMWP_Classes_Tools::getValue( 'user_id' ) ) {
			$user_id = HMWP_Classes_Tools::getValue( 'user_id' );

			$this->user = get_user_by( 'ID', $user_id );

			if ( $this->user instanceof WP_User ) {
				$this->user->details = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Templogin' )->getUserDetails( $this->user );
			}
		}

		if ( HMWP_Classes_Tools::getValue( 'hmwp_message' ) ) {
			HMWP_Classes_Error::setNotification( HMWP_Classes_Tools::getValue( 'hmwp_message', false, true ), 'success' );
		}

	}

	/**
	 * Firewall page init
	 *
	 * @return void
	 * @throws Exception
	 */
	public function twofactor() {

        HMWP_Classes_ObjController::getClass( 'HMWP_Classes_DisplayController' )->loadMedia( 'qrcode' );
        HMWP_Classes_ObjController::getClass( 'HMWP_Classes_DisplayController' )->loadMedia( 'twofactor' );

	}

	/**
	 * Load media header
	 */
	public function hookHead() {
	}

	/**
	 * Show this message to notify the user when to update the settings
	 *
	 * @return void
	 * @throws Exception
	 */
	public function showSaveRequires() {
		if ( HMWP_Classes_Tools::getOption( 'hmwp_hide_plugins' ) || HMWP_Classes_Tools::getOption( 'hmwp_hide_themes' ) ) {
			global $pagenow;
			if ( $pagenow == 'plugins.php' ) {

				HMWP_Classes_ObjController::getClass( 'HMWP_Classes_DisplayController' )->loadMedia( 'alert' );

				?>
                <div class="notice notice-warning is-dismissible" style="margin-left: 0;">
                    <div style="display: inline-block;">
                        <form action="<?php echo esc_url( HMWP_Classes_Tools::getSettingsUrl() ) ?>" method="POST">
							<?php wp_nonce_field( 'hmwp_newpluginschange', 'hmwp_nonce' ) ?>
                            <input type="hidden" name="action" value="hmwp_newpluginschange"/>
                            <p>
                                <?php /* translators: 1: Plugin name, 2: Opening <button> tag, 3: Closing </button> tag. */
                                echo wp_kses_post( sprintf( __( 'New Plugin/Theme detected! Update %1$s settings to hide it. %2$sClick here%3$s', 'hide-my-wp' ), esc_html( HMWP_Classes_Tools::getOption( 'hmwp_plugin_name' ) ), '<button type="submit" style="color: blue; text-decoration: underline; cursor: pointer; background: none; border: none;">', '</button>' ) ); ?>
                            </p>
                        </form>

                    </div>
                </div>
				<?php
			}
		}
	}

	public function showPurchaseRequires() {
		global $pagenow;

		$expires = (int) HMWP_Classes_Tools::getOption( 'hmwp_expires' );

		if ( $expires > 0 ) {
            /* translators: 1: Opening <strong> tag, 2: Plugin name, 3: Expiration date, 4: Closing </strong> tag, 5: Opening <a> tag to account page, 6: Closing </a> tag. */
            $error = wp_kses_post( sprintf( __( 'Your %1$s %2$s license expired on %3$s %4$s. To keep your website security up to date please make sure you have a valid subscription on %5$s%6$s', 'hide-my-wp' ), '<strong>', esc_html( HMWP_Classes_Tools::getOption( 'hmwp_plugin_name' ) ), esc_html( gmdate( 'd M Y', $expires ) ), '</strong>', '<a href="' . esc_url( HMWP_Classes_Tools::getCloudUrl( 'orders' ) ) . '" style="line-height: 30px;" target="_blank">', esc_html( _HMWP_ACCOUNT_SITE_ ) . '</a>' ) );

			if ( $pagenow == 'plugins.php' ) {
				$ignore_errors = (array) HMWP_Classes_Tools::getOption( 'ignore_errors' );

				if ( ! empty( $ignore_errors ) && in_array( strlen( $error ), $ignore_errors ) ) {
					return;
				}

				$url = add_query_arg( array(
					'hmwp_nonce' => wp_create_nonce( 'hmwp_ignoreerror' ),
					'action'     => 'hmwp_ignoreerror',
					'hash'       => strlen( $error )
				) );

				?>
                <div class="col-sm-12 mx-0 hmwp_notice error notice">
                    <div style="display: inline-block;"><p> <?php echo wp_kses_post( $error ) ?> </p></div>
                    <a href="<?php echo esc_url( $url ) ?>" style="float: right; color: gray; text-decoration: underline; font-size: 0.8rem;">
                        <p><?php echo esc_html__( 'ignore alert', 'hide-my-wp' ) ?></p></a>
                </div>
				<?php
			} else {
				HMWP_Classes_Error::setNotification( $error );
			}
		}
	}

	/**
	 * Get the Admin Toolbar
	 *
	 * @param null $current
	 *
	 * @return string $content
	 * @throws Exception
	 */
    public function getAdminTabs( $current = null ) {

        // Get subtabs
        $subtabs = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Menu' )->getSubMenu( $current );

        // Extract tab slugs
        $tabs = array_column( $subtabs, 'tab' );

        // Get current tab from URL
        $current_tab = sanitize_key( HMWP_Classes_Tools::getValue( 'tab' ) );

        // Validate or fallback to first tab
        if ( empty( $tabs ) ) {
            $current_tab = '';
        } elseif ( ! $current_tab || ! in_array( $current_tab, $tabs, true ) ) {
            $current_tab = reset( $tabs );
        }

        // Build menu
        $content  = '<div class="hmwp_nav d-flex flex-column bd-highlight mb-3">';


        $svg =  '<img src="' . esc_url( _HMWP_ASSETS_URL_ . 'img/logo.svg' ) . '" class="ml-0 mr-2" alt="">';

        if ( ! HMWP_Classes_Tools::getOption( 'hmwp_plugin_logo' ) ){
            $logoFile = _HMWP_ASSETS_DIR_ . 'img/logo.svg';

            if ( file_exists( $logoFile ) && is_readable( $logoFile ) ) {
                $svg = file_get_contents( $logoFile );
            }
        } else {
            $svg =  '<img src="' . esc_url( HMWP_Classes_Tools::getOption( 'hmwp_plugin_logo' ) ) . '" class="ml-0 mr-2" alt="">';
        }

        $content .= '<div class="m-0 px-3 pt-2 pb-3 font-dark font-weight-bold text-logo">' . $svg . '</div>';

        foreach ( $subtabs as $tab ) {

            $is_active = ( $current_tab === $tab['tab'] );

            $url = HMWP_Classes_Tools::getSettingsUrl(
                    $current . '&tab=' . $tab['tab'],
                    true
            );

            $content .= '<a href="' . esc_url( $url ) . '"  class="m-0 px-3 py-3 font-dark hmwp_nav_item' . ( $is_active ? ' active' : '' ) . '"   data-tab="' . esc_attr( $tab['tab'] ) . '">' . wp_kses_post( $tab['title'] ) . '</a>';
        }

        $content .= '</div>';

        $allowed = wp_kses_allowed_html( 'post' );

        // Force-allow the tags/attributes this internally-built nav uses, so a third-party wp_kses_allowed_html filter can't strip its classes and break the layout.
        foreach ( array( 'a', 'div', 'span', 'i', 'img' ) as $tag ) {
                if ( ! isset( $allowed[ $tag ] ) || ! is_array( $allowed[ $tag ] ) ) {
                        $allowed[ $tag ] = array();
                }
                $allowed[ $tag ]['class']    = true;
                $allowed[ $tag ]['style']    = true;
                $allowed[ $tag ]['data-tab'] = true;
        }
        $allowed['a']['href']  = true;
        $allowed['img']['src'] = true;
        $allowed['img']['alt'] = true;

        // Inline SVG logo tags.
        $allowed['svg'] = array(
                'xmlns'   => true,
                'viewbox' => true,
                'fill'    => true,
                'width'   => true,
                'height'  => true,
        );

        $allowed['style'] = array(
                'type' => true,
        );

        $allowed['g'] = array(
                'clip-path' => true,
                'opacity'   => true,
        );

        $allowed['path'] = array(
                'd'       => true,
                'fill'    => true,
                'opacity' => true,
                'id'      => true,
                'stroke'  => true,
        );

        $allowed['defs'] = array();

        $allowed['clippath'] = array(
                'id' => true,
        );

        $allowed['rect'] = array(
                'width'  => true,
                'height' => true,
                'fill'   => true,
        );

        echo wp_kses( $content, $allowed );
    }

	/**
	 * Called when an action is triggered
	 *
	 * @throws Exception
	 */
	public function action() {
		parent::action();

        // Check if the current user has the 'hmwp_manage_settings' capability
        if ( ! HMWP_Classes_Tools::userCan( HMWP_CAPABILITY ) ) {
            return;
        }

		switch ( HMWP_Classes_Tools::getValue( 'action' ) ) {
			case 'hmwp_settings':

				//Save the settings
				if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] === 'POST' ) {

					// Save the whitelist IPs
					$this->saveWhiteListIps();

					// Save the whitelist paths
					$this->saveWhiteListPaths();

                    // If preset settings is selected, load the preset settings
                    if ( $index = HMWP_Classes_Tools::getValue( 'hmwp_preset_settings' ) ) {

                        /** @var HMWP_Models_Presets $presetsModel */
                        $presetsModel = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Presets' );

                        if ( method_exists( $presetsModel, 'getPreset' . $index ) ) {
                            $presets = call_user_func( array( $presetsModel, 'getPreset' . $index ) );
                        }

                        if ( ! empty( $presets ) ) {
                            foreach ($presets as $key => $value) {
                                $_POST[$key] = $value;
                            }
                        }

                    }

                    /**  @var $this ->model HMWP_Models_Settings */
                    $this->model->savePermalinks( $_POST ); //phpcs:ignore

                    HMWP_Classes_Tools::saveOptions( 'hmwp_uniquelogin_title', HMWP_Classes_Tools::getValue( 'hmwp_uniquelogin_title' ) );

                }

				//load the after saving settings process
				if ( $this->model->applyPermalinksChanged() ) {

                    // Show saved message on success
					HMWP_Classes_Error::setNotification( esc_html__( 'Saved', 'hide-my-wp' ), 'success' );

					//add action for later use
					do_action( 'hmwp_settings_saved' );
				}

				break;
			case 'hmwp_tweakssettings':
				//Save the settings
				if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
					$this->model->saveValues( $_POST ); //phpcs:ignore
				}

				HMWP_Classes_Tools::saveOptions( 'hmwp_disable_click_message', HMWP_Classes_Tools::getValue( 'hmwp_disable_click_message' ) );
				HMWP_Classes_Tools::saveOptions( 'hmwp_disable_inspect_message', HMWP_Classes_Tools::getValue( 'hmwp_disable_inspect_message' ) );
				HMWP_Classes_Tools::saveOptions( 'hmwp_disable_source_message', HMWP_Classes_Tools::getValue( 'hmwp_disable_source_message') );
				HMWP_Classes_Tools::saveOptions( 'hmwp_disable_copy_paste_message', HMWP_Classes_Tools::getValue( 'hmwp_disable_copy_paste_message' ) );
				HMWP_Classes_Tools::saveOptions( 'hmwp_disable_drag_drop_message', HMWP_Classes_Tools::getValue( 'hmwp_disable_drag_drop_message' ) );

				//load the after saving settings process
				if ( $this->model->applyPermalinksChanged() ) {
					HMWP_Classes_Error::setNotification( esc_html__( 'Saved', 'hide-my-wp' ), 'success' );

					//add action for later use
					do_action( 'hmwp_tweakssettings_saved' );
				}

				break;
			case 'hmwp_mappsettings':
				//Save Mapping for classes and ids
				HMWP_Classes_Tools::saveOptions( 'hmwp_mapping_classes', HMWP_Classes_Tools::getValue( 'hmwp_mapping_classes' ) );
				HMWP_Classes_Tools::saveOptions( 'hmwp_mapping_file', HMWP_Classes_Tools::getValue( 'hmwp_mapping_file' ) );
				HMWP_Classes_Tools::saveOptions( 'hmwp_file_cache', HMWP_Classes_Tools::getValue( 'hmwp_file_cache' ) );

				//Save the patterns as array
				//Save CDN URLs
				if ( $urls = HMWP_Classes_Tools::getValue( 'hmwp_cdn_urls' ) ) {
					$hmwp_cdn_urls = array();
					foreach ( $urls as $row ) {
						if ( $row <> '' ) {
							$row = preg_replace( '/[^A-Za-z0-9-_.:\/]/', '', $row );
							if ( $row <> '' ) {
								$hmwp_cdn_urls[] = $row;
							}
						}
					}
					HMWP_Classes_Tools::saveOptions( 'hmwp_cdn_urls', wp_json_encode( $hmwp_cdn_urls ) );
				}

				//Save Text Mapping
				if ( $hmwp_text_mapping_from = HMWP_Classes_Tools::getValue( 'hmwp_text_mapping_from' ) ) {
					if ( $hmwp_text_mapping_to = HMWP_Classes_Tools::getValue( 'hmwp_text_mapping_to' ) ) {
						$this->model->saveTextMapping( $hmwp_text_mapping_from, $hmwp_text_mapping_to );
					}
				}

				//Save URL mapping
				if ( $hmwp_url_mapping_from = HMWP_Classes_Tools::getValue( 'hmwp_url_mapping_from' ) ) {
					if ( $hmwp_url_mapping_to = HMWP_Classes_Tools::getValue( 'hmwp_url_mapping_to' ) ) {
						$this->model->saveURLMapping( $hmwp_url_mapping_from, $hmwp_url_mapping_to );
					}
				}

				//load the after saving settings process
				if ( $this->model->applyPermalinksChanged( true ) ) {
					HMWP_Classes_Error::setNotification( esc_html__( 'Saved', 'hide-my-wp' ), 'success' );

					//add action for later use
					do_action( 'hmwp_mappsettings_saved' );
				}

				break;
			case 'hmwp_firewall':
				// Save the settings
				if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] === 'POST' ) {

					// Save the whitelist IPs
					$this->saveWhiteListIps();

					// Blacklist ips,hostnames, user-agents, referrers
					$this->saveBlackListIps();
					$this->saveBlackListHostnames();
					$this->saveBlackListUserAgents();
					$this->saveBlackListReferrers();

					// Save the whitelist paths
					$this->saveWhiteListPaths();
					$this->saveWhiteListRules();

                    // Save the rest of the settings
					$this->model->saveValues( $_POST ); //phpcs:ignore

					// If no change is made on settings, just return
					if ( ! $this->model->checkOptionsChange() ) {
						return;
					}

					// Save the rules and add the rewrites
					$this->model->saveRules();

					// Load the after saving settings process
					if ( $this->model->applyPermalinksChanged() ) {
						HMWP_Classes_Error::setNotification( esc_html__( 'Saved', 'hide-my-wp' ), 'success' );

						// Add action for later use
						do_action( 'hmwp_firewall_saved' );

					}

				}

				break;
            case 'hmwp_firewall_whitelist_path':
                $path = HMWP_Classes_Tools::getValue( 'path' );

                if ( $path <> '' ) {

                    $urls = HMWP_Classes_Tools::getOption( 'whitelist_urls' );

                    // Get the array of urls
                    if ( ! empty( $urls ) ) {
                        $urls = json_decode( $urls, true );
                    }

                    // Add the new path
                    $urls[] = $path;

                    // Remove duplicates
                    $urls = array_unique( $urls );

                    HMWP_Classes_Tools::saveOptions( 'whitelist_urls', wp_json_encode( $urls ) );
                }

                $redirect = remove_query_arg( array( 'hmwp_nonce', 'action', 'hash', 'path', 'rule', 'ip' ) ) ;
                $redirect = add_query_arg( 'hmwp_message', esc_html__( 'Saved', 'hide-my-wp' ), $redirect );

                if ( wp_safe_redirect( esc_url_raw( $redirect ) ) ) {
                    exit;
                }
                break;
            case 'hmwp_firewall_whitelist_rule':
                $rule = HMWP_Classes_Tools::getValue( 'rule' );

                if ( $rule <> '' ) {

                    $rules = HMWP_Classes_Tools::getOption( 'whitelist_rules' );

                    // Get the array of rules
                    if ( ! empty( $rules ) ) {
                        $rules = json_decode( $rules, true );
                    }

                    // Add the new rule
                    $rules[] = $rule;

                    // Remove duplicates
                    $rules = array_unique( $rules );

                    HMWP_Classes_Tools::saveOptions( 'whitelist_rules', wp_json_encode( $rules ) );
                }

                $redirect = remove_query_arg( array( 'hmwp_nonce', 'action', 'hash', 'path', 'rule', 'ip' ) ) ;
                $redirect = add_query_arg( 'hmwp_message', esc_html__( 'Saved', 'hide-my-wp' ), $redirect );

                if ( wp_safe_redirect( esc_url_raw( $redirect ) ) ) {
                    exit;
                }
                break;
            case 'hmwp_firewall_blacklist_ip':
                $ip = HMWP_Classes_Tools::getValue( 'ip');

                if ( $ip <> '' ) {

                    $ips = HMWP_Classes_Tools::getOption( 'banlist_ip' );

                    // Get the array of urls
                    if ( ! empty( $ips ) ) {
                        $ips = json_decode( $ips, true );
                    }

                    // Add the new ip
                    $ips[] = $ip;

                    // Remove duplicates
                    $ips = array_unique( $ips );

                    HMWP_Classes_Tools::saveOptions( 'banlist_ip', wp_json_encode( $ips ) );

                }

                $redirect = remove_query_arg( array( 'hmwp_nonce', 'action', 'hash', 'path', 'rule', 'ip' ) ) ;
                $redirect = add_query_arg( 'hmwp_message', esc_html__( 'Saved', 'hide-my-wp' ), $redirect );

                if ( wp_safe_redirect( esc_url_raw( $redirect ) ) ) {
                    exit;
                }
                break;
            case 'hmwp_geo_download':
	            // Download the Geo Country database
	            HMWP_Classes_ObjController::getClass( 'HMWP_Models_Geoip_GeoLocator' )->downloadDatabase( true );

                if ( wp_safe_redirect( esc_url_raw( remove_query_arg( array( 'hmwp_nonce', 'action', 'hash' ) ) ) ) ) {
                    exit;
                }

                break;
			case 'hmwp_advsettings':

				if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
					$this->model->saveValues( $_POST ); //phpcs:ignore

					//save the loading moment
					HMWP_Classes_Tools::saveOptions( 'hmwp_firstload', in_array( 'first', HMWP_Classes_Tools::getOption( 'hmwp_loading_hook' ) ) );
					HMWP_Classes_Tools::saveOptions( 'hmwp_priorityload', in_array( 'priority', HMWP_Classes_Tools::getOption( 'hmwp_loading_hook' ) ) );
					HMWP_Classes_Tools::saveOptions( 'hmwp_laterload', in_array( 'late', HMWP_Classes_Tools::getOption( 'hmwp_loading_hook' ) ) );

					if ( HMWP_Classes_Tools::getOption( 'hmwp_firstload' ) ) {
						//Add the must-use plugin to force loading before all others plugins
						HMWP_Classes_ObjController::getClass( 'HMWP_Models_Compatibility' )->addMUPlugin();
					} else {
						HMWP_Classes_ObjController::getClass( 'HMWP_Models_Compatibility' )->deleteMUPlugin();
					}


					//load the after saving settings process
					if ( $this->model->applyPermalinksChanged() ) {
						HMWP_Classes_Error::setNotification( esc_html__( 'Saved', 'hide-my-wp' ), 'success' );

						//add action for later use
						do_action( 'hmwp_advsettings_saved' );

					}

				}

				//add action for later use
				do_action( 'hmwp_advsettings_saved' );

				break;
			case 'hmwp_savecachepath':

				//Save the option to change the paths in the cache file
				HMWP_Classes_Tools::saveOptions( 'hmwp_change_in_cache', HMWP_Classes_Tools::getValue( 'hmwp_change_in_cache' ) );

				//Save the cache directory
				$directory = HMWP_Classes_Tools::getValue( 'hmwp_change_in_cache_directory' );

				if ( $directory <> '' ) {
					$directory = trim( $directory, '/' );

					//Remove subdirs
					if ( strpos( $directory, '/' ) !== false ) {
						$directory = substr( $directory, 0, strpos( $directory, '/' ) );
					}

					if ( ! in_array( $directory, array( 'languages', 'mu-plugins', 'plugins', 'themes', 'upgrade', 'uploads' ) ) ) {
						HMWP_Classes_Tools::saveOptions( 'hmwp_change_in_cache_directory', $directory );
					} else {
						wp_send_json_error( esc_html__( 'Path not allowed. Avoid paths like plugins and themes.', 'hide-my-wp' ) );
					}
				} else {
					HMWP_Classes_Tools::saveOptions( 'hmwp_change_in_cache_directory', '' );
				}

				if ( HMWP_Classes_Tools::isAjax() ) {
					wp_send_json_success( esc_html__( 'Saved', 'hide-my-wp' ) );
				}

				break;
			case 'hmwp_logsettings':
                HMWP_Classes_Tools::saveOptions( 'hmwp_threats_log', HMWP_Classes_Tools::getValue( 'hmwp_threats_log', 0 ) );
                HMWP_Classes_Tools::saveOptions( 'hmwp_activity_log', HMWP_Classes_Tools::getValue( 'hmwp_activity_log', 0 ) );

                // Clear the cache if there are no errors
                if ( ! HMWP_Classes_Tools::getOption( 'error' ) ) {

                    if ( ! HMWP_Classes_Tools::getOption( 'logout' ) ) {
                        HMWP_Classes_Tools::saveOptionsBackup();
                    }

                    HMWP_Classes_Error::setNotification( esc_html__( 'Saved', 'hide-my-wp' ), 'success' );
                }

                // Add action for later use
                do_action( 'hmwp_logsettings_saved' );

                if ( wp_safe_redirect( esc_url_raw( remove_query_arg( array( 'hmwp_nonce', 'action' ) ) ) ) ) {
                    exit;
                }

                break;
			case 'hmwp_ignore_errors':
				//Empty WordPress rewrites count for 404 error.
				//This happens when the rules are not saved through config file
				HMWP_Classes_Tools::saveOptions( 'file_mappings', array() );

				break;
			case 'hmwp_abort':
			case 'hmwp_restore_settings':
				//get keys that should not be replaced
				$tmp_options = array(
					'hmwp_token',
					'api_token',
					'hmwp_plugin_name',
					'hmwp_plugin_menu',
					'hmwp_plugin_logo',
					'hmwp_plugin_website',
					'hmwp_plugin_account_show',
				);

				$tmp_options = array_fill_keys( $tmp_options, true );
				foreach ( $tmp_options as $keys => &$value ) {
					$value = HMWP_Classes_Tools::getOption( $keys );
				}

				//get the safe options from database
				HMWP_Classes_Tools::$options = HMWP_Classes_Tools::getOptions( true );

				//set tmp data back to options
				foreach ( $tmp_options as $keys => $value ) {
					HMWP_Classes_Tools::$options[ $keys ] = $value;
				}
				HMWP_Classes_Tools::saveOptions();

				//set frontend, error & logout to false
				HMWP_Classes_Tools::saveOptions( 'test_frontend', false );
				HMWP_Classes_Tools::saveOptions( 'file_mappings', array() );
				HMWP_Classes_Tools::saveOptions( 'error', false );
				HMWP_Classes_Tools::saveOptions( 'logout', false );

				//load the after saving settings process
				$this->model->applyPermalinksChanged( true );

				break;
			case 'hmwp_newpluginschange':
				//reset the change notification
				HMWP_Classes_Tools::saveOptions( 'changes', 0 );
				remove_action( 'admin_notices', array( $this, 'showSaveRequires' ) );

				//generate unique names for plugins if needed
				if ( HMWP_Classes_Tools::getOption( 'hmwp_hide_plugins' ) ) {
					HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rewrite' )->hidePluginNames();
				}
				if ( HMWP_Classes_Tools::getOption( 'hmwp_hide_themes' ) ) {
					HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rewrite' )->hideThemeNames();
				}

				//load the after saving settings process
				if ( $this->model->applyPermalinksChanged() ) {
					HMWP_Classes_Error::setNotification( esc_html__( 'The list of plugins and themes was updated with success!', 'hide-my-wp' ), 'success' );
				}

				break;
			case 'hmwp_confirm':
				HMWP_Classes_Tools::saveOptions( 'error', false );
				HMWP_Classes_Tools::saveOptions( 'logout', false );
				HMWP_Classes_Tools::saveOptions( 'test_frontend', false );
				HMWP_Classes_Tools::saveOptions( 'file_mappings', array() );

				// Save to safe mode in case of db
				if ( ! HMWP_Classes_Tools::getOption( 'logout' ) ) {
					HMWP_Classes_Tools::saveOptionsBackup();
				}

                // Force the recheck security notification
                HMWP_Classes_ObjController::getClass( 'HMWP_Controllers_SecurityCheck' )->resetSecurityCheck();

                // Download the setting on paths confirmation
				HMWP_Classes_Tools::saveOptions( 'download_settings', true );

				// Add action for later use
				do_action( 'hmwp_confirmed_settings' );

				break;
			case 'hmwp_manualrewrite':
				HMWP_Classes_Tools::saveOptions( 'error', false );
				HMWP_Classes_Tools::saveOptions( 'logout', false );
				HMWP_Classes_Tools::saveOptions( 'test_frontend', true );
				HMWP_Classes_Tools::saveOptions( 'file_mappings', array() );

				//save to safe mode in case of db
				if ( ! HMWP_Classes_Tools::getOption( 'logout' ) ) {
					HMWP_Classes_Tools::saveOptionsBackup();
				}

				if ( HMWP_Classes_Tools::isNginx() ) {
					@shell_exec( 'nginx -s reload' );
				}

				break;
			case 'hmwp_changepathsincache':
				//Check the cache plugin
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Compatibility' )->checkCacheFiles();

				HMWP_Classes_Error::setNotification( esc_html__( 'Paths changed in the existing cache files', 'hide-my-wp' ), 'success' );
				break;
			case 'hmwp_backup':
				//Save the Settings into backup
				if ( ! HMWP_Classes_Tools::userCan( HMWP_CAPABILITY ) ) {
					return;
				}
				HMWP_Classes_Tools::getOptions();
				HMWP_Classes_Tools::setHeader( 'text' );
				$filename = preg_replace( '/[-.]/', '_', wp_parse_url( home_url(), PHP_URL_HOST ) );
				header( "Content-Disposition: attachment; filename=" . $filename . "_settings_backup.txt" );

				if ( function_exists( 'base64_encode' ) ) {
					echo base64_encode( wp_json_encode( HMWP_Classes_Tools::$options ) ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				} else {
					echo wp_json_encode( HMWP_Classes_Tools::$options );
				}
				exit();

			case 'hmwp_rollback':

				$hmwp_token = HMWP_Classes_Tools::getOption( 'hmwp_token' );
				$api_token  = HMWP_Classes_Tools::getOption( 'api_token' );

				//Get the default values
				$options = array_merge( HMWP_Classes_Tools::$init, HMWP_Classes_Tools::$default );

				//Prevent duplicates
				foreach ( $options as $key => $value ) {
					//set the default params from tools
					HMWP_Classes_Tools::saveOptions( $key, $value );
					HMWP_Classes_Tools::saveOptions( 'hmwp_token', $hmwp_token );
					HMWP_Classes_Tools::saveOptions( 'api_token', $api_token );
				}

				//remove the custom rules
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rules' )->writeToFile( '', 'HMWP_VULNERABILITY' );
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rules' )->writeToFile( '', 'HMWP_RULES' );

				HMWP_Classes_Error::setNotification( esc_html__( 'Great! The initial values are restored.', 'hide-my-wp' ), 'success' );

				break;

            case 'hmwp_rollback_stable':

                HMWP_Classes_Tools::setHeader( 'html' );
                $plugin_slug = 'hide-my-wp';

                /** @var HMWP_Models_Rollback $rollback */
                $rollback    = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rollback' );

                $rollback->set_plugin( array(
                        'version'     => HMWP_STABLE_VERSION,
                        'plugin_name' => _HMWP_ROOT_DIR_,
                        'plugin_slug' => $plugin_slug,
                        'package_url' => sprintf( 'https://downloads.wordpress.org/plugin/%s.%s.zip', $plugin_slug, HMWP_STABLE_VERSION ),
                ) );

                $rollback->run();

                wp_die( '', esc_html( "Rollback to Previous Version" ), [ 'response' => 200 ] );
			case 'hmwp_restore':

				$tmp_options = array(
					'hmwp_token',
					'api_token',
					'hmwp_plugin_name',
					'hmwp_plugin_menu',
					'hmwp_plugin_logo',
					'hmwp_plugin_website',
					'hmwp_plugin_account_show',
				);

				//Initialize WordPress Filesystem
				$wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

				//Restore the backup
				if ( ! HMWP_Classes_Tools::userCan( HMWP_CAPABILITY ) ) {
					return;
				}

				if ( isset( $_FILES['hmwp_options']['tmp_name'] ) && $_FILES['hmwp_options']['tmp_name'] <> '' ) { //phpcs:ignore
					$options = $wp_filesystem->get_contents( $_FILES['hmwp_options']['tmp_name'] ); //phpcs:ignore
					try {
						if ( function_exists( 'base64_encode' ) && base64_decode( $options ) <> '' ) {
							$options = base64_decode( $options );
						}
						$options = json_decode( $options, true );

						if ( is_array( $options ) && isset( $options['hmwp_ver'] ) ) {
							foreach ( $options as $key => $value ) {
								if ( !in_array($key, $tmp_options) ) {
									HMWP_Classes_Tools::saveOptions( $key, $value );
								}
							}

							//load the after saving settings process
							if ( $this->model->applyPermalinksChanged() ) {
								HMWP_Classes_Error::setNotification( esc_html__( 'Great! The backup is restored.', 'hide-my-wp' ), 'success' );
							}

						} else {
							HMWP_Classes_Error::setNotification( esc_html__( 'Error! The backup is not valid.', 'hide-my-wp' ) );
						}
					} catch ( Exception $e ) {
						HMWP_Classes_Error::setNotification( esc_html__( 'Error! The backup is not valid.', 'hide-my-wp' ) );
					}
				} else {
					HMWP_Classes_Error::setNotification( esc_html__( 'Error! No backup to restore.', 'hide-my-wp' ) );
				}
				break;
			case 'hmwp_advanced_install':

				if ( ! HMWP_Classes_Tools::userCan( HMWP_CAPABILITY ) ) {
					return;
				}

				//check the version
				$response = wp_remote_get( 'https://account.wpghost.com/updates-hide-my-wp.json?rnd=' . wp_rand( 1111, 9999 ) );

				if ( is_wp_error( $response ) ) {
					HMWP_Classes_Error::setNotification( $response->get_error_message() );
				} elseif ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
					HMWP_Classes_Error::setNotification( esc_html__( "Can't download the plugin.", 'hide-my-wp' ) );
				} else {
					if ( $data = json_decode( wp_remote_retrieve_body( $response ) ) ) {

                        /** @var HMWP_Models_Rollback $rollback */
						$rollback = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rollback' );

						$output = $rollback->install( array(
							'version'     => $data->version,
							'plugin_name' => $data->name,
							'plugin_slug' => $data->slug,
							'package_url' => $data->download_url,
						) );

						if ( ! is_wp_error( $output ) ) {
							$rollback->activate( $data->slug . '/index.php' );

                            if ( wp_safe_redirect( esc_url_raw( HMWP_Classes_Tools::getSettingsUrl( HMWP_Classes_Tools::getValue( 'page' ) . '&tab=' . HMWP_Classes_Tools::getValue( 'tab' ), true ) ) ) ) {
                                exit;
                            }
						} else {
							HMWP_Classes_Error::setNotification( $output->get_error_message() );
						}

					}

				}
				break;

            case 'hmwp_pause_enable':

                if ( ! HMWP_Classes_Tools::userCan( HMWP_CAPABILITY ) ) {
                    return;
                }

                set_transient( 'hmwp_disable', 1, 300 );

                break;
            case 'hmwp_pause_disable':

                if ( ! HMWP_Classes_Tools::userCan( HMWP_CAPABILITY ) ) {
                    return;
                }

                delete_transient( 'hmwp_disable' );

                break;
            case 'hmwp_update_product_name':
	            if(HMWP_Classes_Tools::getOption('hmwp_plugin_name') == 'Hide My WP Ghost'){
		            HMWP_Classes_Tools::saveOptions('hmwp_plugin_name', _HMWP_PLUGIN_FULL_NAME_);
	            }
	            if(HMWP_Classes_Tools::getOption('hmwp_plugin_menu') == 'Hide My WP'){
		            HMWP_Classes_Tools::saveOptions('hmwp_plugin_menu', _HMWP_PLUGIN_FULL_NAME_);
	            }
	            if(HMWP_Classes_Tools::getOption('hmwp_plugin_website') == 'https://hidemywpghost.com'){
		            HMWP_Classes_Tools::saveOptions('hmwp_plugin_website', 'https://wpghost.com');
	            }
                break;

			case 'hmwp_export_threats_csv':
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_ThreatsListTable' )->exportCsv();
				exit;

			case 'hmwp_export_events_csv':
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_EventsListTable' )->exportCsv();
				exit;

        }

	}

	/**
	 * Save the whitelist IPs into database
	 *
	 * @return void
	 */
	private function saveWhiteListIps() {

		$whitelist = HMWP_Classes_Tools::getValue( 'whitelist_ip', '', true );

		//is there are separated by commas
		if ( strpos( $whitelist, ',' ) !== false ) {
			$whitelist = str_replace( ',', PHP_EOL, $whitelist );
		}

		$ips = explode( PHP_EOL, $whitelist );

		if ( ! empty( $ips ) ) {
			foreach ( $ips as &$ip ) {
				$ip = trim( $ip );

				// Check for IPv4 IP cast as IPv6
				if ( preg_match( '/^::ffff:(\d+\.\d+\.\d+\.\d+)$/', $ip, $matches ) ) {
					$ip = $matches[1];
				}
			}

			$ips = array_unique( $ips );
			HMWP_Classes_Tools::saveOptions( 'whitelist_ip', wp_json_encode( $ips ) );
		}

	}

	/**
	 * Save the whitelist Paths into database
	 *
	 * @return void
	 */
	private function saveWhiteListPaths() {

		$whitelist = HMWP_Classes_Tools::getValue( 'whitelist_urls', '', true );

		//is there are separated by commas
		if ( strpos( $whitelist, ',' ) !== false ) {
			$whitelist = str_replace( ',', PHP_EOL, $whitelist );
		}

		$urls = explode( PHP_EOL, $whitelist );

		if ( ! empty( $urls ) ) {
			foreach ( $urls as &$url ) {
				$url = trim( $url );
			}

			$urls = array_unique( $urls );
			HMWP_Classes_Tools::saveOptions( 'whitelist_urls', wp_json_encode( $urls ) );
		}

	}

    /**
     * Save the whitelist Rules into database
     *
     * @return void
     */
    private function saveWhiteListRules() {

        $whitelist = HMWP_Classes_Tools::getValue( 'whitelist_rules', '', true );

        //is there are separated by commas
        if ( strpos( $whitelist, ',' ) !== false ) {
            $whitelist = str_replace( ',', PHP_EOL, $whitelist );
        }

        $rules = explode( PHP_EOL, $whitelist );

        if ( ! empty( $rules ) ) {
            foreach ( $rules as &$rule ) {
                $rule = trim( $rule );
            }

            $rules = array_unique( $rules );
            HMWP_Classes_Tools::saveOptions( 'whitelist_rules', wp_json_encode( $rules ) );
        }

    }

	/**
	 * Save the whitelist IPs into database
	 *
	 * @return void
	 */
	private function saveBlackListIps() {

		$banlist = HMWP_Classes_Tools::getValue( 'banlist_ip', '', true );

		//is there are separated by commas
		if ( strpos( $banlist, ',' ) !== false ) {
			$banlist = str_replace( ',', PHP_EOL, $banlist );
		}

		$ips = explode( PHP_EOL, $banlist );

		if ( ! empty( $ips ) ) {
			foreach ( $ips as &$ip ) {
				$ip = trim( $ip );

				// Check for IPv4 IP cast as IPv6
				if ( preg_match( '/^::ffff:(\d+\.\d+\.\d+\.\d+)$/', $ip, $matches ) ) {
					$ip = $matches[1];
				}
			}

			$ips = array_unique( $ips );
			HMWP_Classes_Tools::saveOptions( 'banlist_ip', wp_json_encode( $ips ) );
		}

	}

	/**
	 * Save the hostname
	 *
	 * @return void
	 */
	private function saveBlackListHostnames() {

		$banlist = HMWP_Classes_Tools::getValue( 'banlist_hostname', '', true );

		//is there are separated by commas
		if ( strpos( $banlist, ',' ) !== false ) {
			$banlist = str_replace( ',', PHP_EOL, $banlist );
		}

		$list = explode( PHP_EOL, $banlist );

		if ( ! empty( $list ) ) {
			foreach ( $list as $index => &$row ) {
				$row = trim( $row );

				if ( preg_match( '/^[a-z0-9\.\*\-]+$/i', $row, $matches ) ) {
					$row = $matches[0];
				} else {
					unset( $list[ $index ] );
				}
			}

			$list = array_unique( $list );
			HMWP_Classes_Tools::saveOptions( 'banlist_hostname', wp_json_encode( $list ) );
		}

	}

	/**
	 * Save the User-Agents
	 *
	 * @return void
	 */
	private function saveBlackListUserAgents() {

		$banlist = HMWP_Classes_Tools::getValue( 'banlist_user_agent', '', true );

		//is there are separated by commas
		if ( strpos( $banlist, ',' ) !== false ) {
			$banlist = str_replace( ',', PHP_EOL, $banlist );
		}

		$list = explode( PHP_EOL, $banlist );

		if ( ! empty( $list ) ) {
			foreach ( $list as $index => &$row ) {
				$row = trim( $row );

				if ( preg_match( '/^[a-z0-9\.\*\-]+$/i', $row, $matches ) ) {
					$row = $matches[0];
				} else {
					unset( $list[ $index ] );
				}
			}

			$list = array_unique( $list );
			HMWP_Classes_Tools::saveOptions( 'banlist_user_agent', wp_json_encode( $list ) );
		}

	}

	/**
	 * Save the Referrers
	 *
	 * @return void
	 */
	private function saveBlackListReferrers() {

		$banlist = HMWP_Classes_Tools::getValue( 'banlist_referrer', '', true );

		//is there are separated by commas
		if ( strpos( $banlist, ',' ) !== false ) {
			$banlist = str_replace( ',', PHP_EOL, $banlist );
		}

		$list = explode( PHP_EOL, $banlist );

		if ( ! empty( $list ) ) {
			foreach ( $list as $index => &$row ) {
				$row = trim( $row );

				if ( preg_match( '/^[a-z0-9\.\*\-]+$/i', $row, $matches ) ) {
					$row = $matches[0];
				} else {
					unset( $list[ $index ] );
				}
			}

			$list = array_unique( $list );
			HMWP_Classes_Tools::saveOptions( 'banlist_referrer', wp_json_encode( $list ) );
		}

	}

	/**
	 * If javascript is not loaded
	 *
	 * @return void
	 */
	public function hookFooter() {
		echo '<noscript><style>.tab-panel {display: block;}</style></noscript>';
	}

}
