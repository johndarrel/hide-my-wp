<?php
/**
 * Settings Model
 * Handles the plugin settings actions and database
 *
 * @file  The Settings Model file
 * @package HMWP/SettingsModel
 * @since 4.0.0
 */
defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Models_Settings
{

	/**
	 * Filter the keys that need validation
	 * @var array $validate_keys
	 */
	private $validate_keys;

	/**
	 * Filter the names that need validation
	 * @var array $validate_keys
	 */
	private $invalid_names;

	/**
	 * Initialize the validation keys and names
	 * @return void
	 */
	public function initValidationFields() {

		$this->validate_keys = apply_filters('hmwp_validate_keys', array(
			'hmwp_admin_url',
			'hmwp_login_url',
			'hmwp_activate_url',
			'hmwp_lostpassword_url',
			'hmwp_register_url',
			'hmwp_logout_url',
			'hmwp_plugin_url',
			'hmwp_themes_url',
			'hmwp_upload_url',
			'hmwp_admin-ajax_url',
			'hmwp_wp-content_url',
			'hmwp_wp-includes_url',
			'hmwp_author_url',
			'hmwp_wp-comments-post',
			'hmwp_themes_style',
			'hmwp_wp-json',
		));

		$this->invalid_names = apply_filters('hmwp_invalid_names', array(
			'index.php',
			'readme.html',
			'sitemap.xml',
			'.htaccess',
			'license.txt',
			'wp-blog-header.php',
			'wp-config.php',
			'wp-config-sample.php',
			'wp-cron.php',
			'wp-mail.php',
			'wp-load.php',
			'wp-links-opml.php',
			'wp-settings.php',
			'wp-signup.php',
			'wp-trackback.php',
			'xmlrpc.php',
			'content',
			'includes',
			'css',
			'js',
			'font',
		));

	}

	/**
	 * Set the permalinks in database
	 *
	 * @param  array
	 *  $params
	 * @throws Exception
	 */
	public function savePermalinks($params)
	{
		HMWP_Classes_Tools::saveOptions('error', false);
		HMWP_Classes_Tools::saveOptions('changes', false);

		if ($params['hmwp_admin_url'] == $params['hmwp_login_url'] && $params['hmwp_admin_url'] <> '') {
			HMWP_Classes_Tools::saveOptions('error', true);
			HMWP_Classes_Tools::saveOptions('test_frontend', false);
			HMWP_Classes_Error::setError(esc_html__("You can't set both ADMIN and LOGIN with the same name. Please use different names", 'hide-my-wp'));
			return;
		}

		//send email when the admin is changed
		if (isset($params['hmwp_send_email'])) {
			HMWP_Classes_Tools::$default['hmwp_send_email'] = $params['hmwp_send_email'];
		}

		if ($params['hmwp_mode'] == 'default') {
			$params = HMWP_Classes_Tools::$default;
		}

		////////////////////////////////////////////
		//Set the Category and Tags dirs
		global $wp_rewrite;
		$blog_prefix = '';
		if (HMWP_Classes_Tools::isMultisites() && !is_subdomain_install() && is_main_site() && 0 === strpos(get_option('permalink_structure'), '/blog/')) {
			$blog_prefix = '/blog';
		}

		if (isset($params['hmwp_category_base']) && method_exists($wp_rewrite, 'set_category_base')) {
			$category_base = $params['hmwp_category_base'];
			if (!empty($category_base)) {
				$category_base = $blog_prefix . preg_replace('#/+#', '/', '/' . str_replace('#', '', $category_base));
			}
			$wp_rewrite->set_category_base($category_base);
		}

		if (isset($params['hmwp_tag_base']) && method_exists($wp_rewrite, 'set_tag_base')) {
			$tag_base = $params['hmwp_tag_base'];
			if (!empty($tag_base)) {
				$tag_base = $blog_prefix . preg_replace('#/+#', '/', '/' . str_replace('#', '', $tag_base));
			}
			$wp_rewrite->set_tag_base($tag_base);
		}
		////////////////////////////////////////////

		//Save all values
		$this->saveValues($params, true);

		//Some values need to be saved as blank is case no data is received
		//Set them to blank or value
		HMWP_Classes_Tools::saveOptions('hmwp_lostpassword_url', HMWP_Classes_Tools::getValue('hmwp_lostpassword_url', ''));
		HMWP_Classes_Tools::saveOptions('hmwp_register_url', HMWP_Classes_Tools::getValue('hmwp_register_url', ''));
		HMWP_Classes_Tools::saveOptions('hmwp_logout_url', HMWP_Classes_Tools::getValue('hmwp_logout_url', ''));

		//Make sure the theme style name is ending with .css to be a static file
		if($stylename = HMWP_Classes_Tools::getValue('hmwp_themes_style')) {
			if(strpos($stylename, '.css') === false) {
				HMWP_Classes_Tools::saveOptions('hmwp_themes_style', $stylename . '.css');
			}
		}

		//generate unique names for plugins if needed
		if (HMWP_Classes_Tools::getOption('hmwp_hide_plugins')) {
			HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite')->hidePluginNames();
		}
		if (HMWP_Classes_Tools::getOption('hmwp_hide_themes')) {
			HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite')->hideThemeNames();
		}

		if(!HMWP_Classes_Tools::getOption('hmwp_hide_oldpaths')) {
			HMWP_Classes_Tools::saveOptions('hmwp_hide_oldpaths_plugins', 0);
			HMWP_Classes_Tools::saveOptions('hmwp_hide_oldpaths_themes', 0);
		}

		//If no change is made on settings, just return
		if(!$this->checkOptionsChange()) {
			return;
		}

		//Save the rules and add the rewrites
		$this->saveRules();

	}

	/**
	 * Check if the current setup changed the last settings
	 *
	 * @return bool
	 */
	public function checkOptionsChange()
	{
		$lastsafeoptions = HMWP_Classes_Tools::getOptions(true);

		foreach ($lastsafeoptions as $index => $value){
			if(HMWP_Classes_Tools::getOption($index) <> $value) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if the main paths were change and a logout is needed
	 *
	 * @return void
	 */
	public function checkMainPathsChange(){
		//If the admin is changed, require a logout if necessary
		$lastsafeoptions = HMWP_Classes_Tools::getOptions(true);
		$options = HMWP_Classes_Tools::getOptions();

		if(!empty($lastsafeoptions)) {
			if ($lastsafeoptions['hmwp_admin_url'] <> HMWP_Classes_Tools::getOption('hmwp_admin_url')) {
				HMWP_Classes_Tools::saveOptions('logout', true);
			} elseif ($lastsafeoptions['hmwp_login_url'] <> HMWP_Classes_Tools::getOption('hmwp_login_url')) {
				HMWP_Classes_Tools::saveOptions('logout', true);
			} elseif ($lastsafeoptions['hmwp_admin-ajax_url'] <> HMWP_Classes_Tools::getOption('hmwp_admin-ajax_url')) {
				HMWP_Classes_Tools::saveOptions('logout', true);
			} elseif ($lastsafeoptions['hmwp_wp-json'] <> HMWP_Classes_Tools::getOption('hmwp_wp-json')) {
				HMWP_Classes_Tools::saveOptions('logout', true);
			} elseif ($lastsafeoptions['hmwp_upload_url'] <> HMWP_Classes_Tools::getOption('hmwp_upload_url')) {
				HMWP_Classes_Tools::saveOptions('logout', true);
			} elseif ($lastsafeoptions['hmwp_wp-content_url'] <> HMWP_Classes_Tools::getOption('hmwp_wp-content_url')) {
				HMWP_Classes_Tools::saveOptions('logout', true);
			}

		}
	}

	/**
	 * Save the Values in database
	 *
	 * @param $params
	 * @param bool $validate
	 */
	public function saveValues($params, $validate = false)
	{
		//Save the option values
		foreach ($params as $key => $value) {
			if (in_array($key, array_keys(HMWP_Classes_Tools::$options))) {
				//Make sure is set in POST
				if (HMWP_Classes_Tools::getIsset($key)) {
					//sanitize the value first
					$value = HMWP_Classes_Tools::getValue($key);

					//set the default value in case of nothing to prevent empty paths and errors
					if ($value == '') {
						if (isset(HMWP_Classes_Tools::$default[$key])) {
							$value = HMWP_Classes_Tools::$default[$key];
						} elseif (isset(HMWP_Classes_Tools::$init[$key])) {
							$value = HMWP_Classes_Tools::$init[$key];
						}
					}

					//Detect Invalid Names
					if ($validate) {
						//if there is no the default mode
						//Don't check the validation for whitlist URLs
						if (isset($params['hmwp_mode']) && $params['hmwp_mode'] <> 'default') {

							//check if the name is valid
							if ($this->checkValidName($key, $value) && $this->checkValidPath($key, $value)) {
								//Detect Weak Names
								$this->checkWeakName($value); //show weak names

								HMWP_Classes_Tools::saveOptions($key, $value);
							}

						} else {
							HMWP_Classes_Tools::saveOptions($key, $value);
						}

					} else {
						HMWP_Classes_Tools::saveOptions($key, $value);
					}
				}
			}
		}
	}

	/**
	 * Save the rules in the config file
	 *
	 * @throws Exception
	 */
	public function saveRules()
	{
		//CLEAR RULES ON DEFAULT
		if (HMWP_Classes_Tools::getOption('hmwp_mode') == 'default') {
			HMWP_Classes_ObjController::getClass('HMWP_Models_Rules')->writeToFile('', 'HMWP_VULNERABILITY');
			return;
		}


		//INSERT SEURITY RULES
		if (!HMWP_Classes_Tools::isIIS()) {
			//For Nginx and Apache the rules can be inserted separately
			$rules = HMWP_Classes_ObjController::getClass('HMWP_Models_Rules')->getInjectionRewrite();
			if (HMWP_Classes_Tools::getOption('hmwp_hide_oldpaths') || HMWP_Classes_Tools::getOption('hmwp_hide_commonfiles')) {
				$rules .= HMWP_Classes_ObjController::getClass('HMWP_Models_Rules')->getHideOldPathRewrite();
			}

			if(strlen($rules) > 1) {
				if (!HMWP_Classes_ObjController::getClass('HMWP_Models_Rules')->writeToFile($rules, 'HMWP_VULNERABILITY') ) {
					$config_file = HMWP_Classes_ObjController::getClass('HMWP_Models_Rules')->getConfFile();
					HMWP_Classes_Error::setError(sprintf(esc_html__('Config file is not writable. Create the file if not exists or copy to %s file with the following lines: %s', 'hide-my-wp'), '<strong>' . $config_file . '</strong>', '<br /><br /><pre><strong># BEGIN HMWP_VULNERABILITY<br />' . htmlentities(str_replace('    ', ' ', $rules)) . '# END HMWP_VULNERABILITY</strong></pre>'));
				}
			}
		}

	}

	/**
	 * Save the Text mapping
	 * @param $hmwp_url_mapping_from
	 * @param $hmwp_url_mapping_to
	 * @return void
	 * @throws Exception
	 */
	public function saveTextMapping($hmwp_text_mapping_from, $hmwp_text_mapping_to){
		$hmwp_text_mapping = array();

		add_filter('hmwp_validate_keys', function ($keys){
			return array(
				'hmwp_text_mapping'
			);
		});

		add_filter('hmwp_invalid_names', function ($invalid_paths){
			return array(
				'wp-post-image',
				'wp-content',
				'wp-includes',
				'wp-admin',
				'wp-login.php',
				'uploads',
			);
		});

		foreach ( $hmwp_text_mapping_from as $index => $from ) {
			if ($hmwp_text_mapping_from[$index] <> '' && $hmwp_text_mapping_to[$index] <> '' ) {
				$hmwp_text_mapping_from[$index] = preg_replace('/[^A-Za-z0-9-_.{}\/]/', '', $hmwp_text_mapping_from[$index]);
				$hmwp_text_mapping_to[$index] = preg_replace('/[^A-Za-z0-9-_.{}\/]/', '', $hmwp_text_mapping_to[$index]);

				//check for invalid names
				if($this->checkValidName('hmwp_text_mapping', $hmwp_text_mapping_from[$index]) && $this->checkValidName('hmwp_text_mapping', $hmwp_text_mapping_to[$index])) {
					if (!isset($hmwp_text_mapping['from']) || !in_array($hmwp_text_mapping_from[$index], (array)$hmwp_text_mapping['from'])) {

						//Don't save the wp-posts for Woodmart theme
						if (HMWP_Classes_Tools::isPluginActive('woocommerce/woocommerce.php')) {
							if ($hmwp_text_mapping_from[$index] == 'wp-post-image') {
								continue;
							}
						}

						if ($hmwp_text_mapping_from[$index] <> $hmwp_text_mapping_to[$index]) {
							$hmwp_text_mapping['from'][] = $hmwp_text_mapping_from[$index];
							$hmwp_text_mapping['to'][] = $hmwp_text_mapping_to[$index];
						}

					} else {
						HMWP_Classes_Error::setError(esc_html__('Error: You entered the same text twice in the Text Mapping. We removed the duplicates to prevent any redirect errors.'));
					}
				}
			}
		}

		//let other plugins to change
		$hmwp_text_mapping = apply_filters('hmwp_text_mapping_before_save', $hmwp_text_mapping);

		HMWP_Classes_Tools::saveOptions('hmwp_text_mapping', json_encode($hmwp_text_mapping));

	}


	/**
	 * Save the URL mapping
	 * @param $hmwp_url_mapping_from
	 * @param $hmwp_url_mapping_to
	 * @return void
	 * @throws Exception
	 */
	public function saveURLMapping($hmwp_url_mapping_from, $hmwp_url_mapping_to) {
		$hmwp_url_mapping = array();

		add_filter('hmwp_validate_keys', function ($keys){
			return array(
				'hmwp_url_mapping'
			);
		});

		add_filter('hmwp_invalid_names', function ($invalid_paths){
			return array(
				'wp-content',
				'/wp-content',
				site_url('wp-content'),
				site_url('wp-content','relative'),
				'wp-includes',
				'/wp-includes',
				site_url('wp-includes'),
				site_url('wp-includes','relative'),
				'wp-admin',
				'/wp-admin',
				site_url('wp-admin'),
				site_url('wp-admin','relative'),
				'wp-login.php',
				'/wp-login.php',
				home_url('wp-login.php'),
				home_url('wp-login.php','relative'),
				'uploads',
				'wp-content/uploads',
				'/wp-content/uploads',
				'plugins',
				'wp-content/plugins',
				'/wp-content/plugins',
				'themes',
				'wp-content/themes',
				'/wp-content/themes',
			);
		});

		foreach ( $hmwp_url_mapping_from as $index => $from ) {
			if ($hmwp_url_mapping_from[$index] <> '' && $hmwp_url_mapping_to[$index] <> '') {
				$hmwp_url_mapping_from[$index] = preg_replace('/[^A-Za-z0-9-_;:=%.#\/\?]/', '', $hmwp_url_mapping_from[$index]);
				$hmwp_url_mapping_to[$index] = preg_replace('/[^A-Za-z0-9-_;:%=.#\/\?]/', '', $hmwp_url_mapping_to[$index]);

				if($this->checkValidName('hmwp_url_mapping', $hmwp_url_mapping_from[$index]) && $this->checkValidName('hmwp_url_mapping', $hmwp_url_mapping_to[$index])) {
					if (!isset($hmwp_url_mapping['from']) || (!in_array($hmwp_url_mapping_from[$index], (array)$hmwp_url_mapping['from']) && !in_array($hmwp_url_mapping_to[$index], (array)$hmwp_url_mapping['to']))) {
						if ($hmwp_url_mapping_from[$index] <> $hmwp_url_mapping_to[$index]) {
							$hmwp_url_mapping['from'][] = $hmwp_url_mapping_from[$index];
							$hmwp_url_mapping['to'][] = $hmwp_url_mapping_to[$index];
						}
					} else {
						HMWP_Classes_Error::setError(esc_html__('Error: You entered the same URL twice in the URL Mapping. We removed the duplicates to prevent any redirect errors.'));
					}
				}

			}
		}

		//let other plugins to change
		$hmwp_url_mapping = apply_filters('hmwp_url_mapping_before_save', $hmwp_url_mapping);

		HMWP_Classes_Tools::saveOptions('hmwp_url_mapping', json_encode($hmwp_url_mapping));

		if (!empty($hmwp_url_mapping) ) {
			//show rules to be added manually
			if (!HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite')->clearRedirect()->setRewriteRules()->flushRewrites() ) {
				HMWP_Classes_Tools::saveOptions('test_frontend', false);
				HMWP_Classes_Tools::saveOptions('file_mappings', array());
				HMWP_Classes_Tools::saveOptions('error', true);
			}
		}
	}

	/**
	 * Check invalid name and avoid errors
	 *
	 * @param string $key  DB Option name
	 * @param string $name Option value
	 * @return bool
	 */
	public function checkValidName($key, $name)
	{

		if(is_array($name)) {
			foreach ($name as $current){
				if(!$this->checkValidName($key, $current)){
					return false;
				}
			}
		}else {

			//initialize validation fields
			$this->initValidationFields();

			if ( in_array( $key, $this->validate_keys ) ) {

				// Avoid names that lead to WordPress errors
				if ( ( $key <> 'hmwp_themes_url' && $name == 'themes' ) ||
				     ( $key == 'hmwp_themes_url' && $name == 'assets' ) ||
				     ( $key <> 'hmwp_upload_url' && $name == 'uploads' ) ||
				     in_array( $name, $this->invalid_names ) ) {

					HMWP_Classes_Error::setError( sprintf( esc_html__( "Invalid name detected: %s. You need to use another name to avoid WordPress errors.", 'hide-my-wp' ), '<strong>' . $name . '</strong>' ) );

					return false;
				}

			}
		}

		return true;
	}

	/**
	 * Check if the path is valid
	 *
	 * @param $key
	 * @param $name
	 *
	 * @return bool
	 */
	public function checkValidPath($key, $name){

		//initialize validation fields
		$this->initValidationFields();

		if(in_array($key, $this->validate_keys)) {

			if ( strlen( $name ) > 1 && strlen( $name ) < 3 ) {
				HMWP_Classes_Error::setError( sprintf( esc_html__( "Short name detected: %s. You need to use unique paths with more than 4 chars to avoid WordPress errors.", 'hide-my-wp' ), '<strong>' . $name . '</strong>' ) );

				return false;
			}

			if ( strpos( $name, '//' ) !== false ) {
				HMWP_Classes_Error::setError( sprintf( esc_html__( "Invalid name detected: %s. Add only the final path name to avoid WordPress errors.", 'hide-my-wp' ), '<strong>' . $name . '</strong>' ) );

				return false;
			}

			if ( strpos( $name, '/' ) !== false && strpos( $name, '/' ) == 0 ) {
				HMWP_Classes_Error::setError( sprintf( esc_html__( "Invalid name detected: %s. The name can't start with / to avoid WordPress errors.", 'hide-my-wp' ), '<strong>' . $name . '</strong>' ) );

				return false;
			}

			if ( strpos( $name, '/' ) !== false && substr( $name, - 1 ) == '/' ) {
				HMWP_Classes_Error::setError( sprintf( esc_html__( "Invalid name detected: %s. The name can't end with / to avoid WordPress errors.", 'hide-my-wp' ), '<strong>' . $name . '</strong>' ) );

				return false;
			}

			$array = explode( '/', $name );
			if ( ! empty( $array ) ) {
				foreach ( $array as $row ) {
					if ( substr( $row, - 1 ) === '.' ) {
						HMWP_Classes_Error::setError( sprintf( esc_html__( "Invalid name detected: %s. The paths can't end with . to avoid WordPress errors.", 'hide-my-wp' ), '<strong>' . $name . '</strong>' ) );

						return false;
					}
				}
			}
		}

		return true;
	}

	/**
	 * Check if the name is week for security
	 *
	 * @param string $name
	 */
	public function checkWeakName($name)
	{
		$invalit_paths = array(
			'login',
			'mylogin',
			'wp-login',
			'admin',
			'wp-mail.php',
			'wp-settings.php',
			'wp-signup.php',
			'wp-trackback.php',
			'xmlrpc.php',
			'wp-include',
		);

		if (in_array($name, $invalit_paths)) {
			HMWP_Classes_Error::setError(sprintf(esc_html__("Weak name detected: %s. You need to use another name to increase your website security.", 'hide-my-wp'), '<strong>' . $name . '</strong>'));
		}
	}
}
