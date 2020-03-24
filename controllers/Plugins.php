<?php
defined('ABSPATH') || die('Cheatin\' uh?');

require_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');

class QuietSkin extends \WP_Upgrader_Skin {
    public function feedback($string) { /* no output */ }
}

class HMW_Controllers_Plugins extends HMW_Classes_FrontController {

    public function action() {
        parent::action();

        if (!current_user_can('manage_options')) {
            return;
        }

        switch (HMW_Classes_Tools::getValue('action')) {
            case 'hmw_plugin_install':
                HMW_Classes_Tools::setHeader('json');

                if (HMW_Classes_Tools::getValue('plugin', '') <> '') {
                    $plugins = HMW_Classes_ObjController::getClass('HMW_Models_Settings')->getPlugins();
                    $pluginPath = false;

                    foreach ($plugins as $plugin => $details) {
                        if ($plugin == HMW_Classes_Tools::getValue('plugin')) {
                            $pluginPath = WP_PLUGIN_DIR . '/' . $details['path'];
                            break;
                        }
                    }

                    if (!empty($plugin) && $pluginPath) {
                        if (!file_exists($pluginPath)) {

                            //includes necessary for Plugin_Upgrader and Plugin_Installer_Skin
                            require_once(ABSPATH . 'wp-admin/includes/plugin-install.php');
                            include_once(ABSPATH . 'wp-admin/includes/file.php');
                            include_once(ABSPATH . 'wp-admin/includes/misc.php');
                            include_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');

                            remove_all_actions('upgrader_process_complete');


                            $api = plugins_api('plugin_information', array(
                                'slug' => $plugin,
                                'fields' => array(
                                    'short_description' => false,
                                    'sections' => false,
                                    'requires' => false,
                                    'rating' => false,
                                    'ratings' => false,
                                    'downloaded' => false,
                                    'last_updated' => false,
                                    'added' => false,
                                    'tags' => false,
                                    'compatibility' => false,
                                    'homepage' => false,
                                    'donate_link' => false,
                                ),
                            ));

                            ob_start();
                            // Replace with new QuietSkin for no output
                            $upgrader = new Plugin_Upgrader(new QuietSkin(array('api' => $api)));
                            $upgrader->install($api->download_link);
                            ob_get_clean();
                        }

                        if (file_exists($pluginPath)) {
                            activate_plugin($pluginPath);
                            echo json_encode(array('success' => true));
                        } else {
                            echo json_encode(array('success' => false));
                        }
                    }

                }
                exit();
        }
    }
}
