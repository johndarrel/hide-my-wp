<?php
/**
 * Overview Class
 * Called on plugin overview
 *
 * @file The Settings Overview
 * @package HMWP/Overview
 * @since 6.0.0
 */

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Controllers_Overview extends HMWP_Classes_FrontController {

	/**
	 * Called on Menu hook
	 * Init the Settings page
	 *
	 * @return void
	 * @throws Exception
	 */
	public function init() {


		//Load the CSS for Settings
		HMWP_Classes_ObjController::getClass( 'HMWP_Classes_DisplayController' )->loadMedia( 'popper' );

		if ( is_rtl() ) {
			HMWP_Classes_ObjController::getClass( 'HMWP_Classes_DisplayController' )->loadMedia( 'bootstrap.rtl' );
			HMWP_Classes_ObjController::getClass( 'HMWP_Classes_DisplayController' )->loadMedia( 'rtl' );
		} else {
			HMWP_Classes_ObjController::getClass( 'HMWP_Classes_DisplayController' )->loadMedia( 'bootstrap' );
		}

		HMWP_Classes_ObjController::getClass( 'HMWP_Classes_DisplayController' )->loadMedia( 'font-awesome' );
		HMWP_Classes_ObjController::getClass( 'HMWP_Classes_DisplayController' )->loadMedia( 'switchery' );
		HMWP_Classes_ObjController::getClass( 'HMWP_Classes_DisplayController' )->loadMedia( 'alert' );
		HMWP_Classes_ObjController::getClass( 'HMWP_Classes_DisplayController' )->loadMedia( 'settings' );

		//Show connection for activation
		if ( ! HMWP_Classes_Tools::getOption( 'hmwp_token' ) ) {
			$this->show( 'Connect' );

			return;
		}

		wp_enqueue_script( 'postbox' );
		wp_enqueue_script( 'common' );
		wp_enqueue_script( 'wp-lists' );


		// Check compatibilities with other plugins
		HMWP_Classes_ObjController::getClass( 'HMWP_Models_Compatibility' )->getAlerts();

		// Show errors on top
		HMWP_Classes_ObjController::getClass( 'HMWP_Classes_Error' )->hookNotices();

		//Show connection for activation
		/* translators: 1: Plugin name. */
		echo '<noscript><div class="alert-danger text-center py-3">' . sprintf( esc_html__( 'Javascript is disabled on your browser! You need to activate the javascript in order to use %1$s plugin.', 'hide-my-wp' ), esc_html( HMWP_Classes_Tools::getOption( 'hmwp_plugin_name' ) ) ) . '</div></noscript>';
		$this->show( 'Overview' );

	}

	/**
	 * Retrieves an array of features offered by the application, including details
	 * such as title, description, status, and configuration options.
	 *
	 * @return array Returns a list of features where each feature contains properties
	 *               such as title, description, activation status, configuration links,
	 *               and additional details to help users understand the functionality
	 *               and purpose of each feature.
	 * @return array
	 */
	public function getFeatures() {
		$features = array(
			array(
				'title'       => esc_html__( "Secure WP Paths", 'hide-my-wp' ),
				'description' => esc_html__( "Customize & Secure all WordPress paths (login, core, plugins, themes) so hacker bots can’t find your entry points or exploit known vulnerabilities.", 'hide-my-wp' ),
				'free'        => true,
				'option'      => false,
				'active'      => ( HMWP_Classes_Tools::getOption( 'hmwp_mode' ) <> 'default' ),
				'optional'    => false,
				'connection'  => false,
				'logo'        => 'dashicons-before dashicons-shield-alt',
				'link'        => HMWP_Classes_Tools::getSettingsUrl( 'hmwp_permalinks', true ),
				'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/set-up-wp-ghost-in-safe-mode-in-3-minutes/',
				'show'        => true,
			),
            array(
                'title'       => esc_html__( "Hide WP Common Paths", 'hide-my-wp' ),
                'description' => esc_html__( "Hide default WordPress paths like /wp-content and /wp-includes so automated hacker bots can’t detect your site structure.", 'hide-my-wp' ),
                'free'        => true,
                'option'      => 'hmwp_hide_oldpaths',
                'active'      => HMWP_Classes_Tools::getOption( 'hmwp_hide_oldpaths' ),
                'optional'    => ( HMWP_Classes_Tools::getOption( 'hmwp_mode' ) <> 'default' ),
                'connection'  => false,
                'logo'        => 'fa fa-file-word-o',
                'link'        => HMWP_Classes_Tools::getSettingsUrl( 'hmwp_permalinks&tab=core', true ),
                'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/hide-wordpress-common-paths-and-files/#ghost-hide-common-paths',
                'show'        => true,
            ),
            array(
                'title'       => esc_html__( "Hide WP Common Files", 'hide-my-wp' ),
                'description' => esc_html__( "Block access to sensitive files (wp-config.php, readme, install files) that attackers use to gather information about your site.", 'hide-my-wp' ),
                'free'        => true,
                'option'      => 'hmwp_hide_commonfiles',
                'active'      => HMWP_Classes_Tools::getOption( 'hmwp_hide_commonfiles' ),
                'optional'    => ( HMWP_Classes_Tools::getOption( 'hmwp_mode' ) <> 'default' ),
                'connection'  => false,
                'logo'        => 'fa fa-file-word-o',
                'link'        => HMWP_Classes_Tools::getSettingsUrl( 'hmwp_permalinks&tab=core', true ),
                'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/hide-wordpress-common-paths-and-files/#ghost-hide-common-files',
                'show'        => true,
            ),
			//--
            array(
                'title'       => esc_html__( "2FA", 'hide-my-wp' ),
                'description' => esc_html__( "Add a second verification step via authenticator app, email code, or passkey (Face ID, Touch ID), so a stolen password alone can't unlock your site.", 'hide-my-wp' ),
                'free'        => true,
                'option'      => 'hmwp_2falogin',
                'active'      => HMWP_Classes_Tools::getOption( 'hmwp_2falogin' ),
                'optional'    => true,
                'connection'  => false,
                'logo'        => 'fa fa-window-maximize',
                'link'        => HMWP_Classes_Tools::getSettingsUrl( 'hmwp_twofactor', true ),
                'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/two-factor-authentication/',
                'show'        => true,
            ),

            array(
                'title'       => esc_html__( "Brute Force Protection", 'hide-my-wp' ),
                'description' => esc_html__( "Stop repeated login attempts and block bots trying to guess usernames and passwords.", 'hide-my-wp' ),
                'free'        => true,
                'option'      => 'hmwp_bruteforce',
                'active'      => HMWP_Classes_Tools::getOption( 'hmwp_bruteforce' ),
                'optional'    => true,
                'connection'  => false,
                'logo'        => 'fa fa-ban',
                'link'        => HMWP_Classes_Tools::getSettingsUrl( 'hmwp_brute&tab=brute', true ),
                'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/brute-force-attack-protection/',
                'show'        => true,
            ),
            array(
                'title'       => esc_html__( "WooCommerce Safe Login", 'hide-my-wp' ),
                'description' => esc_html__( "Protect customer accounts from brute force attacks and credential stuffing.", 'hide-my-wp' ),
                'free'        => true,
                'option'      => 'hmwp_bruteforce_woocommerce',
                'active'      => HMWP_Classes_Tools::getOption( 'hmwp_bruteforce_woocommerce' ),
                'optional'    => true,
                'connection'  => false,
                'logo'        => 'fa fa-ban',
                'link'        => HMWP_Classes_Tools::getSettingsUrl( 'hmwp_brute&tab=brute', true ),
                'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/brute-force-attack-protection/#ghost-protected-forms',
                'show'        => ( HMWP_Classes_Tools::isPluginActive( 'woocommerce/woocommerce.php' ) && HMWP_Classes_Tools::getOption( 'hmwp_bruteforce' ) ),
            ),

            array(
                'title'       => esc_html__( "Firewall", 'hide-my-wp' ),
                'description' => esc_html__( "Block malicious requests, SQL injections, and exploit attempts before they reach your website.", 'hide-my-wp' ),
                'free'        => true,
                'option'      => 'hmwp_sqlinjection',
                'active'      => HMWP_Classes_Tools::getOption( 'hmwp_sqlinjection' ),
                'optional'    => true,
                'connection'  => false,
                'logo'        => 'dashicons dashicons-privacy',
                'link'        => HMWP_Classes_Tools::getSettingsUrl( 'hmwp_firewall', true ),
                'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/firewall-security/',
                'show'        => true,
            ),

            array(
                'title'       => esc_html__( "Country Blocking", 'hide-my-wp' ),
                'description' => esc_html__( "Block traffic from high-risk countries to reduce hacking attempts, spam, and unwanted access.", 'hide-my-wp' ),
                'free'        => false,
                'option'      => 'hmwp_geoblock',
                'active'      => HMWP_Classes_Tools::getOption( 'hmwp_geoblock' ),
                'optional'    => true,
                'connection'  => false,
                'logo'        => 'fa fa-globe',
                'link'        => HMWP_Classes_Tools::getSettingsUrl( 'hmwp_firewall&tab=geoblock', true ),
                'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/geo-security-country-blocking/',
                'show'        => true,
            ),
            array(
                'title'       => esc_html__( "Temporary Logins", 'hide-my-wp' ),
                'description' => esc_html__( "Create secure, time-limited access links without sharing usernames or passwords for a limited period of time.", 'hide-my-wp' ),
                'free'        => true,
                'option'      => 'hmwp_templogin',
                'active'      => HMWP_Classes_Tools::getOption( 'hmwp_templogin' ),
                'optional'    => true,
                'connection'  => false,
                'logo'        => 'fa fa-clock-o',
                'link'        => HMWP_Classes_Tools::getSettingsUrl( 'hmwp_templogin&tab=logins', true ),
                'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/temporary-logins/',
                'show'        => true,
            ),
            array(
                'title'       => esc_html__( "Magic Link Login", 'hide-my-wp' ),
                'description' => esc_html__( "Allow passwordless login using a secure link sent by email, reducing the risk of stolen credentials.", 'hide-my-wp' ),
                'free'        => true,
                'option'      => 'hmwp_uniquelogin',
                'active'      => HMWP_Classes_Tools::getOption( 'hmwp_uniquelogin' ),
                'optional'    => true,
                'connection'  => false,
                'logo'        => 'fa fa-link',
                'link'        => HMWP_Classes_Tools::getSettingsUrl( 'hmwp_permalinks&tab=newlogin#uniquelogin', true ),
                'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/magic-link-login/',
                'show'        => true,
            ),
			array(
				'title'       => esc_html__( "Login Page Design", 'hide-my-wp' ) . ' <span class="new_badge">New</span>',
				'description' => esc_html__( "Customize your login page design while keeping it aligned with your secured login path and branding.", 'hide-my-wp' ),
				'free'        => true,
				'option'      => 'hmwp_login_page',
				'active'      => HMWP_Classes_Tools::getOption( 'hmwp_login_page' ),
				'optional'    => true,
				'connection'  => false,
				'logo'        => 'fa fa-paint-brush',
				'link'        => HMWP_Classes_Tools::getSettingsUrl( 'hmwp_tweaks&tab=login_design', true ),
				'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/login-page-design-customization/',
				'show'        => true,
			),

			array(
				'title'       => esc_html__( "Text Mapping", 'hide-my-wp' ),
				'description' => esc_html__( "Replace WordPress-specific class names and IDs in your page source so scanners can't fingerprint your site as WordPress.", 'hide-my-wp' ),
				'free'        => true,
				'option'      => 'hmwp_mapping_text_show',
				'active'      => HMWP_Classes_Tools::getOption( 'hmwp_mapping_text_show' ),
				'optional'    => true,
				'connection'  => false,
				'logo'        => 'fa fa-eye-slash',
				'link'        => HMWP_Classes_Tools::getSettingsUrl( 'hmwp_mapping&tab=text', true ),
				'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/text-mapping/',
				'show'        => true,
			),
			array(
				'title'       => esc_html__( "URL Mapping", 'hide-my-wp' ),
				'description' => esc_html__( "Rewrite CSS and JS file URLs to custom paths, removing default WordPress signatures that bots use to identify your setup.", 'hide-my-wp' ),
				'free'        => true,
				'option'      => 'hmwp_mapping_url_show',
				'active'      => HMWP_Classes_Tools::getOption( 'hmwp_mapping_url_show' ),
				'optional'    => true,
				'connection'  => false,
				'logo'        => 'fa fa-refresh',
				'link'        => HMWP_Classes_Tools::getSettingsUrl( 'hmwp_mapping&tab=url', true ),
				'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/url-mapping/',
				'show'        => ! HMWP_Classes_Tools::isWpengine(),
			),
			array(
				'title'       => esc_html__( "CDN Mapping", 'hide-my-wp' ),
				'description' => esc_html__( "Load CSS, JS, and images from a CDN or custom domain while keeping your real server paths invisible.", 'hide-my-wp' ),
				'free'        => true,
				'option'      => 'hmwp_mapping_cdn_show',
				'active'      => HMWP_Classes_Tools::getOption( 'hmwp_mapping_cdn_show' ),
				'optional'    => true,
				'connection'  => false,
				'logo'        => 'fa fa-link',
				'link'        => HMWP_Classes_Tools::getSettingsUrl( 'hmwp_mapping&tab=cdn', true ),
				'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/cdn-url-mapping/',
				'show'        => true,
			),
			array(
				'title'       => esc_html__( "Security Threats Log", 'hide-my-wp' ) . ' <span class="new_badge">New</span>',
				'description' => esc_html__( "See real attacks blocked on your site, including brute force attempts, injections, and bot scans.", 'hide-my-wp' ),
				'free'        => true,
				'option'      => 'hmwp_threats_log',
				'active'      => HMWP_Classes_Tools::getOption( 'hmwp_threats_log' ),
				'optional'    => true,
				'connection'  => false,
				'logo'        => 'dashicons dashicons-privacy',
				'link'        => HMWP_Classes_Tools::getSettingsUrl( 'hmwp_log&tab=threats', true ),
				'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/security-threats-log/',
				'show'        => true,
			),
			array(
				'title'       => esc_html__( "Automate IP Blocking", 'hide-my-wp' ) . ' <span class="new_badge">New</span>',
				'description' => esc_html__( "Automatically block IPs that trigger repeated attacks, stopping threats without manual intervention.", 'hide-my-wp' ),
				'free'        => false,
				'option'      => 'hmwp_threats_auto',
				'active'      => HMWP_Classes_Tools::getOption( 'hmwp_threats_auto' ),
				'optional'    => true,
				'connection'  => false,
				'logo'        => 'dashicons dashicons-superhero',
				'link'        => HMWP_Classes_Tools::getSettingsUrl( 'hmwp_firewall&tab=firewall', true ),
				'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/firewall-security/#ghost-automate-ip-blocking',
				'show'        => true,
			),
			array(
				'title'       => esc_html__( "User Events Log", 'hide-my-wp' ),
				'description' => esc_html__( "Log user logins, content edits, plugin changes, and role updates so you can spot suspicious behavior and audit site activity.", 'hide-my-wp' ),
				'free'        => true,
				'option'      => 'hmwp_activity_log',
				'active'      => HMWP_Classes_Tools::getOption( 'hmwp_activity_log' ),
				'optional'    => true,
				'connection'  => true,
				'logo'        => 'fa fa-calendar',
				'link'        => HMWP_Classes_Tools::getSettingsUrl( 'hmwp_log&tab=events', true ),
				'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/events-log-report/',
				'show'        => true,
			),
			array(
				'title'       => esc_html__( "Login & Logout Redirects", 'hide-my-wp' ),
				'description' => esc_html__( "Redirect users to custom pages after login or logout based on their role. e.g., send editors to the dashboard and customers to their account.", 'hide-my-wp' ),
				'free'        => true,
				'option'      => 'hmwp_do_redirects',
				'active'      => HMWP_Classes_Tools::getOption( 'hmwp_do_redirects' ),
				'optional'    => true,
				'connection'  => false,
				'logo'        => 'fa fa-code-fork',
				'link'        => HMWP_Classes_Tools::getSettingsUrl( 'hmwp_tweaks&tab=redirects', true ),
				'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/redirects/#ghost-login-logout-redirects',
				'show'        => true,
			),

			array(
				'title'       => esc_html__( "Header Security", 'hide-my-wp' ),
				'description' => esc_html__( "Add security headers to protect against XSS, clickjacking, and code injection attacks.", 'hide-my-wp' ),
				'free'        => true,
				'option'      => 'hmwp_security_header',
				'active'      => HMWP_Classes_Tools::getOption( 'hmwp_security_header' ),
				'optional'    => true,
				'connection'  => false,
				'logo'        => 'fa fa-code',
				'link'        => HMWP_Classes_Tools::getSettingsUrl( 'hmwp_firewall&tab=header', true ),
				'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/header-security/',
				'show'        => true,
			),
			array(
				'title'       => esc_html__( "Feed Security", 'hide-my-wp' ),
				'description' => esc_html__( "Replace default WordPress paths inside RSS feeds so bots crawling your feed can't map your site structure.", 'hide-my-wp' ),
				'free'        => true,
				'option'      => 'hmwp_hide_in_feed',
				'active'      => HMWP_Classes_Tools::getOption( 'hmwp_hide_in_feed' ),
				'optional'    => true,
				'connection'  => false,
				'logo'        => 'fa fa-sitemap',
				'link'        => HMWP_Classes_Tools::getSettingsUrl( 'hmwp_tweaks&tab=sitemap', true ),
				'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/sitemap-feed-and-robots/#ghost-change-rss-feed',
				'show'        => true,
			),
			array(
				'title'       => esc_html__( "Sitemap Security", 'hide-my-wp' ),
				'description' => esc_html__( "Replace WordPress paths in your XML sitemap so attackers scanning it can't identify your plugins, themes, or directory structure.", 'hide-my-wp' ),
				'free'        => true,
				'option'      => 'hmwp_hide_in_sitemap',
				'active'      => HMWP_Classes_Tools::getOption( 'hmwp_hide_in_sitemap' ),
				'optional'    => true,
				'connection'  => false,
				'logo'        => 'fa fa-sitemap',
				'link'        => HMWP_Classes_Tools::getSettingsUrl( 'hmwp_tweaks&tab=sitemap', true ),
				'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/sitemap-feed-and-robots/#ghost-change-sitemap',
				'show'        => true,
			),
			array(
				'title'       => esc_html__( "Robots Security", 'hide-my-wp' ),
				'description' => esc_html__( "Clean up robots.txt so it doesn't accidentally reveal WordPress paths like /wp-admin or /wp-content to scanners.", 'hide-my-wp' ),
				'free'        => true,
				'option'      => 'hmwp_robots',
				'active'      => HMWP_Classes_Tools::getOption( 'hmwp_robots' ),
				'optional'    => true,
				'connection'  => false,
				'logo'        => 'fa fa-android',
				'link'        => HMWP_Classes_Tools::getSettingsUrl( 'hmwp_tweaks&tab=changes', true ),
				'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/sitemap-feed-and-robots/#ghost-change-robots',
				'show'        => true,
			),
			array(
				'title'       => esc_html__( "Admin Toolbar", 'hide-my-wp' ),
				'description' => esc_html__( "Hide the WordPress admin toolbar for specific user roles to keep the backend less visible to non-admins.", 'hide-my-wp' ),
				'free'        => true,
				'option'      => 'hmwp_hide_admin_toolbar',
				'active'      => HMWP_Classes_Tools::getOption( 'hmwp_hide_admin_toolbar' ),
				'optional'    => true,
				'connection'  => false,
				'logo'        => 'fa fa-window-maximize',
				'link'        => HMWP_Classes_Tools::getSettingsUrl( 'hmwp_tweaks&tab=hide', true ),
				'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/hide-admin-toolbar/',
				'show'        => true,
			),
			array(
				'title'       => esc_html__( "Disable Right-Click", 'hide-my-wp' ),
				'description' => esc_html__( "Disable right-click context menus to discourage casual content theft and source code inspection.", 'hide-my-wp' ),
				'free'        => true,
				'option'      => 'hmwp_disable_click',
				'active'      => HMWP_Classes_Tools::getOption( 'hmwp_disable_click' ),
				'optional'    => true,
				'connection'  => false,
				'logo'        => 'fa fa-mouse-pointer',
				'link'        => HMWP_Classes_Tools::getSettingsUrl( 'hmwp_tweaks&tab=disable', true ),
				'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/disable-right-click-and-keys/#ghost-disable-right-click',
				'show'        => true,
			),
			array(
				'title'       => esc_html__( "Disable Copy/Paste", 'hide-my-wp' ),
				'description' => esc_html__( "Block copy, paste, and drag actions to make it harder for visitors to scrape your content.", 'hide-my-wp' ),
				'free'        => true,
				'option'      => 'hmwp_disable_copy_paste',
				'active'      => HMWP_Classes_Tools::getOption( 'hmwp_disable_copy_paste' ),
				'optional'    => true,
				'connection'  => false,
				'logo'        => 'fa fa-keyboard-o',
				'link'        => HMWP_Classes_Tools::getSettingsUrl( 'hmwp_tweaks&tab=disable', true ),
				'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/disable-right-click-and-keys/#ghost-disable-copy-paste',
				'show'        => true,
			),

			//--
			array(
				'title'       => esc_html__( "Wordfence Security", 'hide-my-wp' ),
				'description' => esc_html__( "Compatible with Wordfence Security plugin. Use them together for Malware Scan, Firewall, Brute Force protection.", 'hide-my-wp' ),
				'free'        => true,
				'option'      => false,
				'active'      => true,
				'optional'    => false,
				'connection'  => false,
				'logo'        => 'dashicons-before dashicons-shield-alt',
				'link'        => false,
				'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/wp-ghost-and-wordfence-security/',
				'show'        => HMWP_Classes_Tools::isPluginActive( 'wordfence/wordfence.php' ),
			),
			array(
				'title'       => esc_html__( "All In One WP Security", 'hide-my-wp' ),
				'description' => esc_html__( "Compatible with All In One WP Security plugin. Use them together for Virus Scan, Firewall, Brute Force protection.", 'hide-my-wp' ),
				'free'        => true,
				'option'      => false,
				'active'      => true,
				'optional'    => false,
				'connection'  => false,
				'logo'        => 'dashicons-before dashicons-shield-alt',
				'link'        => false,
				'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/wp-ghost-compatibility-plugins-list/',
				'show'        => HMWP_Classes_Tools::isPluginActive( 'all-in-one-wp-security-and-firewall/wp-security.php' ),
			),
			array(
				'title'       => esc_html__( "Sucuri Security", 'hide-my-wp' ),
				'description' => esc_html__( "Compatible with Sucuri Security plugin. Use them together for Virus Scan, Firewall, File Integrity Monitoring.", 'hide-my-wp' ),
				'free'        => true,
				'option'      => false,
				'active'      => true,
				'optional'    => false,
				'connection'  => false,
				'logo'        => 'dashicons-before dashicons-shield-alt',
				'link'        => false,
				'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/wp-ghost-and-sucuri-security/',
				'show'        => HMWP_Classes_Tools::isPluginActive( 'sucuri-scanner/sucuri.php' ),
			),
			array(
				'title'       => esc_html__( "Solid Security", 'hide-my-wp' ),
				'description' => esc_html__( "Compatible with Solid Security plugin. Use them together for Site Scanner, File Change Detection, Brute Force Protection.", 'hide-my-wp' ),
				'free'        => true,
				'option'      => false,
				'active'      => true,
				'optional'    => false,
				'connection'  => false,
				'logo'        => 'dashicons-before dashicons-shield-alt',
				'link'        => false,
				'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/wp-ghost-and-solid-security/',
				'show'        => HMWP_Classes_Tools::isPluginActive( 'better-wp-security/better-wp-security.php' ),
			),
			//--
			array(
				'title'       => esc_html__( "Autoptimize", 'hide-my-wp' ),
				'description' => esc_html__( "Fully compatible with Autoptimizer cache plugin. Works best with the the option Optimize/Aggregate CSS and JS files.", 'hide-my-wp' ),
				'free'        => true,
				'option'      => false,
				'active'      => true,
				'optional'    => false,
				'connection'  => false,
				'logo'        => 'dashicons-before dashicons-admin-plugins',
				'link'        => false,
				'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/wp-ghost-and-autoptimize-cache/',
				'show'        => HMWP_Classes_Tools::isPluginActive( 'autoptimize/autoptimize.php' ),
			),
			array(
				'title'       => esc_html__( "Hummingbird", 'hide-my-wp' ),
				'description' => esc_html__( "Fully compatible with Hummingbird cache plugin. Works best with the the option Minify CSS and JS files.", 'hide-my-wp' ),
				'free'        => true,
				'option'      => false,
				'active'      => true,
				'optional'    => false,
				'connection'  => false,
				'logo'        => 'dashicons-before dashicons-admin-plugins',
				'link'        => false,
				'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/wp-ghost-and-hummingbird-cache-plugin/',
				'show'        => HMWP_Classes_Tools::isPluginActive( 'hummingbird-performance/wp-hummingbird.php' ),
			),
			array(
				'title'       => esc_html__( "WP Super Cache", 'hide-my-wp' ),
				'description' => esc_html__( "Fully compatible with WP Super Cache cache plugin.", 'hide-my-wp' ),
				'free'        => true,
				'option'      => false,
				'active'      => true,
				'optional'    => false,
				'connection'  => false,
				'logo'        => 'dashicons-before dashicons-admin-plugins',
				'link'        => false,
				'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/wp-ghost-compatibility-plugins-list/',
				'show'        => HMWP_Classes_Tools::isPluginActive( 'wp-super-cache/wp-cache.php' ),
			),
			array(
				'title'       => esc_html__( "Cache Enabler", 'hide-my-wp' ),
				'description' => esc_html__( "Fully compatible with Cache Enabler plugin. Works best with the the option Minify CSS and JS files.", 'hide-my-wp' ),
				'free'        => true,
				'option'      => false,
				'active'      => true,
				'optional'    => false,
				'connection'  => false,
				'logo'        => 'dashicons-before dashicons-admin-plugins',
				'link'        => false,
				'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/wp-ghost-compatibility-plugins-list/',
				'show'        => HMWP_Classes_Tools::isPluginActive( 'cache-enabler/cache-enabler.php' ),
			),
			array(
				'title'       => esc_html__( "WP Rocket", 'hide-my-wp' ),
				'description' => esc_html__( "Fully compatible with WP-Rocket cache plugin. Works best with the the option Minify/Combine CSS and JS files.", 'hide-my-wp' ),
				'free'        => true,
				'option'      => false,
				'active'      => true,
				'optional'    => false,
				'connection'  => false,
				'logo'        => 'dashicons-before dashicons-admin-plugins',
				'link'        => false,
				'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/wp-ghost-and-wp-rocket-cache/',
				'show'        => HMWP_Classes_Tools::isPluginActive( 'wp-rocket/wp-rocket.php' ),
			),
			array(
				'title'       => esc_html__( "WP Fastest Cache", 'hide-my-wp' ),
				'description' => esc_html__( "Fully compatible with WP Fastest Cache plugin. Works best with the the option Minify CSS and JS files.", 'hide-my-wp' ),
				'free'        => true,
				'option'      => false,
				'active'      => true,
				'optional'    => false,
				'connection'  => false,
				'logo'        => 'dashicons-before dashicons-admin-plugins',
				'link'        => false,
				'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/wp-ghost-compatibility-plugins-list/',
				'show'        => HMWP_Classes_Tools::isPluginActive( 'wp-fastest-cache/wpFastestCache.php' ),
			),
			array(
				'title'       => esc_html__( "W3 Total Cache", 'hide-my-wp' ),
				'description' => esc_html__( "Fully compatible with W3 Total Cache plugin. Works best with the the option Minify CSS and JS files.", 'hide-my-wp' ),
				'free'        => true,
				'option'      => false,
				'active'      => true,
				'optional'    => false,
				'connection'  => false,
				'logo'        => 'dashicons-before dashicons-admin-plugins',
				'link'        => false,
				'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/wp-ghost-compatibility-plugins-list/',
				'show'        => HMWP_Classes_Tools::isPluginActive( 'w3-total-cache/w3-total-cache.php' ),
			),
			array(
				'title'       => esc_html__( "LiteSpeed Cache", 'hide-my-wp' ),
				'description' => esc_html__( "Fully compatible with LiteSpeed Cache plugin. Works best with the the option Minify CSS and JS files.", 'hide-my-wp' ),
				'free'        => true,
				'option'      => false,
				'active'      => true,
				'optional'    => false,
				'connection'  => false,
				'logo'        => 'dashicons-before dashicons-admin-plugins',
				'link'        => false,
				'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/wp-ghost-and-litespeed-cache/',
				'show'        => HMWP_Classes_Tools::isPluginActive( 'litespeed-cache/litespeed-cache.php' ),
			),
			array(
				'title'       => esc_html__( "JCH Optimize Cache", 'hide-my-wp' ),
				'description' => esc_html__( "Compatible with JCH Optimize Cache plugin. Works with all the options to optimize for CSS and JS.", 'hide-my-wp' ),
				'free'        => true,
				'option'      => false,
				'active'      => true,
				'optional'    => false,
				'connection'  => false,
				'logo'        => 'dashicons-before dashicons-admin-plugins',
				'link'        => false,
				'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/wp-ghost-compatibility-plugins-list/',
				'show'        => false,
			),
			//--
			array(
				'title'       => esc_html__( "WooCommerce", 'hide-my-wp' ),
				'description' => esc_html__( "Fully compatible with WooCommerce plugin.", 'hide-my-wp' ),
				'free'        => true,
				'option'      => false,
				'active'      => true,
				'optional'    => false,
				'connection'  => false,
				'logo'        => 'dashicons-before dashicons-admin-plugins',
				'link'        => false,
				'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/wp-ghost-compatibility-plugins-list/',
				'show'        => HMWP_Classes_Tools::isPluginActive( 'woocommerce/woocommerce.php' ),
			),
			array(
				'title'       => esc_html__( "Elementor", 'hide-my-wp' ),
				'description' => esc_html__( "Fully compatible with Elementor Website Builder plugin. Works best together with a cache plugin", 'hide-my-wp' ),
				'free'        => true,
				'option'      => false,
				'active'      => true,
				'optional'    => false,
				'connection'  => false,
				'logo'        => 'dashicons-before dashicons-admin-plugins',
				'link'        => false,
				'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/wp-ghost-compatibility-plugins-list/',
				'show'        => HMWP_Classes_Tools::isPluginActive( 'elementor/elementor.php' ),
			),
			array(
				'title'       => esc_html__( "Oxygen", 'hide-my-wp' ),
				'description' => esc_html__( "Fully compatible with Oxygen Builder plugin. Works best together with a cache plugin.", 'hide-my-wp' ),
				'free'        => true,
				'option'      => false,
				'active'      => true,
				'optional'    => false,
				'connection'  => false,
				'logo'        => 'dashicons-before dashicons-admin-plugins',
				'link'        => false,
				'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/wp-ghost-compatibility-plugins-list/',
				'show'        => HMWP_Classes_Tools::isPluginActive( 'oxygen/functions.php' ),
			),
			array(
				'title'       => esc_html__( "Beaver Builder", 'hide-my-wp' ),
				'description' => esc_html__( "Fully compatible with Beaver Builder plugin. Works best together with a cache plugin.", 'hide-my-wp' ),
				'free'        => true,
				'option'      => false,
				'active'      => true,
				'optional'    => false,
				'connection'  => false,
				'logo'        => 'dashicons-before dashicons-admin-plugins',
				'link'        => false,
				'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/wp-ghost-compatibility-plugins-list/',
				'show'        => false,
			),
			array(
				'title'       => esc_html__( "WPBakery Page Builder", 'hide-my-wp' ),
				'description' => esc_html__( "Fully compatible with Beaver Builder plugin. Works best together with a cache plugin.", 'hide-my-wp' ),
				'free'        => true,
				'option'      => false,
				'active'      => true,
				'optional'    => false,
				'connection'  => false,
				'logo'        => 'dashicons-before dashicons-admin-plugins',
				'link'        => false,
				'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/wp-ghost-compatibility-plugins-list/',
				'show'        => ( HMWP_Classes_Tools::isPluginActive( 'beaver-builder-lite-version/fl-builder.php' ) || HMWP_Classes_Tools::isPluginActive( 'beaver-builder/fl-builder.php' ) ),
			),
			array(
				'title'       => esc_html__( "Fusion Builder", 'hide-my-wp' ),
				'description' => esc_html__( "Fully compatible with Fusion Builder plugin by Avada. Works best together with a cache plugin.", 'hide-my-wp' ),
				'free'        => true,
				'option'      => false,
				'active'      => true,
				'optional'    => false,
				'connection'  => false,
				'logo'        => 'dashicons-before dashicons-admin-plugins',
				'link'        => false,
				'details'     => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/wp-ghost-compatibility-plugins-list/',
				'show'        => HMWP_Classes_Tools::isPluginActive( 'fusion-builder/fusion-builder.php' ),
			),

		);

		//for PHP 7.3.1 version
		$features = array_filter( $features );

		return apply_filters( 'hmwp_features', $features );
	}

	/**
	 * Called when an action is triggered
	 *
	 * @throws Exception
	 */
	public function action() {
		parent::action();

		// Check if the current user has the 'hmwp_manage_settings' capability
		if ( ! HMWP_Classes_Tools::userCan( HMWP_CAPABILITY ) ) {
			return;
		}

		//Save the settings
		if ( HMWP_Classes_Tools::getValue( 'action' ) == 'hmwp_feature_save' ) {
			if ( ! empty( $_POST ) ) { //phpcs:ignore
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Settings' )->saveValues( $_POST ); //phpcs:ignore

				if(HMWP_Classes_Tools::getIsset( 'hmwp_hide_oldpaths') ||
				   HMWP_Classes_Tools::getIsset( 'hmwp_hide_commonfiles') ||
				   HMWP_Classes_Tools::getIsset( 'hmwp_sqlinjection') ||
				   HMWP_Classes_Tools::getIsset( 'hmwp_disable_xmlrpc') ||
				   HMWP_Classes_Tools::getIsset( 'hmwp_mapping_text_show') ||
				   HMWP_Classes_Tools::getIsset( 'hmwp_mapping_url_show')
				){
					HMWP_Classes_ObjController::getClass( 'HMWP_Models_Settings' )->saveRules();
					HMWP_Classes_ObjController::getClass( 'HMWP_Models_Settings' )->applyPermalinksChanged( true );
				}

			}

			wp_send_json_success( esc_html__( 'Saved', 'hide-my-wp' ) );
		}
	}
}
