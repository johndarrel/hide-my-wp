<?php
/**
 * Logging Class
 * Called on Events Log
 *
 * @file The Events Log file
 * @package HMWP/Events
 * @since 4.0.0
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Controllers_Log extends HMWP_Classes_FrontController
{

    public function __construct()
    {
        parent::__construct();
        //Hook the login process
        add_filter('authenticate', array( $this, 'hmwp_authenticate' ), 99, 1);
        apply_filters('woocommerce_process_login_errors', array( $this, 'hmwp_authenticate' ), 99, 1);

        //Hook all actions
        add_action('wp_loaded', array( $this, 'hmwp_log' ), 9);
    }

    /**
     * Admin actions
     */
    public function action()
    {
        parent::action();

        if (HMWP_Classes_Tools::getValue('action') == 'hmwp_logsettings') {
            HMWP_Classes_Tools::saveOptions('hmwp_activity_log', HMWP_Classes_Tools::getValue('hmwp_activity_log', 0));
            HMWP_Classes_Tools::saveOptions('hmwp_activity_log_roles', HMWP_Classes_Tools::getValue('hmwp_activity_log_roles', array()));

            //Clear the cache if there are no errors
            if (!HMWP_Classes_Tools::getOption('error')) {

                if (!HMWP_Classes_Tools::getOption('logout')) {
                    HMWP_Classes_Tools::saveOptionsBackup();
                }

                HMWP_Classes_Tools::emptyCache();
                HMWP_Classes_Error::setError(esc_html__('Saved'), 'success');
            }
        }
    }

    /**
     * Function called on login process
     *
     * @param null $user
     *
     * @return null
     */
    public function hmwp_authenticate( $user = null )
    {
        if (empty($_POST) ) {
            return $user;
        }

        //set default action name
        $action = 'login';

        if (is_wp_error($user) ) {
            if (method_exists($user, 'get_error_codes') ) {
                $codes = $user->get_error_codes();
                if (! empty($codes) ) {
                    foreach ( $codes as $action ) {
                        //Log the authenticate process
                        $this->model->hmwp_log_actions($action);//log the login process
                    }
                }
            }

            return $user;
        }

        //Log the success authenticate process
        $this->model->hmwp_log_actions($action);//log the login process

        return $user;
    }

    /**
     * Function called on user events
     */
    public function hmwp_log()
    {

        try {
            //Log user activity
            if (HMWP_Classes_Tools::getValue('action') ) {
                if (empty($_POST) && empty($_GET) ) {
                    return;
                }

                //Get user roles
                $current_user = wp_get_current_user();

                //If the user has roles
                if (isset($current_user->user_login) && is_array($current_user->roles) ) {

                    //If there is use role restriction
                    $user_roles   = $current_user->roles;
                    $option_roles = ( array ) HMWP_Classes_Tools::getOption('hmwp_activity_log_roles');

                    //In case the user roles are selected
                    if(!empty($option_roles) && ! empty($user_roles)) {
                        if (!array_intersect($user_roles, $option_roles) ) {
                            return;
                        }
                    }

                    $values = array(
                        'username' => $current_user->user_login,
                        'role'     => ( ! empty($user_roles) ? $user_roles[0] : '' ),
                    );

                    $this->model->hmwp_log_actions(HMWP_Classes_Tools::getValue('action'), $values);

                }
            }
        } catch ( Exception $e ) {
        }

    }

}
