<?php
/**
 * Events Log Model
 * Called to hook and log the users Events
 *
 * @file  The Events file
 * @package HMWP/EventsModel
 * @since 6.0.0
 */

defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

class HMWP_Models_Log {

	//List of allowed logged keys
	public $allow_keys = array(
		'username',
		'role',
		'log',
		'ip',
		'referer',
		'post',
		'post_id',
		'post_ID',
		'doaction',
		'id',
		'ids',
		'user_id',
		'user',
		'users',
		'product_id',
		'post_type',
		'plugin',
		'new',
		'name',
		'slug',
		'stylesheet',
		'customize_theme',
		'widget-id',
		'delete_widget',
		'menu-name',
	);

	//List of allowed logged actions
	public $allow_actions = array( //users
		'empty_username',
		'invalid_username',
		'incorrect_password',
		'invalid_email',
		'authentication_failed',
		'update',
		'login',
		'logout',
		'block_ip',
		'createuser', //posts
		'trash',
		'untrash',
		'edit',
		'inline-save',
		'delete-post',
		'upload-attachment',
		'activate',
		'deactivate', //comments
		'dim-comment',
		'replyto-comment', //plugins
		'delete',
		'delete-plugin',
		'install-plugin',
		'update-plugin',
		'dodelete', //file edit
		'edit-theme-plugin-file', //theme
		'customize_save', //widgets
		'save-widget',
	);

	/**
	 * Save the log to Cloud
	 *
	 * @param  mixed  $action
	 * @param  array  $values
	 *
	 * @throws Exception
	 */
	public function save( $action = null, $values = array() ) {
		$posts = array();

		if ( isset( $action ) && $action <> '' ) {

			//remove unwanted actions
			$allow_actions = apply_filters( 'hmwp_allow_actions', $this->allow_actions );
			$allow_keys = apply_filters( 'hmwp_allow_keys', $this->allow_keys );

			if ( in_array( $action, $allow_actions ) ) {

				$allow_keys = array_flip( $allow_keys );

				if ( ! empty( $values ) ) {
					$values = array_intersect_key( $values, $allow_keys );
				}
				if ( ! empty( $_GET ) ) {
					$posts = array_intersect_key( $_GET, $allow_keys );
				}
				if ( ! empty( $_POST ) ) {
					$posts = array_intersect_key( $_POST, $allow_keys );
				}

				//Try to get the name and the type for the current record
				$post_id = 0;
				if ( isset( $posts['id'] ) ) {
					$post_id = $posts['id'];
				}
				if ( isset( $posts['post'] ) ) {
					$post_id = $posts['post'];
				}
				if ( isset( $posts['post_ID'] ) ) {
					$post_id = $posts['post_ID'];
				}
				if ( isset( $posts['post_id'] ) ) {
					$post_id = $posts['post_id'];
				}

				if ( ! isset( $posts['username'] ) || $posts['username'] == '' ) {

					if ( ! function_exists( 'wp_get_current_user' ) ) {
						include_once ABSPATH . WPINC . '/pluggable.php';
					}

					// Get current user if logged in
					$current_user = wp_get_current_user();

					if ( ! empty( $current_user->user_login ) ) {
						$posts['username'] = $current_user->user_login;
					}
					if ( ! empty( $current_user->roles ) ) {
						$posts['role'] = current( $current_user->roles );
					}
				}

				if ( $post_id > 0 ) {
					if ( function_exists( 'get_post' ) ) {
						if ( $record = @get_post( $post_id ) ) {
							$posts['name'] = $record->post_name;
							$posts['post_type'] = $record->post_type;
						}
					}
				}

				/////////////////////////////////////////////////////
				/// Add referer and IP
				/** @var HMWP_Models_Bruteforce_IpAddress $bruteForceIp */
				$bruteForceIp = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Bruteforce_IpAddress' );

				// Populate data path and IP
				$data = array( 'referer' => wp_get_raw_referer(), 'ip' => $bruteForceIp->getIp() );

				// Get the current request if referer is not set
				if ( isset( $_SERVER['REQUEST_URI'] ) && ! $data['referer'] ) {
					$data['referer'] = $_SERVER['REQUEST_URI'];
				}

				// Merge all the data
				$data = array_merge( $data, (array) $values, $posts );

				// Log the block IP on the server
				$args = array( 'action' => $action, 'data' => serialize( $data ), );

				HMWP_Classes_Tools::hmwp_remote_post( _HMWP_ACCOUNT_SITE_ . '/api/log', $args, array( 'timeout' => 5 ) );
			}
		}
	}

	/**
	 * Log the known event
	 *
	 * @param $action
	 * @param $values
	 *
	 * @return void
	 * @throws Exception
	 * @deprecated since version 5.4
	 */
	public function hmwp_log_actions( $action = null, $values = array() ) {
		$this->save( $action, $values );
	}
}
