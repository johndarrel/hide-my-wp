<?php
defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Models_Firewall_Database {

	const DB_VERSION = '1.1.0';
	const TRANSIENT_TABLE_READY = 'hmwp_threats_table_ready';

	/**
	 * Constructs and returns the full table name for storing traffic data.
	 * The method determines the appropriate table prefix based on whether the site is part of a multisite network
	 * and appends the traffic table identifier to it.
	 *
	 * @return string The fully constructed table name.
	 */
	public function tableName() {
		global $wpdb;

		$prefix = is_multisite() ? $wpdb->base_prefix : $wpdb->prefix;

		return $prefix . 'hmwp_logs';
	}

	/**
	 * Inserts a new row into the database table.
	 *
	 * This method inserts data into the specified table using the provided associative
	 * array and format. The data array keys correspond to table column names, and the format
	 * array specifies the data types for each value.
	 *
	 * @param array $data An associative array of column-value pairs to insert into the table.
	 * @param array $format An array of value formats, such as '%d' for integers or '%s' for strings.
	 *                      The array structure should match the $data array.
	 *
	 * @return void
	 */
	public function insert( $data, $format ) {
		global $wpdb;

		$wpdb->insert( $this->tableName(), $data, $format ); //phpcs:ignore
	}

	/**
	 * Attempts to ensure the target database table exists.
	 * If the table is already present, a transient is set to skip further checks temporarily.
	 * Otherwise, the method attempts to create the table and rechecks its existence.
	 *
	 * @return void
	 */
	public function maybeCreateTable() {

		$storedVersion = HMWP_Classes_Tools::getOption( 'hmwp_threats_log_version' );

		// Check to avoid running schema checks on every request
		if ( $storedVersion === self::DB_VERSION && get_transient( self::TRANSIENT_TABLE_READY ) ) {
			return;
		}

		global $wpdb;
		$table = $this->tableName();

		// If table exists, check if a schema upgrade is needed
		//phpcs:ignore
		$exists = $wpdb->get_var(
			$wpdb->prepare( "SHOW TABLES LIKE %s", $table )
		);

		if ( $exists === $table ) {
			if ( $storedVersion !== self::DB_VERSION ) {
				// Run dbDelta to add any missing columns
				$this->createTable();
			}

			set_transient( self::TRANSIENT_TABLE_READY, 1, HOUR_IN_SECONDS );
			return;
		}

		$this->createTable();

		// Re-check quickly
		//phpcs:ignore
		$exists = $wpdb->get_var(
			$wpdb->prepare( "SHOW TABLES LIKE %s", $table )
		);

		if ( $exists === $table ) {
			set_transient( self::TRANSIENT_TABLE_READY, 1, HOUR_IN_SECONDS );
		}
	}

	/**
	 * Creates a database table with predefined columns and indexes.
	 *
	 * This method creates a new table in the WordPress database using the provided
	 * schema definition. The table includes performance-critical columns with primary key and
	 * indexes for optimized query performance. After creation, the database version is updated
	 * in the WordPress options to reflect the current structure.
	 *
	 * @return void
	 */
	public function createTable() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table           = $this->tableName();
		$charset_collate = $wpdb->get_charset_collate();

		/**
		 * Performance-critical: primary key + indexes.
		 * Keep columns aligned with your schema; added log_id + stamp for practical logging.
		 */
		$sql = "CREATE TABLE {$table} (
			log_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			stamp int(10) unsigned NOT NULL DEFAULT 0,
			ip varchar(39) NOT NULL,
			country_code varchar(2) DEFAULT NULL,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			blog_id int(10) unsigned NOT NULL DEFAULT 0,
			uri text NOT NULL,
			event varchar(96) DEFAULT NULL,
			request_fields mediumtext DEFAULT NULL,
			request_details mediumtext NOT NULL,
			request_id char(32) NOT NULL,
			request_method char(8) NOT NULL,
			http_code int(3) unsigned NOT NULL DEFAULT 0,
			is_bot int(1) unsigned NOT NULL DEFAULT 0,
			blocked int(1) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY  (log_id),
			KEY stamp (stamp),
			KEY ip (ip),
			KEY country_code (country_code),
			KEY user_id (user_id),
			KEY request_id (request_id),
			KEY blog_id (blog_id),
			KEY event (event),
			KEY http_code (http_code)
		) {$charset_collate};";

		dbDelta( $sql ); //phpcs:ignore

		HMWP_Classes_Tools::saveOptions('hmwp_threats_log_version', self::DB_VERSION);
	}

	/**
	 * Deletes the database table and cleans up associated metadata.
	 *
	 * This method removes the specified database table from the WordPress database.
	 * It also deletes the stored option tracking the database version and clears
	 * any transient indicating the readiness of the table.
	 *
	 * @return void
	 */
	public function deleteTable() {
		global $wpdb;

		$table = $this->tableName();

		// Direct query is acceptable here; table name is internal and not user input
		$wpdb->query( 'DROP TABLE IF EXISTS `' . esc_sql( $table ) . '`' ); //phpcs:ignore

		// Clear transients tracking
		delete_transient( self::TRANSIENT_TABLE_READY );
	}

}
