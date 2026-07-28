<?php
/**
 * Firewall Protection
 * Called when the Firewall Protection is activated
 *
 * @file  The Firewall file
 * @package HMWP/Firewall
 * @since 5.0
 */

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Models_Firewall_Rules {

	/** @var string Requested URI */
	protected $uri;
	/** @var string Request query string */
	protected $qs;
	/** @var string User agent string */
	protected $ua;
	/** @var array Request referrer */
	protected $ref;

	public function __construct() {

		$this->uri    = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; //phpcs:ignore

		// If multisite, exclude the subpath
		if ( $this->uri  <> '' && HMWP_Classes_Tools::isMultisiteWithPath() && ! is_main_site() ){
			$subsite_path = wp_parse_url( get_home_url(), PHP_URL_PATH );
			$this->uri = str_replace( $subsite_path, '', $this->uri );
		}

		$this->qs  = isset( $_SERVER['QUERY_STRING'] ) ? (string) $_SERVER['QUERY_STRING'] : ''; //phpcs:ignore
		$this->ua    =  isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : ''; //phpcs:ignore
		$this->ref    =  isset( $_SERVER['HTTP_REFERER'] ) ? (string) $_SERVER['HTTP_REFERER'] : ''; //phpcs:ignore
	}

	/**
	 * Detects WordPress theme and plugin detectors based on the HTTP_USER_AGENT string.
	 *
	 * This method checks if the user agent matches common patterns for popular WordPress detection services
	 * such as wpthemedetector, builtwith, isitwp, wappalyzer, and others. If blocking detectors is enabled
	 * in the settings, it returns true if a match is found.
	 *
	 * @return array|bool
	 */
	public function detectWPDetectors() {

		// If block detectors are activated
		if ( HMWP_Classes_Tools::getOption( 'hmwp_detectors_block' ) ) {
			if ( $this->ua !== '' ) {
				$hit = $this->matchRules( $this->ua, $this->getRulesTDUserAgent(), 'user_agent' );
				return $hit ?: false;
			}
		}

		return false;
	}

	/**
	 * Detects potential SQL injection and other malicious patterns in HTTP request data.
	 *
	 * This method implements different levels of protection against SQL injection attacks
	 * and other vulnerabilities by inspecting `QUERY_STRING` and `HTTP_USER_AGENT` inputs.
	 * It can block suspicious requests based on configured security levels.
	 *
	 * Level 1 - Minimal firewall: Basic checks for malicious patterns.
	 * Level 2 - Medium firewall (6G Firewall): More advanced rules for stricter security.
	 * Level 3 - Advanced firewall (7G Firewall): More comprehensive checks including defense against common payloads and exploits.
	 * Level 4 - Advanced firewall (8G Firewall): More comprehensive checks including defensexagainst common payloads and exploits.
	 *
	 * @return array|bool|string[] false if the request is secure or requires skipping firewall checks.
	 */
	public function detectRule( $level ) {

		// Cheap prefilter: if nothing to check, return early
		if ( $this->qs === '' && $this->uri === '' && $this->ua === '' && $this->ref === '' ) {
			return false;
		}

		// Level 1 (Minimal)
		if ( (int) $level === 1 ) {
			$hit = $this->matchRules( $this->qs, $this->getRulesL1Query(), 'query' );
			return $hit ?: false;
		}

		// Level 2 (6G)
		if ( (int) $level === 2 ) {
			if ( $this->ua !== '' ) {
				$hit = $this->matchRules( $this->ua, $this->getRulesL2UserAgent(), 'user_agent' );
				return $hit ?: false;
			}

			if ( $this->qs !== '' ) {
				$hit = $this->matchRules( $this->qs, $this->getRulesL2Query(), 'query' );
				if ( $hit ) {
					return $hit;
				}

				// Additional XSS/JS patterns unless specific plugins are active
				if (
					! HMWP_Classes_Tools::isPluginActive( 'backup-guard-gold/backup-guard-pro.php' ) &&
					! HMWP_Classes_Tools::isPluginActive( 'wp-reset/wp-reset.php' ) &&
					! HMWP_Classes_Tools::isPluginActive( 'wp-statistics/wp-statistics.php' )
				) {
					$hit = $this->matchRules( $this->qs, $this->getRulesL2QueryXss(), 'query' );
					if ( $hit ) {
						return $hit;
					}
				}
			}

			return false;
		}

		// Level 3 (7G)
		if ( (int) $level === 3 ) {
			if ( $this->ua !== '' ) {
				$hit = $this->matchRules( $this->ua, $this->getRulesL3UserAgent(), 'user_agent' );
				if ( $hit ) {
					return $hit;
				}
			}

			if ( $this->qs !== '' ) {
				$hit = $this->matchRules( $this->qs, $this->getRulesL3Query(), 'query' );
				if ( $hit ) {
					return $hit;
				}
			}

			if ( $this->uri !== '' ) {
				$hit = $this->matchRules( $this->uri, $this->getRulesL3Uri(), 'uri' );
				if ( $hit ) {
					return $hit;
				}
			}

			return false;
		}

		// Level 4 (8G)
		if ( (int) $level === 4 ) {

			if ( $this->uri !== '' ) {
				$hit = $this->matchRules( $this->uri, $this->getRulesL4Referer(), 'referer' );
				if ( $hit ) {
					return $hit;
				}
			}

			if ( $this->ua !== '' ) {
				$hit = $this->matchRules( $this->ua, $this->getRulesL4UserAgent(), 'user_agent' );
				if ( $hit ) {
					return $hit;
				}
			}

			if ( $this->qs !== '' ) {
				$hit = $this->matchRules( $this->qs, $this->getRulesL4Query(), 'query' );
				if ( $hit ) {
					return $hit;
				}
			}

			if ( $this->uri !== '' ) {
				$hit = $this->matchRules( $this->uri, $this->getRulesL4Uri(), 'uri' );
				if ( $hit ) {
					return $hit;
				}
			}

			return false;
		}

		return false;
	}

	/**
	 * Theme Detectors - USER_AGENT rules
	 */
	protected function getRulesTDUserAgent() {
		static $rules = null;
		if ( $rules !== null ) {
			return $rules;
		}

		$rules = array(
			'DET_UA_HOST'      => '/(wpthemedetector|builtwith|isitwp|wappalyzer|Wappalyzer|mShots|WhatCMS|gochyu|wpdetector|scanwp)/i',
		);

		return $rules;
	}

	/**
	 * Level 1 (Minimal) - QUERY_STRING rules
	 */
	protected function getRulesL1Query() {
		static $rules = null;
		if ( $rules !== null ) {
			return $rules;
		}

		$rules = array(
			'FW_QS_LONG_TOKEN'     => '/([a-z0-9]{2000,})/i',
			'FW_QS_OBJECT_TAG'     => '/(<|%3C).*object.*(>|%3E)/i',
			'FW_QS_OBF_OBJECT'     => '/(<|%3C)([^o]*o)+bject.*(>|%3E)/i',
			'FW_QS_IFRAME_TAG'     => '/(<|%3C).*iframe.*(>|%3E)/i',
			'FW_QS_OBF_IFRAME'     => '/(<|%3C)([^i]*i)+frame.*(>|%3E)/i',
			'FW_QS_ETC_PASSWD'     => '/(etc(\/|%2f)passwd|self(\/|%2f)environ)/i',
			'FW_QS_BASE64_ENCODE'  => '/base64_encode.*\(.*\)/i',
			'FW_QS_BASE64_CODEC'   => '/base64_(en|de)code[^(]*\([^)]*\)/i',
			'FW_QS_LOCALHOST'      => '/(localhost|loopback|127\.0\.0\.1)/i',
			'FW_QS_SQL_KEYWORDS_1'  => '/((\+|%2b)(concat|delete|get|select|union)(\+|%2b))/i',
			'FW_QS_EVAL_CONCAT'     => '/(concat|eval)(.*)(\(|%28)/i',
			'FW_QS_UNION_SELECT'    => '/union([^s]*s)+elect/i',
			'FW_QS_UNION_ALL_SEL'   => '/union([^a]*a)+ll([^s]*s)+elect/i',
		);

		return $rules;
	}


	/**
	 * Level 2 (6G) - USER_AGENT rules
	 */
	protected function getRulesL2UserAgent() {
		static $rules = null;
		if ( $rules !== null ) {
			return $rules;
		}

		$rules = array(
			'FW_UA_LONG_TOKEN'      => '/([a-z0-9]{2000,})/i',
			'FW_UA_BAD_CHARS'       => '/(&lt;|%0a|%0d|%27|%3c|%3e|%00|0x00)/i',
			'FW_UA_SHELL_TOKENS'     => '/(base64_decode|bin\/bash|disconnect|eval|lwp-download|unserialize)/i',
			'FW_UA_CRLF_NULL'        => '/(%0A|%0D|%3C|%3E|%00)/i',
			'FW_UA_SCANNER_STACK'    => '/(;|<|>|\'|"|\)|\(|%0A|%0D|%22|%28|%3C|%3E|%00).*(libwww-perl|wget|python|nikto|curl|scan|java|winhttp|HTTrack|clshttp|archiver|loader|email|harvest|extract|grab|miner)/i',
		);

		return $rules;
	}

	/**
	 * Level 2 (6G) - QUERY_STRING rules
	 */
	protected function getRulesL2Query() {
		static $rules = null;
		if ( $rules !== null ) {
			return $rules;
		}

		$rules = array(
			'FW_QS_TRAVERSAL_ASSIGN'  => '/[a-zA-Z0-9_]=(\.\.\/\/?)+/i',
			'FW_QS_ABS_PATH_ASSIGN'   => '/[a-zA-Z0-9_]=\/([a-z0-9_.]\/\/?)+/i',
			'FW_QS_PHPSESSID_FORMAT'  => '/=PHP[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i',
			'FW_QS_TRAVERSAL_ENC'     => '/(\.\.\/|%2e%2e%2f|%2e%2e\/|\.\.%2f|%2e\.%2f|%2e\.\/|\.%2e%2f|\.%2e\/)/i',
			'FW_QS_FTP_SCHEME'        => '/ftp:/i',
			'FW_QS_SELF_PATH'         => '/^(.*)\/self\/(.*)$/i',
			'FW_QS_CPATH_URL'         => '/^(.*)cPath=(http|https):\/\/(.*)$/i',
			'FW_QS_ETC_PASSWD'        => '/(etc(\/|%2f)passwd|self(\/|%2f)environ)/i',
			'FW_QS_BASE64_ENCODE'     => '/base64_encode.*\(.*\)/i',
			'FW_QS_BASE64_CODEC'      => '/base64_(en|de)code[^(]*\([^)]*\)/i',
			'FW_QS_LOCALHOST'         => '/(localhost|loopback|127\.0\.0\.1)/i',
			'FW_QS_GLOBALS'           => '/GLOBALS(=|\[|%[0-9A-Z]{0,2})/i',
			'FW_QS_REQUEST'           => '/_REQUEST(=|\[|%[0-9A-Z]{0,2})/i',
			'FW_QS_CTRL_BYTES'        => '/^.*(x00|x04|x08|x0d|x1b|x20|x3c|x3e|x7f).*/i',
			'FW_QS_NULL_OUTFILE'      => '/(NULL|OUTFILE|LOAD_FILE)/i',
			'FW_QS_DOTSLASH_MOTD'     => '/(\.{1,}\/)+(motd|etc|bin)/i',
			'FW_QS_SQL_KEYWORDS_1'    => '/((\+|%2b)(concat|delete|get|select|union)(\+|%2b))/i',
			'FW_QS_EVAL_CONCAT'       => '/(concat|eval)(.*)(\(|%28)/i',
			'FW_QS_PHP_INI_OVERRIDES' => '/-[sdcr].*(allow_url_include|allow_url_fopen|safe_mode|disable_functions|auto_prepend_file)/i',
			'FW_QS_SP_EXECUTESQL'     => '/sp_executesql/i',
		);

		return $rules;
	}

	/**
	 * Level 2 (6G) - QUERY_STRING XSS/JS rules (conditional by plugin active list)
	 */
	protected function getRulesL2QueryXss() {
		static $rules = null;
		if ( $rules !== null ) {
			return $rules;
		}

		$rules = array(
			'FW_QS_SCRIPT_TAG'     => '/(<|%3C).*script.*(>|%3E)/i',
			'FW_QS_OBF_SCRIPT'     => '/(<|%3C)([^s]*s)+cript.*(>|%3E)/i',
			'FW_QS_EMBED_TAG'      => '/(<|%3C).*embed.*(>|%3E)/i',
			'FW_QS_OBF_EMBED'      => '/(<|%3C)([^e]*e)+mbed.*(>|%3E)/i',
			'FW_QS_OBJECT_TAG'     => '/(<|%3C).*object.*(>|%3E)/i',
			'FW_QS_OBF_OBJECT'     => '/(<|%3C)([^o]*o)+bject.*(>|%3E)/i',
			'FW_QS_IFRAME_TAG'     => '/(<|%3C).*iframe.*(>|%3E)/i',
			'FW_QS_OBF_IFRAME'     => '/(<|%3C)([^i]*i)+frame.*(>|%3E)/i',
			'FW_QS_ANGLE_QUOTES'   => '/(<|>|\'|%0A|%0D|%3C|%3E|%00)/i',
			'FW_QS_SQLI_XSS_MIX'   => '/(;|<|>|\'|"|\)|%0A|%0D|%22|%27|%3C|%3E|%00).*(\/\*|union|select|insert|drop|delete|cast|create|char|convert|alter|declare|script|set|md5|benchmark|encode)/i',
		);

		return $rules;
	}

	/**
	 * Level 3 (7G) - USER_AGENT rules
	 */
	protected function getRulesL3UserAgent() {
		static $rules = null;
		if ( $rules !== null ) {
			return $rules;
		}

		$rules = array(
			'FW_UA_LONG_TOKEN'    => '/([a-z0-9]{2000,})/i',
			'FW_UA_BAD_CHARS'     => '/(&lt;|%0a|%0d|%27|%3c|%3e|%00|0x00)/i',
			'FW_UA_SHELL_TOKENS'  => '/(base64_decode|bin\/bash|disconnect|eval|lwp-download|unserialize)/i',
		);

		return $rules;
	}

	/**
	 * Level 3 (7G) - QUERY_STRING rules
	 */
	protected function getRulesL3Query() {
		static $rules = null;
		if ( $rules !== null ) {
			return $rules;
		}

		$rules = array(
			'FW_QS_LONG_TOKEN'       => '/([a-z0-9]{2000,})/i',
			'FW_QS_SLASH_COLON'      => '/(\/|%2f)(:|%3a)(\/|%2f)/i',
			'FW_QS_ORDER_BY_NUM'     => '/order(\s|%20)+by(\s|%20)*[0-9]+(--)?/i',
			'FW_QS_COMMENT_BLOCK'    => '/(\/|%2f)(\*|%2a)(\*|%2a)(\/|%2f)/i',
			'FW_QS_CKEDITOR'         => '/(ckfinder|fckeditor|fullclick)/i',
			'FW_QS_META_CHARS'       => '/(`|<|>|\^|\|\\\\|0x00|%00|%0d%0a)/i',
			'FW_QS_HEADER_INJECT'    => '/((.*)header:|(.*)set-cookie:(.*)=)/i',
			'FW_QS_LOCALHOST'        => '/(localhost|127(\.|%2e)0(\.|%2e)0(\.|%2e)1)/i',
			'FW_QS_CMD_CHDIR'        => '/(cmd|command)(=|%3d)(chdir|mkdir)(.*)(x20)/i',
			'FW_QS_GLOBALS_REQUEST'  => '/(globals|mosconfig([a-z_]{1,22})|request)(=|\[)/i',
			'FW_QS_WP_CONFIG'        => '/(\/|%2f)((wp-)?config)((\.|%2e)inc)?((\.|%2e)php)/i',
			'FW_QS_TIMTHUMB'         => '/(thumbs?(_editor|open)?|tim(thumbs?)?)((\.|%2e)php)/i',
			'FW_QS_ABS_PATHS'        => '/(absolute_|base|root_)(dir|path)(=|%3d)(ftp|https?)/i',
			'FW_QS_SFTP_INURL'       => '/(s)?(ftp|inurl|php)(s)?(:(%2f|%u2215)(%2f|%u2215))/i',
			'FW_QS_ETC_PASSWD'       => '/((boot|win)((\.|%2e)ini)|etc(\/|%2f)passwd|self(\/|%2f)environ)/i',
			'FW_QS_TRAVERSAL_HEAVY'  => '/(((\/|%2f){3,3})((\.|%2e){3,3})|((\.|%2e){2,2})(\/|%2f|%u2215))/i',
			'FW_QS_FUNC_CALLS'       => '/(benchmark|char|exec|fopen|function|html)(.*)(\(|%28)(.*)(\)|%29)/i',
			'FW_QS_PHP_UUID'         => '/(php)([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i',
			'FW_QS_OBF_EVAL'         => '/(e|%65|%45)(v|%76|%56)(a|%61|%31)(l|%6c|%4c)(.*)(\(|%28)(.*)(\)|%29)/i',
			'FW_QS_PATH_EQ_MOD'      => '/(\/|%2f)(=|%3d|_mm|inurl(:|%3a)(\/|%2f)|(mod|path)(=|%3d)(\.|%2e))/i',
			'FW_QS_EMBED_TAG'        => '/(<|%3c)(.*)(e|%65|%45)(m|%6d|%4d)(b|%62|%42)(e|%65|%45)(d|%64|%44)(.*)(>|%3e)/i',
			'FW_QS_IFRAME_TAG'       => '/(<|%3c)(.*)(i|%69|%49)(f|%66|%46)(r|%72|%52)(a|%61|%41)(m|%6d|%4d)(e|%65|%45)(.*)(>|%3e)/i',
			'FW_QS_OBJECT_TAG'       => '/(<|%3c)(.*)(o|%4f|%6f)(b|%62|%42)(j|%4a|%6a)(e|%65|%45)(c|%63|%43)(t|%74|%54)(.*)(>|%3e)/i',
			'FW_QS_SCRIPT_TAG'       => '/(<|%3c)(.*)(s|%73|%53)(c|%63|%43)(r|%72|%52)(i|%69|%49)(p|%70|%50)(t|%74|%54)(.*)(>|%3e)/i',
			'FW_QS_DELETE_KEYWORD'   => '/(\+|%2b|%20)(d|%64|%44)(e|%65|%45)(l|%6c|%4c)(e|%65|%45)(t|%74|%54)(e|%65|%45)(\+|%2b|%20)/i',
			'FW_QS_INSERT_KEYWORD'   => '/(\+|%2b|%20)(i|%69|%49)(n|%6e|%4e)(s|%73|%53)(e|%65|%45)(r|%72|%52)(t|%74|%54)(\+|%2b|%20)/i',
			'FW_QS_SELECT_KEYWORD'   => '/(\+|%2b|%20)(s|%73|%53)(e|%65|%45)(l|%6c|%4c)(e|%65|%45)(c|%63|%43)(t|%74|%54)(\+|%2b|%20)/i',
			'FW_QS_UPDATE_KEYWORD'   => '/(\+|%2b|%20)(u|%75|%55)(p|%70|%50)(d|%64|%44)(a|%61|%41)(t|%74|%54)(e|%65|%45)(\+|%2b|%20)/i',
			'FW_QS_CAST_OR_1EQ1'     => '/(\\\\x00|("|%22|\'|%27)?0("|%22|\'|%27)?(=|%3d)("|%22|\'|%27)?0|cast(\(|%28)0x|or%201(=|%3d)1)/i',
			'FW_QS_GLOBALS_ENC'      => '/(g|%67|%47)(l|%6c|%4c)(o|%6f|%4f)(b|%62|%42)(a|%61|%41)(l|%6c|%4c)(s|%73|%53)(=|\[|%[0-9A-Z]{0,2})/i',
			'FW_QS_REQUEST_ENC'      => '/(_|%5f)(r|%72|%52)(e|%65|%45)(q|%71|%51)(u|%75|%55)(e|%65|%45)(s|%73|%53)(t|%74|%54)(=|\[|%[0-9A-Z]{2,})/i',
			'FW_QS_JS_PROTOCOL'      => '/(j|%6a|%4a)(a|%61|%41)(v|%76|%56)(a|%61|%31)(s|%73|%53)(c|%63|%43)(r|%72|%52)(i|%69|%49)(p|%70|%50)(t|%74|%54)(:|%3a)(.*)(;|%3b|\)|%29)/i',
			'FW_QS_BASE64_OBF'       => '/(b|%62|%42)(a|%61|%41)(s|%73|%53)(e|%65|%45)(6|%36)(4|%34)(_|%5f)(e|%65|%45|d|%64|%44)(e|%65|%45|n|%6e|%4e)(c|%63|%43)(o|%6f|%4f)(d|%64|%44)(e|%65|%45)(.*)(\()(.*)(\))/i',
			'FW_QS_MALWARE_TOKENS_1' => '/(@copy|\$_(files|get|post)|allow_url_(fopen|include)|auto_prepend_file|blexbot|browsersploit|(c99|php)shell|curl(_exec|test))/i',
			'FW_QS_MALWARE_TOKENS_2' => '/(disable_functions?|document_root|elastix|encodeuricom|exploit|fclose|fgets|file_put_contents|fputs|fsbuff|fsockopen|gethostbyname|grablogin|hmei7)/i',
			'FW_QS_MALWARE_TOKENS_3' => '/(input_file|open_basedir|outfile|passthru|phpinfo|popen|proc_open|quickbrute|remoteview|root_path|safe_mode|shell_exec|site((.){0,2})copier|sux0r|trojan|user_func_array|wget|xertive)/i',
			'FW_QS_SQLI_MIX'         => '/(;|<|>|\'|"|\)|%0a|%0d|%22|%27|%3c|%3e|%00)(.*)(\/\*|alter|base64|benchmark|cast|concat|convert|create|encode|declare|delete|drop|insert|md5|request|script|select|set|union|update)/i',
			'FW_QS_SQL_KEYWORDS_1'   => '/((\+|%2b)(concat|delete|get|select|union)(\+|%2b))/i',
			'FW_QS_UNION_SELECT_FN'  => '/(union)(.*)(select)(.*)(\(|%28)/i',
			'FW_QS_EVAL_CONCAT'      => '/(concat|eval)(.*)(\(|%28)/i',
		);

		return $rules;
	}

	/**
	 * Level 3 (7G) - REQUEST_URI rules
	 */
	protected function getRulesL3Uri() {
		static $rules = null;
		if ( $rules !== null ) {
			return $rules;
		}

		$rules = array(
			'FW_URI_META_CHARS'     => '/(\^|`|<|>|\\\\|\|)/i',
			'FW_URI_LONG_TOKEN'     => '/([a-z0-9]{2000,})/i',
			'FW_URI_TRAILING_GARB'  => '/(\/)(\*|"|\'|\.|,|&|&amp;?)\/?$/i',
			'FW_URI_VBULLETIN'      => '/(\/)(vbulletin|boards|vbforum)(\/)?/i',
			'FW_URI_HEADER_INJECT'  => '/\/((.*)header:|(.*)set-cookie:(.*)=)/i',
			'FW_URI_CKEDITOR'       => '/(\/)(ckfinder|fckeditor|fullclick)/i',
			'FW_URI_SFTP_CONFIG'    => '/(\.(s?ftp-?)config|(s?ftp-?)config\.)/i',
			'FW_URI_ZEROS'          => '/(\{0\}|"?0"?="?0|\(\/\(|\+\+\+|\\\\")/i',
			'FW_URI_TIMTHUMB'       => '/(thumbs?(_editor|open)?|tim(thumbs?)?)(\.php)/i',
			'FW_URI_GET_PERMALINK'  => '/(\.|20)(get|the)(_)(permalink|posts_page_url)(\()/i',
			'FW_URI_CRLF_NULL'      => '/(\/\/\/|\?\?|\/&&|\/\*(.*)\*\/|\/:\/|\\\\\\\\|0x00|%00|%0d%0a)/i',
			'FW_URI_TILDE_ROOT'     => '/(\/%7e)(root|ftp|bin|nobody|named|guest|logs|sshd)(\/)/i',
			'FW_URI_ETC_VAR'        => '/(\/)(etc|var)(\/)(hidden|secret|shadow|ninja|passwd|tmp)(\/)?$/i',
			'FW_URI_SFTP_INURL'     => '/(s)?(ftp|inurl|php)(s)?(:(\/|%2f|%u2215)(\/|%2f|%u2215))/i',
			'FW_URI_EQ_MM_VTI'      => '/(\/)(=|\$&?|&?(pws|rk)=0|_mm|_vti_|(=|\/|;|,)nt\.)/i',
			'FW_URI_DOTFILES'       => '/(\.)(ds_store|htaccess|htpasswd|init?|mysql-select-db)(\/)?$/i',
			'FW_URI_BIN_TOOLS'      => '/(\/)(bin)(\/)(cc|chmod|chsh|cpp|echo|id|kill|mail|nasm|perl|ping|ps|python|tclsh)(\/)?$/i',
			'FW_URI_LOCALHOSTS'     => '/(\/)(::[0-9999]|%3a%3a[0-9999]|127\.0\.0\.1|localhost|makefile|pingserver|wwwroot)(\/)?/i',
			'FW_URI_COMMON_PAYLOAD' => '/(\(null\)|\{\$itemURL\}|cAsT\(0x|echo(.*)kae|etc\/passwd|eval\(|self\/environ|\+union\+all\+select)/i',
			'FW_URI_JS_PROTOCOL'    => '/(\/)?j((\s)+)?a((\s)+)?v((\s)+)?a((\s)+)?s((\s)+)?c((\s)+)?r((\s)+)?i((\s)+)?p((\s)+)?t((\s)+)?(%3a|:)/i',
			'FW_URI_SHELL_WORDS'    => '/(\/)(awstats|(c99|php|web)shell|document_root|error_log|listinfo|muieblack|remoteview|site((.){0,2})copier|sqlpatch|sux0r)/i',
			'FW_URI_SHELL_FILES'    => '/(\/)((php|web)?shell|crossdomain|fileditor|locus7|nstview|php(get|remoteview|writer)|r57|remview|sshphp|storm7|webadmin)(.*)(\.|\()/i',
			'FW_URI_ADMIN_DIRS'     => '/(\/)(author-panel|(db|mysql)-?admin|filemanager|htdocs|httpdocs|https?|mailman|mailto|msoffice|_?php-my-admin(.*)|tmp|undefined|usage|var|vhosts|webmaster)(\/)/i',
			'FW_URI_FUNC_CALLS'     => '/(base64_(en|de)code|benchmark|child_terminate|curl_exec|e?chr|eval|function|fwrite|(f|p)open|html|leak|passthru|p?fsockopen|phpinfo|posix_(kill|mkfifo|setpgid|setsid|setuid)|proc_(close|get_status|nice|open|terminate)|(shell_)?exec|system)(.*)(\()(.*)(\))/i',
			'FW_URI_SHELL_DBS'      => '/(\/)(^$|00.temp00|0day|3index|3xp|70bex?|admin_events|bkht|(php|web)?shell|c99|config(\.)?bak|curltest|db|dompdf|filenetworks|hmei7|index\.php\/index\.php\/index|jahat|kcrew|keywordspy|libsoft|marg|mobiquo|mysql|php-?info|racrew|sql|vuln|(web-?|wp-)?(conf\b|config(uration)?)|xertive)(\.php)/i',
			'FW_URI_BAD_EXTS_1'       => '/(\.)(ab4|ace|afm|ashx|aspx?|bash|ba?k?|bin|bz2|cfg|cfml?|conf\b|config|ctl|dat|db|dist)$/i',
			'FW_URI_BAD_EXTS_2'       => '/(\.)(eml|engine|env|et2|fec|fla|hg|inc|inv|jsp|lqd|make|mbf|mdb|mmw|mny|module|old|one|orig|out)$/i',
			'FW_URI_BAD_EXTS_3'       => '/(\.)(passwd|pdbprofile|psd|pst|ptdb|pwd|py|qbb|qdf|rdf|save|sdb|sh|soa|svn|swl|swo|swp|stx|tax|tgz|theme|tls|tmd|wow|xtmpl|ya?ml)$/i',
		);

		return $rules;
	}

	/**
	 * Level 4 (8G) - HTTP_REFERER rules
	 */
	protected function getRulesL4Referer() {
		static $rules = null;
		if ( $rules !== null ) {
			return $rules;
		}

		$rules = array(
			'FW_REF_ORDER_BY'     => '/order(\s|%20)+by(\s|%20)*[0-9]+(--)?/i',
			'FW_REF_BAD_CHARS'    => '/(&lt;|%0a|%0d|%27|%3c|%3e|%00|0x00)/i',
			'FW_REF_UNLINK_ASSERT'=> '/(@unlink|assert\(|print_r\(|x00|xbshell)/i',
			'FW_REF_SPAM_PHARMA_1'=> '/(100dollars|blue\spill|cocaine|ejaculat|erectile|erections|hoodia|huronriveracres|impotence)/i',
			'FW_REF_SPAM_PHARMA_2'=> '/(pornhelm|pro[sz]ac|sandyauer|semalt\.com|social-buttions|todaperfeita|tramadol|troyhamby|ultram|unicauca|valium|viagra|vicodin|xanax|ypxaieo)/i',
		);

		return $rules;
	}

	/**
	 * Level 4 (8G) - USER_AGENT rules
	 */
	protected function getRulesL4UserAgent() {
		static $rules = null;
		if ( $rules !== null ) {
			return $rules;
		}

		$rules = array(
			'FW_UA_LONG_TOKEN'    => '/([a-z0-9]{2000,})/i',
			'FW_UA_BAD_CHARS'     => '/(&lt;|%0a|%0d|%27|%3c|%3e|%00|0x00)/i',
			'FW_UA_SHELL_TOKENS'  => '/(base64_decode|bin\/bash|disconnect|eval|lwp-download|unserialize)/i',

			// Massive bad-bot list (kept as-is from your code)
			'FW_UA_BAD_BOTS_1'      => '/(acapbot|acoonbot|alexibot|asterias|attackbot|awario)/i',
			'FW_UA_BAD_BOTS_2'      => '/(backdor|becomebot|binlar|blackwidow|blekkobot|blex|blowfish|bullseye|bunnys|butterfly)/i',
			'FW_UA_BAD_BOTS_3'      => '/(careerbot|casper|checkpriv|cheesebot|cherrypick|chinaclaw|choppy|clshttp|cmsworld|copernic|copyrightcheck|cosmos|crescent)/i',
			'FW_UA_BAD_BOTS_4'      => '/(datacha|diavol|discobot|dittospyder|dotnetdotcom|dumbot)/i',
			'FW_UA_BAD_BOTS_5'      => '/(econtext|emailcollector|emailsiphon|emailwolf|eolasbot|eventures|extract|eyenetie)/i',
			'FW_UA_BAD_BOTS_6'      => '/(feedfinder|flaming|flashget|flicky|foobot|fuck)/i',
			'FW_UA_BAD_BOTS_7'      => '/(g00g1e|getright|go-ahead-got|gozilla|grabnet|grafula|harvest|httracks?|icarus6j|jetbot|jetcar|kmccrew|leechftp|libweb|liebaofast|linkscan|linkwalker|loader|lwp-download)/i',
			'FW_UA_BAD_BOTS_8'      => '/(miner|morfeus|moveoverbot|netmechanic|netspider|nicerspro|nikto|ninja|nominet|octopus|pagegrabber|planetwork|postrank|proximic|purebot|queryn|queryseeker)/i',
			'FW_UA_BAD_BOTS_9'      => '/(radian6|radiation|realdownload|remoteview|scan|semalt|siclab|sindice|sitebot|sitesnagger|skygrid|smartdownload|snoopy|sosospider|spankbot|spbot|sqlmap|stackrambler|stripper|sucker|surftbot|sux0r|suzukacz|suzuran)/i',
			'FW_UA_BAD_BOTS_10'      => '/(takeout|teleport|telesoft|true_robots|turingos|turnit|vampire|vikspider|voideye|webleacher|webreaper|webstripper|webvac|webviewer|webwhacker|winhttp|wwwoffle|woxbot|xaldon|xxxyy|youda|zeus|zmeu|zune|zyborg)/i',
		);

		return $rules;
	}

	/**
	 * Level 4 (8G) - QUERY_STRING rules
	 */
	protected function getRulesL4Query() {
		static $rules = null;
		if ( $rules !== null ) {
			return $rules;
		}

		$rules = array(
			'FW_QS_DASH_ONLY'        => '/^(%2d|-)[^=]+$/i',
			'FW_QS_LONG_TOKEN'       => '/([a-z0-9]{4000,})/i',
			'FW_QS_SLASH_COLON'      => '/(\/|%2f)(:|%3a)(\/|%2f)/i',
			'FW_QS_ETC_HOSTS'        => '/(etc\/(hosts|motd|shadow))/i',
			'FW_QS_ORDER_BY_NUM'     => '/order(\s|%20)+by(\s|%20)*[0-9]+(--)?/i',
			'FW_QS_COMMENT_BLOCK'    => '/(\/|%2f)(\*|%2a)(\*|%2a)(\/|%2f)/i',
			'FW_QS_META_CHARS'       => '/(`|<|>|\^|\|\\\\|0x00|%00|%0d%0a)/i',
			'FW_QS_CKEDITOR'         => '/(f?ckfinder|f?ckeditor|fullclick)/i',
			'FW_QS_HEADER_INJECT'    => '/((.*)header:|(.*)set-cookie:(.*)=)/i',
			'FW_QS_LOCALHOST'        => '/(localhost|127(\.|%2e)0(\.|%2e)0(\.|%2e)1)/i',
			'FW_QS_CMD_CHDIR'        => '/(cmd|command)(=|%3d)(chdir|mkdir)(.*)(x20)/i',
			'FW_QS_GLOBALS_REQUEST'  => '/(globals|mosconfig([a-z_]{1,22})|request)(=|\[)/i',
			'FW_QS_WP_CONFIG'        => '/(\/|%2f)((wp-)?config)((\.|%2e)inc)?((\.|%2e)php)/i',
			'FW_QS_TIMTHUMB'         => '/(thumbs?(_editor|open)?|tim(thumbs?)?)((\.|%2e)php)/i',
			'FW_QS_ABS_PATHS'        => '/(absolute_|base|root_)(dir|path)(=|%3d)(ftp|https?)/i',
			'FW_QS_SFTP_INURL'       => '/(s)?(ftp|inurl|php)(s)?(:(\/|%2f|%u2215)(\/|%2f|%u2215))/i',
			'FW_QS_GET_PERMALINK'    => '/(\.|20)(get|the)(_|%5f)(permalink|posts_page_url)(\(|%28)/i',
			'FW_QS_ETC_PASSWD'       => '/((boot|win)((\.|%2e)ini)|etc(\/|%2f)passwd|self(\/|%2f)environ)/i',
			'FW_QS_TRAVERSAL_HEAVY'  => '/(((\/|%2f){3,3})((\.|%2e){3,3})|((\.|%2e){2,2})(\/|%2f|%u2215))/i',
			'FW_QS_FUNC_CALLS'       => '/(benchmark|char|exec|fopen|function|html)(.*)(\(|%28)(.*)(\)|%29)/i',
			'FW_QS_PHP_UUID'         => '/(php)([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i',
			'FW_QS_OBF_EVAL'         => '/(e|%65|%45)(v|%76|%56)(a|%61|%31)(l|%6c|%4c)(.*)(\(|%28)(.*)(\)|%29)/i',
			'FW_QS_PATH_EQ_MOD'      => '/(\/|%2f)(=|%3d|\$&|_mm|inurl(:|%3a)(\/|%2f)|(mod|path)(=|%3d)(\.|%2e))/i',
			'FW_QS_EMBED_TAG'        => '/(<|%3c)(.*)(e|%65|%45)(m|%6d|%4d)(b|%62|%42)(e|%65|%45)(d|%64|%44)(.*)(>|%3e)/i',
			'FW_QS_IFRAME_TAG'       => '/(<|%3c)(.*)(i|%69|%49)(f|%66|%46)(r|%72|%52)(a|%61|%41)(m|%6d|%4d)(e|%65|%45)(.*)(>|%3e)/i',
			'FW_QS_OBJECT_TAG'       => '/(<|%3c)(.*)(o|%4f|%6f)(b|%62|%42)(j|%4a|%6a)(e|%65|%45)(c|%63|%43)(t|%74|%54)(.*)(>|%3e)/i',
			'FW_QS_SCRIPT_TAG'       => '/(<|%3c)(.*)(s|%73|%53)(c|%63|%43)(r|%72|%52)(i|%69|%49)(p|%70|%50)(t|%74|%54)(.*)(>|%3e)/i',
			'FW_QS_DELETE_KEYWORD'   => '/(\+|%2b|%20)(d|%64|%44)(e|%65|%45)(l|%6c|%4c)(e|%65|%45)(t|%74|%54)(e|%65|%45)(\+|%2b|%20)/i',
			'FW_QS_INSERT_KEYWORD'   => '/(\+|%2b|%20)(i|%69|%49)(n|%6e|%4e)(s|%73|%53)(e|%65|%45)(r|%72|%52)(t|%74|%54)(\+|%2b|%20)/i',
			'FW_QS_SELECT_KEYWORD'   => '/(\+|%2b|%20)(s|%73|%53)(e|%65|%45)(l|%6c|%4c)(e|%65|%45)(c|%63|%43)(t|%74|%54)(\+|%2b|%20)/i',
			'FW_QS_UPDATE_KEYWORD'   => '/(\+|%2b|%20)(u|%75|%55)(p|%70|%50)(d|%64|%44)(a|%61|%41)(t|%74|%54)(e|%65|%45)(\+|%2b|%20)/i',
			'FW_QS_CAST_OR_1EQ1'     => '/(\\\\x00|("|%22|\'|%27)?0("|%22|\'|%27)?(=|%3d)("|%22|\'|%27)?0|cast(\(|%28)0x|or%201(=|%3d)1)/i',
			'FW_QS_GLOBALS_ENC'      => '/(g|%67|%47)(l|%6c|%4c)(o|%6f|%4f)(b|%62|%42)(a|%61|%41)(l|%6c|%4c)(s|%73|%53)(=|\[|%[0-9A-Z]{0,2})/i',
			'FW_QS_REQUEST_ENC'      => '/(_|%5f)(r|%72|%52)(e|%65|%45)(q|%71|%51)(u|%75|%55)(e|%65|%45)(s|%73|%53)(t|%74|%54)(=|\[|%[0-9A-Z]{2,})/i',
			'FW_QS_JS_PROTOCOL'      => '/(j|%6a|%4a)(a|%61|%41)(v|%76|%56)(a|%61|%31)(s|%73|%53)(c|%63|%43)(r|%72|%52)(i|%69|%49)(p|%70|%50)(t|%74|%54)(:|%3a)(.*)(;|%3b|\)|%29)/i',
			'FW_QS_BASE64_OBF'       => '/(b|%62|%42)(a|%61|%41)(s|%73|%53)(e|%65|%45)(6|%36)(4|%34)(_|%5f)(e|%65|%45|d|%64|%44)(e|%65|%45|n|%6e|%4e)(c|%63|%43)(o|%6f|%4f)(d|%64|%44)(e|%65|%45)(.*)(\()(.*)(\))/i',

			// Split malware tokens across 3 patterns as per your code
			'FW_QS_MALWARE_TOKENS_1' => '/(@copy|\$_(files|get|post)|allow_url_(fopen|include)|auto_prepend_file|blexbot|browsersploit|call_user_func_array|(php|web)shell|curl(_exec|test)|disable_functions?|document_root)/i',
			'FW_QS_MALWARE_TOKENS_2' => '/(elastix|encodeuricom|exploit|fclose|fgets|file_put_contents|fputs|fsbuff|fsockopen|gethostbyname|grablogin|hmei7|hubs_post-cta|input_file|invokefunction|(\b)load_file|open_basedir|outfile|p3dlite)/i',
			'FW_QS_MALWARE_TOKENS_3' => '/(pass(=|%3d)shell|passthru|phpinfo|phpshells|popen|proc_open|quickbrute|remoteview|root_path|safe_mode|shell_exec|site((.){0,2})copier|sp_executesql|sux0r|trojan|udtudt|user_func_array|wget|wp_insert_user|xertive)/i',

			'FW_QS_SQLI_MIX'         => '/(;|<|>|\'|"|\)|%0a|%0d|%22|%27|%3c|%3e|%00)(.*)(\/\*|alter|base64|benchmark|cast|concat|convert|create|encode|declare|delay|delete|drop|hex|insert|load|md5|null|replace|request|script|select|set|sleep|truncate|unhex|update)/i',
			'FW_QS_SQL_KEYWORDS_1'   => '/((\+|%2b)(concat|delete|get|select|union)(\+|%2b))/i',
			'FW_QS_UNION_SELECT_FN'  => '/(union)(.*)(select)(.*)(\(|%28)/i',
			'FW_QS_EVAL_CONCAT'      => '/(concat|eval)(.*)(\(|%28)/i',
		);

		return $rules;
	}

	/**
	 * Level 4 (8G) - REQUEST_URI rules
	 *
	 * This is the biggest block in your code; it is included as rule-codes one-to-one
	 * with your preg_match calls so you can log exactly which one triggered.
	 */
	protected function getRulesL4Uri() {
		static $rules = null;
		if ( $rules !== null ) {
			return $rules;
		}

		$rules = array(
			'FW_URI_TRIPLE_COMMA'     => '/(,,,)/i',
			'FW_URI_SEVEN_DASHES'     => '/(-------)/i',
			'FW_URI_META_CHARS'       => '/(\^|`|<|>|\\\\|\|)/i',
			'FW_URI_LONG_TOKEN'       => '/([a-z0-9]{2000,})/i',
			'FW_URI_QUOTE_DOT'        => '/(=?\(\'|%27\)\/?)(\.)/i',
			'FW_URI_TRAILING_GARB'    => '/(\/)(\*|"|\'|\.|,|&|&amp;?)(\/)?$/i',
			'FW_URI_DOTPHP_NUM'       => '/(\.)(php)(\()?([0-9]+)(\))?(\/)?$/i',
			'FW_URI_HEADER_INJECT'    => '/(\/)((.*)header:|(.*)set-cookie:(.*)=)/i',
			'FW_URI_SFTP_CONFIG'      => '/(\.(s?ftp-?)config|(s?ftp-?)config\.)/i',
			'FW_URI_CKEDITOR'         => '/(\/)(f?ckfinder|f?ckeditor|fullclick)/i',
			'FW_URI_DL_FRAMEWORK'     => '/(\/)((force-)?download|framework\/main)(\.php)/i',
			'FW_URI_ZEROS'            => '/(\{0\}|"?0"?="?0|\(\/\(|\+\+\+|\\\\")/i',
			'FW_URI_VBULLETIN'        => '/(\/)(vbull(etin)?|boards|vbforum|vbweb|webvb)(\/)?/i',
			'FW_URI_GET_PERMALINK'    => '/(\.|20)(get|the)(_)(permalink|posts_page_url)(\()/i',
			'FW_URI_CRLF_NULL'        => '/(\/\/\/|\?\?|\/&&|\/\*(.*)\*\/|\/:\/|\\\\\\\\|0x00|%00|%0d%0a)/i',
			'FW_URI_CGI_ALPHA'        => '/(\/)(cgi_?)?alfa(_?cgiapi|_?data|_?v[0-9]+)?(\.php)/i',
			'FW_URI_TIMTHUMB'         => '/(thumbs?(_editor|open)?|tim(thumbs?)?)((\.|%2e)php)/i',
			'FW_URI_ADMINER'          => '/(\/)((boot)?_?admin(er|istrator|s)(_events)?)(\.php)/i',
			'FW_URI_TILDE_ROOT'       => '/(\/%7e)(root|ftp|bin|nobody|named|guest|logs|sshd)\//i',
			'FW_URI_SHELL_SH3LL'      => '/(\/)(\.?mad|alpha|c99|web)?sh(3|e)ll([0-9]+|\w)(\.php)/i',
			'FW_URI_UPLOADERS'        => '/(\/)(admin-?|file-?)(upload)(bg|_?file|ify|svu|ye)?(\.php)/i',
			'FW_URI_ETC_VAR'          => '/(\/)(etc|var)(\/)(hidden|secret|shadow|ninja|passwd|tmp)(\/)?$/i',
			'FW_URI_SFTP_INURL'       => '/(s)?(ftp|inurl|php)(s)?(:(\/|%2f|%u2215)(\/|%2f|%u2215))/i',
			'FW_URI_EQ_MM_VTI'        => '/(\/)(=|\$&?|&?(pws|rk)=0|_mm|_vti_|(=|\/|;|,)nt\.)/i',
			'FW_URI_DOTFILES'         => '/(\.)(ds_store|htaccess|htpasswd|init?|mysql-select-db)(\/)?$/i',
			'FW_URI_BIN_TOOLS'        => '/(\/)(bin)(\/)(cc|chmod|chsh|cpp|echo|id|kill|mail|nasm|perl|ping|ps|python|tclsh)(\/)?$/i',
			'FW_URI_LOCALHOSTS'       => '/(\/)(::[0-9999]|%3a%3a[0-9999]|127\.0\.0\.1|ccx|localhost|makefile|pingserver|wwwroot)(\/)?/i',
			'FW_URI_JS_PROTOCOL'      => '/(\/)?j((\s)+)?a((\s)+)?v((\s)+)?a((\s)+)?s((\s)+)?c((\s)+)?r((\s)+)?i((\s)+)?p((\s)+)?t((\s)+)?(%3a|:)/i',
			'FW_URI_OLD_STAGING'      => '/^(\/)(old-?site(back)?|old(web)?site(here)?|sites?|staging|undefined)(\/)?$/i',
			'FW_URI_HTTPDOCS'         => '/(\/)(htdocs|httpdocs|https?|mailman|mailto|msoffice|undefined|usage|var|vhosts|webmaster|www)(\/)/i',
			'FW_URI_COMMON_PAYLOAD'   => '/(\(null\)|\{\$itemURL\}|cast\(0x|echo(.*)kae|etc\/passwd|eval\(|null(.*)null|open_basedir|self\/environ|\+union\+all\+select)/i',
			'FW_URI_DB_CONFIG'        => '/(\/)(db-?|j-?|my(sql)?-?|setup-?|web-?|wp-?)?(admin-?)?(setup-?)?(conf\b|conf(ig)?)(uration)?(\.?bak|\.inc)?(\.inc|\.old|\.php|\.txt)/i',
			'FW_URI_ADMIN_DIRS'       => '/(\/)((.*)crlf-?injection|(.*)xss-?protection|__(inc|jsc)|administrator|author-panel|downloader|(db|mysql)-?admin)(\/)/i',
			'FW_URI_TYPO_FILENAMES'   => '/(\/)(haders|head|hello|helpear|incahe|includes?|indo(sec)?|infos?|install|ioptimizes?|jmail|js|king|kiss|kodox|kro|legion|libsoft)(\.php)/i',
			'FW_URI_PHPUNIT_REMOTE'   => '/(\/)(awstats|document_root|dologin\.action|error.log|extension\/ext|htaccess\.|lib\/php|listinfo|phpunit\/php|remoteview|server\/php|www\.root\.)/i',
			'FW_URI_FUNC_CALLS_1'     => '/(base64_(en|de)code|benchmark|curl_exec|e?chr|eval|function|fwrite|(f|p)open|html|leak|passthru|p?fsockopen|phpinfo)(.*)(\(|%28)(.*)(\)|%29)/i',
			'FW_URI_FUNC_CALLS_2'     => '/(posix_(kill|mkfifo|setpgid|setsid|setuid)|(child|proc)_(close|get_status|nice|open|terminate)|(shell_)?exec|system)(.*)(\(|%28)(.*)(\)|%29)/i',
			'FW_URI_SHELL_FILES'      => '/(\/)((c99|php|web)?shell|crossdomain|fileditor|locus7|nstview|php(get|remoteview|writer)|r57|remview|sshphp|storm7|webadmin)(.*)(\.|%2e|\(|%28)/i',
			'FW_URI_WP_FAKE_FILES'    => '/(\/)((wp-)((201\d|202\d|[0-9]{2})|ad|admin(fx|rss|setup)|booking|confirm|crons|data|file|mail|one|plugins?|readindex|reset|setups?|story))(\.php)/i',

			// The remaining huge filename-based patterns from your code (kept)
			'FW_URI_FILENAME_BUCKET_1'=> '/(\/)(^$|-|\!|\w|\.(.*)|100|123|([^iI])?ndex|index\.php\/index|3xp|777|7yn|90sec|99|active|aill|ajs\.delivery|al277|alexuse?|ali|allwrite)(\.php)/i',
			'FW_URI_FILENAME_BUCKET_2'=> '/(\/)(analyser|apache|apikey|apismtp|authenticat(e|ing)|autoload_classmap|backup(_index)?|bakup|bkht|black|bogel|bookmark|bypass|cachee?)(\.php)/i',
			'FW_URI_FILENAME_BUCKET_3'=> '/(\/)(clean|cm(d|s)|con|connector\.minimal|contexmini|contral|curl(test)?|data(base)?|db|db-cache|db-safe-mode|defau11|defau1t|dompdf|dst)(\.php)/i',
			'FW_URI_FILENAME_BUCKET_4'=> '/(\/)(elements|emails?|error.log|ecscache|edit-form|eval-stdin|evil|fbrrchive|filemga|filenetworks?|f0x|gank(\.php)?|gass|gel|guide)(\.php)/i',
			'FW_URI_FILENAME_BUCKET_5'=> '/(\/)(logo_img|lufix|mage|marg|mass|mide|moon|mssqli|mybak|myshe|mysql|mytag_js?|nasgor|newfile|nf_?tracking|nginx|ngoi|ohayo|old-?index)(\.php)/i',
			'FW_URI_FILENAME_BUCKET_6'=> '/(\/)(olux|owl|pekok|petx|php-?info|phpping|popup-pomo|priv|r3x|radio|rahma|randominit|readindex|readmy|reads|repair-?bak|root)(\.php)/i',
			'FW_URI_FILENAME_BUCKET_7'=> '/(\/)(router|savepng|semayan|shell|shootme|sky|socket(c|i|iasrgasf)ontrol|sql(bak|_?dump)?|support|sym403|sys|system_log|test|tmp-?(uploads)?)(\.php)/i',
			'FW_URI_FILENAME_BUCKET_8'=> '/(\/)(traffic-advice|u2p|udd|ukauka|up__uzegp|up14|upxx?|vega|vip|vu(ln)?(\w)?|webroot|weki|wikindex|wp_logns?|wp_wrong_datlib)(\.php)/i',
			'FW_URI_FILENAME_BUCKET_9'=> '/(\/)((wp-?)?install(ation)?|wp(3|4|5|6)|wpfootes|wpzip|ws0|wsdl|wso(\w)?|www|(uploads|wp-admin)?xleet(-shell)?|xmlsrpc|xup|xxu|xxx|zibi|zipy)(\.php)/i',

			'FW_URI_MARKERS_1'        => '/(bkv74|cachedsimilar|core-stab|crgrvnkb|ctivrc|deadcode|deathshop|dkiz|e7xue|eqxafaj90zir|exploits|ffmkpcal|filellli7|(fox|sid)wso|gel4y|goog1es|gvqqpinc)/i',
			'FW_URI_MARKERS_2'        => '/(@md5|00.temp00|0byte|0d4y|0day|0xor|wso1337|1h6j5|3xp|40dd1d|4price|70bex?|a57bze893|abbrevsprl|abruzi|adminer|aqbmkwwx|archivarix|backdoor|beez5|bgvzc29)/i',
			'FW_URI_MARKERS_3'        => '/(handler_to_code|hax(0|o)r|hmei7|hnap1|ibqyiove|icxbsx|indoxploi|jahat|jijle3|kcrew|keywordspy|laobiao|lock360|longdog|marijuan|mod_(aratic|ariimag))/i',
			'FW_URI_MARKERS_4'        => '/(mobiquo|muiebl|osbxamip|phpunit|priv8|qcmpecgy|r3vn330|racrew|raiz0|reportserver|r00t|respectmus|rom2823|roseleif|sh3ll|site((.){0,2})copier|sqlpatch|sux0r)/i',
			'FW_URI_MARKERS_5'        => '/(sym403|telerik|uddatasql|utchiha|visualfrontend|w0rm|wangdafa|wpyii2|wsoyanzo|x5cv|xattack|xbaner|xertive|xiaolei|xltavrat|xorz|xsamxad|xsvip|xxxs?s?|zabbix|zebda)/i',

			'FW_URI_BAD_EXTS_1'       => '/(\.)(ab4|ace|afm|alfa|as(h|m)x?|aspx?|aws|axd|bash|ba?k?|bat|bin|bz2|cfg|cfml?|cms|conf\b|config|ctl|dat|db|dist|dll|eml|eng(ine)?|env|et2|fec|fla|git(ignore)?)$/i',
			'FW_URI_BAD_EXTS_2'       => '/(\.)(hg|idea|inc|index|ini|inv|jar|jspa?|lib|local|log|lqd|make|mbf|mdb|mmw|mny|mod(ule)?|msi|old|one|orig|out|passwd|pdb|php\.(php|suspect(ed)?)|php([^\/])|phtml?|pl|profiles?)$/i',
			'FW_URI_BAD_EXTS_3'       => '/(\.)(pst|ptdb|production|pwd|py|qbb|qdf|rdf|remote|save|sdb|sh|soa|svn|swf|swl|swo|swp|stx|tax|tgz?|theme|tls|tmb|tmd|wok|wow|xsd|xtmpl|xz|ya?ml|za|zlib)$/i',
		);

		return $rules;
	}


	/**
	 * Returns first match with code/pattern.
	 *
	 * @param string $value
	 * @param array  $rules array('CODE' => '/regex/i', ...)
	 * @param string $area  query|uri|user_agent|referer
	 *
	 * @return array|false
	 */
	protected function matchRules( $value, $rules, $area ) {
		if ( $value === '' || empty( $rules ) ) {
			return false;
		}

		foreach ( $rules as $code => $regex ) {
			if ( preg_match( $regex, $value ) ) {
				return array(
					'code'    => (string) $code,
					'area'    => (string) $area,
					'pattern' => (string) $regex,
				);
			}
		}

		return false;
	}

	public function detectBanlist( ) {

		// If user_agent blocking is activated
		if ( $banlist = HMWP_Classes_Tools::getOption( 'banlist_user_agent' ) ) {

			if ( ! is_array( $banlist ) ) {
				$banlist = json_decode( $banlist, true );
				$banlist = is_array($banlist) ? $banlist : array();
			}

			if ( ! empty( $banlist ) ) {
				// Remove empty data
				$banlist = array_filter( $banlist );
			}

			if ( ! empty( $banlist ) ) {
				if ( $this->ua <> '' ) {

					// Check if the current item is in the blocked list
					foreach ( $banlist as $item ) {
						if($item <> '') {
							if ( stripos( $this->ua, $item ) !== false ) {
								return true;
							}
						}
					}

				}
			}

		}

		// If referrer blocking is activated
		if ( $banlist = HMWP_Classes_Tools::getOption( 'banlist_referrer' ) ) {

			if ( ! is_array( $banlist ) ) {
				$banlist = json_decode( $banlist, true );
				$banlist = is_array($banlist) ? $banlist : array();
			}

			if ( ! empty( $banlist ) ) {
				// Remove empty data
				$banlist = array_filter( $banlist );
			}

			if ( ! empty( $banlist ) ) {
				if ( isset( $_SERVER['HTTP_REFERER'] ) && $_SERVER['HTTP_REFERER'] <> '' ) {

					// Set the referrer
					$referrer = wp_unslash( $_SERVER['HTTP_REFERER'] ); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

					// Check if the current item is in the blocked list
					foreach ( $banlist as $item ) {
						if($item <> '') {
							if ( stripos( $referrer, $item ) !== false ) {
								return true;
							}
						}
					}

				}
			}
		}

		// If hostname blocking is activated
		if ( $banlist = HMWP_Classes_Tools::getOption( 'banlist_hostname' ) ) {

			if ( ! is_array( $banlist ) ) {
				$banlist = json_decode( $banlist, true );
				$banlist = is_array($banlist) ? $banlist : array();
			}

			if ( ! empty( $banlist ) ) {
				// Remove empty data
				$banlist = array_filter( $banlist );
			}

			if ( ! empty( $banlist ) ) {

				// Get caller server IPs
				$ips = $this->getServerVariableIPs();

				if ( ! empty( $ips ) ) {

					// For each IP found on the caller
					foreach ( $ips as $ip ) {
						// Get the hostname from the IP if possible
						$hostname = $this->getHostname( $ip );

						// Check if the current item is in the blocked list
						foreach ( $banlist as $item ) {
							if($item <> '' && $hostname <> ''){
								if ( stripos( $hostname, $item ) !== false ) {
									return true;
								}
							}
						}

					}
				}
			}
		}

		return false;
	}


    /**
     * Check if there are whitelisted IPs for accessing the hidden paths
     *
     * @return void
     * @throws Exception
     */
	public function checkWhitelistIPs() {

		// Get caller server IPs
		$ips = $this->getServerVariableIPs();

		if ( ! empty( $ips ) ) {
			// For each IP found on the caller
			foreach ( $ips as $ip ) {
				// If the IP is whitelisted, apply the whitelist level of security
				if ( $this->isWhitelistedIP( $ip ) ) {
					$this->whitelistLevel( HMWP_Classes_Tools::getOption( 'whitelist_level' ) );
					break;
				}
			}
		}

	}

	/**
	 * Checks if a given threat code is part of the whitelist rules.
	 *
	 * The method retrieves the whitelist rules from the options and determines
	 * if the provided threat code is included in those rules.
	 *
	 * @return bool Returns true if the threat code is whitelisted, otherwise false.
	 * @throws Exception
	 */
	public function checkWhitelistRule( $code ) {

		if ( $code <> '' && $rules = HMWP_Classes_Tools::getOption( 'whitelist_rules' ) ) {
			// Get the array of urls
			if ( ! empty( $rules ) ) {
				$rules = json_decode( $rules, true );
			}

			if ( in_array( $code, $rules, true ) ) {
				return true;
			}
		}

		return false;
	}

    /**
     * Check if there are whitelisted paths for the current path
     *
     * @return void
     * @throws Exception
     */
	public function checkWhitelistPaths() {

		if ( $this->uri <> '' ) {
			$uri = untrailingslashit( strtok( $this->uri, '?' ) );

            // Check the whitelist URLs
			$whitelist_urls = HMWP_Classes_Tools::getOption( 'whitelist_urls' );
			if ( ! empty( $whitelist_urls ) ) {
                // Unpack and filter whitelist URLs
				$whitelist_urls = json_decode( $whitelist_urls, true );
				// Remove empty data
				$whitelist_urls = array_filter( $whitelist_urls );
			}

			if ( ! empty( $whitelist_urls ) ) {
				foreach ( $whitelist_urls as $path ) {
					if ( strpos( $path, ',' ) ) {
						$paths = explode( ',', $path );

						foreach ( $paths as $spath ) {
							if ( HMWP_Classes_Tools::searchInString( $uri, array( $spath ) ) ) {
                                // Apply whitelist level of security
								$this->whitelistLevel( HMWP_Classes_Tools::getOption( 'whitelist_level' ) );
							}
						}

					} else {
						if ( HMWP_Classes_Tools::searchInString( $uri, array( $path ) ) ) {
                            // Apply whitelist level of security
							$this->whitelistLevel( HMWP_Classes_Tools::getOption( 'whitelist_level' ) );
						}
					}

				}
			}

		}

	}


	/**
	 * Check if there are whitelisted IPs for accessing the hidden paths
	 *
	 * @return bool
	 */
	public function isWhitelistedIP( $ip ) {
		$wl_items = array();

		// Fail closed: an invalid/unparsable IP must never be treated as whitelisted,
		// otherwise a malformed forwarded header could downgrade the protections.
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return false;
		}

		//jetpack whitelist
		$known_ips = array(
			'122.248.245.244/32',
			'54.217.201.243/32',
			'54.232.116.4/32',
			'185.64.140.0/22',
			'76.74.255.0/22',
			'192.0.64.0/18',
			'192.0.65.0/22',
			'192.0.80.0/22',
			'192.0.96.0/22',
			'192.0.112.0/20',
			'192.0.123.0/22',
			'195.234.108.0/22',
			'54.148.171.133',//WordFence
			'35.83.41.128', //WordFence
			'52.25.185.95', //WordFence
		);

		$local_ips = $this->getLocalIPs();

		if ( HMWP_Classes_Tools::getOption( 'whitelist_ip' ) ) {
			$wl_items = (array) json_decode( HMWP_Classes_Tools::getOption( 'whitelist_ip' ), true );
		}

		//merge all the whitelisted ips and also add the hook for users
		$wl_items = apply_filters( 'hmwp_whitelisted_ips', array_merge( $local_ips, $wl_items, $known_ips ) );
		$wl_items = array_filter( $wl_items );
		$wl_items = array_unique( $wl_items );

		try {
			foreach ( $wl_items as $item ) {
				$item = trim( $item );

				if ( filter_var( $item, FILTER_VALIDATE_IP ) && $ip == $item ) {
					return true;
				}

				if ( strpos( $item, '*' ) === false && strpos( $item, '/' ) === false ) { //no match, no wildcard
					continue;
				}

				if ( strpos( $ip, '.' ) !== false ) {

					if ( strpos( $item, '/' ) !== false ) {
						list( $range, $bits ) = explode( '/', $item, 2 );

						if ( 0 == (int) $bits ) {
							continue;
						}

						if ( (int) $bits < 0 || (int) $bits > 32 ) {
							continue;
						}

						$subnet = ip2long( $range );
						$iplong = ip2long( $ip );
						$mask   = - 1 << ( 32 - $bits );
						$subnet &= $mask;

						if ( ( $iplong & $mask ) == $subnet ) {
							return true;
						}
					}

					$iplong  = ip2long( $ip );
					$ip_low  = ip2long( str_replace( '*', '0', $item ) );
					$ip_high = ip2long( str_replace( '*', '255', $item ) );

					if ( $iplong >= $ip_low && $iplong <= $ip_high ) {//IP is within wildcard range
						return true;
					}
				}

			}
		} catch ( Exception $e ) {
		}

		return false;
	}

	/**
	 * Return the server IP address
	 *
	 * @return array
	 */
	public function getLocalIPs() {

		$ips = array();

		$domain = ( HMWP_Classes_Tools::isMultisites() && defined( 'BLOG_ID_CURRENT_SITE' ) ) ? get_home_url( BLOG_ID_CURRENT_SITE ) : site_url();

		if ( filter_var( $domain, FILTER_VALIDATE_URL ) !== false && strpos( $domain, '.' ) !== false ) {
			if ( ! HMWP_Classes_Tools::isLocalFlywheel() ) {
				$ips[] = '127.0.0.1';

				//set local domain IP
				if ( HMWP_Classes_Tools::getOption( 'hmwp_disable_rest_api' ) ) {
					if( $local_ip = get_transient('hmwp_local_ip') ){
						$ips[] = $local_ip;
					}elseif( $local_ip = @gethostbyname( wp_parse_url($domain, PHP_URL_HOST) ) ) {
						set_transient( 'hmwp_local_ip', $local_ip);
						$ips[] = $local_ip;
					}
				}
			}
		}

		return $ips;

	}

	/**
	 * Check if there are banned IPs for accessing the hidden paths
	 *
	 * @return bool
	 */
	public function isBlacklistedIP( $ip ) {
		$bl_items = array();

		$bl_blacklisted = array(
			'35.214.130.0/22', // detector
			'54.86.50.0/22', // detector
			'172.105.48.0/22', // detector
			'192.185.4.40', // detector
			'172.105.48.130', // detector
			'167.99.233.123', // detector
		);

		if ( HMWP_Classes_Tools::getOption( 'banlist_ip' ) ) {
			$bl_items = (array) json_decode( HMWP_Classes_Tools::getOption( 'banlist_ip' ), true );
		}

		//merge all the whitelisted ips and also add the hook for users
		$bl_items = apply_filters( 'hmwp_banlist_ips', array_merge( $bl_blacklisted, $bl_items ) );

		try {
			foreach ( $bl_items as $item ) {
				$item = trim( $item );

				if ( $ip == $item ) {
					return true;
				}

				if ( strpos( $item, '*' ) === false && strpos( $item, '/' ) === false ) { //no match, no wildcard
					continue;
				}

				if ( strpos( $ip, '.' ) !== false ) {

					if ( strpos( $item, '/' ) !== false ) {
						list( $range, $bits ) = explode( '/', $item, 2 );

						if ( 0 == (int) $bits ) {
							continue;
						}

						if ( (int) $bits < 0 || (int) $bits > 32 ) {
							continue;
						}

						$subnet = ip2long( $range );
						$iplong = ip2long( $ip );
						$mask   = - 1 << ( 32 - $bits );
						$subnet &= $mask;

						if ( ( $iplong & $mask ) == $subnet ) {
							return true;
						}
					}

					$iplong  = ip2long( $ip );
					$ip_low  = ip2long( str_replace( '*', '0', $item ) );
					$ip_high = ip2long( str_replace( '*', '255', $item ) );

					if ( $iplong >= $ip_low && $iplong <= $ip_high ) {//IP is within wildcard range
						return true;
					}
				}

			}
		} catch ( Exception $e ) {
		}

		return false;
	}

    /**
     * Check if the IP is in blacklist
     * Include also the theme detectors
     *
     * @return void
     * @throws Exception
     */
	public function checkBlacklistIPs() {

		// Don't ban IPs in preview mode
		if ( HMWP_Classes_Tools::getValue( 'hmwp_preview' ) == HMWP_Classes_Tools::getOption( 'hmwp_disable_name' ) ) {
			return;
		}

        // Get caller server IPs
		$ips = $this->getServerVariableIPs();

		if ( ! empty( $ips ) ) {
            // For each IP found on the caller
			foreach ( $ips as $ip ) {
                // If the IP is not whitelisted and is blacklisted, block it
                if ( ! $this->isWhitelistedIP( $ip ) && $this->isBlacklistedIP( $ip ) ) {
                    /** @var HMWP_Models_Brute $bruteForceModel */
                    $bruteForceModel = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Brute' );

                    // Register the process as failed
                    $bruteForceModel->bruteForceBlock();
					break;
				}
			}
		}

	}

    /**
     * Whitelist features based on whitelist level
     *
     * @param int $level
     *
     * @return void
     * @throws Exception
     */
	private function whitelistLevel( $level = 0 ) {

		// Disable brute force reCaptcha on whitelist paths
		add_filter( 'hmwp_option_brute_use_math', '__return_false' );
		add_filter( 'hmwp_option_brute_use_captcha', '__return_false' );
		add_filter( 'hmwp_option_brute_use_captcha_v3', '__return_false' );
		add_filter( 'hmwp_preauth_check', '__return_false' );

        // If whitelist_level == 0, stop hiding URLs
		add_filter( 'hmwp_process_hide_urls', '__return_false' );
		add_filter( 'hmwp_process_firewall', '__return_false' );
		add_filter( 'hmwp_process_threats', '__return_false' );

        // If whitelist_level > 0, stop hiding URLs and find/replace process
		if ( $level > 0 ) {
			add_filter( 'hmwp_process_find_replace', '__return_false' );
		}

        // If whitelist_level > 1, stop further processes
		if ( $level > 1 ) {
			//add_filter( 'hmwp_process_init', '__return_false' );
			add_filter( 'hmwp_process_buffer', '__return_false' );
			add_filter( 'hmwp_process_hide_disable', '__return_false' );
		}
	}

	/**
	 * Get Hostname from IP
	 *
	 * @param $ip
	 *
	 * @return array|false|mixed|string
	 */
	private function getHostname( $ip ) {
		$host = false;

		// This function works for IPv4 or IPv6
		if ( function_exists( 'gethostbyaddr' ) ) {
			$host = @gethostbyaddr( $ip );
		}

		if ( ! $host ) {
			$ptr = false;
			if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) !== false ) {
				$ptr = implode( ".", array_reverse( explode( ".", $ip ) ) ) . ".in-addr.arpa";
			} else if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) !== false ) {
				$ptr = implode( ".", array_reverse( str_split( bin2hex( $ip ) ) ) ) . ".ip6.arpa";
			}

			if ( $ptr && function_exists( 'dns_get_record' ) ) {
				$host = @dns_get_record( $ptr, DNS_PTR );

				if ( $host ) {
					$host = $host[0]['target'];
				}

			}
		}

		return $host;
	}

	private function getServerVariableIPs() {
		/** @var HMWP_Models_Firewall_Server $server */
		$server = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Firewall_Server' );

		// Get caller server IP
		return $server->getServerVariableIPs();
	}
}
