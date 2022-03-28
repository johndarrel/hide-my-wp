<?php
/**
 * Plugin Menu Configuration Model
 * Called when the user is logged in as admin or with the proper capabilities
 *
 * @file  The Menu Model file
 * @package HMWP/MenuModel
 * @since 4.0.0
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Models_Menu
{

    /**
     * Get the admin Menu Tabs
     *
     * @return array
     * @throws Exception
     */
    public function getMenu()
    {

        $menu =  array(
            'hmwp_settings' => array(
                'name' => esc_html__("Overview", 'hide-my-wp'). ' ' . apply_filters('hmwp_alert_count', ''),
                'title' => esc_html__("Overview", 'hide-my-wp'),
                'capability' => 'hmwp_manage_settings',
                'parent' => 'hmwp_settings',
                'function' => array(HMWP_Classes_ObjController::getClass('HMWP_Controllers_Overview'), 'init'),
            ),
            'hmwp_permalinks' => array(
                'name' => esc_html__("Change Paths", 'hide-my-wp'),
                'title' => esc_html__("Change Paths", 'hide-my-wp'),
                'capability' => 'hmwp_manage_settings',
                'parent' => 'hmwp_settings',
                'function' => array(HMWP_Classes_ObjController::getClass('HMWP_Controllers_Settings'), 'init'),
            ),
            'hmwp_tweaks' => array(
                'name' => esc_html__("Tweaks", 'hide-my-wp'),
                'title' => esc_html__("Tweaks", 'hide-my-wp'),
                'capability' => 'hmwp_manage_settings',
                'parent' => 'hmwp_settings',
                'function' => array(HMWP_Classes_ObjController::getClass('HMWP_Controllers_Settings'), 'init'),
            ),
            'hmwp_mapping' => array(
                'name' => esc_html__("Mapping", 'hide-my-wp'),
                'title' => esc_html__("Text & URL Mapping", 'hide-my-wp'),
                'capability' => 'hmwp_manage_settings',
                'parent' => 'hmwp_settings',
                'function' => array(HMWP_Classes_ObjController::getClass('HMWP_Controllers_Settings'), 'init'),
            ),
            'hmwp_brute' => array(
                'name' => esc_html__("Brute Force", 'hide-my-wp'),
                'title' => esc_html__("Brute Force", 'hide-my-wp'),
                'capability' => 'hmwp_manage_settings',
                'parent' => 'hmwp_settings',
                'function' => array(HMWP_Classes_ObjController::getClass('HMWP_Controllers_Settings'), 'init'),
            ),
            'hmwp_log' => array(
                'name' => esc_html__("Events Log", 'hide-my-wp'),
                'title' => esc_html__("Events Log", 'hide-my-wp'),
                'capability' => 'hmwp_manage_settings',
                'parent' => 'hmwp_settings',
                'function' => array(HMWP_Classes_ObjController::getClass('HMWP_Controllers_Settings'), 'init'),
            ),
            'hmwp_securitycheck' => array(
                'name' => esc_html__("Security Check", 'hide-my-wp'),
                'title' => esc_html__("Security Check", 'hide-my-wp'),
                'capability' => 'hmwp_manage_settings',
                'parent' => 'hmwp_settings',
                'function' => array(HMWP_Classes_ObjController::getClass('HMWP_Controllers_SecurityCheck'), 'init'),
            ),
            'hmwp_backup' => array(
                'name' => esc_html__("Backup/Restore", 'hide-my-wp'),
                'title' => esc_html__("Backup/Restore", 'hide-my-wp'),
                'capability' => 'hmwp_manage_settings',
                'parent' => 'hmwp_settings',
                'function' => array(HMWP_Classes_ObjController::getClass('HMWP_Controllers_Settings'), 'init'),
            ),
            'hmwp_advanced' => array(
                'name' => esc_html__("Advanced", 'hide-my-wp'),
                'title' => esc_html__("Advanced Settings", 'hide-my-wp'),
                'capability' => 'hmwp_manage_settings',
                'parent' => 'hmwp_settings',
                'function' => array(HMWP_Classes_ObjController::getClass('HMWP_Controllers_Settings'), 'init'),
            ),
            'hmwp_plugins' => array(
                'name' => esc_html__("Plugins", 'hide-my-wp'),
                'title' => esc_html__("Recommended Plugins", 'hide-my-wp'),
                'capability' => 'hmwp_manage_settings',
                'parent' => 'hmwp_settings',
                'function' => array(HMWP_Classes_ObjController::getClass('HMWP_Controllers_Plugins'), 'init'),
            ),
        );

        //Remove the menu when the feature in hidden by the user
        foreach ($menu as $key => $value){
            $keys = array_keys(HMWP_Classes_Tools::$options);
            if (!empty($keys) && in_array($key . '_menu_show', $keys)) {
                if (!HMWP_Classes_Tools::getOption($key . '_menu_show')) {
                    unset($menu[$key]);
                }
            }
        }

        //Show the account link only if the option is active
        if(HMWP_Classes_Tools::getOption('api_token') && apply_filters('hmwp_showaccount', true)) {
            $menu['hmwp_account'] = array(
                'name' => esc_html__("My Account", 'hide-my-wp'),
                'title' => esc_html__("My Account", 'hide-my-wp'),
                'capability' => 'hmwp_manage_settings',
                'parent' => 'hmwp_settings',
                'href' => HMWP_Classes_Tools::getCloudUrl('orders'),
                'function' => false,
            );
        }

        //Return the menu array
        return $menu;
    }

    /**
     * Get the Submenu section for each menu
     *
     * @param string $current
     * @return array|mixed
     */
    public function getSubMenu($current)
    {
        $subtabs = array(
            'hmwp_permalinks' => array(
                array(
                    'title' => esc_html__("Level of Security", 'hide-my-wp') . ' ' . '<i class="dashicons-before dashicons-shield-alt text-black-50" style="vertical-align: middle" ></i>',
                    'tab' =>'level',
                ),
                array(
                    'title' => esc_html__("Admin Security", 'hide-my-wp'),
                    'tab' =>'newadmin',
                ),
                array(
                    'title' => esc_html__("Login Security", 'hide-my-wp'),
                    'tab' =>'newlogin',
                ),
                array(
                    'title' => esc_html__("Ajax Security", 'hide-my-wp'),
                    'tab' =>'ajax',
                ),
                array(
                    'title' => esc_html__("User Security", 'hide-my-wp'),
                    'tab' =>'author',
                ),
                array(
                    'title' => esc_html__("WP Core Security", 'hide-my-wp'),
                    'tab' =>'core',
                ),
                array(
                    'title' => esc_html__("Plugins Security", 'hide-my-wp'),
                    'tab' =>'plugin',
                ),
                array(
                    'title' => esc_html__("Themes Security", 'hide-my-wp'),
                    'tab' =>'theme',
                ),
                array(
                    'title' => esc_html__("API Security", 'hide-my-wp'),
                    'tab' =>'api',
                ),
                array(
                    'title' => esc_html__("Firewall & Headers", 'hide-my-wp'),
                    'tab' =>'firewall',
                ),
                array(
                    'title' => esc_html__("Other Options", 'hide-my-wp'),
                    'tab' =>'more',
                )
            ),
            'hmwp_mapping' => array(
                array(
                    'title' => esc_html__("Text Mapping", 'hide-my-wp'),
                    'tab' =>'text',
                ),
                array(
                    'title' => esc_html__("URL Mapping", 'hide-my-wp'),
                    'tab' =>'url',
                ),
                array(
                    'title' => esc_html__("CDN", 'hide-my-wp'),
                    'tab' =>'cdn',
                ),
                array(
                    'title' => esc_html__("Experimental", 'hide-my-wp'),
                    'tab' =>'experimental',
                ),
            ),
            'hmwp_tweaks' => array(
                array(
                    'title' => esc_html__("Redirects", 'hide-my-wp'),
                    'tab' =>'redirects',
                ),
                array(
                    'title' => esc_html__("Feed & Sitemap", 'hide-my-wp'),
                    'tab' =>'sitemap',
                ),
                array(
                    'title' => esc_html__("Change Options", 'hide-my-wp'),
                    'tab' =>'changes',
                ),
                array(
                    'title' => esc_html__("Hide Options", 'hide-my-wp'),
                    'tab' =>'hide',
                ),
                array(
                    'title' => esc_html__("Disable Options", 'hide-my-wp'),
                    'tab' =>'disable',
                ),
            ),
            'hmwp_brute' => array(
	            array(
		            'title' => esc_html__("Blocked IPs Report", 'hide-my-wp'),
		            'tab' =>'blocked',
	            ),
	            array(
		            'title' => esc_html__("Brute Force Settings", 'hide-my-wp'),
		            'tab' =>'brute',
	            ),
            ),
            'hmwp_log' => array(
	            array(
		            'title' => esc_html__("Events Log Settings", 'hide-my-wp'),
		            'tab' =>'log',
	            ),

            ),
            'hmwp_advanced' => array(
                array(
                    'title' => esc_html__("Safe URL", 'hide-my-wp'),
                    'tab' =>'rollback',
                ),
                array(
                    'title' => esc_html__("Compatibility", 'hide-my-wp'),
                    'tab' =>'compatibility',
                ),
                array(
                    'title' => esc_html__("Email Notification", 'hide-my-wp'),
                    'tab' =>'notification',
                ),

            ),
        );

        //Remove the submenu is the user hides it from all features
        foreach ($subtabs as $key => &$values) {
            foreach ($values as $index => $value) {
                if (in_array($key . '_' . $value['tab'] . '_show', array_keys(HMWP_Classes_Tools::$options))) {
                    if (!HMWP_Classes_Tools::getOption($key . '_' . $value['tab'] . '_show')) {
                        unset($values[$index]);
                    }
                }
            }
        }

        //Return all submenus
        if(isset($subtabs[$current])) {
            return  $subtabs[$current];
        }

        return array();
    }

    /**
     * 
     *
     * @var array with the menu content
     *
     * $page_title (string) (required) The text to be displayed in the title tags of the page when the menu is selected
     * $menu_title (string) (required) The on-screen name text for the menu
     * $capability (string) (required) The capability required for this menu to be displayed to the user. User levels are deprecated and should not be used here!
     * $menu_slug (string) (required) The slug name to refer to this menu by (should be unique for this menu). Prior to Version 3.0 this was called the file (or handle) parameter. If the function parameter is omitted, the menu_slug should be the PHP file that handles the display of the menu page content.
     * $function The function that displays the page content for the menu page. Technically, the function parameter is optional, but if it is not supplied, then WordPress will basically assume that including the PHP file will generate the administration screen, without calling a function. Most plugin authors choose to put the page-generating code in a function within their main plugin file.:In the event that the function parameter is specified, it is possible to use any string for the file parameter. This allows usage of pages such as ?page=my_super_plugin_page instead of ?page=my-super-plugin/admin-options.php.
     * $icon_url (string) (optional) The url to the icon to be used for this menu. This parameter is optional. Icons should be fairly small, around 16 x 16 pixels for best results. You can use the plugin_dir_url( __FILE__ ) function to get the URL of your plugin directory and then add the image filename to it. You can set $icon_url to "div" to have WordPress generate <br> tag instead of <img>. This can be used for more advanced formating via CSS, such as changing icon on hover.
     * $position (integer) (optional) The position in the menu order this menu should appear. By default, if this parameter is omitted, the menu will appear at the bottom of the menu structure. The higher the number, the lower its position in the menu. WARNING: if 2 menu items use the same position attribute, one of the items may be overwritten so that only one item displays!
     * */
    public $menu = array();
    public $meta = array();

    /**
     * Add a menu in WP admin page
     *
     * @param array $param
     *
     * @return void
     */
    public function addMenu($param)
    {
        $this->menu = $param;

        if (is_array($this->menu)) {

            if ($this->menu[0] <> '' && $this->menu[1] <> '') {

                if (!isset($this->menu[5])) {
                    $this->menu[5] = null;
                }
                if (!isset($this->menu[6])) {
                    $this->menu[6] = null;
                }

                /* add the menu with WP */
                add_menu_page($this->menu[0], $this->menu[1], $this->menu[2], $this->menu[3], $this->menu[4], $this->menu[5], $this->menu[6]);
            }
        }
    }

    /**
     * Add a submenumenu in WP admin page
     *
     * @param array $param
     *
     * @return void
     */
    public function addSubmenu($param = null)
    {
        if ($param) {
            $this->menu = $param;
        }

        if (is_array($this->menu)) {

            if ($this->menu[0] <> '' && $this->menu[1] <> '') {

                if (!isset($this->menu[5])) {
                    $this->menu[5] = null;
                }
                if (!isset($this->menu[6])) {
                    $this->menu[6] = null;
                }

                /* add the menu with WP */
                add_submenu_page($this->menu[0], $this->menu[1], $this->menu[2], $this->menu[3], $this->menu[4], $this->menu[5], $this->menu[6]);
            }
        }
    }

    /**
     * Load the Settings class when the plugin settings are loaded
     * Used for loading the CSS and JS only in the settings area
     *
     * @param string $classes
     * @return string
     * @throws Exception
     */
    public function addSettingsClass( $classes )
    {
        if ($page = HMWP_Classes_Tools::getValue('page')) {

            $menu = $this->getMenu();
            if(in_array($page, array_keys($menu))) {
                //Add the class when loading the plugin settings
                $classes = "$classes hmwp-settings";
            }

        }

        //Return the classes
        return $classes;
    }

    /**
     * Add compatibility on CSS and JS with other plugins and themes
     * Called in Menu Controller to fix teh CSS and JS compatibility
     */
    public function fixEnqueueErrors()
    {

        $exclude = array('boostrap',
            'wpcd-admin-js', 'ampforwp_admin_js', '__ytprefs_admin__', 'wpf-graphics-admin-style',
            'wwp-bootstrap', 'wwp-bootstrap-select', 'wwp-popper', 'wwp-script',
            'wpf_admin_style', 'wpf_bootstrap_script', 'wpf_wpfb-front_script', 'auxin-admin-style',
            'wdc-styles-extras', 'wdc-styles-main', 'wp-color-picker-alpha',  //collor picker compatibility
            'td_wp_admin', 'td_wp_admin_color_picker', 'td_wp_admin_panel', 'td_edit_page', 'td_page_options', 'td_tooltip', 'td_confirm', 'thickbox',
            'font-awesome', 'bootstrap-iconpicker-iconset', 'bootstrap-iconpicker',
            'cs_admin_styles_css', 'jobcareer_admin_styles_css','jobcareer_editor_style', 'jobcareer_bootstrap_min_js', 'cs_fonticonpicker_bootstrap_css',
            'cs_bootstrap_slider_css', 'cs_bootstrap_css', 'cs_bootstrap_slider', 'cs_bootstrap_min_js', 'cs_bootstrap_slider_js', 'bootstrap',
            'wp-reset', 'buy-me-a-coffee'
        );

        //Exclude the styles and scripts that affects the plugin functionality
        foreach ($exclude as $name) {
            wp_dequeue_script($name);
            wp_dequeue_style($name);
        }
    }

}
