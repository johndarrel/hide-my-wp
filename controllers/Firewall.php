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

class HMWP_Controllers_Firewall extends HMWP_Classes_FrontController {

	/**
	 * Load the firewall on QUERY and URI
	 *
	 * @return void
	 */
	public function init() {

		try {

			// If a firewall process is not activated, exit
			if ( ! $this->doFirewall() ) {
				return;
			}

			// Detect all potential threats
			$this->detectAllThreats();

			/** @var HMWP_Models_Firewall_Rules $firewallRules */
			$firewallRules = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Firewall_Rules' );

			// Detect WP Theme & Plugins Detectors
			if ( $threat = $firewallRules->detectWPDetectors() ) {

				do_action( 'hmwp_threat_detected', $threat );

				$this->firewallBlock( 'Detectors Security' );
			}

			// Check whitelist & blacklist IPs
			if ( $firewallRules->detectBanlist() ) {
				$this->firewallBlock( 'IP Banned Security' );
			}

			// Check and allow search engine bots
			/** @var HMWP_Models_Firewall_Bots $bots */
			$bots = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Firewall_Bots' );
			if ( $bots->isSearchEngineBot() ) {
				return;
			}

		} catch ( Exception $e ) {
		}

	}

	/**
	 * Check if it's valid to load firewall on the page
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function doFirewall() {

		//If a firewall process is deactivated, return false
		if ( ! apply_filters( 'hmwp_process_firewall', true ) ) {
			return false;
		}

		// If safe URL is called
		if ( HMWP_Classes_Tools::calledSafeUrl() ) {
			return false;
		}

		// If there is a preview mode on the page
		if ( HMWP_Classes_Tools::getValue( 'hmwp_preview' ) == HMWP_Classes_Tools::getOption( 'hmwp_disable_name' ) ) {
			return false;
		}


		//If always change path admin & frontend
		if ( defined( 'HMW_ALWAYS_RUN_FIREWALL' ) && HMW_ALWAYS_RUN_FIREWALL ) {
			return true;
		}

		if ( HMWP_Classes_Tools::isApi() ) {
			return false;
		}

		//If not admin but logged in
		if ( ! is_admin() && ! is_network_admin() ) {

			//if a user is not logged in
			if ( ! HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cookies' )->isLoggedInCookie() ) {
				return true;
			}

		}

		return false;
	}

	/**
	 * Detects all potential threats by executing firewall rules and threat detection mechanisms.
	 *
	 * The method checks if the firewall and/or threat logging options are enabled. If enabled, it:
	 * - Executes firewall rules.
	 * - Logs detected threats based on the configuration.
	 * - Blocks threats that meet specific security levels.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function detectAllThreats() {

		$firewallEnabled = HMWP_Classes_Tools::getOption( 'hmwp_sqlinjection' );
		$logThreats      = HMWP_Classes_Tools::getOption( 'hmwp_threats_log' );
		$level           = (int) HMWP_Classes_Tools::getOption( 'hmwp_sqlinjection_level' );

		/** @var HMWP_Models_Firewall_Rules $firewallRules */
		$firewallRules = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Firewall_Rules' );
		/** @var HMWP_Models_Firewall_Threats $firewallThreats */
		$firewallThreats = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Firewall_Threats' );

		if ( $firewallEnabled || $logThreats ) {

			if ( $fw = $firewallRules->detectRule( $level ) ) {

				if ( isset( $fw['code'] ) ){
					if ( $firewallRules->checkWhitelistRule( $fw['code'] ) ){
						return;
					}
				}

				// Hook the threat detection event
				do_action( 'hmwp_threat_detected', $fw );

				if ( $firewallEnabled ) {
					$this->firewallBlock( 'Firewall Rules Security' );
				}

			}

			if ( $logThreats || ( $firewallEnabled && $level >= 3 ) ) {

				// Run threat detector (THR_* codes)
				if ( $thr = $firewallThreats->detectThreat() ) {

					// Check if the threat rule is whitelisted
					if ( isset( $thr['code'] ) ){
						if ( $firewallRules->checkWhitelistRule( $thr['code'] ) ){
							return;
						}
					}

					// Hook the threat detection event
					do_action( 'hmwp_threat_detected', $thr );

					// Block only on L3/L4
					if ( $firewallEnabled && $level >= 3 ) {
						$this->firewallBlock( 'Threat Security' );
					}
				}
			}
		}
	}

	/**
	 * Show the error message on the firewall block
	 *
	 * @return void
	 * @throws Exception
	 */
	public function firewallBlock( $name ) {

		// Avoid showing load_textdomain_just_in_time on block page
		global $wp_actions;
		$wp_actions['after_setup_theme'] = 1;

		// Load the Multilingual support for frontend
		HMWP_Classes_Tools::loadMultilanguage();

		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
		if ( $path === '' ) {
			$path = '/';
		}

		// Trim and sanitize displayed path
		$path = wp_strip_all_tags( $path );
		if ( strlen( $path ) > 120 ) {
			$path = substr( $path, 0, 120 ) . '...';
		}

		// Set threat as prevented
		add_filter( 'hmwp_threat_prevented', '__return_true' );

		// Hook action on firewall block.
		do_action( 'hmwp_firewall_block', true );

		// Short request ID for support correlation (safe, non-sensitive)
		/** @var HMWP_Models_Firewall_Threats $threats */
		$threats = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Firewall_Threats' );
		/** @var HMWP_Models_Firewall_Server $server */
		$server = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Firewall_Server' );
		$rid    = $threats->getRid();
		$ip     = $server->getIp();

		$this->renderFirewallBlockPage( array(
			'title'      => esc_html__( 'This request was blocked for security reasons', 'hide-my-wp' ),
			'message'    => HMWP_Classes_Tools::getOption( 'hmwp_plugin_name' ) . ' ' . esc_html__( 'firewall stopped this request to protect the website from suspicious behavior.', 'hide-my-wp' ),
			'name'       => esc_html( $name ),
			'ip'         => esc_attr( $ip ),
			'path'       => esc_attr( $path ),
			'rid'        => esc_attr( $rid ),
			'statusCode' => 403,
		) );
		exit;
	}

	/**
	 * Renders a firewall block page with customizable content.
	 *
	 * @param array $args {
	 *     Optional. Arguments to customize the block page. Default values are:
	 *
	 * @type string $title The title of the block page. Default 'Access blocked'.
	 * @type string $message The message displayed on the block page. Default 'This request was blocked for security reasons'.
	 * @type string $name The name of the firewall or rule that blocked the request. Default empty string.
	 * @type string $rid The request ID associated with the blocked request. Default empty string.
	 * @type string $ip The IP address of the user making the blocked request. Default empty string.
	 * @type string $path The requested path that triggered the block. Default empty string.
	 * @type int $statusCode The HTTP status code displayed on the block page. Default 403.
	 * }
	 * @return void Shows the HTML string for the firewall block page to display to the user.
	 */
	public function renderFirewallBlockPage( $args = array() ) {

		header_remove( 'Link' );

		if ( function_exists( 'status_header' ) ) {
			status_header( 403 );
		} else {
			header( 'HTTP/1.1 403 Forbidden', true, 403 );
		}

		if ( function_exists( 'nocache_headers' ) ) {
			nocache_headers();
		}


		$defaults = array(
			'title'      => 'Access blocked',
			'message'    => 'This request was blocked for security reasons',
			'name'       => '',
			'rid'        => '',
			'ip'         => '',
			'path'       => '',
			'statusCode' => 403,
		);

		$args = wp_parse_args( $args, $defaults );
		$language   = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
		$title      = (string) $args['title'];
		$message    = (string) $args['message'];
		$name       = (string) $args['name'];
		$ip         = (string) $args['ip'];
		$path       = (string) $args['path'];
		$rid        = (string) $args['rid'];
		$statusCode = (int) $args['statusCode'];

		$out = '';

		$out .= '<!doctype html>';
		$out .= '<html lang="' . esc_attr( $language ) . '">';
		$out .= '<head>';
		$out .= '<meta charset="utf-8">';
		$out .= '<meta name="viewport" content="width=device-width, initial-scale=1">';
		$out .= '<title>' . esc_html( $title ) . '</title>';

		$out .= '<style>
		:root{color-scheme:light;}
		body{margin:0;font-family:Inter, -apple-system,BlinkMacSystemFont,Roboto,Arial,sans-serif;background:#f6f7fb;color:#1d2327;}
		.wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;}
		.card{max-width:820px;width:100%;background:#fff;border:1px solid #e5e7eb;border-radius:14px;box-shadow:0 8px 30px rgba(0,0,0,.06);overflow:hidden;}
		.head{display:flex;gap:16px;align-items:flex-start;padding:22px 22px 12px 22px;border-bottom:1px solid #eef0f3;background:linear-gradient(180deg,#ffffff 0%, #fbfbfd 100%);}
		.badge{flex:0 0 auto;width:52px;height:52px;border-radius:14px;background:#111827;display:flex;align-items:center;justify-content:center;}
		.badge svg{width:26px;height:26px;fill:#fff;opacity:.92}
		.hgroup{flex:1 1 auto;}
		h1{margin:0;font-size:20px;line-height:1.25;}
		.sub{margin:6px 0 0 0;color:#50575e;font-size:14px;line-height:1.45;}
		.body{padding:16px 22px 22px 22px;}
		.grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin:14px 0 0 0;}
		.box{border:1px solid #eef0f3;border-radius:12px;padding:12px 12px;background:#fff;}
		.k{display:block;font-size:12px;color:#6b7280;margin-bottom:6px;}
		.v{display:block;font-size:13px;color:#111827;word-break:break-word;}
		.note{margin:14px 0 0 0;color:#50575e;font-size:13px;line-height:1.5;}
		.actions{margin-top:16px;display:flex;gap:10px;flex-wrap:wrap;}
		.btn{display:inline-block;text-decoration:none;border-radius:10px;padding:10px 14px;border:1px solid #dcdcde;background:#f6f7f7;color:#1d2327;font-weight:600;font-size:13px;}
		.btn.primary{background:#111827;border-color:#111827;color:#fff;}
		.small{margin-top:14px;color:#6b7280;font-size:12px;}
		@media (max-width:720px){
			.grid{grid-template-columns:1fr;}
			.head{align-items:center}
		}
	</style>';

		$out .= '</head>';
		$out .= '<body>';
		$out .= '<div class="wrap">';
		$out .= '<div class="card">';

		$out .= '<div class="head">';
		$out .= '<div class="badge" aria-hidden="true">
		<svg viewBox="0 0 24 24" role="img" focusable="false">
			<path d="M12 2l8 4v6c0 5-3.4 9.7-8 10-4.6-.3-8-5-8-10V6l8-4zm0 4.2L6 8.9V12c0 3.8 2.4 7.3 6 7.7 3.6-.4 6-3.9 6-7.7V8.9l-6-2.7z"/>
		</svg>
	</div>';

		$out .= '<div class="hgroup">';
		$out .= '<h1>' . esc_html( $title ) . '</h1>';
		$out .= '<p class="sub">' . esc_html( $message ) . '</p>';
		$out .= '</div>';
		$out .= '</div>';

		$out .= '<div class="body">';

		$out .= '<div class="grid">';
		$out .= '<div class="box"><span class="k">' . esc_html__( 'Blocked by', 'hide-my-wp' ) . '</span><span class="v">' . esc_html( $name ) . '</span></div>';
		$out .= '<div class="box"><span class="k">' . esc_html__( 'Requested path', 'hide-my-wp' ) . '</span><span class="v">' . esc_html( $path ) . '</span></div>';
		$out .= '</div>';

		if ( $rid !== '' ) {
			$out .= '<div class="grid">';
			$out .= '<div class="box"><span class="k">' . esc_html__( 'Request ID', 'hide-my-wp' ) . '</span><span class="v">' . esc_html( $rid ) . '</span></div>';
			$out .= '<div class="box"><span class="k">' . esc_html__( 'HTTP status', 'hide-my-wp' ) . '</span><span class="v">' . esc_html( (string) $statusCode ) . '</span></div>';
			$out .= '</div>';
		}

		$out .= '<p class="note">' . esc_html__( 'If you believe this was a mistake, please contact the website owner and include the Request ID shown above.', 'hide-my-wp' ) . '</p>';

		$out .= '<div class="actions">';
		$out .= '<a class="btn primary" href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Go to homepage', 'hide-my-wp' ) . '</a>';
		$out .= '<a class="btn" href="javascript:history.back()">' . esc_html__( 'Go back', 'hide-my-wp' ) . '</a>';
		$out .= '</div>';

		if ( $ip !== '' ) {
			$out .= '<div class="small">' . esc_html__( 'IP detected', 'hide-my-wp' ) . ' ' . esc_html( $ip ) . '</div>';
		}

		$out .= '</div>'; // body
		$out .= '</div>'; // card
		$out .= '</div>'; // wrap
		$out .= '</body>';
		$out .= '</html>';

		// Allow final HTML filtering (for small edits without replacing full template)
		$out = apply_filters( 'hmwp_firewall_block_template', $out, $args );

		echo $out; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

}
