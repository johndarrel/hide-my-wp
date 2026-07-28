<?php
/**
 * Compatibility Class
 *
 * @file The Wordfence Model file
 * @package HMWP/Compatibility/Wordfence
 * @since 7.0.0
 */

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Models_Compatibility_Wordfence extends HMWP_Models_Compatibility_Abstract {

    /** @var array Wordfence config cache */
	public static $config = array();

	public function __construct() {
		parent::__construct();

        // Disable path blocking during Wordfence scan
		add_filter( 'hmwp_process_init', array( $this, 'checkWordfenceScan' ) );

        // Adding actions for various Wordfence menu items in the admin dashboard
        add_action( 'init', function() {
			if ( is_admin() ) {

				//Add the Wordfence menu when the wp-admin path is changed
				if ( is_multisite() ) {
					if ( class_exists( 'wfUtils' ) && ! wfUtils::isAdminPageMU() ) {
						add_action( 'network_admin_menu', 'wordfence::admin_menus', 10 );
						add_action( 'network_admin_menu', 'wordfence::admin_menus_20', 20 );
						add_action( 'network_admin_menu', 'wordfence::admin_menus_30', 30 );
						add_action( 'network_admin_menu', 'wordfence::admin_menus_40', 40 );
						add_action( 'network_admin_menu', 'wordfence::admin_menus_50', 50 );
						add_action( 'network_admin_menu', 'wordfence::admin_menus_60', 60 );
						add_action( 'network_admin_menu', 'wordfence::admin_menus_70', 70 );
						add_action( 'network_admin_menu', 'wordfence::admin_menus_80', 80 );
						add_action( 'network_admin_menu', 'wordfence::admin_menus_90', 90 );
					} //else don't show the menu
				}
			}
		} );

        // Checking if brute force protection with captcha is enabled
        if ( HMWP_Classes_Tools::getOption( 'hmwp_bruteforce' ) && HMWP_Classes_Tools::getOption( 'brute_use_captcha_v3' ) ) {
            // Adding compatibility to not load brute force when 2FA is active
            if ( $this->wfIs2FA() ) {
				//Add compatibility with Wordfence to not load the Bruteforce when 2FA is active
				add_filter( 'hmwp_option_brute_use_captcha_v3', '__return_false' );
			}
		}

        // Adding actions for handling Wordfence scan
		add_action( 'wf_scan_monitor', array( $this, 'witelistWordfence' ) );
		add_action( 'wordfence_start_scheduled_scan', array( $this, 'witelistWordfence' ) );
		add_action( 'wordfence_scan_completed', array( $this, 'clearWordfenceWhitelist' ) );

		//Add local IPs in whitelist
		add_filter( 'hmwp_rules_whitelisted_ips', function ( $ips ) {
			/** @var HMWP_Models_Firewall_Rules $firewallRules */
			$firewallRules = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Firewall_Rules' );
			return array_merge( $firewallRules->getLocalIPs(), $ips );
		});
	}

    /**
     * Retrieves the configuration value for a given key from the wfconfig table.
     *
     * @param  string  $key  The key for the configuration value to retrieve.
     *
     * @return mixed The configuration value if found, otherwise false.
     */
	public function wfConfig( $key ) {
        // Make $wpdb instance available
        global $wpdb;

        // Check if the configuration for the given key already exists in self::$config
        if ( isset( self::$config[ $key ] ) ) {
			return self::$config[ $key ];
            // Return the stored configuration value
		}

        // Define the table name by concatenating the base prefix and 'wfconfig'
        $table = $wpdb->base_prefix . 'wfconfig';
        // Check if the wfconfig table exists in the current database
		if ( $wpdb->get_col( $wpdb->prepare( 'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s', $table ) ) ) { //phpcs:ignore
			// Query the table for the row that matches the given key
			if ( $option = $wpdb->get_row( $wpdb->prepare( 'SELECT name, val, autoload FROM `' . esc_sql( $table ) . '` WHERE name = %s', $key ) ) ) { //phpcs:ignore
                // Check if the value exists in the result
                if ( isset( $option->val ) ) {
                    // Store the value in self::$config for future use
                    self::$config[ $key ] = $option->val;

                    // Return the value
					return $option->val;
				}
			}
		}

        // If the value is not found, return false
        return false;
	}

    /**
     * Checks whether the 2FA (Two-Factor Authentication) table exists and has at least one entry.
     *
     * @return bool Returns true if the 2FA table exists and contains at least one row, otherwise false.
     */
	public function wfIs2FA() {
        global $wpdb;
        $table = $wpdb->base_prefix . 'wfls_2fa_secrets';

        // Check if the 2FA secrets table exists
		//phpcs:ignore
		$tableExists = $wpdb->get_col(
			$wpdb->prepare(
				'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_NAME = %s',
				$table
			)
		);

        if ($tableExists) {
            // Check if there is any record in the 2FA secrets table
	        //phpcs:ignore
	        $recordExists = $wpdb->get_row(
		        $wpdb->prepare(
			        'SELECT id FROM `' . esc_sql( $table ) . '` LIMIT %d',
			        1
		        )
	        );

            if ($recordExists) {
                return true;
            }
        }

        return false;
	}

	/**
	 * Check and handle Wordfence scan status.
	 *
	 * Wordfence signs the fork URL using admin_url('admin-ajax.php'). The URL returned
	 * depends on WP Ghost's doChangePaths() logic:
	 *
	 * - hmwp_hide_loggedusers = FALSE: generation (admin AJAX, logged-in) returns the REAL
	 *   admin-ajax URL; fork verification (unauthenticated AJAX) would return the CUSTOM URL
	 *   → mismatch. Fix: disable the admin_url filter so verification also uses the real URL.
	 *
	 * - hmwp_hide_loggedusers = TRUE: generation (admin AJAX) already returns the CUSTOM URL;
	 *   fork verification also returns the CUSTOM URL → they match naturally. No fix needed.
	 *   Disabling the filter here would BREAK the match.
	 *
	 * @param  bool  $status  The current hmwp_process_init filter value.
	 *
	 * @return bool
	 */
	public function checkWordfenceScan( $status ) {

		// When a scan is in progress (transient set by a prior request), disable path hiding
		// only when hmwp_hide_loggedusers is false — otherwise the custom URL is used
		// consistently on both sides and disabling it would create a mismatch.
		if ( get_transient( 'hmwp_disable_hide_urls' ) ) {
			return $status;
		}

		if ( HMWP_Classes_Tools::isCron() || HMWP_Classes_Tools::isAjax() ) {

			$action = HMWP_Classes_Tools::getValue( 'action' );
			if ( 'wordfence_scan' === $action || 'wordfence_doScan' === $action || $this->isRunning() ) {
				$this->witelistWordfence();
				return false;
			}
		}

		return $status;
	}

	/**
	 * Temporarily disable URL hiding while Wordfence scans.
	 * Transient is refreshed by wf_scan_monitor during the scan.
	 *
	 * @return void
	 */
	public function witelistWordfence() {
		set_transient( 'hmwp_disable_hide_urls', 1, HOUR_IN_SECONDS );
	}

	/**
	 * Re-enable URL hiding immediately when the scan completes.
	 *
	 * @return void
	 */
	public function clearWordfenceWhitelist() {
		delete_transient( 'hmwp_disable_hide_urls' );
		self::$config = array();
	}

	/**
	 * Check if a Wordfence scan is currently running.
	 * Uses Wordfence's native wfConfig API when available to avoid raw DB queries.
	 *
	 * @return bool
	 */
	public function isRunning() {
		if ( class_exists( 'wfConfig' ) ) {
			return wfConfig::get( 'wf_scanRunning' ) || wfConfig::get( 'scanStartAttempt' );
		}

		return $this->wfConfig( 'wf_scanRunning' ) || $this->wfConfig( 'scanStartAttempt' );
	}


}
