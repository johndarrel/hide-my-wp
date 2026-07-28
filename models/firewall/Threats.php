<?php
defined( 'ABSPATH' ) || die( 'Cheating uh?' );

/**
 * Class HMWP_Models_Firewall_Threats
 *
 * This class is responsible for managing and detecting potential threats
 * from HTTP requests, such as SQL injection attacks, unauthorized access to
 * prohibited URLs, brute force login attempts, and probing activities.
 */
class HMWP_Models_Firewall_Threats {

	/** @var string Requested URI */
	protected $uri;
	/** @var string User agent string */
	protected $ua;
	/** @var array Request referrer */
	protected $ref;
	/** @var string HTTP protocol version */
	protected $proto;
	/** @var string HTTP method used for the request */
	protected $method;
	/** @var array Request parameters */
	protected $params;
	/** @var string Request ID */
	protected $rid;

	public function __construct() {

		$this->uri    = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; //phpcs:ignore

		// If multisite, exclude the subpath
		if ( $this->uri  <> '' && HMWP_Classes_Tools::isMultisiteWithPath() && ! is_main_site() ){
			$subsite_path = wp_parse_url( get_home_url(), PHP_URL_PATH );
			$this->uri = str_replace( $subsite_path, '', $this->uri );
		}

		$this->method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : 'GET'; //phpcs:ignore
		$this->ua     = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : ''; //phpcs:ignore
		$this->ref    = isset( $_SERVER['HTTP_REFERER'] ) ? (string) $_SERVER['HTTP_REFERER'] : ''; //phpcs:ignore
		$this->proto  = isset( $_SERVER['SERVER_PROTOCOL'] ) ? (string) wp_unslash( $_SERVER['SERVER_PROTOCOL'] ) : ''; //phpcs:ignore
		$this->params = json_decode( $this->snapshotRequestFields(), true ); //phpcs:ignore
	}

	/**
	 * Generate a unique Request ID for this request.
	 *
	 * @return string
	 */
	public function getRid() {
		if ( empty( $this->rid ) ) {
			$this->rid = md5( $this->uri . $this->method . join( ',', $this->params ) );
		}

		return $this->rid;
	}


	/**
	 * Detects potential threats in a given URI, HTTP method, and parameters.
	 *
	 * This method analyzes the specified URI, HTTP method, and parameters to determine
	 * if they contain prohibited URLs, payload-based threats, or any suspicious indicators.
	 * If a match is found, the threat is stored for further handling.
	 *
	 * @param string|false $uri The URI to inspect. If false, uses the currently set URI.
	 * @param string|false $method The HTTP method to inspect. If false, uses the currently set method.
	 * @param array $params The request parameters to analyze. If empty, uses the currently set parameters.
	 *
	 * @return false|array True if a threat is detected, false otherwise.
	 */
	public function detectThreat( $uri = false, $method = false, $params = array() ) {

		//If threat check process is deactivated, return false
		if ( ! apply_filters( 'hmwp_process_threats', true ) ) {
			return false;
		}

		// Set the URI if not set
		if ( ! $uri ) {
			$uri = $this->uri;
		}

		// Set the method if not set
		if ( ! $method ) {
			$method = $this->method;
		}

		// Set the params if not set
		if ( empty( $params ) ) {
			$params = $this->params;
		}

		if ( $uri === '' ) {
			return false;
		}

		$path = wp_parse_url( $uri, PHP_URL_PATH );
		$path = is_string( $path ) ? $path : $uri;

		// If multisite, exclude the subpath
		if ( $path <> '' && HMWP_Classes_Tools::isMultisiteWithPath() && ! is_main_site() ){
			$subsite_path = wp_parse_url( get_home_url(), PHP_URL_PATH );
			$path = str_replace($subsite_path,'', $path);
		}

		// Probes and prohibited paths (must run BEFORE fast-exit)
		$prohibited = $this->matchProhibitedUrl( $path );
		if ( $prohibited ) {
			return $prohibited;
		}
		// Fast exit for non-probe, non-blocked, non-payload requests
		if ( empty( $params ) && ! $this->hasThreatIndicators( $uri ) ) {
			return false;
		}

		// Payload-based attacks
		$payload = $this->buildThreatHaystack( $uri, $method, $params );
		$match   = $this->matchThreatPatterns( $payload );
		if ( $match ) {
			return $match;
		}

		return false;

	}

	/**
	 * Checks if the provided URI contains any indicators of potential threats.
	 *
	 * The method performs a string-based search (no regex) to identify specific patterns
	 * or keywords commonly associated with malicious activity.
	 *
	 * @param string $uri The URI to be checked for threat indicators.
	 *
	 * @return bool Returns true if the URI contains any threat indicators, false otherwise.
	 */
	protected function hasThreatIndicators( $uri ) {
		$uri = (string) $uri;

		$needles = array(
			// traversal / encoding
			'../','..\\','%2e%2e','%2f','%5c','%252e','%c0%af',

			// obvious injection punctuation or html/js
			'<','>','%3c','%3e','"','%22','\'','%27',';','%3b','`','%60','|','%7c',

			// common exploit keywords (keep specific)
			'php://','data://','phar://','file://','expect://',
			'base64','gzinflate','union%20select','union+select','sleep(','benchmark(',

			// IMPORTANT: enable “URI-only” probes to be evaluated
			'.php',          // catches /random/index.php etc. (rare for legit frontend traffic)
			'@',             // catches /me@domain.com probes
			'://',           // catches /https://domain.com/... and weird double-scheme payloads
		);

		foreach ( $needles as $n ) {
			if ( stripos( $uri, $n ) !== false ) {
				return true;
			}
		}

		// Domain-in-path probes: /example.com, /foo.net, etc.
		$tlds = array('.com', '.net', '.org', '.info', '.biz', '.io', '.co', '.app', '.dev', '.me', '.ru', '.cn', '.ro', '.uk', '.de', '.fr', '.es', '.it', '.nl');
		foreach ( $tlds as $tld ) {
			if ( stripos( $uri, $tld ) !== false ) {
				return true;
			}
		}

		return false;
	}


	/**
	 * Checks if the given path matches any prohibited URL patterns.
	 *
	 * This method is used to identify unauthorized access attempts to sensitive
	 * files or potentially harmful probing activities based on predefined rules.
	 *
	 * @param string $path The URL path to be evaluated.
	 *
	 * @return array|false Returns an associative array containing details of the matched rule if a prohibited pattern is found; otherwise, returns false.
	 */
	/**
	 * Prohibited URL / probe detection.
	 *
	 * Returns rule-style arrays:
	 * array('code' => 'THR_...', 'area' => 'URI', 'pattern' => '...')
	 *
	 * @param string $path Requested path (typically $_SERVER['REQUEST_URI'] without host)
	 *
	 * @return array|false
	 */
	protected function matchProhibitedUrl( $path ) {

		$path  = (string) $path;
		$lower = strtolower( $path );

		// 1) Hard targets / sensitive paths
		$prohibitedNeedles = array(
			'/.env'                        => 'THR_URI_PROHIBITED_ENV',
			'/.git'                        => 'THR_URI_PROHIBITED_GIT',
			'/composer.json'               => 'THR_URI_PROHIBITED_COMPOSER_JSON',
			'/composer.lock'               => 'THR_URI_PROHIBITED_COMPOSER_LOCK',
			'/readme.html'                 => 'THR_URI_PROHIBITED_README',
			'/license.txt'                 => 'THR_URI_PROHIBITED_LICENSE',
			'/hidemywp.conf'               => 'THR_URI_PROHIBITED_HMWP_CONF',
		);

		foreach ( $prohibitedNeedles as $needle => $code ) {
			if ( $needle !== '' && strpos( $lower, $needle ) !== false ) {
				return array(
					'code'    => (string) $code,
					'area'    => 'uri',
					'pattern' => (string) $needle,
				);
			}
		}

		// 2) Backup / dump / exposure suffixes
		$badSuffix = $this->endsWithAny( $lower, array(
			// DB dumps / backups
			'.sql',
			'.sql.gz',
			'.sql.zip',
			'.sql.bz2',
			'.dump',
			'.dmp',

			// common backup file suffixes
			'.bak',
			'.backup',
			'.old',
			'.orig',
			'.save',

			// editor / temp swap files
			'.swp',
			'.swo',
			'.tmp',

			// logs often exposed by mistake
			'.log',

			// config/source exposure (rarely legitimate public downloads)
			'.ini',
			'.yaml',
			'.yml',
			'.env',
		) );

		if ( $badSuffix ) {
			return array(
				'code'    => 'THR_URI_EXPOSED_FILE_SUFFIX',
				'area'    => 'uri',
				'pattern' => (string) $badSuffix,
			);
		}

		// 3) Suspicious PHP probes (non-existing PHP)
		if ( substr( $lower, - 4 ) === '.php' ) {

			// Allow known public WP endpoints (cheap allowlist)
			$allowed = $this->getAllowedFiles();
			if ( ! empty( $allowed ) ) {
				foreach ( (array) $allowed as $a ) {
					$a = strtolower( (string) $a );
					if ( $a !== '' && substr_compare( $lower, $a, - strlen( $a ), strlen( $a ) ) === 0 ) {
						return false;
					}
				}
			}



			// If the requested PHP exists on disk, do NOT treat it as a probe
			if ( $this->requestedPhpFileExists( $path ) ) {
				return false;
			}

			// Non-existing PHP requested => classic probe
			return array(
				'code'    => 'THR_URI_PHP_NONEXISTENT',
				'area'    => 'uri',
				'pattern' => '.php_nonexistent',
			);
		}

		return false;
	}


	/**
	 * Checks if a given string ends with any of the specified suffixes.
	 *
	 * This method examines whether the provided string ends with at least one of the
	 * given suffixes. If a match is found, the matching suffix is returned. If no
	 * matches are found, the method returns false. Empty suffixes are ignored during
	 * the evaluation.
	 *
	 * @param string $str The input string to check.
	 * @param string[]|string $suffixes A single suffix or an array of suffixes to check against.
	 *
	 * @return string|false The matching suffix if found, or false if none match.
	 */
	protected function endsWithAny( $str, $suffixes ) {
		$str    = (string) $str;
		$strLen = strlen( $str );

		foreach ( (array) $suffixes as $s ) {
			$s = (string) $s;
			if ( $s === '' ) {
				continue;
			}

			$len = strlen( $s );
			if ( $len === 0 || $len > $strLen ) {
				continue;
			}

			// Compare suffix without allocating substrings
			if ( substr_compare( $str, $s, - $len, $len ) === 0 ) {
				return $s;
			}
		}

		return false;
	}

	/**
	 * Checks if a requested PHP file exists in the server's file system.
	 *
	 * This method validates and processes a given path to ensure that it conforms to
	 * specific security constraints, such as avoiding directory traversal and null-byte injections.
	 * It verifies the existence of the file by searching within known root directories.
	 *
	 * @param string $path The path to the requested file. It may include query strings or encoded characters.
	 *                     The method will sanitize and process this value.
	 *
	 * @return bool True if the PHP file exists within the specified server roots; false otherwise.
	 */
	protected function requestedPhpFileExists( $path ) {

		$path = (string) $path;
		if ( $path === '' ) {
			return false;
		}

		// Remove query string if present
		$qpos = strpos( $path, '?' );
		if ( $qpos !== false ) {
			$path = substr( $path, 0, $qpos );
		}

		// Decode once (avoid warnings on malformed)
		$path = rawurldecode( $path );

		// Normalize slashes and ensure leading slash
		$path = '/' . ltrim( str_replace( '\\', '/', $path ), '/' );

		// If traversal is present, don't trust filesystem resolution here
		// (you will catch it via Directory Traversal checks separately)
		if ( strpos( $path, '..' ) !== false || strpos( $path, "\0" ) !== false ) {
			return false;
		}

		$roots = array();

		if ( ! empty( $_SERVER['DOCUMENT_ROOT'] ) ) {
			$roots[] = rtrim( (string) wp_unslash( $_SERVER['DOCUMENT_ROOT'] ), '/\\' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}

		if ( defined( 'ABSPATH' ) ) {
			$roots[] = rtrim( (string) ABSPATH, '/\\' );
		}

		foreach ( $roots as $root ) {
			$full = $root . $path;

			// Avoid expensive realpath(); is_file() is enough for your purpose.
			if ( @is_file( $full ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				return true;
			}
		}

		return false;
	}


	/**
	 * Determines if the given path corresponds to an allowed PHP endpoint.
	 *
	 * @return array List of the allowed PHP endpoints.
	 */
	public function getAllowedFiles() {

		return array(
			'/wp-login.php',
			'/wp-cron.php',
			'/wp-mail.php',
			'/wp-comments-post.php',
			'/wp-signup.php',
			'/wp-activate.php',
			'/wp-trackback.php',
			'/wp-admin/admin-ajax.php',
			'/wp-admin/install.php',
			'/wp-admin/upgrade.php',
			'/wp-admin/update.php',
		);

	}

	/**
	 * Constructs a threat detection haystack string based on the provided URI,
	 * HTTP method, and request parameters. This is used for analyzing and
	 * processing potential threats within HTTP requests.
	 *
	 * @param string $uri The requested URI.
	 * @param string $method The HTTP method used ('get' or 'post').
	 * @param array $params The array of request parameters.
	 *
	 * @return string A concatenated string representing the processed URI, GET, or POST data to be used for threat analysis.
	 */
	protected function buildThreatHaystack( $uri, $method, $params ) {
		$parts = array();

		$parts[] = 'uri=' . rawurldecode( (string) $uri );

		if ( $method == 'get' && ! empty( $params ) ) {
			$parts[] = 'get=' . $this->capString( rawurldecode( http_build_query( $params ) ), 4000 );
		}

		if ( $method == 'post' && ! empty( $params ) ) {
			// Cap POST payload aggressively for performance and privacy
			$parts[] = 'post=' . $this->capString( rawurldecode( http_build_query( $this->redactPostArray( $params ) ) ), 4000 );
		}

		return implode( '&', $parts );
	}

	/**
	 * Matches the provided input string against a set of predefined threat patterns.
	 * The method checks the input against multiple regex patterns classified into categories
	 * and also performs an additional direct check for directory traversal sequences.
	 *
	 * @param string $haystack The input string to be analyzed for potential threats.
	 *
	 * @return array|false An array containing threat details if a match is found, or false if no match is detected.
	 */
	protected function matchThreatPatterns( $haystack, $area = 'request' ) {

		$haystack = (string) $haystack;
		if ( $haystack === '' ) {
			return false;
		}

		$patterns = $this->getThreatPatterns();
		foreach ( $patterns as $regex => $code ) {
			if ( preg_match( $regex, $haystack ) ) {
				return array(
					'code'     => (string) $code,
					'area'     => (string) $area,
					'pattern'  => (string) $regex,
				);
			}
		}

		$traversalRegex = '#(\.\./|%2e%2e%2f|%252e%252e%252f)#i';
		if ( preg_match( $traversalRegex, $haystack ) ) {
			return array(
				'code'     => 'THR_' . strtoupper( (string) $area ) . '_TRAVERSAL_ENCODED',
				'area'     => (string) $area,
				'pattern'  => $traversalRegex,
			);
		}

		return false;
	}

	/**
	 * Retrieves a predefined set of threat patterns categorized by attack type.
	 * The patterns include regular expressions for detecting SQL injection, XSS,
	 * file inclusion, script injection, malware injection, vulnerability exploits,
	 * and default WordPress path exploits. The result can be extended or modified
	 * through the 'hmwp_threat_patterns' filter.
	 *
	 * @return array An associative array where keys are attack categories, and values there are arrays mapping regex patterns to descriptive labels.
	 */
	protected function getThreatPatterns() {

		$patterns = array(
			'#\binformation_schema\b#i'         => 'THR_QS_SQLI_INFORMATION_SCHEMA',
			'#\bsleep\s*\(#i'                   => 'THR_QS_SQLI_SLEEP_FUNC',
			'#\binto\s+outfile\b#i'             => 'THR_QS_SQLI_INTO_OUTFILE',
			'#(\bor\b|\band\b)\s+1\s*=\s*1\b#i' => 'THR_QS_SQLI_TAUTOLOGY_1EQ1',
			//--
			'#\bonerror\s*=\s*#i' => 'THR_QS_XSS_ONERROR',
			'#\bonload\s*=\s*#i'  => 'THR_QS_XSS_ONLOAD',
			'#document\.cookie#i' => 'THR_QS_XSS_DOCUMENT_COOKIE',
			//--
			'#\bphp\s*://#i'    => 'THR_QS_FI_PHP_STREAM',
			'#\bdata\s*://#i'   => 'THR_QS_FI_DATA_STREAM',
			'#\bexpect\s*://#i' => 'THR_QS_FI_EXPECT_STREAM',
			'#\bphar\s*://#i'   => 'THR_QS_FI_PHAR_STREAM',
			'#\bfile\s*://#i'   => 'THR_QS_FI_FILE_STREAM',
			//--
			'#\bgzinflate\s*\(#i' => 'THR_QS_MAL_GZINFLATE',
			'#\bstr_rot13\s*\(#i' => 'THR_QS_MAL_STR_ROT13',
			'#/(?:phpmyadmin|pma|myadmin|mysqladmin)(?:/|$)#' => 'THR_URI_PMA_PROBE',

			// URI probes
			'#uri=/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}(?:[/?\#]|$)#i' => 'THR_URI_EMAIL_PATH',
			'#uri=/(?:[A-Z0-9](?:[A-Z0-9\-]{0,61}[A-Z0-9])?\.)+(?:com|net|org|info|biz|co|io|app|dev|me|ru|cn|ro|uk|de|fr|es|it|nl)(?:[/?\#]|$)#i' => 'THR_URI_DOMAIN_PATH',
			'#uri=/(?:https?|ftp):/{1,2}[A-Z0-9.\-]+(?:[/?\#]|$)#i'=> 'THR_URI_SCHEME_IN_PATH',

		);

		return (array) apply_filters( 'hmwp_threats_threat_patterns', $patterns );
	}


	/**
	 * Redacts sensitive data from an associative array, typically representing input data such as `$_POST`.
	 * This method replaces specific sensitive keys with a redacted value and truncates other values as needed.
	 *
	 * @param array $posts The associative array to be processed and sanitized.
	 *
	 * @return array The sanitized and redacted array, preserving up to 30 entries.
	 */
	protected function redactPostArray( $posts ) {
		if ( ! is_array( $posts ) ) {
			return array();
		}

		$redact = array( 'pwd', 'pass', 'password', 'token', 'nonce', '_wpnonce', 'authorization', 'auth' );

		$out = array();
		$max = 30;

		foreach ( $posts as $k => $v ) {
			if ( count( $out ) >= $max ) {
				break;
			}

			$key = sanitize_key( (string) $k );

			if ( in_array( $key, $redact, true ) ) {
				$out[ $key ] = '[redacted]';
				continue;
			}

			if ( is_array( $v ) ) {
				$out[ $key ] = '[array]';
				continue;
			}

			$out[ $key ] = $this->capString( sanitize_text_field( (string) $v ), 300 );
		}

		return $out;
	}

	/**
	 * Truncates a string to a specified maximum length. If the string exceeds the
	 * maximum length, it is truncated to the limit; otherwise, it is returned unchanged.
	 *
	 * @param string $str The input string to be truncated.
	 * @param int $max The maximum allowed length for the string.
	 *
	 * @return string The truncated string, or the original string if its length is within the limit.
	 */
	public function capString( $str, $max ) {
		$str = (string) $str;

		return ( strlen( $str ) <= $max ) ? $str : substr( $str, 0, $max );
	}

	/**
	 * Determines if the current request originates from a bot based on the User-Agent header.
	 *
	 * This method analyzes the HTTP_USER_AGENT string to identify patterns commonly associated
	 * with bots, crawlers, spiders, and other automated processes.
	 *
	 * @return bool True if the request is likely from a bot, false otherwise.
	 */
	protected function isBotRequest() {

		/** @var HMWP_Models_Firewall_Bots $bots */
		$bots = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Firewall_Bots' );

		return $bots->isSearchEngineBot();

	}

	/**
	 * Captures and processes request fields for potential threat logging.
	 *
	 * This method retrieves and sanitizes GET and POST request fields, encodes them as a JSON string,
	 * and truncates the resulting string to a predefined maximum length.
	 *
	 * @return string A truncated JSON-encoded string of sanitized GET and POST request fields.
	 */
	protected function snapshotRequestFields() {
		$gets  = isset( $_GET ) ? wp_unslash( $_GET ) : array(); //phpcs:ignore
		$posts = isset( $_POST ) ? wp_unslash( $_POST ) : array(); //phpcs:ignore

		// Keep it small; only on threat requests
		$json = wp_json_encode( array_merge(
			$this->sanitizeKeyValueArray( $gets ),
			$this->sanitizeKeyValueArray( $posts )
		) );

		return $this->capString( (string) $json, 20000 );
	}

	/**
	 * Captures and encodes request details into a JSON string with length constraints.
	 *
	 * This method collects several pieces of information from the server request environment,
	 * such as user agent, referrer, host, and protocol. Each value is processed to ensure
	 * its length is capped to a specified maximum. The processed details are then converted
	 * into a JSON string, which is also limited to a specific maximum length.
	 *
	 * @return string The JSON-encoded string of capped request details.
	 */
	protected function snapshotRequestDetails() {
		$details = array(
			'ua'      => $this->capString( $this->ua, 1000 ),
			'referer' => $this->capString( $this->ref, 2000 ),
			'proto'   => $this->capString( $this->proto, 20 ),
		);

		$json = wp_json_encode( $details );

		return (string) $json;
	}

	/**
	 * Sanitizes an array of key-value pairs by cleansing and capping keys and values.
	 *
	 * This method takes an input array and processes each key-value pair:
	 * - Keys are sanitized to ensure they are safe for internal use.
	 * - Values are sanitized and capped to a specified length.
	 * - Sensitive keys, such as those involving authentication or passwords, are redacted.
	 * - Array values are replaced with a placeholder string.
	 * The output is limited to a maximum number of processed entries.
	 *
	 * @param array $arr The input array of key-value pairs to sanitize.
	 *
	 * @return array The sanitized key-value array with sensitive information redacted.
	 */
	protected function sanitizeKeyValueArray( $arr ) {
		if ( ! is_array( $arr ) ) {
			return array();
		}

		$redact = array( 'pwd', 'pass', 'password', 'token', 'nonce', '_wpnonce', 'auth', 'authorization' );

		$out = array();
		$max = 50;

		foreach ( $arr as $k => $v ) {
			if ( count( $out ) >= $max ) {
				break;
			}

			$key = sanitize_key( (string) $k );

			if ( in_array( $key, $redact, true ) ) {
				$out[ $key ] = '[hidden]';
				continue;
			}

			if ( is_array( $v ) ) {
				$out[ $key ] = '[array]';
				continue;
			}

			$out[ $key ] = $this->capString( sanitize_text_field( (string) $v ), 500 );
		}

		return $out;
	}


}
