<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

class HMW_Controllers_Settings extends HMW_Classes_FrontController {

	public $tabs;
	public $logout = false;
	public $show_token = false;
	public $plugins;

	public function __construct() {
		parent::__construct();

		//If save settings is required, show the alert
		if ( HMW_Classes_Tools::getOption( 'changes' ) ) {
			add_action( 'admin_notices', array( $this, 'showSaveRequires' ) );
		}

		//Add the Settings class only for Hide My WP plugin
		add_filter( 'admin_body_class', array(
			HMW_Classes_ObjController::getClass( 'HMW_Models_Menu' ),
			'addSettingsClass'
		) );

	}

	/**
	 * Initialize the Hide My WP Ghost Settings
	 * @return void
	 */
	public function init() {
		//Get the current Page
		$page = HMW_Classes_Tools::getValue( 'page' );

		//If the page is not for Hide My WP Settings, return
		if ( $page <> 'hmw_settings' ) {
			if ( strpos( $page, '-' ) !== false ) {
				if ( substr( $page, 0, strpos( $page, '-' ) ) <> 'hmw_settings' ) {
					return;
				}
			}
		}

		//Check if it's a subpage
		if ( strpos( $page, '-' ) !== false ) {
			$_GET['tab'] = substr( $page, ( strpos( $page, '-' ) + 1 ) );
		}

		//We need that function so make sure is loaded
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}

		//Add the Plugin Paths in variable
		$this->plugins = $this->model->getPlugins();

		if ( HMW_Classes_Tools::isNginx() && HMW_Classes_Tools::getOption( 'test_frontend' ) && HMW_Classes_Tools::getOption( 'hmw_mode' ) <> 'default' ) {
			$config_file = HMW_Classes_ObjController::getClass( 'HMW_Models_Rules' )->getConfFile();
			HMW_Classes_Error::setError( sprintf( __( "NGINX detected. In case you didn't add the code in the NGINX config already, please add the following line. %s", _HMW_PLUGIN_NAME_ ), '<br /><br /><code><strong>include ' . $config_file . ';</strong></code> <br /><br /><h5>' . __( "Don't forget to reload the Nginx service.", _HMW_PLUGIN_NAME_ ) . ' ' . '</h5><strong><br /><a href="http://hidemywp.co/article/how-to-setup-hide-my-wp-on-nginx-server/" target="_blank" style="color: red">' . __( "Learn how to setup on Nginx server", _HMW_PLUGIN_NAME_ ) . '</a></strong>' ) );
		}

		//Settings Alerts based on Logout and Error statements
		if ( get_transient( 'hmw_restore' ) == 1 ) {
			$restoreForm = '
                        <form method="POST">
                            ' . wp_nonce_field( 'hmw_abort', 'hmw_nonce', true, false ) . '
                            <input type="hidden" name="action" value="hmw_abort" />
                            <input type="submit" class="hmw_btn hmw_btn-warning" value="' . __( "Restore Settings", _HMW_PLUGIN_NAME_ ) . '" />
                        </form>
                        ';
			HMW_Classes_Error::setError( __( 'You want to restore the last saved settings? ', _HMW_PLUGIN_NAME_ ) . '<div class="hmw_abort" style="display: inline-block;">' . $restoreForm . '</div>' );
			// Delete the redirect transient
			delete_transient( 'hmw_restore' );

		}

		//Check compatibilities with other plugins
		HMW_Classes_ObjController::getClass( 'HMW_Classes_DisplayController' )->loadMedia( 'alert' );
		HMW_Classes_ObjController::getClass( 'HMW_Models_Compatibility' )->getAlerts();

		//Load the css for Settings
		if ( is_rtl() ) {
			HMW_Classes_ObjController::getClass( 'HMW_Classes_DisplayController' )->loadMedia( 'popper.min' );
			HMW_Classes_ObjController::getClass( 'HMW_Classes_DisplayController' )->loadMedia( 'bootstrap.rtl.min' );
			HMW_Classes_ObjController::getClass( 'HMW_Classes_DisplayController' )->loadMedia( 'rtl' );
		} else {
			HMW_Classes_ObjController::getClass( 'HMW_Classes_DisplayController' )->loadMedia( 'popper.min' );
			HMW_Classes_ObjController::getClass( 'HMW_Classes_DisplayController' )->loadMedia( 'bootstrap.min' );
		}

		HMW_Classes_ObjController::getClass( 'HMW_Classes_DisplayController' )->loadMedia( 'font-awesome.min' );
		HMW_Classes_ObjController::getClass( 'HMW_Classes_DisplayController' )->loadMedia( 'switchery.min' );
		HMW_Classes_ObjController::getClass( 'HMW_Classes_DisplayController' )->loadMedia( 'settings' );

		//Show Hide My WP Offer
		if ( HMW_Classes_Tools::getOption( 'hmw_mode' ) == 'lite' && date( 'Y-m-d' ) >= '2020-11-27' && date( 'Y-m-d' ) < '2020-11-01' ) {
			HMW_Classes_Error::setError( sprintf( __( '%sBlack Friday!!%s Get Hide My WP Ghost today with the best discounts of the year. %sSee Ofers!%s', _HMW_PLUGIN_NAME_ ), '<strong style="color: red; font-size: 20px;">', '</strong>', '<a href="https://hidemywpghost.com/hide-my-wp-ghost-black-friday-offer/" target="_blank" style="font-weight: bold">', '</a>' ) );
		} elseif ( HMW_Classes_Tools::getOption( 'hmw_mode' ) == 'lite' && date( 'Y-m-d' ) >= '2020-10-28' && date( 'Y-m-d' ) < '2020-11-01' ) {
			HMW_Classes_Error::setError( sprintf( __( '%sHalloween Special!!%s Get %s80%% OFF%s on Hide My WP Ghost - Unlimited Websites License until 31 October 2020. %sSee Ofer!%s', _HMW_PLUGIN_NAME_ ), '<strong style="color: red; font-size: 20px;">', '</strong>', '<strong style="color: red">', '</strong>', '<a href="https://hidemywpghost.com/hide-my-wp-ghost-halloween-offer/" target="_blank" style="font-weight: bold">', '</a>' ) );
		} elseif ( HMW_Classes_Tools::getOption( 'hmw_mode' ) == 'lite' && date( 'm' ) <> 10 && date( 'm' ) <> 11 && ( ( date( 'd' ) >= 15 && date( 'd' ) <= 20 ) || ( date( 'd' ) >= 25 && date( 'd' ) <= 30 ) ) ) {
			HMW_Classes_Error::setError( sprintf( __( '%sLimited Time Offer%s: Get %s65%% OFF%s today on Hide My WP Ghost 5 Websites License. %sHurry Up!%s', _HMW_PLUGIN_NAME_ ), '<a href="https://wpplugins.tips/buy/5_websites_special" target="_blank" style="font-weight: bold;"><strong style="color: red">', '</strong></a>', '<a href="https://wpplugins.tips/buy/5_websites_special" target="_blank" style="font-weight: bold"><strong style="color: red">', '</strong></a>', '<a href="https://wpplugins.tips/buy/5_websites_special" target="_blank" style="font-weight: bold">', '</a>' ) );
		}

		//Show errors on top
		HMW_Classes_ObjController::getClass( 'HMW_Classes_Error' )->hookNotices();


		//Show connect for activation
		if ( ! HMW_Classes_Tools::getOption( 'hmw_token' ) ) {
			echo $this->getView( 'Connect' );

			return;
		}

		//Add the Menu Tabs in variable
		$this->tabs = $this->model->getTabs();


		//Show the Tab Content
		foreach ( $this->tabs as $slug => $value ) {
			if ( HMW_Classes_Tools::getValue( 'tab', 'hmw_permalinks' ) == $slug ) {
				if ( isset( $value['class'] ) && $value['class'] <> '' ) {
					echo HMW_Classes_ObjController::getClass( $value['class'] )->init()->getView();
				} else {
					echo $this->getView( ucfirst( str_replace( 'hmw_', '', $slug ) ) );
				}
			}
		}

	}

	/**
	 * Show this message to notify the user when to update th esettings
	 */
	public function showSaveRequires() {
		if ( HMW_Classes_Tools::getOption( 'hmw_hide_plugins' ) || HMW_Classes_Tools::getOption( 'hmw_hide_plugins' ) ) {
			global $pagenow;
			if ( $pagenow == 'plugins.php' || HMW_Classes_Tools::getValue( 'page' ) == 'hmw_settings' ) {

				HMW_Classes_ObjController::getClass( 'HMW_Classes_DisplayController' )->loadMedia( 'alert' );

				?>
                <div class="hmw_notice error notice" style="margin-left: 0;">
                    <div style="display: inline-block;">
                        <form action="<?php echo HMW_Classes_Tools::getSettingsUrl() ?>" method="POST">
							<?php wp_nonce_field( 'hmw_newpluginschange', 'hmw_nonce' ) ?>
                            <input type="hidden" name="action" value="hmw_newpluginschange"/>
                            <p>
								<?php echo sprintf( __( "New Plugin/Theme detected! You need to save the Hide My WP Setting again to include them all! %sClick here%s", _HMW_PLUGIN_NAME_ ), '<button type="submit" style="color: blue; text-decoration: underline; cursor: pointer; background: none; border: none;">', '</button>' ); ?>
                            </p>
                        </form>
                    </div>
                </div>
				<?php
			}
		}
	}


	/**
	 * Get the Admin Toolbar
	 *
	 * @param null $current
	 *
	 * @return string
	 */
	public function getAdminTabs( $current = null ) {
		//Add the Menu Tabs in variable if not set before
		if ( ! isset( $this->tabs ) ) {
			$this->tabs = $this->model->getTabs();
		}

		$content = '';
		$content .= '<div class="hmw_nav d-flex flex-column bd-highlight mb-3">';
		$content .= '<div  class="m-0 p-4 font-dark text-logo"><a href="https://hidemywpghost.com/" target="_blank"><img src="' . _HMW_THEME_URL_ . 'img/logo.png" class="ml-0 mr-2" style="width:30px;"></a>' . __( 'Hide My WP', _HMW_PLUGIN_NAME_ ) . ' <span style="color: #d6cdd1">' . _HMW_VER_NAME_ . '</span></div>';
		foreach ( $this->tabs as $location => $tab ) {
			if ( $current == $location ) {
				$class = 'active';
			} else {
				$class = '';
			}
			if ( $location == 'hmw_securitycheck' ) {
				$content .= '<a class="m-0 p-4 font-dark hmw_nav_item ' . $class . ' fa fa-' . $tab['icon'] . '" href="' . HMW_Classes_Tools::getSettingsUrl( $location, true ) . '">';
			} else {
				$content .= '<a class="m-0 p-4 font-dark hmw_nav_item ' . $class . ' fa fa-' . $tab['icon'] . '" href="' . HMW_Classes_Tools::getSettingsUrl( 'hmw_settings', true ) . ( $location <> 'hmw_permalinks' ? '-' . $location : '' ) . '">';
			}
			$content .= '<span>' . $tab['title'] . '</span>';
			$content .= '<span class="hmw_nav_item_description">' . $tab['description'] . '</span>';
			$content .= '</a>';
		}
		if ( HMW_Classes_Tools::getOption( 'api_token' ) <> '' ) {
			$content .= '<div  class="m-2 p-4 hmw_nav_button"><a href="' . _HMW_ACCOUNT_SITE_ . '/api/auth/' . HMW_Classes_Tools::getOption( 'api_token' ) . '" class="btn btn-warning btn-lg rounded-0 text-white" target="_blank">' . __( 'My Account', _HMW_PLUGIN_NAME_ ) . '</a></div>';
		}
		$content .= '</div>';

		return $content;
	}

	/**
	 * Called when an action is triggered
	 * @throws Exception
	 */
	public function action() {
		parent::action();

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		switch ( HMW_Classes_Tools::getValue( 'action' ) ) {
			case 'hmw_settings':

				//Save the settings
				if ( ! empty( $_POST ) ) {
					$this->model->savePermalinks( $_POST );
				}

				//If no errors and no reconnect required
				if ( ! HMW_Classes_Tools::getOption( 'error' ) ) {

					//Force the rechck security notification
					delete_option( 'hmw_securitycheck_time' );
					//Clear the cache if there are no errors
					HMW_Classes_Tools::emptyCache();
					//Flush the WordPress rewrites
					HMW_Classes_Tools::flushWPRewrites();

					//Flush the changes
					HMW_Classes_ObjController::getClass( 'HMW_Models_Rewrite' )->flushChanges();

					if ( ! HMW_Classes_Error::isError() ) {

						if ( ! HMW_Classes_Tools::getOption( 'logout' ) || HMW_Classes_Tools::getOption( 'hmw_mode' ) == 'default' ) {
							//Save the working options into backup
							HMW_Classes_Tools::saveOptionsBackup();
						}

						HMW_Classes_Error::setError( __( 'Saved' ), 'success' );

						//Send email notification about the path changed
						HMW_Classes_ObjController::getClass( 'HMW_Models_Rewrite' )->sendEmail();


						if ( HMW_Classes_Tools::isNginx() && ! HMW_Classes_Tools::getOption( 'test_frontend' ) && HMW_Classes_Tools::getOption( 'hmw_mode' ) <> 'default' ) {
							$config_file = HMW_Classes_ObjController::getClass( 'HMW_Models_Rules' )->getConfFile();
							HMW_Classes_Error::setError( sprintf( __( "NGINX detected. In case you didn't add the code in the NGINX config already, please add the following line. %s", _HMW_PLUGIN_NAME_ ), '<br /><br /><code><strong>include ' . $config_file . ';</strong></code> <br /><br /><h5>' . __( "Don't forget to reload the Nginx service.", _HMW_PLUGIN_NAME_ ) . ' ' . '</h5><strong><br /><a href="http://hidemywp.co/article/how-to-setup-hide-my-wp-on-nginx-server/" target="_blank" style="color: red">' . __( "Learn how to setup on Nginx server", _HMW_PLUGIN_NAME_ ) . '</a></strong>' ) );
						}

						//Redirect to the new admin URL
						if ( HMW_Classes_Tools::getOption( 'logout' ) ) {

							//Set the cookies for the current path
							$cookies = HMW_Classes_ObjController::newInstance( 'HMW_Models_Cookies' );

							if ( HMW_Classes_Tools::isNginx() || $cookies->setCookiesCurrentPath() ) {
								//set logout to false
								HMW_Classes_Tools::saveOptions( 'logout', false );
								//activate frontend test
								HMW_Classes_Tools::saveOptions( 'test_frontend', true );

								remove_all_filters( 'wp_redirect' );
								remove_all_filters( 'admin_url' );
								wp_safe_redirect( HMW_Classes_Tools::getSettingsUrl() );
								exit();
							}
						}
					}
				}

				break;
			case 'hmw_tweakssettings':
				//Save the settings
				if ( ! empty( $_POST ) ) {
					$this->model->saveValues( $_POST );
				}

				//Flush the changes for xmlrpc.php rules
				HMW_Classes_ObjController::getClass( 'HMW_Models_Rewrite' )->flushChanges();

				if ( ! HMW_Classes_Tools::getOption( 'error' ) ) {

					if ( ! HMW_Classes_Tools::getOption( 'logout' ) ) {
						//Save the working options into backup
						HMW_Classes_Tools::saveOptionsBackup();
					}

					//Clear the cache if there are no errors
					HMW_Classes_Tools::emptyCache();
					HMW_Classes_Error::setError( __( 'Saved' ), 'success' );
				}

				break;
			case 'hmw_mappsettings':

				HMW_Classes_Tools::saveOptions( 'hmw_mapping_classes', HMW_Classes_Tools::getValue( 'hmw_mapping_classes' ) );

				//Save the patterns as array
				if ( $hmw_text_mapping_from = HMW_Classes_Tools::getValue( 'hmw_text_mapping_from', false ) ) {
					if ( $hmw_text_mapping_to = HMW_Classes_Tools::getValue( 'hmw_text_mapping_to', false ) ) {
						$hmw_text_mapping = array();

						if ( HMW_Classes_Tools::getOption( 'hmw_hide_classes' ) ) {
							$custom_classes = json_decode( HMW_Classes_Tools::getOption( 'hmw_hide_classes' ), true );
							if ( ! empty( $custom_classes ) ) {
								foreach ( $custom_classes as $custom_classe ) {
									if ( ! in_array( $custom_classe, array( 'wp-image', 'wp-post', 'wp-caption' ) ) ) {
										$hmw_text_mapping['from'][] = $custom_classe;
										$hmw_text_mapping['to'][]   = '';
									}
								}
								HMW_Classes_Tools::saveOptions( 'hmw_hide_classes', json_encode( array() ) );
							}
						}
						foreach ( $hmw_text_mapping_from as $index => $from ) {
							if ( $hmw_text_mapping_from[ $index ] <> '' && $hmw_text_mapping_to[ $index ] <> '' ) {
								$hmw_text_mapping_from[ $index ] = preg_replace( '/[^A-Za-z0-9-_\/\.\{\}]/', '', $hmw_text_mapping_from[ $index ] );
								$hmw_text_mapping_to[ $index ]   = preg_replace( '/[^A-Za-z0-9-_\/\.\{\}]/', '', $hmw_text_mapping_to[ $index ] );

								if ( ! isset( $hmw_text_mapping['from'] ) || ! in_array( $hmw_text_mapping_from[ $index ], (array) $hmw_text_mapping['from'] ) ) {
									//Don't save the wp-posts for Woodmart theme
									if ( HMW_Classes_Tools::isPluginActive( 'woocommerce/woocommerce.php' ) ) {
										if ( $hmw_text_mapping_from[ $index ] == 'wp-post' || $hmw_text_mapping_from[ $index ] == 'wp-post-image' ) {
											continue;
										}
									}

									if ( $hmw_text_mapping_from[ $index ] <> $hmw_text_mapping_to[ $index ] ) {
										$hmw_text_mapping['from'][] = $hmw_text_mapping_from[ $index ];
										$hmw_text_mapping['to'][]   = $hmw_text_mapping_to[ $index ];
									}
								} else {
									HMW_Classes_Error::setError( __( 'Error: You entered the same text twice in the Text Mapping. We removed the duplicates to prevent any redirect errors.' ) );
								}
							}
						}
						HMW_Classes_Tools::saveOptions( 'hmw_text_mapping', json_encode( $hmw_text_mapping ) );

					}
				}

				//Clear the cache if there are no errors
				if ( ! HMW_Classes_Tools::getOption( 'error' ) ) {

					if ( ! HMW_Classes_Tools::getOption( 'logout' ) ) {
						//Save the working options into backup
						HMW_Classes_Tools::saveOptionsBackup();
					}

					//Clear the cache if there are no errors
					HMW_Classes_Tools::emptyCache();
					HMW_Classes_Error::setError( __( 'Saved' ), 'success' );
				}
				break;
			case 'hmw_advsettings':

				if ( ! empty( $_POST ) ) {
					$this->model->saveValues( $_POST );

					//Clear the cache if there are no errors
					if ( ! HMW_Classes_Tools::getOption( 'error' ) ) {

						//Flush the changes
						HMW_Classes_ObjController::getClass( 'HMW_Models_Rewrite' )->flushChanges();

						if ( HMW_Classes_Tools::getOption( 'hmw_firstload' ) ) {
							//Add the must use plugin to force loading before all other plugins
							HMW_Classes_ObjController::getClass( 'HMW_Models_Compatibility' )->addMUPlugin();
						}

						//Clear the cache if there are no errors
						if ( ! HMW_Classes_Tools::getOption( 'error' ) ) {

							if ( ! HMW_Classes_Tools::getOption( 'logout' ) ) {
								//Save the working options into backup
								HMW_Classes_Tools::saveOptionsBackup();
							}

							//Clear the cache if there are no errors
							HMW_Classes_Tools::emptyCache();
							HMW_Classes_Error::setError( __( 'Saved' ), 'success' );
						}
					}
				}

				break;
			case 'hmw_abort':
			    //get the token
				$hmw_token = HMW_Classes_Tools::getOption( 'hmw_token' );
				//get the safe options from database
				HMW_Classes_Tools::$options = HMW_Classes_Tools::getOptions( true );
				//set th eprevious admin path
				if($hmw_token) HMW_Classes_Tools::saveOptions( 'hmw_token',$hmw_token);
				HMW_Classes_Tools::saveOptions( 'error', false );
				//set logout to false
                HMW_Classes_Tools::saveOptions( 'logout', false );
				//set test frontend to false
				HMW_Classes_Tools::saveOptions( 'test_frontend', false );

				//Clear the cache if there are no errors
				HMW_Classes_Tools::emptyCache();
				//Flush the WordPress rewrites
				HMW_Classes_Tools::flushWPRewrites();

				HMW_Classes_ObjController::getClass( 'HMW_Models_Rewrite' )->clearRedirect();

				//Flush config to remove the rules
				HMW_Classes_ObjController::getClass( 'HMW_Models_Rewrite' )->flushChanges();

				//Set the cookies for the current path
				$cookies = HMW_Classes_ObjController::newInstance( 'HMW_Models_Cookies' );

				if ( HMW_Classes_Tools::isNginx() || $cookies->setCookiesCurrentPath() ) {
					remove_all_filters( 'wp_redirect' );
					remove_all_filters( 'admin_url' );
					wp_safe_redirect( HMW_Classes_Tools::getSettingsUrl() );
					exit();
				}

				break;
			case 'hmw_savedefault':
				HMW_Classes_Tools::saveOptions( 'logout', false );

				//Save the working options into backup
				HMW_Classes_Tools::saveOptionsBackup();
				break;
			case 'hmw_newpluginschange':
				//reset the change notification
				HMW_Classes_Tools::saveOptions( 'changes', 0 );
				remove_action( 'admin_notices', array( $this, 'showSaveRequires' ) );

				//generate unique names for plugins if needed
				if ( HMW_Classes_Tools::getOption( 'hmw_hide_plugins' ) ) {
					HMW_Classes_ObjController::getClass( 'HMW_Models_Rewrite' )->hidePluginNames();
				}
				if ( HMW_Classes_Tools::getOption( 'hmw_hide_themes' ) ) {
					HMW_Classes_ObjController::getClass( 'HMW_Models_Rewrite' )->hideThemeNames();
				}

				//Clear the cache and remove the redirects
				HMW_Classes_Tools::emptyCache();

				//Flush the WordPress rewrites
				HMW_Classes_Tools::flushWPRewrites();

				//Flush the changes
				HMW_Classes_ObjController::getClass( 'HMW_Models_Rewrite' )->flushChanges();

				if ( ! HMW_Classes_Error::isError() ) {
					HMW_Classes_Error::setError( __( 'The list of plugins and themes was updated with success!' ), 'success' );
				}
				break;
			case 'hmw_confirm':
				HMW_Classes_Tools::saveOptions( 'error', false );
				HMW_Classes_Tools::saveOptions( 'logout', false );
				HMW_Classes_Tools::saveOptions( 'test_frontend', false );

				//Save the working options into backup
				HMW_Classes_Tools::saveOptionsBackup();

				//Send email notification about the path changed
				HMW_Classes_ObjController::getClass( 'HMW_Models_Rewrite' )->sendEmail();

				//Force the rechck security notification
				delete_option( 'hmw_securitycheck_time' );

				break;
			case 'hmw_logout':
				HMW_Classes_Tools::saveOptions( 'error', false );
				HMW_Classes_Tools::saveOptions( 'logout', false );
				HMW_Classes_Tools::saveOptions( 'test_frontend', false );

				//Send email notification about the path changed
				HMW_Classes_ObjController::getClass( 'HMW_Models_Rewrite' )->sendEmail();

				//Save the working options into backup
				HMW_Classes_Tools::saveOptionsBackup();

				//Force the rechck security notification
				delete_option( 'hmw_securitycheck_time' );
				//Clear the cache if there are no errors
				HMW_Classes_Tools::emptyCache();
				//Flush the WordPress rewrites
				HMW_Classes_Tools::flushWPRewrites();

				HMW_Classes_ObjController::getClass( 'HMW_Models_Rewrite' )->flushChanges();

				wp_logout();
				wp_redirect( site_url( HMW_Classes_Tools::getOption( 'hmw_login_url' ) ) );
				die();
				break;
			case 'hmw_manualrewrite':
				HMW_Classes_Tools::saveOptions( 'error', false );
				HMW_Classes_Tools::saveOptions( 'logout', false );
				HMW_Classes_Tools::saveOptions( 'test_frontend', true );

				//Clear the cache if there are no errors
				HMW_Classes_Tools::emptyCache();

				//Clear the cache if there are no errors
				HMW_Classes_Tools::emptyCache();
				if ( HMW_Classes_Tools::isNginx() || HMW_Classes_Tools::isWpengine() ) {
					@shell_exec( 'nginx -s reload' );
				}
				break;
			case 'hmw_connect':
				//Connect to API with the Email
				$email = sanitize_email( HMW_Classes_Tools::getValue( 'hmw_email', '' ) );
				$token = HMW_Classes_Tools::getValue( 'hmw_token', '' );

				$redirect_to = HMW_Classes_Tools::getSettingsUrl();
				if ( $token <> '' ) {
					if ( preg_match( '/^[a-z0-9\-]{32}$/i', $token ) ) {
						HMW_Classes_Tools::saveOptions( 'hmw_token', $token );
						HMW_Classes_Tools::saveOptions( 'error', false );
						HMW_Classes_Tools::checkApi();

						//Save the working options into backup
						HMW_Classes_Tools::saveOptionsBackup();

					} else {
						HMW_Classes_Error::setError( __( 'ERROR! Please make sure you use a valid token to connect the plugin with WPPlugins', _HMW_PLUGIN_NAME_ ) . " <br /> " );
					}
				} elseif ( $email <> '' ) {
					HMW_Classes_Tools::checkApi( $email, $redirect_to );
				} else {
					HMW_Classes_Error::setError( __( 'ERROR! Please make sure you use an email address to connect the plugin with WPPlugins', _HMW_PLUGIN_NAME_ ) . " <br /> " );
				}
				break;
			case 'hmw_dont_connect':
				$redirect_to = HMW_Classes_Tools::getSettingsUrl();

				HMW_Classes_Tools::saveOptions( 'hmw_token', md5( home_url() ) );
				HMW_Classes_Tools::saveOptions( 'error', false );

				//Save the working options into backup
				HMW_Classes_Tools::saveOptionsBackup();

				wp_redirect( $redirect_to );
				exit();
			case 'hmw_backup':
				//Save the Settings into backup
				if ( ! current_user_can( 'manage_options' ) ) {
					return;
				}
				HMW_Classes_Tools::getOptions();
				HMW_Classes_Tools::setHeader( 'text' );
				header( "Content-Disposition: attachment; filename=hidemywp_backup.txt" );

				if ( function_exists( 'base64_encode' ) ) {
					echo base64_encode( json_encode( HMW_Classes_Tools::$options ) );
				} else {
					echo json_encode( HMW_Classes_Tools::$options );
				}
				exit();
				break;
			case 'hmw_restore':
				//Restore the backup
				if ( ! current_user_can( 'manage_options' ) ) {
					return;
				}

				if ( ! empty( $_FILES['hmw_options'] ) && $_FILES['hmw_options']['tmp_name'] <> '' ) {
					$options = file_get_contents( $_FILES['hmw_options']['tmp_name'] );
					try {
						if ( function_exists( 'base64_encode' ) && base64_decode( $options ) <> '' ) {
							$options = base64_decode( $options );
						}
						$options = json_decode( $options, true );
						if ( is_array( $options ) && isset( $options['hmw_ver'] ) ) {
							HMW_Classes_Tools::$options = $options;
							HMW_Classes_Tools::saveOptions();
							HMW_Classes_Error::setError( __( 'Great! The backup is restored.', _HMW_PLUGIN_NAME_ ) . " <br /> ", 'success' );

							if ( ! HMW_Classes_Tools::getOption( 'error' ) ) {
								//Clear the cache if there are no errors
								HMW_Classes_Tools::emptyCache();
								//Flush the WordPress rewrites
								HMW_Classes_Tools::flushWPRewrites();
							}

							if ( ! HMW_Classes_Tools::getOption( 'error' ) && ! HMW_Classes_Tools::getOption( 'logout' ) ) {
								HMW_Classes_ObjController::getClass( 'HMW_Models_Rewrite' )->flushChanges();
							}

						} else {
							HMW_Classes_Error::setError( __( 'Error! The backup is not valid.', _HMW_PLUGIN_NAME_ ) . " <br /> " );
						}
					} catch ( Exception $e ) {
						HMW_Classes_Error::setError( __( 'Error! The backup is not valid.', _HMW_PLUGIN_NAME_ ) . " <br /> " );
					}
				} else {
					HMW_Classes_Error::setError( __( 'Error! You have to enter a previous saved backup file.', _HMW_PLUGIN_NAME_ ) . " <br /> " );
				}

				break;
			case 'hmw_support':
				global $current_user, $wp_version;
				$return = array();


				$line     = "\n\n" . "______________________________________________________________________" . "\n";
				$versions = 'URL:' . get_bloginfo( 'wpurl' ) . ", " . 'PV: ' . HMW_VERSION . ", " . 'WPV: ' . $wp_version;
				$from     = HMW_Classes_Tools::getValue( 'hmw_email' );
				$subject  = __( 'Hide My Wp > Question', _HMW_PLUGIN_NAME_ );
				$message  = HMW_Classes_Tools::getValue( 'hmw_message', '', true );

				if ( $message <> '' ) {
					$message .= $line;
					$message .= $versions;

					$headers[] = 'From: ' . $current_user->display_name . ' <' . $from . '>';
					if ( $response = wp_mail( _HMW_SUPPORT_EMAIL_, $subject, $message, $headers ) ) {
						$return['success'] = true;
					} else {
						$return['error'] = true;
					}
				} else {
					$return['error'] = true;
				}

				HMW_Classes_Tools::setHeader( 'json' );
				echo json_encode( $return );
				exit();
		}
	}


	/**
	 * Add Javascript in the page footer
	 */
	public function hookFooter() {
		HMW_Classes_Tools::saveOptions();
		echo '<script>var hmwQuery = {"ajaxurl": "' . admin_url( 'admin-ajax.php' ) . '","nonce": "' . wp_create_nonce( _HMW_NONCE_ID_ ) . '"}</script>';
	}

}
