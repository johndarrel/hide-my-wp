<?php
/**
 * Widget Class
 * Called on WP Dashboard
 *
 * @file The Widget file
 * @package HMWP/Widget
 * @since 6.0.0
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Controllers_Widget extends HMWP_Classes_FrontController
{

    /**
     * Array of security tasks from Security Check
     * @var array
     */
    public $riskreport = array();
    public $risktasks;
    public $stats = false;

    /**
     * Called when dashboard is loaded
     *
     * @throws Exception
     */
    public function dashboard()
    {
        //Get the stats
        $args = $urls = array();
        //If it's multisite
        if(is_multisite()) {
            if (function_exists('get_sites') && class_exists('WP_Site_Query') ) {
                $sites = get_sites();
                if(!empty($sites)) {
                    foreach ($sites as $site) {
                        $urls[] = (_HMWP_CHECK_SSL_ ? 'https://' : 'http://') . rtrim($site->domain . $site->path, '/');
                    }
                }
            }
        }else{
            $urls[] = home_url();
        }
        //pack the urls
        $args['urls'] = json_encode(array_unique($urls));

        //call the stats
        $stats = HMWP_Classes_Tools::hmwp_remote_get(_HMWP_API_SITE_ . '/api/log/stats', $args);

        if ($stats = json_decode($stats, true)) {
            if(isset($stats['data'])) {
                $this->stats = $stats['data'];
            }
        }

        $this->risktasks = HMWP_Classes_ObjController::getClass('HMWP_Controllers_SecurityCheck')->getRiskTasks();
        $this->riskreport = HMWP_Classes_ObjController::getClass('HMWP_Controllers_SecurityCheck')->getRiskReport();

        $this->show('Dashboard');
    }

    /**
     * Called when an action is triggered
     *
     * @throws Exception
     */
    public function action()
    {
        parent::action();

        if (!HMWP_Classes_Tools::userCan('hmwp_manage_settings')) {
            return;
        }

        if (HMWP_Classes_Tools::getValue('action') == 'hmwp_widget_securitycheck') {
            HMWP_Classes_ObjController::getClass('HMWP_Controllers_SecurityCheck')->doSecurityCheck();

            //Get the stats
            $args = $urls = array();
            //If it's multisite
            if (is_multisite()) {
                if (function_exists('get_sites') && class_exists('WP_Site_Query')) {
                    $sites = get_sites();
                    if (!empty($sites)) {
                        foreach ($sites as $site) {
                            $urls[] = (_HMWP_CHECK_SSL_ ? 'https://' : 'http://') . rtrim($site->domain . $site->path, '/');
                        }
                    }
                }
            } else {
                $urls[] = home_url();
            }
            //pack the urls
            $args['urls'] = json_encode(array_unique($urls));
            //call the stats
            $stats = HMWP_Classes_Tools::hmwp_remote_get(_HMWP_API_SITE_ . '/api/log/stats', $args);

            if ($stats = json_decode($stats, true)) {
                if (isset($stats['data'])) {
                    $this->stats = $stats['data'];
                }
            }

            $this->risktasks = HMWP_Classes_ObjController::getClass('HMWP_Controllers_SecurityCheck')->getRiskTasks();
            $this->riskreport = HMWP_Classes_ObjController::getClass('HMWP_Controllers_SecurityCheck')->getRiskReport();

            wp_send_json_success($this->getView('Dashboard'));

        }
    }
}
