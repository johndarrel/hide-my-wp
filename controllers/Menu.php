<?php
defined('ABSPATH') || die('Cheatin\' uh?');

class HMW_Controllers_Menu extends HMW_Classes_FrontController {

    public $alert = '';

    /**
     * Hook the Admin load
     */
    public function hookInit() {
        /* add the plugin menu in admin */
        if (current_user_can('manage_options')) {
            //check if activated
            if (get_transient('hmw_activate') == 1) {
                // Delete the redirect transient
                delete_transient('hmw_activate');

                //Make sure the plugin is loaded first
                $plugin = _HMW_PLUGIN_NAME_ . '/index.php';
                $active_plugins = get_option('active_plugins');
                if(!empty($active_plugins)) {
                    $this_plugin_key = array_search($plugin, $active_plugins);
                    if($this_plugin_key <> '') {
                        array_splice($active_plugins, $this_plugin_key, 1);
                        array_unshift($active_plugins, $plugin);
                        update_option('active_plugins', $active_plugins);
                    }
                }

	            //Check if there are expected upgrades
	            HMW_Classes_Tools::checkUpgrade();
            }



            //Load notice class in admin
            HMW_Classes_ObjController::getClass('HMW_Controllers_Notice');

            //Show Dashboard Box
            add_action('wp_dashboard_setup', array($this, 'hookDashboardSetup'));


            if (HMW_Classes_Tools::getValue('page', false) == 'hmw_settings') {
                add_action('admin_enqueue_scripts', array($this->model, 'fixEnqueueErrors'), PHP_INT_MAX);
            }
        }

    }

    /**
     * Creates the Setting menu in WordPress
     */
    public function hookMenu() {
        if (current_user_can('manage_options')) {
            $this->model->addMenu(array(ucfirst(_HMW_PLUGIN_NAME_),
                'Hide My WP' . $this->alert,
                'manage_options',
                'hmw_settings',
                null,
                _HMW_THEME_URL_ . 'img/logo_16.png'
            ));

            /* add the Hide My WP admin menu */
            $this->model->addSubmenu(array('hmw_settings',
                __('Hide My WP - Customize Permalinks', _HMW_PLUGIN_NAME_),
                __('Change Paths', _HMW_PLUGIN_NAME_),
                'manage_options',
                'hmw_settings',
                array(HMW_Classes_ObjController::getClass('HMW_Controllers_Settings'), 'init')
            ));

	        $this->model->addSubmenu(array('hmw_settings',
		        __('Hide My WP - Mapping', _HMW_PLUGIN_NAME_),
		        __('Mapping', _HMW_PLUGIN_NAME_),
		        'manage_options',
		        'hmw_settings-hmw_mapping',
		        array(HMW_Classes_ObjController::getClass('HMW_Controllers_Settings'), 'init')
	        ));

            $this->model->addSubmenu(array('hmw_settings',
                __('Hide My WP - Tweaks', _HMW_PLUGIN_NAME_),
                __('Tweaks', _HMW_PLUGIN_NAME_),
                'manage_options',
                'hmw_settings-hmw_tweaks',
                array(HMW_Classes_ObjController::getClass('HMW_Controllers_Settings'), 'init')
            ));


            $this->model->addSubmenu(array('hmw_settings',
                __('Hide My WP - Brute Force Protection', _HMW_PLUGIN_NAME_),
                __('Brute Force Protection', _HMW_PLUGIN_NAME_),
                'manage_options',
                'hmw_settings-hmw_brute',
                array(HMW_Classes_ObjController::getClass('HMW_Controllers_Settings'), 'init')
            ));


	        $this->model->addSubmenu(array('hmw_settings',
                __('Hide My WP - Log Events', _HMW_PLUGIN_NAME_),
                __('Log Events', _HMW_PLUGIN_NAME_),
                'manage_options',
                'hmw_settings-hmw_log',
                array(HMW_Classes_ObjController::getClass('HMW_Controllers_Settings'), 'init')
            ));

            /* add the security check in menu */
            $this->model->addSubmenu(array('hmw_settings',
                __('Hide My WP - Security Check', _HMW_PLUGIN_NAME_),
                __('Security Check', _HMW_PLUGIN_NAME_) . $this->alert,
                'manage_options',
                'hmw_securitycheck',
                array(HMW_Classes_ObjController::getClass('HMW_Controllers_SecurityCheck'), 'show')
            ));

            $this->model->addSubmenu(array('hmw_settings',
                __('Hide My WP - Recommended Plugins', _HMW_PLUGIN_NAME_),
                __('Install Plugins', _HMW_PLUGIN_NAME_),
                'manage_options',
                'hmw_settings-hmw_plugins',
                array(HMW_Classes_ObjController::getClass('HMW_Controllers_Settings'), 'init')
            ));

            $this->model->addSubmenu(array('hmw_settings',
                __('Hide My WP - Backup & Restore', _HMW_PLUGIN_NAME_),
                __('Backup/Restore', _HMW_PLUGIN_NAME_),
                'manage_options',
                'hmw_settings-hmw_backup',
                array(HMW_Classes_ObjController::getClass('HMW_Controllers_Settings'), 'init')
            ));

            $this->model->addSubmenu(array('hmw_settings',
                __('Hide My WP - Advanced Settings', _HMW_PLUGIN_NAME_),
                __('Advanced', _HMW_PLUGIN_NAME_),
                'manage_options',
                'hmw_settings-hmw_advanced',
                array(HMW_Classes_ObjController::getClass('HMW_Controllers_Settings'), 'init')
            ));


        }
    }

    public function hookDashboardSetup(){
        wp_add_dashboard_widget(
            'hmw_dashboard_widget',
            __('Hide My WP',_HMW_PLUGIN_NAME_),
            array(HMW_Classes_ObjController::getClass('HMW_Controllers_Widget'), 'dashboard')
        );

        // Move our widget to top.
        global $wp_meta_boxes;

        $dashboard = $wp_meta_boxes['dashboard']['normal']['core'];
        $ours      = array( 'hmw_dashboard_widget' => $dashboard['hmw_dashboard_widget'] );
        $wp_meta_boxes['dashboard']['normal']['core'] = array_merge( $ours, $dashboard );
    }


    /**
     * Creates the Setting menu in Multisite WordPress
     */
    public function hookMultisiteMenu() {

        $this->model->addMenu(array(ucfirst(_HMW_PLUGIN_NAME_),
            'Hide My WP' . $this->alert,
            'manage_options',
            'hmw_settings',
            null,
            _HMW_THEME_URL_ . 'img/logo_16.png'
        ));

        /* add the Hide My WP admin menu */
        $this->model->addSubmenu(array('hmw_settings',
            __('Hide My WP - Customize Permalinks', _HMW_PLUGIN_NAME_),
            __('Change Paths', _HMW_PLUGIN_NAME_),
            'manage_options',
            'hmw_settings',
            array(HMW_Classes_ObjController::getClass('HMW_Controllers_Settings'), 'init')
        ));

	    $this->model->addSubmenu(array('hmw_settings',
		    __('Hide My WP - Mapping', _HMW_PLUGIN_NAME_),
		    __('Mapping', _HMW_PLUGIN_NAME_),
		    'manage_options',
		    'hmw_settings-hmw_mapping',
		    array(HMW_Classes_ObjController::getClass('HMW_Controllers_Settings'), 'init')
	    ));

	    $this->model->addSubmenu(array('hmw_settings',
            __('Hide My WP - Tweaks', _HMW_PLUGIN_NAME_),
            __('Tweaks', _HMW_PLUGIN_NAME_),
            'manage_options',
            'hmw_settings-hmw_tweaks',
            array(HMW_Classes_ObjController::getClass('HMW_Controllers_Settings'), 'init')
        ));


        $this->model->addSubmenu(array('hmw_settings',
            __('Hide My WP - Brute Force Protection', _HMW_PLUGIN_NAME_),
            __('Brute Force Protection', _HMW_PLUGIN_NAME_),
            'manage_options',
            'hmw_settings-hmw_brute',
            array(HMW_Classes_ObjController::getClass('HMW_Controllers_Settings'), 'init')
        ));

        $this->model->addSubmenu(array('hmw_settings',
            __('Hide My WP - Log Events', _HMW_PLUGIN_NAME_),
            __('Log Events', _HMW_PLUGIN_NAME_),
            'manage_options',
            'hmw_settings-hmw_log',
            array(HMW_Classes_ObjController::getClass('HMW_Controllers_Settings'), 'init')
        ));

        /* add the security check in menu */
        $this->model->addSubmenu(array('hmw_settings',
            __('Hide My WP - Security Check', _HMW_PLUGIN_NAME_),
            __('Security Check', _HMW_PLUGIN_NAME_) . $this->alert,
            'manage_options',
            'hmw_securitycheck',
            array(HMW_Classes_ObjController::getClass('HMW_Controllers_SecurityCheck'), 'show')
        ));

        $this->model->addSubmenu(array('hmw_settings',
            __('Hide My WP - Recommended Plugins', _HMW_PLUGIN_NAME_),
            __('Install Plugins', _HMW_PLUGIN_NAME_),
            'manage_options',
            'hmw_settings-hmw_plugins',
            array(HMW_Classes_ObjController::getClass('HMW_Controllers_Settings'), 'init')
        ));

        $this->model->addSubmenu(array('hmw_settings',
            __('Hide My WP - Backup & Restore', _HMW_PLUGIN_NAME_),
            __('Backup/Restore', _HMW_PLUGIN_NAME_),
            'manage_options',
            'hmw_settings-hmw_backup',
            array(HMW_Classes_ObjController::getClass('HMW_Controllers_Settings'), 'init')
        ));

        $this->model->addSubmenu(array('hmw_settings',
            __('Hide My WP - Advanced Settings', _HMW_PLUGIN_NAME_),
            __('Advanced', _HMW_PLUGIN_NAME_),
            'manage_options',
            'hmw_settings-hmw_advanced',
            array(HMW_Classes_ObjController::getClass('HMW_Controllers_Settings'), 'init')
        ));
    }
}