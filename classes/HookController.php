<?php
/**
 * The class handles the actions in WP
 *
 * @file The Hook Class file
 * @package HMWP/Hooks
 * @since 4.0.0
 */

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Classes_HookController {

	/**
	 * @var array An array to hold hooks for admin actions
	 */
	private $admin_hooks;
	/**
	 * @var array An array to hold hooks for frontend actions
	 */
	private $front_hooks;

	public function __construct() {
		// Called in admin context
		$this->admin_hooks = array(
			'init'          => 'init', // WP init action
			'menu'          => 'admin_menu', // WP admin menu action
			'head'          => 'admin_head', // WP admin head action
			'multisiteMenu' => 'network_admin_menu', // WP network admin menu action
			'footer'        => 'admin_footer', // WP admin footer action
		);

		// Called in frontend context
		$this->front_hooks = array(
			// --
			'frontinit' => 'init', // WP frontend init action
			'load'      => 'plugins_loaded', // WP plugins_loaded action
		);

	}

	/**
	 * Calls the specified action in WP
	 *
	 * @param  object  $instance  The parent class instance
	 *
	 * @return void
	 */
	public function setHooks( $instance ) {
		if ( is_admin() || is_network_admin() ) {
			// Set hooks for admin context
			$this->setAdminHooks( $instance );
		} else {
			// Set hooks for frontend context
			$this->setFrontHooks( $instance );
		}
	}

	/**
	 * Calls the specified action in WP for admin
	 *
	 * @param  object  $instance  The parent class instance
	 *
	 * @return void
	 */
	public function setAdminHooks( $instance ) {
		// For each admin action, check if it is defined in the class and call it
		foreach ( $this->admin_hooks as $hook => $value ) {

			if ( is_callable( array( $instance, 'hook' . ucfirst( $hook ) ) ) ) {
				// Call the WP add_action function
				add_action( $value, array( $instance, 'hook' . ucfirst( $hook ) ) );
			}
		}
	}

	/**
	 * Calls the specified action in WP for frontend
	 *
	 * @param  object  $instance  The parent class instance
	 *
	 * @return void
	 */
	public function setFrontHooks( $instance ) {
		// For each frontend action, check if it is defined in the class and call it
		foreach ( $this->front_hooks as $hook => $value ) {
			if ( is_callable( array( $instance, 'hook' . ucfirst( $hook ) ) ) ) {
				// Call the WP add_action function with priority 11111
				add_action( $value, array( $instance, 'hook' . ucfirst( $hook ) ), 11111 );
			}
		}
	}

	/**
	 * Calls the specified action in WP
	 *
	 * @param  string  $action  The action to set
	 * @param  HMWP_Classes_FrontController  $obj  The object that contains the callback
	 * @param  array  $callback  Contains the class name or object and the callback function
	 *
	 * @return void
	 */
	public function setAction( $action, $obj, $callback ) {

		// Call the custom action function from WP with priority 10
		add_action( $action, array( $obj, $callback ), 10 );
	}

}
