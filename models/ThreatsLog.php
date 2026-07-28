<?php

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

HMWP_Classes_ObjController::getClass( 'HMWP_Models_Firewall_Threats' );

class HMWP_Models_ThreatsLog extends HMWP_Models_Firewall_Threats {

	/**
	 * Retrieves and caches threat statistics by day.
	 *
	 * This method fetches threat statistics for the specified retention period, either
	 * from the cache or by querying the data. The statistics are stored in a transient
	 * cache for optimized performance.
	 *
	 * @return array The threat statistics indexed by day.
	 */
	public function getThreatStatsByDay( $days = false ) {
		global $wpdb;

		// You override $days from option anyway, so keep it consistent
		if ( ! $days ) {
			$days = 7;
		}

		$cacheKey = 'hmwp_threats_' . $days;

		if ( $cached = get_transient( $cacheKey ) ) {
			return $cached;
		}

		/** @var string $table */
		$table = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Firewall_Database' )->tableName();

		// Midnight-aligned start in WP timezone — matches the thr_range filter in the threats log table.
		$start = (int) strtotime( wp_date( 'Y-m-d', strtotime( '-' . ( $days - 1 ) . ' days' ) ) );

		// Shift stamps by the WP timezone offset before DATE() so MySQL produces the same
		// Y-m-d strings that wp_date() produces in the loop below.
		$tz_offset = (int) round( (float) get_option( 'gmt_offset' ) * 3600 );

		$sql = "
	        SELECT
	            DATE(FROM_UNIXTIME(stamp + %d)) AS day,
	            COUNT(*) AS threats,
	            SUM(blocked = 1) AS blocked
	        FROM {$table}
	        WHERE stamp >= %d AND user_id = 0 AND is_bot = 0
	        GROUP BY day
	        ORDER BY day ASC
	    ";

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $tz_offset, $start ), ARRAY_A ); //phpcs:ignore

		// Index DB rows by date string (Y-m-d)
		$by_day = [];
		foreach ( (array) $rows as $r ) {
			$by_day[ $r['day'] ] = [
				'threats' => max( 0, ( (int) $r['threats'] - (int) $r['blocked'] ) ),
				'blocked' => (int) $r['blocked'],
			];
		}

		// Produce a complete series with zeros for missing days
		$out = [
			'date'    => [],
			'threats' => [],
			'blocked' => [],
		];

		for ( $i = 0; $i < $days; $i ++ ) {
			$d = wp_date( 'Y-m-d', strtotime( '+' . $i . ' day', $start ) );

			$out['date'][]    = $d;
			$out['threats'][] = $by_day[ $d ]['threats'] ?? 0;
			$out['blocked'][] = $by_day[ $d ]['blocked'] ?? 0;
		}


		set_transient( $cacheKey, $out, MINUTE_IN_SECONDS * 10 );

		return $out;
	}

	/**
	 * Retrieve threat counts grouped by country for the last N days.
	 * Uses the GeoIP database to resolve each distinct attacker IP to a country code.
	 * Results are cached for 10 minutes to avoid repeated GeoIP lookups.
	 *
	 * @param  int  $days  Number of days to look back (default 30).
	 *
	 * @return array  Associative array keyed by ISO-3166-1 alpha-2 country code:
	 *                [ 'CC' => [ 'threats' => int, 'blocked' => int ], … ]
	 *                Sorted descending by total (threats + blocked).
	 */
	public function getThreatStatsByCountry( $days = 30 ) {
		global $wpdb;

		$days     = max( 1, (int) $days );
		$cacheKey = 'hmwp_threats_country_' . $days;

		$cached = get_transient( $cacheKey );
		if ( false !== $cached ) {
			return $cached;
		}

		/** @var string $table */
		$table = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Firewall_Database' )->tableName();
		$start = (int) strtotime( wp_date( 'Y-m-d', strtotime( '-' . ( $days - 1 ) . ' days' ) ) );

		$stats = array();

		// Fast path: group by the stored country_code column. no PHP-level GeoIP loop needed
		$sql = "
			SELECT country_code, COUNT(log_id) AS total, SUM(blocked = 1) AS blocked
			FROM {$table}
			WHERE stamp >= %d AND user_id = 0 AND is_bot = 0 AND country_code IS NOT NULL AND country_code != '' AND country_code != '--'
			GROUP BY country_code
		";

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $start ), ARRAY_A ); // phpcs:ignore

		foreach ( (array) $rows as $row ) {
			$cc      = (string) $row['country_code'];
			$total   = (int) $row['total'];
			$blocked = (int) $row['blocked'];
			$passed  = max( 0, $total - $blocked );

			if ( ! isset( $stats[ $cc ] ) ) {
				$stats[ $cc ] = array( 'threats' => 0, 'blocked' => 0 );
			}
			$stats[ $cc ]['threats'] += $passed;
			$stats[ $cc ]['blocked'] += $blocked;
		}

		// Fallback: handle rows where country_code is still NULL (cron not yet run or GeoIP unavailable)
		$nullSql = "
			SELECT ip, COUNT(log_id) AS total, SUM(blocked = 1) AS blocked
			FROM {$table}
			WHERE stamp >= %d AND user_id = 0 AND is_bot = 0 AND (country_code IS NULL OR country_code = '')
			GROUP BY ip
			LIMIT 500
		";

		$nullRows = $wpdb->get_results( $wpdb->prepare( $nullSql, $start ), ARRAY_A ); // phpcs:ignore

		if ( ! empty( $nullRows ) ) {
			/** @var HMWP_Models_Geoip_GeoLocator $geo */
			$geo          = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Geoip_GeoLocator' )->getInstance();
			$countryCache = array();

			foreach ( $nullRows as $row ) {
				$ip = $row['ip'];

				if ( ! isset( $countryCache[ $ip ] ) ) {
					$countryCache[ $ip ] = $geo ? (string) $geo->getCountryCode( $ip ) : '';
				}

				$cc = $countryCache[ $ip ];
				if ( $cc === '' ) {
					continue;
				}

				// Persist the resolved country code so future calls (and stats) don't need GeoIP again.
				$wpdb->update( $table, array( 'country_code' => $cc ), array( 'ip' => $ip ), array( '%s' ), array( '%s' ) ); // phpcs:ignore

				$total   = (int) $row['total'];
				$blocked = (int) $row['blocked'];
				$passed  = max( 0, $total - $blocked );

				if ( ! isset( $stats[ $cc ] ) ) {
					$stats[ $cc ] = array( 'threats' => 0, 'blocked' => 0 );
				}
				$stats[ $cc ]['threats'] += $passed;
				$stats[ $cc ]['blocked'] += $blocked;
			}
		}

		if ( empty( $stats ) ) {
			set_transient( $cacheKey, array(), HOUR_IN_SECONDS );
			return array();
		}

		uasort( $stats, static function ( $a, $b ) {
			return ( $b['threats'] + $b['blocked'] ) <=> ( $a['threats'] + $a['blocked'] );
		} );

		set_transient( $cacheKey, $stats, MINUTE_IN_SECONDS * 10 );

		return $stats;
	}

	/**
	 * Generate a payload for the threat map visualization, containing threat and block statistics per country.
	 * The payload includes mapping data, country names, and associated threat stats for visual representation.
	 *
	 * @param int $days Number of days to include in the threat map calculation (default 30).
	 *
	 * @return array Associative array containing:
	 *               - 'points': Array of map points, each with keys:
	 *                 - 'cc': Lowercase ISO-3166-1 alpha-2 country code.
	 *                 - 'name': Country name or code if unresolved.
	 *                 - 'total': Total number of threats and blocks for the country.
	 *                 - 'blocked': Boolean indicating if the country is blocked.
	 *               - 'maxCount': Maximum aggregated count among all countries (for scale).
	 *               - 'labels': Array of label translations for 'threats' and 'blocked'.
	 */
	public function getThreatMapPayload( $days = 30 ) {

		/** @var HMWP_Models_Geoip_GeoLocator $geo */
		$geo = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Geoip_GeoLocator' )->getInstance();

		$countryStats = $this->getThreatStatsByCountry( $days );
		$allCountries = is_object( $geo ) && method_exists( $geo, 'getCountryCodes' ) ? $geo->getCountryCodes() : array();

		$blockedCountries = array();

		$maxCount = 1;
		foreach ( $countryStats as $d ) {
			$t = (int) $d['threats'] + (int) $d['blocked'];
			if ( $t > $maxCount ) {
				$maxCount = $t;
			}
		}

		$mapPoints = array();

		foreach ( $countryStats as $cc => $data ) {
			$cc        = strtoupper( $cc );
			$total     = (int) $data['threats'] + (int) $data['blocked'];
			$isBlocked = in_array( $cc, $blockedCountries, true );

			$mapPoints[] = array(
				'cc'      => strtolower( $cc ),
				'name'    => isset( $allCountries[ $cc ] ) ? $allCountries[ $cc ] : $cc,
				'total'   => $total,
				'blocked' => $isBlocked,
			);
		}

		foreach ( $blockedCountries as $cc ) {
			$cc = strtoupper( $cc );

			if ( isset( $countryStats[ $cc ] ) ) {
				continue;
			}

			$mapPoints[] = array(
				'cc'      => strtolower( $cc ),
				'name'    => isset( $allCountries[ $cc ] ) ? $allCountries[ $cc ] : $cc,
				'total'   => 0,
				'blocked' => true,
			);
		}

		return array(
			'points'         => $mapPoints,
			'maxCount'       => max( 1, (int) $maxCount ),
			'threatsLogUrl'  => HMWP_Classes_Tools::getSettingsUrl( 'hmwp_log&thr_range=7&tab=threats', true ),
			'labels'         => array(
				'threats' => __( 'threats', 'hide-my-wp' ),
				'blocked' => __( 'Blocked', 'hide-my-wp' ),
			),
		);
	}

	/**
	 * Saves threat details to the database and prevents duplicate recording within a specified time period.
	 *
	 * This method retrieves request information, prepares the data in a specific format, and records it
	 * in the database for threat detection and analysis purposes. It uses caching mechanisms to avoid
	 * duplicating threats within an hour.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function save( $threat = array() ) {

		if ( ! isset( $threat['code'] ) ) {
			return;
		}

		/** @var HMWP_Models_Firewall_Threats $threats */
		$threats = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Firewall_Threats' );

		/** @var HMWP_Models_Firewall_Server $server */
		$server = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Firewall_Server' );

		$httpCode = 200;
		if ( function_exists( 'http_response_code' ) ) {
			$tmp = (int) http_response_code();
			if ( $tmp > 0 ) {
				$httpCode = $tmp;
			}
		}

		// Merge request details with threat details
		$details = array_merge( $threat, json_decode( $this->snapshotRequestDetails(), true ) );
		$details = wp_json_encode( $details );

		$data = array(
			'stamp'           => time(),
			'ip'              => $server->getIp(),
			'user_id'         => function_exists( 'get_current_user_id' ) ? (int) get_current_user_id() : 0,
			'blog_id'         => function_exists( 'get_current_blog_id' ) ? (int) get_current_blog_id() : 0,
			'uri'             => $this->capString( (string) $this->uri, 2000 ),
			'event'           => $this->getCategoryFromCode( $threat['code'] ),
			'request_fields'  => $this->snapshotRequestFields(),
			'request_details' => $details,
			'request_id'      => $threats->getRid(),
			'request_method'  => $this->capString( (string) $this->method, 8 ),
			'http_code'       => (int) $httpCode,
			'is_bot'          => $this->isBotRequest() ? 1 : 0,
			'blocked'         => apply_filters( 'hmwp_threat_prevented', false ),
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
			'%d', // blocked
		);

		/** @var HMWP_Models_Firewall_Database $database */
		$database = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Firewall_Database' );
		$database->insert( $data, $formats );
	}

	/**
	 * Map a rule/threat code (FW_ / THR_) to a human category for storage & UI.
	 *
	 * @param string $code
	 *
	 * @return string
	 */
	public function getCategoryFromCode( $code ) {
		$code = strtoupper( (string) $code );
		if ( $code === '' ) {
			return (string) apply_filters( 'hmwp_threats_category_from_code', 'Unknown', $code );
		}

		// Backward compatibility: FW1_ / FW2_ / FW3_ / FW4_ -> FW_
		$code = preg_replace( '/^FW[0-9]_/', 'FW_', $code );

		// Exact overrides (special cases)
		$exact = array(
			// XML-RPC is better understood separately than "prohibited URL"
			'THR_URI_XMLRPC_PROBE' => 'Request to XML-RPC API denied',
		);
		if ( isset( $exact[ $code ] ) ) {
			return (string) apply_filters( 'hmwp_threats_category_from_code', $exact[ $code ], $code );
		}

		// Prefix-based categorization (fast)
		$prefixMap = array(
			'THR_QS_SQLI_' => 'SQL Injection Attacks',
			'THR_QS_XSS_'  => 'Cross-Site Scripting (XSS)',
			'THR_QS_FI_'   => 'File Inclusion Exploits',
			'THR_QS_MAL_'  => 'Malware Injection',

			'THR_URI_PROHIBITED_'       => 'Attempt to access prohibited URL',
			'BF_LOGIN_INVALID_USERNAME' => 'Attempt login non-existing username',
			'BF_LOGIN_EXCEEDED'         => 'Attempt login brute force',
			'BF_ATTEMPTS_EXCEEDED'      => 'Attempt threats brute force',
		);

		foreach ( $prefixMap as $prefix => $category ) {
			if ( strpos( $code, $prefix ) === 0 ) {
				return (string) apply_filters( 'hmwp_threats_category_from_code', $category, $code );
			}
		}

		// Any other THR_ URI indicators default to probing (non-existing PHP, exposed suffixes, odd markers, etc.)
		if ( strpos( $code, 'THR_URI_' ) === 0 ) {
			return (string) apply_filters( 'hmwp_threats_category_from_code', 'Probing for vulnerable code', $code );
		}

		// Detector codes
		if ( strpos( $code, 'DET_' ) === 0 ) {
			return (string) apply_filters( 'hmwp_threats_category_from_code', 'Probing for vulnerable code', $code );
		}

		// Firewall (FW_) categorization: use code semantics (no heavy parsing)
		if ( strpos( $code, 'FW_' ) === 0 ) {

			// XML-RPC
			if ( strpos( $code, 'XMLRPC' ) !== false ) {
				return (string) apply_filters( 'hmwp_threats_category_from_code', 'Request to XML-RPC API denied', $code );
			}

			// SQLi
			if ( preg_match( '/_(SQL|SQLI|UNION|SELECT|ORDER_BY|OUTFILE|LOAD_FILE|SP_EXECUTESQL|TAUTOLOGY|1EQ1)\b/', $code ) ) {
				return (string) apply_filters( 'hmwp_threats_category_from_code', 'SQL Injection Attacks', $code );
			}

			// XSS
			if ( preg_match( '/_(XSS|SCRIPT|IFRAME|OBJECT|EMBED|JS_PROTOCOL|ONLOAD|ONERROR|DOCUMENT_COOKIE|ANGLE)/', $code ) ) {
				return (string) apply_filters( 'hmwp_threats_category_from_code', 'Cross-Site Scripting (XSS)', $code );
			}

			// Directory traversal / file disclosure
			if ( preg_match( '/_(TRAVERSAL|DOTDOT|DOTSLASH|PASSWD|WIN_INI|MOTD|SELF_ENVIRON)\b/', $code ) ) {
				return (string) apply_filters( 'hmwp_threats_category_from_code', 'Directory Traversal Attacks', $code );
			}

			// File inclusion / stream wrappers / remote include patterns
			if ( preg_match( '/_(FI_|RFI_|ABS_PATH|CPATH|PHP_STREAM|DATA_SCHEME|PHAR|FILE_STREAM|FTP|SFTP|ALLOW_URL_|OPEN_BASEDIR|AUTO_PREPEND)/', $code ) ) {
				return (string) apply_filters( 'hmwp_threats_category_from_code', 'File Inclusion Exploits', $code );
			}

			// Script injection / command injection / header injection
			if ( preg_match( '/_(CMD|EVAL|ASSERT|SYSTEM|PASSTHRU|SHELL_EXEC|FUNC_CALLS|HEADER|SET_COOKIE|CRLF|INJECTION)\b/', $code ) ) {
				return (string) apply_filters( 'hmwp_threats_category_from_code', 'Script Injection Attacks', $code );
			}

			// Malware / obfuscation / webshell indicators
			if ( preg_match( '/_(MAL|MALWARE|BASE64|GZINFLATE|ROT13|WEBSHELL|SHELL|C99|R57)\b/', $code ) ) {
				return (string) apply_filters( 'hmwp_threats_category_from_code', 'Malware Injection', $code );
			}

			// Known exploit/probe families (phpunit, file managers, timthumb, etc.)
			if ( preg_match( '/_(PHPUNIT|CKFINDER|KCFINDER|CKEDITOR|FILEMANAGER|TIMTHUMB|THUMB|ADMINER)\b/', $code ) ) {
				return (string) apply_filters( 'hmwp_threats_category_from_code', 'Vulnerability Exploit', $code );
			}

			// User-Agent / Referrer based blocks: group under 8G Firewall Protection
			if ( strpos( $code, '_UA_' ) !== false || strpos( $code, '_REF_' ) !== false ) {
				return (string) apply_filters( 'hmwp_threats_category_from_code', 'Firewall Protection', $code );
			}

			// Default for any FW_ match
			return (string) apply_filters( 'hmwp_threats_category_from_code', 'Firewall Protection', $code );
		}

		return (string) apply_filters( 'hmwp_threats_category_from_code', 'Unknown', $code );
	}

	/**
	 * Batch-resolve missing country codes for rows that were inserted before the
	 * country_code column existed (or while the cron had not yet run).
	 *
	 * Processes up to 200 distinct IPs per cron run to keep the cron fast.
	 *
	 * @return void
	 */
	public function resolveCountryCodes() {
		global $wpdb;

		/** @var HMWP_Models_Geoip_GeoLocator $geo */
		$geo = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Geoip_GeoLocator' )->getInstance();

		if ( ! $geo ) {
			return;
		}

		$table = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Firewall_Database' )->tableName();

		// Fetch up to 200 distinct IPs that still have no country code
		$ips = $wpdb->get_col( "SELECT DISTINCT ip FROM {$table} WHERE country_code IS NULL OR country_code = '' LIMIT 200" ); // phpcs:ignore

		if ( empty( $ips ) ) {
			return;
		}

		foreach ( $ips as $ip ) {
			$cc = $geo->getCountryCode( $ip );
			if ( $cc === '' || $cc === false ) {
				// Mark as unresolvable so we don't keep retrying
				$cc = '--';
			}

			$wpdb->update( // phpcs:ignore
				$table,
				array( 'country_code' => $cc ),
				array( 'ip' => $ip ),
				array( '%s' ),
				array( '%s' )
			);
		}
	}

	public function purgeOldThreatLogs() {
		global $wpdb;

		$days = 7;
		$cutoff = time() - ( $days * DAY_IN_SECONDS );

		/** @var String $table Get the database table name */
		$table = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Firewall_Database' )->tableName();

		$batchSize = (int) apply_filters( 'hmwp_threats_log_purge_batch', 5000 );
		if ( $batchSize < 500 ) {
			$batchSize = 500;
		}

		$maxSeconds = (int) apply_filters( 'hmwp_threats_log_purge_max_seconds', 10 );
		if ( $maxSeconds < 3 ) {
			$maxSeconds = 3;
		}

		$start = microtime( true );
		$total = 0;

		do {
			// LIMIT reduces lock time on big tables
			$sql     = "DELETE FROM {$table} WHERE stamp < %d LIMIT {$batchSize}";
			$deleted = (int) $wpdb->query( $wpdb->prepare( $sql, $cutoff ) ); //phpcs:ignore

			$total += $deleted;

			if ( $deleted <= 0 ) {
				break;
			}

		} while ( ( microtime( true ) - $start ) < $maxSeconds );

		// Invalidate all cached aggregates
		delete_transient( 'hmwp_threat_stats_7' );
		delete_transient( 'hmwp_threat_stats_14' );
		delete_transient( 'hmwp_threat_stats_30' );
		delete_transient( 'hmwp_threats_country_7' );
		delete_transient( 'hmwp_threats_country_14' );
		delete_transient( 'hmwp_threats_country_30' );

		return $total;
	}
}
