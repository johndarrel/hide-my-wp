<?php
/**
 * The class handles the actions in WP
 *
 * @file The Hook Class file
 * @package HMWP/Hooks
 * @since 4.0.0
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Classes_HookController
{

    /**
     * 
     *
     * @var array the WP actions list from admin 
     */
    private $admin_hooks;
    private $front_hooks;

    public function __construct()
    {
        //called in admin
        $this->admin_hooks = array(
            'init' => 'init',
            'menu' => 'admin_menu',
            'head' => 'admin_head',
            'multisiteMenu' => 'network_admin_menu',
            'footer' => 'admin_footer',
        );

        //called in frontend
        $this->front_hooks = array(
            // --
            'frontinit' => 'init',
        );

    }

    /**
     * Calls the specified action in WP
     *
     * @param object $instance The parent class instance
     *
     * @return void
     */
    public function setHooks($instance)
    {
        if (is_admin() || is_network_admin()) {
            $this->setAdminHooks($instance);
        } else {
            $this->setFrontHooks($instance);
        }
    }

    /**
     * Calls the specified action in WP
     *
     * @param object $instance The parent class instance
     *
     * @return void
     */
    public function setAdminHooks($instance)
    {
        /* for each admin action check if is defined in class and call it */
        foreach ($this->admin_hooks as $hook => $value) {

            if (is_callable(array($instance, 'hook' . ucfirst($hook)))) {
                //call the WP add_action function
                add_action($value, array($instance, 'hook' . ucfirst($hook)));
            }
        }
    }

    /**
     * Calls the specified action in WP
     *
     * @param object $instance The parent class instance
     *
     * @return void
     */
    public function setFrontHooks($instance)
    {
        /* for each admin action check if is defined in class and call it */
        foreach ($this->front_hooks as $hook => $value) {
            if (is_callable(array($instance, 'hook' . ucfirst($hook)))) {
                //call the WP add_action function
                add_action($value, array($instance, 'hook' . ucfirst($hook)), 11111);
            }
        }
    }

    /**
     * Calls the specified action in WP
     *
     * @param string                       $action
     * @param HMWP_Classes_FrontController $obj
     * @param array                        $callback Contains the class name or object and the callback function
     *
     * @return void
     */
    public function setAction($action, $obj, $callback)
    {

        /* calls the custom action function from WP */
        add_action($action, array($obj, $callback), 10);
    }

}
