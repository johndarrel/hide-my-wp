<?php
/**
 * Events Log Model
 * Called to hook and log the users Events
 *
 * @file  The Events file
 * @package HMWP/EventsModel
 * @since 6.0.0
 */

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

HMWP_Classes_ObjController::getClass( 'HMWP_Models_Firewall_Threats' );

class HMWP_Models_EventsLog extends HMWP_Models_Firewall_Threats {

	/**
	 * @var string[] List of allowed keys
	 */
	public $allow_keys = array(
		'username',
		'role',
		'log',
		'ip',
		'referer',
		'post',
		'post_id',
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
		'mode',
	);

	/**
	 * @var string[] List of allowed actions
	 */
	public $allow_actions = array(
		// users / auth
		'empty_username',
		'invalid_username',
		'incorrect_password',
		'invalid_email',
		'authentication_failed',
		'login',
		'logout',
		'update',
		'block_ip',
		'createuser',

		// user lifecycle / permissions
		'user_register',
		'delete_user',
		'remove_user',
		'promote',
		'profile_update',
		'set-password',
		'resetpass',
		'retrievepassword',
		'lostpassword',
		'application-passwords',

		// posts / pages / content
		'trash',
		'untrash',
		'edit',
		'publish',
		'private',
		'draft',
		'pending',
		'delete-post',
		'upload-attachment',
		'delete-attachment',

		// comments
		'dim-comment',
		'replyto-comment',
		'approve-comment',
		'unapprove-comment',
		'spam-comment',
		'unspam-comment',
		'trash-comment',
		'untrash-comment',
		'delete-comment',
		'edit-comment',

		// plugins
		'activate',
		'deactivate',
		'delete',
		'delete-plugin',
		'install-plugin',
		'update-plugin',
		'dodelete',

		// file edit
		'edit-theme-plugin-file',

		// themes
		'install-theme',
		'update-theme',
		'delete-theme',
		'switch-theme',
		'customize_save',

		// core updates
		'update-core',
		'upgrade-core',

		// settings / site configuration
		'options-update',
		'permalink-update',
		'reading-update',
		'general-update',
		'discussion-update',
		'privacy-update',

		// widgets
		'save-widget',
	);


	/**
	 * Save the given data based on a specific action, filtering and processing input values before logging them.
	 * Saves locally in hmwp_logs (same DB class as Threats) and then sends to Cloud.
	 *
	 * Note: Local Events rows are stored only for logged-in users (user_id > 0),
	 * so Events and Threats can be separated by user_id as requested.
	 *
	 * @param string $action
	 * @param array $values
	 *
	 * @return void
	 * @throws Exception
	 */
	public function save( $action, $values = array(), $user_id = 0 ) {

		$allow_actions = (array) apply_filters( 'hmwp_allow_actions', $this->allow_actions );
		$allow_keys    = (array) apply_filters( 'hmwp_allow_keys', $this->allow_keys );

		if ( ! in_array( $action, $allow_actions, true ) ) {
			return;
		}

		$allow_keys = array_flip( $allow_keys );

		$posts = array();

		if ( ! empty( $values ) ) {
			$values = array_intersect_key( (array) $values, $allow_keys );
		}

		if ( ! empty( $_GET ) ) { //phpcs:ignore WordPress.Security.NonceVerification
			$posts = array_intersect_key( (array) $_GET, $allow_keys ); //phpcs:ignore
		}

		if ( ! empty( $_POST ) ) { //phpcs:ignore WordPress.Security.NonceVerification
			$posts = array_merge( $posts, array_intersect_key( (array) $_POST, $allow_keys ) ); //phpcs:ignore
		}

		if ( ! function_exists( 'wp_get_current_user' ) ) {
			include_once ABSPATH . WPINC . '/pluggable.php';
		}

		if ( ! $user_id ){
			$user_id = function_exists( 'get_current_user_id' ) ? (int) get_current_user_id() : 0;
		}

		// Try to get the name and the type for the current record
		$post_id = 0;
		foreach ( array( 'id', 'post', 'post_ID', 'post_id' ) as $k ) {
			if ( isset( $posts[ $k ] ) && (int) $posts[ $k ] > 0 ) {
				$post_id = (int) $posts[ $k ];
				break;
			}
		}

		// Populate username/role if missing
		if ( $user_id > 0 ) {

			if ( function_exists( 'get_current_user_id' ) ){
				$current_user = wp_get_current_user();

				if ( ! empty( $current_user->user_login ) ) {
					$posts['username'] = $current_user->user_login;
				}
				if ( ! empty( $current_user->roles ) ) {
					$posts['role'] = current( $current_user->roles );
				}
			}

		} else {
			// Try to get the user ID from the username
			$username = '';
			if ( isset( $posts['log'] ) ) {
				$username = sanitize_user( (string) $posts['log'], true );
			} elseif ( isset( $posts['username'] ) ) {
				$username = sanitize_user( (string) $posts['username'], true );
			}

			if ( $username !== '' && function_exists( 'username_exists' ) ) {
				if ( $user_id = username_exists( $username ) ) {
					$posts['username'] = $username;
				}
			}

		}

		// Add post-context when possible
		if ( $post_id > 0 && function_exists( 'get_post' ) ) {
			$record = get_post( $post_id );
			if ( $record ) {
				$posts['name']      = $record->post_name;
				$posts['post_type'] = $record->post_type;
			}
		}

		/////////////////////////////////////////////////////
		// Add referer and IP

		/** @var HMWP_Models_Firewall_Server $server */
		$server = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Firewall_Server' );

		$referer = wp_get_raw_referer();
		if ( ! $referer && isset( $_SERVER['REQUEST_URI'] ) ) {
			$referer = (string) wp_unslash( $_SERVER['REQUEST_URI'] ); //phpcs:ignore
		}

		if ( $referer <> '' ) {
			$referer = wp_parse_url( $referer, PHP_URL_PATH );
		}

		$data = array(
			'referer' => $referer,
			'ip'      => $server->getIp(),
		);

		// Merge all the data
		$data = array_merge( $data, (array) $values, (array) $posts );

		// Sanitize/cap values to avoid massive payloads
		$data = $this->sanitizeLogData( $data );

		/////////////////////////////////////////////////////
		if ( $user_id > 0 ) {

			/** @var HMWP_Models_Firewall_Database $database */
			$database = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Firewall_Database' );
			$database->maybeCreateTable();

			$blog_id = function_exists( 'get_current_blog_id' ) ? (int) get_current_blog_id() : 0;

			$httpCode = 200;
			if ( function_exists( 'http_response_code' ) ) {
				$tmp = (int) http_response_code();
				if ( $tmp > 0 ) {
					$httpCode = $tmp;
				}
			}

			$method = isset( $_SERVER['REQUEST_METHOD'] ) ? (string) $_SERVER['REQUEST_METHOD'] : 'GET'; //phpcs:ignore
			$method = $this->capString( $method, 8 );

			$uri = $referer ?: ( ( isset( $_SERVER['REQUEST_URI'] ) ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '' ); //phpcs:ignore

			/** @var HMWP_Models_Firewall_Threats $threats */
			$threats = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Firewall_Threats' );

			$row = array(
				'stamp'           => time(),
				'ip'              => (string) $data['ip'],
				'user_id'         => $user_id,
				'blog_id'         => $blog_id,
				'uri'             => $this->capString( (string) $uri, 2000 ),
				'event'           => (string) $action,
				'request_fields'  => serialize( $data ),
				'request_details' => $this->snapshotRequestDetails(),
				'request_id'      => $threats->getRid(),
				'request_method'  => $method,
				'http_code'       => (int) $httpCode,
				'is_bot'          => 0,
			);

			$formats = array(
				'%d', // stamp
				'%s', // ip
				'%d', // user_id
				'%d', // blog_id
				'%s', // uri
				'%s', // event
				'%s', // request_fields
				'%s', // request_details
				'%s', // request_id
				'%s', // request_method
				'%d', // http_code
				'%d', // is_bot
			);

			$database->insert( $row, $formats );
		}

	}


	/**
	 * Sanitizes and caps log data for safe storage and sending.
	 *
	 * @param array $data
	 * @return array
	 */
	protected function sanitizeLogData( $data ) {
		if ( ! is_array( $data ) ) {
			return array();
		}

		$out = array();

		foreach ( $data as $k => $v ) {

			$key = is_string( $k ) ? $this->capString( $k, 60 ) : (string) $k;

			if ( is_array( $v ) ) {
				$tmp = array();
				foreach ( $v as $kk => $vv ) {
					$kk = is_string( $kk ) ? $this->capString( $kk, 60 ) : (string) $kk;

					if ( is_scalar( $vv ) ) {
						$tmp[ $kk ] = $this->capString( (string) $vv, 600 );
					}
				}
				$out[ $key ] = $tmp;
				continue;
			}

			if ( is_scalar( $v ) ) {
				$out[ $key ] = $this->capString( (string) $v, 600 );
			}
		}

		// Hard cap to avoid enormous serialized payloads
		if ( strlen( serialize( $out ) ) > 8000 ) {
			$out = array(
				'notice' => 'Event payload was trimmed due to size',
				'ip'     => isset( $out['ip'] ) ? $out['ip'] : '',
				'referer'=> isset( $out['referer'] ) ? $out['referer'] : '',
			);
		}

		return $out;
	}
}
