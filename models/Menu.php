<?php
defined('ABSPATH') || die('Cheatin\' uh?');

class HMW_Models_Menu {

    /** @var array with the menu content
     *
     * $page_title (string) (required) The text to be displayed in the title tags of the page when the menu is selected
     * $menu_title (string) (required) The on-screen name text for the menu
     * $capability (string) (required) The capability required for this menu to be displayed to the user. User levels are deprecated and should not be used here!
     * $menu_slug (string) (required) The slug name to refer to this menu by (should be unique for this menu). Prior to Version 3.0 this was called the file (or handle) parameter. If the function parameter is omitted, the menu_slug should be the PHP file that handles the display of the menu page content.
     * $function The function that displays the page content for the menu page. Technically, the function parameter is optional, but if it is not supplied, then WordPress will basically assume that including the PHP file will generate the administration screen, without calling a function. Most plugin authors choose to put the page-generating code in a function within their main plugin file.:In the event that the function parameter is specified, it is possible to use any string for the file parameter. This allows usage of pages such as ?page=my_super_plugin_page instead of ?page=my-super-plugin/admin-options.php.
     * $icon_url (string) (optional) The url to the icon to be used for this menu. This parameter is optional. Icons should be fairly small, around 16 x 16 pixels for best results. You can use the plugin_dir_url( __FILE__ ) function to get the URL of your plugin directory and then add the image filename to it. You can set $icon_url to "div" to have wordpress generate <br> tag instead of <img>. This can be used for more advanced formating via CSS, such as changing icon on hover.
     * $position (integer) (optional) The position in the menu order this menu should appear. By default, if this parameter is omitted, the menu will appear at the bottom of the menu structure. The higher the number, the lower its position in the menu. WARNING: if 2 menu items use the same position attribute, one of the items may be overwritten so that only one item displays!
     *
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
    public function addMenu($param = null) {
        if ($param)
            $this->menu = $param;

        if (is_array($this->menu)) {

            if ($this->menu[0] <> '' && $this->menu[1] <> '') {
                /* add the translation */
                $this->menu[0] = __($this->menu[0], _HMW_PLUGIN_NAME_);
                $this->menu[1] = __($this->menu[1], _HMW_PLUGIN_NAME_);

                if (!isset($this->menu[5]))
                    $this->menu[5] = null;
                if (!isset($this->menu[6]))
                    $this->menu[6] = null;

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
    public function addSubmenu($param = null) {
        if ($param)
            $this->menu = $param;

        if (is_array($this->menu)) {

            if ($this->menu[0] <> '' && $this->menu[1] <> '') {
                /* add the translation */
                $this->menu[0] = __($this->menu[0], _HMW_PLUGIN_NAME_);
                $this->menu[1] = __($this->menu[1], _HMW_PLUGIN_NAME_);

                if (!isset($this->menu[5]))
                    $this->menu[5] = null;

                /* add the menu with WP */
                add_submenu_page($this->menu[0], $this->menu[1], $this->menu[2], $this->menu[3], $this->menu[4], $this->menu[5]);
            }
        }
    }

    /**
     * Add a box Meta in WP
     *
     * @param array $param
     *
     * @return void
     */
    public function addOption($param = null) {
        if ($param) {
            $this->meta = $param;
        }

        if (is_array($this->meta)) {

            if ($this->meta[0] <> '' && $this->meta[1] <> '') {
                /* add the translation */
                $this->meta[1] = __($this->meta[1], _HMW_PLUGIN_NAME_);

                if (!isset($this->meta[5]))
                    $this->meta[5] = null;

                /* add the box content with WP */
                add_options_page($this->meta[0], $this->meta[1], $this->meta[2], $this->meta[3], $this->meta[4]);
            }
        }
    }


	public function addSettingsClass( $classes ){
		if ($page = HMW_Classes_Tools::getValue('page')) {

			if (strpos($page,'hmw_settings') !== false || strpos($page,'hmw_securitycheck') !== false) {
				$classes = "$classes hmw_settings";
			}

		}

		return $classes;
	}


	/**
     * Prevent other plugins to load CSS into HMW
     */
    public function fixEnqueueErrors() {
	    $exclude = array('boostrap','wpcd-admin-js');

	    foreach ($exclude as $name) {
		    wp_dequeue_script($name);
		    wp_dequeue_style($name);
	    }
    }

}
