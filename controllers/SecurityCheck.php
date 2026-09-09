<?php
/**
 * Security Check Class
 * Called on Security Check process
 *
 * @file The Security Check file
 * @package HMWP/Scan
 * @since 5.0.1
 */

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Controllers_SecurityCheck extends HMWP_Classes_FrontController {
	/**
	 * The time when the security check was made
	 *
	 * @var bool|int Security check time
	 */
	public $securitycheck_time = false;
	/**
	 * All the tasks from the security check
	 *
	 * @var array Security Report
	 */
	public $report = array();
	public $risktasks = array();
	public $riskreport = array();

	/**
	 * Set private variable as null
	 *
	 * @var null
	 */
	private $html = null;
	private $headers = null;
	private $htmlerror = null;

	/**
	 * Initialize the Security Check
	 *
	 * @return void
	 * @throws Exception
	 */
	public function init() {

		// If it's not the Security Check, return
		if ( HMWP_Classes_Tools::getValue( 'page' ) <> 'hmwp_securitycheck' ) {
			return;
		}

		// Initiate security
		$this->initSecurity();

		// Add the Menu Tabs in variable
		if ( is_rtl() ) {
			HMWP_Classes_ObjController::getClass( 'HMWP_Classes_DisplayController' )->loadMedia( 'bootstrap.rtl' );
			HMWP_Classes_ObjController::getClass( 'HMWP_Classes_DisplayController' )->loadMedia( 'rtl' );
		} else {
			HMWP_Classes_ObjController::getClass( 'HMWP_Classes_DisplayController' )->loadMedia( 'bootstrap' );
		}

		HMWP_Classes_ObjController::getClass( 'HMWP_Classes_DisplayController' )->loadMedia( 'font-awesome' );
		HMWP_Classes_ObjController::getClass( 'HMWP_Classes_DisplayController' )->loadMedia( 'settings' );
		HMWP_Classes_ObjController::getClass( 'HMWP_Classes_DisplayController' )->loadMedia( 'alert' );
		// The toggle styles live in switchery.css. Without it the switches on this
		// page render as bare text.
		HMWP_Classes_ObjController::getClass( 'HMWP_Classes_DisplayController' )->loadMedia( 'switchery' );

		// The panels on this screen are WordPress postboxes, so they need the core
		// scripts that make the collapse arrows work and remember their state.
		wp_enqueue_script( 'postbox' );
		wp_enqueue_script( 'common' );
		wp_enqueue_script( 'wp-lists' );

		if ( HMWP_Classes_Tools::getOption( 'hmwp_security_alert' ) ) {
			if ( $this->securitycheck_time = get_option( HMWP_SECURITY_CHECK_TIME ) ) {
				if ( time() - $this->securitycheck_time['timestamp'] > ( 3600 * 24 * 7 ) ) {
					HMWP_Classes_Error::setNotification( esc_html__( 'You should check your website every week to see if there are any security changes.', 'hide-my-wp' ) );
				}
			}
		}

		// Show source code analysed on Security Check
		if ( HMWP_Classes_Tools::getValue( 'hmwp_crawled' ) ) {
			HMWP_Classes_Error::setNotification( '<pre>' . htmlentities( $this->getSourceCode() ) . '</pre>' );
		}

		// Show connect for activation
		if ( ! HMWP_Classes_Tools::getOption( 'hmwp_token' ) ) {
			$this->show( 'Connect' );

			return;
		}

		$this->risktasks  = $this->getRiskTasks();
		$this->riskreport = $this->getRiskReport();

		// Show errors on top
		HMWP_Classes_ObjController::getClass( 'HMWP_Classes_Error' )->hookNotices();

		//Show connect for activation
		/* translators: 1: Plugin name. */
		echo '<noscript><div class="alert-danger text-center py-3">' . sprintf( esc_html__( 'Javascript is disabled on your browser! You need to activate the javascript in order to use %1$s plugin.', 'hide-my-wp' ), esc_html( HMWP_Classes_Tools::getOption( 'hmwp_plugin_name' ) ) ) . '</div></noscript>';
		$this->show( 'SecurityCheck' );
	}

	/**
	 * Initiate Security List
	 *
	 * @return array|mixed
	 */
	public function initSecurity() {
		$this->report = get_option( HMWP_SECURITY_CHECK );

		if ( ! empty( $this->report ) ) {
			if ( ! $tasks_ignored = get_option( HMWP_SECURITY_CHECK_IGNORE ) ) {
				$tasks_ignored = array();
			}
			$tasks = $this->getTasks();
			foreach ( $this->report as $function => &$row ) {
				if ( ! in_array( $function, $tasks_ignored ) ) {
					if ( isset( $tasks[ $function ] ) ) {
						if ( isset( $row['version'] ) && $function == 'checkWP' && isset( $tasks[ $function ]['solution'] ) ) {
							$tasks[ $function ]['solution'] = str_replace( '{version}', $row['version'], $tasks[ $function ]['solution'] );
						}
						$row = array_merge( $tasks[ $function ], $row );

						if ( ! HMWP_Classes_Tools::getOption( 'hmwp_token' ) || HMWP_Classes_Tools::getOption( 'hmwp_mode' ) == 'default' ) {
							if ( isset( $row['javascript'] ) && $row['javascript'] <> '' ) {
								$row['javascript'] = 'jQuery(\'#hmwp_security_mode_require_modal\').modal(\'show\')';
							}
						}
					}
				} else {
					unset( $this->report[ $function ] );
				}
			}
		}

		return $this->report;
	}

	/**
	 * Get the Risk Tasks for speedometer
	 *
	 * @return array
	 */
	public function getRiskTasks() {
		return array(
			'checkPHP',
			'checkXmlrpc',
			'checkOldPlugins',
			'checkFilePermissions',
			'checkUsersById',
			'checkUsersEnumeration',
			'checkDbPassword',
			'checkRDS',
			'checkUploadsBrowsable',
			'checkConfig',
			'checkOldLogin',
			'checkOldPaths',
			'checkCommonPaths',
			'checkVersionDisplayed',
			'checkSSL',
			'checkDBDebug',
			'checkAdminUsers',
			'checkFirewall',
		);
	}

	/**
	 * Get the Risk Report for Daskboard Widget and speedometer
	 *
	 * @return array
	 */
	public function getRiskReport() {
		$riskreport = array();
		//get all the risk tasks
		$risktasks = $this->getRiskTasks();
		//initiate the security report
		$report = $this->initSecurity();

		if ( ! empty( $report ) ) {
			foreach ( $report as $function => $row ) {
				if ( in_array( $function, $risktasks ) ) {
					if ( ! $row['valid'] ) {
						//add the invalid tasks into risk report
						$riskreport[ $function ] = $row;
					}
				}
			}
		}

		// Return the risk report
		return $riskreport;
	}

	/**
	 * @return string|void
	 */
	public function getRiskErrorCount() {
		$tasks = $this->getRiskReport();
		if ( is_array( $tasks ) && count( $tasks ) > 0 ) {
			return '<span class="menu-counter">' . count( $tasks ) . '</span>';
		}
	}

	/**
	 * Get all the security tasks
	 *
	 * @return array
	 */
	public function getTasks() {

		return array(
			'checkPHP'              => array(
				'name'     => esc_html__( 'PHP Version', 'hide-my-wp' ),
				'value'    => false,
				'valid'    => false,
				'warning'  => false,
				/* translators: 1: Required PHP version number. */
				'message'  => wp_kses_post( sprintf( __( 'Your server is running an outdated version of PHP. Older PHP versions no longer receive security patches, leaving your site exposed to known vulnerabilities and reducing overall performance. <br /><br />Your website requires <strong>PHP %1$s</strong> or higher.', 'hide-my-wp' ), esc_html( '8.3' ) ) ),
				'solution' => esc_html__( "Contact your hosting provider and request an upgrade to a supported PHP version, or migrate to a host that offers current PHP releases.", 'hide-my-wp' ),
			),
			'checkMysql'            => array(
				'name'     => esc_html__( 'MySQL Version', 'hide-my-wp' ),
				'value'    => false,
				'valid'    => false,
				'warning'  => false,
				/* translators: 1: Required MySql version number, 2: Required MyMariaDBsql version number.*/
				'message'  => wp_kses_post( sprintf( __( 'Your server is running an outdated version of MySQL. Unsupported MySQL versions contain known security vulnerabilities and may cause performance issues. <br /><br />Your website requires minimum <strong>MySQL %1$s or MariaDB %2$s</strong>.', 'hide-my-wp' ), esc_html( '8.0' ), esc_html( '10.0' ) ) ),
				'solution' => esc_html__( "Contact your hosting provider and request an upgrade to a supported MySQL version, or migrate to a host that offers current database releases.", 'hide-my-wp' ),
			),
			'checkWP'               => array(
				'name'     => esc_html__( 'WordPress Version', 'hide-my-wp' ),
				'value'    => false,
				'valid'    => false,
				'warning'  => false,
				/* translators: 1: Opening <a> tag to WordPress download page, 2: Closing </a> tag. */
				'message'  => wp_kses_post( sprintf( __( 'A newer version of WordPress is available. Each release includes security patches and bug fixes that protect your site against newly discovered threats. <br /><br />Always update to the %1$slatest stable version%2$s as soon as it becomes available. You will see an update notice in your WordPress dashboard when a new version is ready.', 'hide-my-wp' ), '<a href="' . esc_url( 'https://wordpress.org/download/' ) . '" target="_blank">', '</a>' ) ),
				'solution' => esc_html__( "There is a newer version of WordPress available ({version}).", 'hide-my-wp' ),
			),
			'checkWPDebug'          => array(
				'name'       => esc_html__( 'WP Debug Mode', 'hide-my-wp' ),
				'value'      => false,
				'valid'      => false,
				'warning'    => false,
				'message'    => wp_kses_post( __( "WP_DEBUG is currently enabled. While useful during development, leaving debug mode active on a live site exposes error messages, file paths, and internal details to visitors — information that attackers can use to identify vulnerabilities.", 'hide-my-wp' ) ),
				'solution'   => wp_kses_post( __( "Set WP_DEBUG to false in wp-config.php: <code>define('WP_DEBUG', false);</code>", 'hide-my-wp' ) ),
				'javascript' => "jQuery(this).hmwp_fixConfig('WP_DEBUG',false);",
			),
			'checkDBDebug'          => array(
				'name'       => esc_html__( 'DB Debug Mode', 'hide-my-wp' ),
				'value'      => false,
				'valid'      => false,
				'warning'    => false,
				'message'    => esc_html__( "Database debug mode is currently enabled. On a live site, this can expose database error messages containing table names, query structures, and other sensitive information to visitors and potential attackers.", 'hide-my-wp' ),
				'solution'   => sprintf( __( "Disable database debugging on live sites. You can suppress database errors by adding <code>global \x24wpdb; \x24wpdb->hide_errors();</code> in wp-config.php.", 'hide-my-wp' ), '<a href="' . HMWP_Classes_Tools::getSettingsUrl( 'hmwp_tweaks&tab=disable' ) . '" >', HMWP_Classes_Tools::getOption( 'hmwp_plugin_menu' ), '</a>' ),
				'javascript' => "pro",
			),
			'checkScriptDebug'      => array(
				'name'       => esc_html__( 'Script Debug Mode', 'hide-my-wp' ),
				'value'      => false,
				'valid'      => false,
				'warning'    => false,
				'message'    => wp_kses_post( __( "SCRIPT_DEBUG is currently enabled. This forces WordPress to load unminified CSS and JS files, which can reveal internal paths and debug information in the frontend — giving attackers insight into your site structure.", 'hide-my-wp' ) ),
				'solution'   => wp_kses_post( __( "Set SCRIPT_DEBUG to false in wp-config.php: <code>define('SCRIPT_DEBUG', false);</code>", 'hide-my-wp' ) ),
				'javascript' => "jQuery(this).hmwp_fixConfig('SCRIPT_DEBUG',false);",
			),
			'checkDisplayErrors'    => array(
				'name'     => esc_html__( 'display_errors PHP directive', 'hide-my-wp' ),
				'value'    => false,
				'valid'    => false,
				'warning'  => false,
				'message'  => esc_html__( "The display_errors PHP directive is enabled. PHP errors displayed on the frontend can reveal file paths, function names, and database details — all of which help attackers map your site. Errors should be logged server-side, never shown to visitors.", 'hide-my-wp' ),
				'solution' => wp_kses_post( __( "Add the following to wp-config.php: <code>ini_set('display_errors', 0);</code>", 'hide-my-wp' ) ),
			),
			'checkSSL'              => array(
				'name'     => esc_html__( 'Backend under SSL', 'hide-my-wp' ),
				'value'    => false,
				'valid'    => false,
				'warning'  => false,
				'message'  => wp_kses_post( __( "Your admin dashboard is not served over HTTPS. Without SSL/TLS encryption, login credentials and session data are transmitted in plain text, making them vulnerable to interception. <br /><br />An SSL certificate is essential for securing all admin communications.", 'hide-my-wp' ) ),
				/* translators: 1: Opening <strong> tag before "Settings", 2: Closing </strong> tag after "General", 3: Opening anchor tag to HTTPS guide article, 4: Closing anchor tag. */
				'solution' => wp_kses_post( sprintf( __( 'Go to %1$s > %2$s and make sure <strong>WordPress Address</strong> starts with <strong>https://</strong>. %3$sRead the guide%4$s', 'hide-my-wp' ), '<strong>' . esc_html__( 'Settings' ), esc_html__( 'General' ) . '</strong>', '<a href="' . esc_url( HMWP_Classes_Tools::getOption( 'hmwp_plugin_website' ) . '/kb/move-wordpress-from-http-to-https/' )  . '" target="_blank">', '</a>' ) ), //phpcs:ignore
			),
			'checkAdminUsers'       => array(
				'name'       => esc_html__( "User 'admin' or 'administrator' as Administrator", 'hide-my-wp' ),
				'value'      => false,
				'valid'      => false,
				'warning'    => false,
				'message'    => wp_kses_post( __( "An administrator account is using the default username 'admin' or 'administrator'. These are the first usernames attackers try during brute force attacks. Since the username is half of the login credentials, using a predictable one significantly reduces the effort needed to compromise your site.", 'hide-my-wp' ) ),
				'solution'   => esc_html__( "Create a new administrator account with a unique username, transfer ownership of existing content, then delete the 'admin' or 'administrator' account.", 'hide-my-wp' ),
				'javascript' => "pro",
			),
			'checkUserRegistration' => array(
				'name'     => esc_html__( "Spammers can easily signup", 'hide-my-wp' ),
				'value'    => false,
				'valid'    => false,
				'warning'  => false,
				'message'  => wp_kses_post( __( "Public user registration is enabled. Unless your site requires it (e-commerce, memberships, or guest posting), open registration invites spam accounts, fake content, and potential exploitation of authenticated-user vulnerabilities. <br /><br />If registration is needed, protect the form with Brute Force protection.", 'hide-my-wp' ) ),
				/* translators: 1: Opening <a> tag to Custom Register URL settings, 2: Plugin menu name, 3: Closing </a> tag, 4: Opening <a> tag to Brute Force settings, 5: Plugin menu name, 6: Closing </a> tag, 7: Opening <strong> tag with Settings label, 8: General label, 9: Anyone can register label with closing </strong> tag. */
				'solution' => wp_kses_post( sprintf( __( 'Change the signup path from %1$s%2$s > Change Paths > Custom Register URL %3$s then activate Brute Force on Sign up from %4$s%5$s > Brute Force > Settings %6$s or uncheck the option %7$s > %8$s > %9$s', 'hide-my-wp' ), '<a href="' . esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_permalinks&tab=newlogin' ) ) . '" >', esc_html( HMWP_Classes_Tools::getOption( 'hmwp_plugin_menu' ) ), '</a>', '<a href="' . esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_brute&tab=brute' ) ) . '" >', esc_html( HMWP_Classes_Tools::getOption( 'hmwp_plugin_menu' ) ), '</a>', '<strong>' . esc_html__( 'Settings', 'hide-my-wp' ), esc_html__( 'General', 'hide-my-wp' ), esc_html__( 'Anyone can register', 'hide-my-wp' ) . '</strong>' ) ),
				'javascript' => (HMWP_Classes_Tools::getDefault( 'hmwp_register_url' ) <> HMWP_Classes_Tools::getOption( 'hmwp_register_url' ) ? "jQuery(this).hmwp_fixSettings('hmwp_bruteforce,hmwp_bruteforce_register','1,1');" : "" ),
			),
			'checkPluginsUpdates'   => array(
				'name'     => esc_html__( "Outdated Plugins", 'hide-my-wp' ),
				'value'    => false,
				'valid'    => false,
				'warning'  => false,
				'message'  => wp_kses_post( __( "One or more plugins have updates available. Plugin updates frequently include patches for known security vulnerabilities. Attackers actively scan for sites running outdated plugin versions with known exploits. <br /><br />Keeping all plugins up to date is one of the most effective and simplest ways to protect your site.", 'hide-my-wp' ) ),
				'solution' => esc_html__( "Go to Dashboard > Plugins and update all plugins to their latest versions.", 'hide-my-wp' ),
			),
			'checkOldPlugins'       => array(
				'name'     => esc_html__( "Abandoned Plugins Detected", 'hide-my-wp' ),
				'value'    => false,
				'valid'    => false,
				'warning'  => false,
				'message'  => esc_html__( "One or more installed plugins have not been updated by their developers in over 12 months. Abandoned plugins no longer receive security patches and may contain unpatched vulnerabilities that attackers can exploit.", 'hide-my-wp' ),
				'solution' => esc_html__( "Review the plugins that have not been updated recently and replace them with actively maintained alternatives from the WordPress directory.", 'hide-my-wp' ),
			),
			'checkThemesUpdates'    => array(
				'name'     => esc_html__( "Outdated Themes", 'hide-my-wp' ),
				'value'    => false,
				'valid'    => false,
				'warning'  => false,
				'message'  => wp_kses_post( __( "One or more themes have updates available. Theme updates include security patches and bug fixes for known vulnerabilities. <br /><br />Even inactive themes can be exploited if they contain vulnerable code, since their files are still accessible on the server. Keep all installed themes up to date.", 'hide-my-wp' ) ),
				'solution' => esc_html__( "Go to Dashboard > Appearance and update all themes to their latest versions. Remove any unused themes.", 'hide-my-wp' ),
			),
			'checkDBPrefix'         => array(
				'name'       => esc_html__( "Default Database Prefix", 'hide-my-wp' ),
				'value'      => false,
				'valid'      => false,
				'warning'    => false,
				'message'    => wp_kses_post( __( "Your database is using the default <strong>wp_</strong> table prefix. Automated SQL injection attacks commonly target this default prefix. Using a custom prefix adds an additional barrier against mass-targeted attacks that assume standard WordPress table names.", 'hide-my-wp' ) ),
				/* translators: 1: Plugin name, 2: Opening <a> tag to database prefix article, 3: Closing </a> tag. */
				'solution'   => wp_kses_post( sprintf( __( '%1$s protects your website from most SQL injections, but using a custom database prefix adds an extra layer of defense. %2$sLearn how to change it%3$s', 'hide-my-wp' ), esc_html( HMWP_Classes_Tools::getOption( 'hmwp_plugin_name' ) ), '<a href="' . esc_url( HMWP_Classes_Tools::getOption( 'hmwp_plugin_website' ) . '/kb/change-database-prefix-in-wordpress/' )  . '" target="_blank">', '</a>' ) ),
				'javascript' => "pro",
			),
			'checkFilePermissions'  => array(
				'name'       => esc_html__( "File Permissions", 'hide-my-wp' ),
				'value'      => false,
				'valid'      => false,
				'warning'    => false,
				'message'    => wp_kses_post( __( "One or more files or directories have overly permissive settings. Incorrect file permissions can allow unauthorized users to read, modify, or execute sensitive files — potentially leading to full site compromise. <br /><br />Directories should typically be set to 755 and files to 644.", 'hide-my-wp' ) ),
				/* translators: 1: Plugin name, 2: Opening <a> tag to file permissions article, 3: Closing </a> tag. */
				'solution'   => wp_kses_post( sprintf( __( 'Even though %1$s protects default paths after customization, we recommend verifying correct permissions for all files and directories. Use File Manager or FTP to review and adjust. %2$sRead the guide%3$s', 'hide-my-wp' ), esc_html( HMWP_Classes_Tools::getOption( 'hmwp_plugin_name' ) ), '<a href="' . esc_url( HMWP_Classes_Tools::getOption( 'hmwp_plugin_website' ) . '/kb/how-to-change-file-permissions-in-wordpress/' )  . '" target="_blank">', '</a>' ) ),
				'javascript' => "pro",
			),
			'checkSaltKeys'         => array(
				'name'       => esc_html__( "Security Keys and Salts", 'hide-my-wp' ),
				'value'      => false,
				'valid'      => false,
				'warning'    => false,
				'message'    => wp_kses_post( __( "WordPress security keys and salts strengthen the encryption of data stored in user cookies and hashed passwords. Without properly configured keys, session tokens and passwords are easier to crack. <br /><br />Each key should be unique, random, and at least 64 characters long.", 'hide-my-wp' ) ),
				'solution'   => wp_kses_post( __( "Define all eight security constants in wp-config.php with unique, random values: <code>AUTH_KEY, SECURE_AUTH_KEY, LOGGED_IN_KEY, NONCE_KEY, AUTH_SALT, SECURE_AUTH_SALT, LOGGED_IN_SALT, NONCE_SALT</code>", 'hide-my-wp' ) ),
				'javascript' => "pro",
			),
			'checkSaltKeysAge'      => array(
				'name'       => esc_html__( "Security Keys Need Rotation", 'hide-my-wp' ),
				'value'      => false,
				'valid'      => false,
				'warning'    => false,
				'message'    => esc_html__( "Your security keys have not been regenerated in a long time. Regularly rotating security keys invalidates all existing sessions and forces re-authentication, limiting the window of exposure if credentials are compromised.", 'hide-my-wp' ),
				/* translators: 1: Opening <a> tag to WordPress secret key generator, 2: Closing </a> tag. */
				'solution'   => wp_kses_post( sprintf( __( 'Generate %1$snew security keys%2$s and replace the existing values in wp-config.php: <code>AUTH_KEY, SECURE_AUTH_KEY, LOGGED_IN_KEY, NONCE_KEY, AUTH_SALT, SECURE_AUTH_SALT, LOGGED_IN_SALT, NONCE_SALT</code>', 'hide-my-wp' ), '<a href="' . esc_url( 'https://api.wordpress.org/secret-key/1.1/salt/' ) . '" target="_blank">', '</a>' ) ),
				'javascript' => "pro",
			),
			'checkDbPassword'       => array(
				'name'     => esc_html__( "Weak Database Password", 'hide-my-wp' ),
				'value'    => false,
				'valid'    => false,
				'warning'  => false,
				'message'  => wp_kses_post( __( "Your database password is empty or too simple. Even though most servers restrict database access to localhost, a weak password provides no defense if that restriction is ever bypassed or misconfigured.", 'hide-my-wp' ) ),
				'solution' => wp_kses_post( __( "Set a strong database password — at least 12 characters with a mix of uppercase, lowercase, numbers, and special characters. Update the password in wp-config.php: <code>define('DB_PASSWORD', 'NEW_DB_PASSWORD_GOES_HERE');</code>", 'hide-my-wp' ) ),
			),
			'checkCommonPaths'      => array(
				/* translators: 1: wp-content path. */
				'name'     => sprintf( esc_html__( '%1$s is visible in source code', 'hide-my-wp' ), esc_html( '/' . HMWP_Classes_Tools::getDefault( 'hmwp_wp-content_url' ) ) ),
				'value'    => false,
				'valid'    => false,
				'warning'  => false,
				'message'  => esc_html__( "Default WordPress paths like wp-content and wp-includes are visible in your source code. These paths confirm to bots and attackers that your site runs WordPress, making it a target for automated exploit scanners.", 'hide-my-wp' ),
				/* translators: 1: Opening <a> tag to Core settings URL, 2: Plugin menu name, 3: Closing </a> tag. */
				'solution' => wp_kses_post( sprintf( __( 'Change default paths from %1$s%2$s > Change Paths > WP Core Security%3$s', 'hide-my-wp' ), '<a href="' . esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_permalinks&tab=core' ) ) . '" >', esc_html( HMWP_Classes_Tools::getOption( 'hmwp_plugin_menu' ) ), '</a>' ) ),
			),
			'checkOldPaths'         => array(
				/* translators: 1: wp-content path. */
				'name'       => sprintf( esc_html__( '%1$s path is accessible', 'hide-my-wp' ), esc_html( '/' . HMWP_Classes_Tools::getDefault( 'hmwp_wp-content_url' ) ) ),
				'value'      => false,
				'valid'      => false,
				'warning'    => false,
				'message'    => wp_kses_post( __( "The original WordPress paths are still accessible even though custom paths are set. Bots can still reach plugins and themes at their default locations, bypassing the path changes. <br /><br />Hiding old paths ensures that only the custom paths work, fully removing the original entry points.", 'hide-my-wp' ) ),
				/* translators: 1: Opening <a> tag to Core settings URL, 2: Plugin menu name, 3: Closing </a> tag. */
				'solution'   => wp_kses_post( sprintf( __( 'Change the default path and switch on %1$s%2$s > Change Paths > Hide WordPress Common Paths%3$s', 'hide-my-wp' ), '<a href="' . esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_permalinks&tab=core' ) ) . '" >', esc_html( HMWP_Classes_Tools::getOption( 'hmwp_plugin_menu' ) ), '</a>' ) ),
				'javascript' => (HMWP_Classes_Tools::getDefault( 'hmwp_wp-content_url' ) <> HMWP_Classes_Tools::getOption( 'hmwp_wp-content_url' ) ? "jQuery(this).hmwp_fixSettings('hmwp_hide_oldpaths',1);" : "" ),
			),
			'checkAdminPath'        => array(
				/* translators: 1: Admin path. */
				'name'     => sprintf( esc_html__( '%1$s is visible in source code', 'hide-my-wp' ), esc_html( '/' . HMWP_Classes_Tools::getOption( 'hmwp_admin_url' ) ) ),
				'value'    => false,
				'valid'    => false,
				'warning'  => false,
				/* translators: 1: Opening <a> tag to KB article, 2: Closing </a> tag. */
				'message'  => wp_kses_post( sprintf( __( 'Your custom admin path is exposed in the page source code (typically through the AJAX URL). This allows attackers to discover your admin path and launch targeted brute force attacks. <br /><br />%1$sLearn how to hide the admin path from source code%2$s.', 'hide-my-wp' ), '<a href="' . esc_url( HMWP_Classes_Tools::getOption( 'hmwp_plugin_website' ) . '/kb/hide-wpadmin-and-wplogin-php-from-source-code/' )  . '" target="_blank">', '</a>' ) ),
				/* translators: 1: Opening <a> tag to AJAX settings URL, 2: Plugin menu name, 3: Closing </a> tag, 4: Opening <strong> tag, 5: Closing </strong> tag. */
				'solution' => wp_kses_post( sprintf( __( 'Change the default ajax path and switch on %1$s%2$s > Change Paths > Hide wp-admin from ajax URL%3$s. %4$sAlso verify that no installed plugins reference the admin path in frontend output.%5$s', 'hide-my-wp' ), '<a href="' . esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_permalinks&tab=ajax' ) ) . '" >', esc_html( HMWP_Classes_Tools::getOption( 'hmwp_plugin_menu' ) ), '</a>', '<strong>', '</strong>' ) ),
			),
			'checkLoginPath'        => array(
				/* translators: 1: Login path. */
				'name'     => sprintf( esc_html__( '%1$s is visible in source code', 'hide-my-wp' ), esc_html( '/' . HMWP_Classes_Tools::getOption( 'hmwp_login_url' ) ) ),
				'value'    => false,
				'valid'    => false,
				'warning'  => false,
				/* translators: 1: Opening <a> tag to KB article, 2: Closing </a> tag. */
				'message'  => wp_kses_post( sprintf( __( 'Your custom login path is exposed in the page source code. This defeats the purpose of changing the login URL, as attackers can still discover it and target it with brute force attacks. <br /><br />The custom login path must be kept secret, and Brute Force Protection should be active. %1$sLearn how to hide the login path from source code%2$s.', 'hide-my-wp' ), '<a href="' . esc_url( HMWP_Classes_Tools::getOption( 'hmwp_plugin_website' ) . '/kb/hide-wpadmin-and-wplogin-php-from-source-code/' )  . '" target="_blank">', '</a>' ) ),
				/* translators: 1: Opening <strong> tag, 2: Closing </strong> tag. */
				'solution' => wp_kses_post( sprintf( __( '%1$sRemove the login path%2$s from any theme menus, widgets, or page content that expose it in the frontend.', 'hide-my-wp' ), '<strong>', '</strong>' ) ),
			),
			'checkOldLogin' => array(
				/* translators: 1: Login path. */
				'name'     => sprintf( esc_html__( '%1$s path is accessible', 'hide-my-wp' ), esc_html( '/' . HMWP_Classes_Tools::getDefault( 'hmwp_login_url' ) ) ),
				'value'    => false,
				'valid'    => false,
				'warning'  => false,
				'message'  => wp_kses_post( __( "The default wp-login.php path is still accessible. Even with a custom login URL configured, bots can still find and attack the original login page. Blocking access to the default path forces all login traffic through your custom URL, where Brute Force Protection can be applied.", 'hide-my-wp' ) ),
				/* translators: 1: Opening <a> tag to Custom login URL settings, 2: Plugin menu name, 3: Closing </a> tag, 4: Opening <a> tag to Brute Force settings, 5: Plugin menu name, 6: Closing </a> tag. */
				'solution' => wp_kses_post( sprintf( __( 'Set a custom login URL from %1$s%2$s > Change Paths > Custom login URL%3$s and switch on %4$s%5$s > Brute Force Protection%6$s', 'hide-my-wp' ), '<a href="' . esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_permalinks&tab=newlogin' ) ) . '" >', esc_html( HMWP_Classes_Tools::getOption( 'hmwp_plugin_menu' ) ), '</a>', '<a href="' . esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_brute&tab=brute' ) ) . '" >', esc_html( HMWP_Classes_Tools::getOption( 'hmwp_plugin_menu' ) ), '</a>' ) ),
				'javascript' => (HMWP_Classes_Tools::getDefault( 'hmwp_login_url' ) <> HMWP_Classes_Tools::getOption( 'hmwp_login_url' ) ? "jQuery(this).hmwp_fixSettings('hmwp_hide_wplogin',1);" : "" ),
			),
			'checkConfig'    => array(
				'name'       => esc_html__( "wp-config.php & wp-config-sample.php are accessible", 'hide-my-wp' ),
				'value'      => false,
				'valid'      => false,
				'warning'    => false,
				'message'    => wp_kses_post( __( "Your wp-config.php file is publicly accessible. This file contains your database credentials, security keys, and other critical configuration details. If an attacker can read this file, they can gain full control of your database and website.", 'hide-my-wp' ) ),
				/* translators: 1: Opening <a> tag to Core settings URL, 2: Plugin menu name, 3: Closing </a> tag. */
				'solution'   => wp_kses_post( sprintf( __( 'Switch on %1$s%2$s > Change Paths > Hide WordPress Common Files%3$s', 'hide-my-wp' ), '<a href="' . esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_permalinks&tab=core' ) ) . '" >', esc_html( HMWP_Classes_Tools::getOption( 'hmwp_plugin_menu' ) ), '</a>' ) ),
				'javascript' => "pro",
			),
			'checkReadme'    => array(
				'name'       => esc_html__( "readme.html file is accessible", 'hide-my-wp' ),
				'value'      => false,
				'valid'      => false,
				'warning'    => false,
				'message'    => esc_html__( "The readme.html file is publicly accessible and reveals your WordPress version number. Attackers use this to identify sites running versions with known vulnerabilities.", 'hide-my-wp' ),
				/* translators: 1: Plugin menu name, 2: Opening <a> tag to Core settings URL, 3: Closing </a> tag. */
				'solution'   => wp_kses_post( sprintf( __( 'Switch on %1$s > Change Paths > Hide WordPress Common Files from %2$sHide WordPress Common Paths%3$s', 'hide-my-wp' ), esc_html( HMWP_Classes_Tools::getOption( 'hmwp_plugin_menu' ) ), '<a href="' . esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_permalinks&tab=core' ) ) . '" >', '</a>' ) ),
				'javascript' => "jQuery(this).hmwp_fixSettings('hmwp_hide_commonfiles',1);",
			),
			'checkInstall'   => array(
				'name'       => esc_html__( "install.php & upgrade.php are accessible", 'hide-my-wp' ),
				'value'      => false,
				'valid'      => false,
				'warning'    => false,
				'message'    => wp_kses_post( __( "The wp-admin/install.php and wp-admin/upgrade.php files are publicly accessible. These files have been associated with security vulnerabilities in the past and should not be reachable on a live site.", 'hide-my-wp' ) ),
				/* translators: 1: Plugin menu name, 2: Opening <a> tag to Core settings URL, 3: Closing </a> tag. */
				'solution'   => wp_kses_post( sprintf( __( 'Switch on %1$s > Change Paths > Hide WordPress Common Files from %2$sHide WordPress Common Paths%3$s and select also install.php & upgrade.php', 'hide-my-wp' ), esc_html( HMWP_Classes_Tools::getOption( 'hmwp_plugin_menu' ) ), '<a href="' . esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_permalinks&tab=core' ) ) . '" >', '</a>' ) ),
				'javascript' => "pro",
			),
			'checkFirewall'  => array(
				'name'       => esc_html__( "7G/8G Firewall Not Loaded", 'hide-my-wp' ),
				'value'      => false,
				'valid'      => false,
				'warning'    => false,
				'message'    => __( "The 7G/8G Firewall is not currently active. Without these rulesets, malicious requests — including SQL injection attempts, script injections, and exploit probes — are not filtered at the server level before they reach your site.", 'hide-my-wp' ),
				/* translators: 1: Opening <a> tag to Firewall settings URL, 2: Plugin menu name, 3: Closing </a> tag. */
				'solution'   => wp_kses_post( sprintf( __( 'Switch on the 7G or 8G Firewall from %1$s%2$s > Firewall & Headers%3$s', 'hide-my-wp' ), '<a href="' . esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_firewall' ) ) . '" >', esc_html( HMWP_Classes_Tools::getOption( 'hmwp_plugin_menu' ) ), '</a>' ) ),
				'javascript' => "jQuery(this).hmwp_fixSettings('hmwp_sqlinjection,hmwp_sqlinjection_level','1,4');",
			),
			'checkFirewallAutomation' => array(
				'name'       => esc_html__( "Automated IP Blocking Not Active", 'hide-my-wp' ),
				'value'      => false,
				'valid'      => false,
				'warning'    => false,
				'message'    => wp_kses_post( __( "Automated IP Blocking is not currently active. Without this protection, repeated suspicious requests from the same IP — such as brute force probes, scanner sweeps, and exploit attempts — are not blocked automatically. This increases server load and leaves your site exposed to sustained attacks that cannot be addressed fast enough manually.", 'hide-my-wp' ) ),
				/* translators: 1: Opening <a> tag to Firewall settings URL, 2: Plugin menu name, 3: Closing </a> tag. */
				'solution'   => wp_kses_post( sprintf( __( 'Switch on the Automated IP Blocking option from %1$s%2$s > Firewall & Headers%3$s', 'hide-my-wp' ), '<a href="' . esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_firewall' ) ) . '" >', esc_html( HMWP_Classes_Tools::getOption( 'hmwp_plugin_menu' ) ), '</a>' ) ),
				'javascript' => "pro",
			),
			'checkVersionDisplayed' => array(
				'name'       => esc_html__( "Version Numbers Exposed", 'hide-my-wp' ),
				'value'      => false,
				'valid'      => false,
				'warning'    => false,
				'message'    => wp_kses_post( __( "WordPress, plugin, and theme version numbers are visible in your source code. Attackers use version information to identify sites running software with known vulnerabilities and target them with automated exploits.", 'hide-my-wp' ) ),
				/* translators: 1: Opening <a> tag to Tweaks settings URL, 2: Plugin menu name, 3: Feature label, 4: Closing </a> tag. */
				'solution'   => wp_kses_post( sprintf( __( 'Switch on %1$s%2$s > Tweaks > %3$s%4$s', 'hide-my-wp' ), '<a href="' . esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_tweaks&tab=hide' ) ) . '" >', esc_html( HMWP_Classes_Tools::getOption( 'hmwp_plugin_menu' ) ), esc_html__( 'Hide Versions from Images, CSS and JS', 'hide-my-wp' ), '</a>' ) ),
				'javascript' => "jQuery(this).hmwp_fixSettings('hmwp_hide_version',1);",
			),
			'checkRegisterGlobals'  => array(
				'name'     => esc_html__( "PHP register_globals is on", 'hide-my-wp' ),
				'value'    => false,
				'valid'    => false,
				'warning'  => false,
				'message'  => esc_html__( "The register_globals PHP directive is enabled. This is a critical security risk — it allows external input to overwrite internal PHP variables, enabling attackers to manipulate your application logic. This directive has been removed in modern PHP versions due to its severity.", 'hide-my-wp' ),
				'solution' => wp_kses_post( __( "Set <code>register_globals = off</code> in php.ini, or contact your hosting provider to disable it immediately. Consider switching hosts if they enable this by default.", 'hide-my-wp' ) ),
			),
			'checkExposedPHP'       => array(
				'name'     => esc_html__( "PHP Version Exposed", 'hide-my-wp' ),
				'value'    => false,
				'valid'    => false,
				'warning'  => false,
				'message'  => esc_html__( "Your server is broadcasting its PHP version in HTTP response headers. This information helps attackers identify which PHP vulnerabilities may apply to your server.", 'hide-my-wp' ),
				'solution' => wp_kses_post( __( "Set <code>expose_php = off</code> in php.ini, or contact your hosting provider to disable it.", 'hide-my-wp' ) ),
			),
			'checkPHPSafe'          => array(
				'name'     => esc_html__( "PHP safe_mode is on", 'hide-my-wp' ),
				'value'    => false,
				'valid'    => false,
				'warning'  => false,
				'message'  => wp_kses_post( __( "PHP safe_mode is enabled on your server. This deprecated directive was an attempt to solve shared hosting security at the PHP level but was architecturally flawed and officially removed in PHP 5.4. <br /><br />When enabled, it can break legitimate PHP functions while providing no real protection — attackers can easily bypass its restrictions.", 'hide-my-wp' ) ),
				'solution' => wp_kses_post( __( "Set <code>safe_mode = off</code> in php.ini, or contact your hosting provider to disable it. If your host requires safe_mode, consider migrating to a modern hosting environment.", 'hide-my-wp' ) ),
			),
			'checkAllowUrlInclude'  => array(
				'name'     => esc_html__( "PHP allow_url_include is on", 'hide-my-wp' ),
				'value'    => false,
				'valid'    => false,
				'warning'  => false,
				'message'  => wp_kses_post( __( "The allow_url_include PHP directive is enabled. This allows PHP to include remote files via URLs in include/require statements, exposing your site to Remote File Inclusion (RFI) attacks where attackers can execute arbitrary code on your server.", 'hide-my-wp' ) ),
				'solution' => wp_kses_post( __( "Set <code>allow_url_include = off</code> in php.ini, or contact your hosting provider to disable it. There is no legitimate use case for this directive on production sites.", 'hide-my-wp' ) ),
			),
			'checkAdminEditor'      => array(
				'name'       => esc_html__( "Plugin/Theme Editor Enabled", 'hide-my-wp' ),
				'value'      => false,
				'valid'      => false,
				'warning'    => false,
				'message'    => wp_kses_post( __( "The built-in plugin and theme file editor is active. If an attacker gains admin access, they can use this editor to inject malicious PHP code directly into your site files — instantly compromising the entire installation without needing FTP or server access.", 'hide-my-wp' ) ),
				'solution'   => wp_kses_post( __( "Add the following to wp-config.php to disable the file editor: <code>define('DISALLOW_FILE_EDIT', true);</code>", 'hide-my-wp' ) ),
				'javascript' => "jQuery(this).hmwp_fixConfig('DISALLOW_FILE_EDIT',true);",
			),
			'checkUploadsBrowsable' => array(
				/* translators: 1: Folder path. */
				'name'       => sprintf( esc_html__( '%1$s directory is browsable', 'hide-my-wp' ), esc_html( HMWP_Classes_Tools::getDefault( 'hmwp_upload_url' ) ) ),
				'value'      => false,
				'valid'      => false,
				'warning'    => false,
				'message'    => esc_html__( "Directory browsing is enabled on your uploads folder. Anyone can view and download all uploaded files by navigating to the directory URL — this is both a security risk and a copyright concern.", 'hide-my-wp' ),
				/* translators: 1: Opening <a> tag to external directory browsing article, 2: Closing </a> tag, 3: Opening <a> tag to Core settings URL, 4: Plugin menu name, 5: Closing </a> tag. */
				'solution'   => wp_kses_post( sprintf( __( 'Learn how to %1$sdisable directory browsing%2$s, or switch on %3$s%4$s > Change Paths > Disable Directory Browsing%5$s', 'hide-my-wp' ), '<a href="' . esc_url( 'https://www.netsparker.com/blog/web-security/disable-directory-listing-web-servers/' ) . '">', '</a>', '<a href="' . esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_permalinks&tab=core' ) ) . '" >', esc_html( HMWP_Classes_Tools::getOption( 'hmwp_plugin_menu' ) ), '</a>' ) ),
				'javascript' => "jQuery(this).hmwp_fixSettings('hmwp_disable_browsing',1);",
			),
			'checkWLW'              => array(
				'name'       => esc_html__( "Windows Live Writer Link Exposed", 'hide-my-wp' ),
				'value'      => false,
				'valid'      => false,
				'warning'    => false,
				'message'    => esc_html__( "The Windows Live Writer manifest link is present in your page header. This is a WordPress-specific fingerprint that confirms your CMS to automated scanners. Since Windows Live Writer is discontinued, there is no reason to keep this enabled.", 'hide-my-wp' ),
				/* translators: 1: Opening <a> tag to Tweaks settings URL, 2: Plugin menu name, 3: Closing </a> tag. */
				'solution'   => wp_kses_post( sprintf( __( 'Switch on %1$s%2$s > Tweaks > Hide WLW Manifest scripts%3$s', 'hide-my-wp' ), '<a href="' . esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_tweaks&tab=hide' ) ) . '" >', esc_html( HMWP_Classes_Tools::getOption( 'hmwp_plugin_menu' ) ), '</a>' ) ),
				'javascript' => "jQuery(this).hmwp_fixSettings('hmwp_disable_manifest',1);",
			),
			'checkXmlrpc'           => array(
				'name'       => esc_html__( "XML-RPC Access Enabled", 'hide-my-wp' ),
				'value'      => false,
				'valid'      => false,
				'warning'    => false,
				'message'    => __( "XML-RPC is currently accessible. This protocol sends credentials in plain text with every request and supports system.multicall, which allows attackers to test thousands of passwords in a single HTTP request. Unless you specifically need XML-RPC for remote publishing or the Jetpack plugin, it should be disabled.", 'hide-my-wp' ),
				/* translators: 1: Opening <a> tag to API settings URL, 2: Plugin menu name, 3: Closing </a> tag. */
				'solution'   => wp_kses_post( sprintf( __( 'Disable XML-RPC from %1$s%2$s > Change Paths > Disable XML-RPC access%3$s', 'hide-my-wp' ), '<a href="' . esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_permalinks&tab=api' ) ) . '" >', esc_html( HMWP_Classes_Tools::getOption( 'hmwp_plugin_menu' ) ), '</a>' ) ),
				'javascript' => "jQuery(this).hmwp_fixSettings('hmwp_disable_xmlrpc',1);",
			),
			'checkRDS'              => array(
				'name'       => esc_html__( "RSD Endpoint Exposed", 'hide-my-wp' ),
				'value'      => false,
				'valid'      => false,
				'warning'    => false,
				'message'    => __( "The Really Simple Discovery (RSD) endpoint link is visible in your page header. Unless you use pingbacks or external blog clients that rely on RSD, this link serves no purpose and acts as a WordPress fingerprint that confirms your CMS to scanners.", 'hide-my-wp' ),
				/* translators: 1: Opening <a> tag to API settings URL, 2: Plugin menu name, 3: Closing </a> tag. */
				'solution'   => wp_kses_post( sprintf( __( 'Switch on %1$s%2$s > Change Paths > Hide RSD Endpoint%3$s', 'hide-my-wp' ), '<a href="' . esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_permalinks&tab=api' ) ) . '" >', esc_html( HMWP_Classes_Tools::getOption( 'hmwp_plugin_menu' ) ), '</a>' ) ),
				'javascript' => "jQuery(this).hmwp_fixSettings('hmwp_hide_rsd',1);",
			),
			'checkUsersById'        => array(
				'name'       => esc_html__( "Author URL Enumeration via ID", 'hide-my-wp' ),
				'value'      => false,
				'valid'      => false,
				'warning'    => false,
				'message'    => __( "Usernames can be discovered by iterating through author IDs (e.g., /?author=1, /?author=2). WordPress redirects valid IDs to the author archive URL, revealing the username. <br /><br />While usernames alone don't grant access, they provide attackers with half the credentials needed and enable targeted brute force or phishing attacks.", 'hide-my-wp' ),
				/* translators: 1: Opening <a> tag to Author settings URL, 2: Plugin menu name, 3: Closing </a> tag. */
				'solution'   => wp_kses_post( sprintf( __( 'Switch on %1$s%2$s > Change Paths > Hide Author ID URL%3$s', 'hide-my-wp' ), '<a href="' . esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_permalinks&tab=author' ) ) . '" >', esc_html( HMWP_Classes_Tools::getOption( 'hmwp_plugin_menu' ) ), '</a>' ) ),
				'javascript' => "jQuery(this).hmwp_fixSettings('hmwp_hide_authors',1);",
			),
			'checkUsersEnumeration'        => array(
				'name'       => esc_html__( "User Enumeration via REST API", 'hide-my-wp' ),
				'value'      => false,
				'valid'      => false,
				'warning'    => false,
				'message'    => __( "Usernames are discoverable through the WordPress REST API (e.g., /wp-json/wp/v2/users). This endpoint can expose author IDs, display names, and author slugs — which often match or closely resemble the actual login username. <br /><br />Attackers use this information for targeted brute force attacks and phishing campaigns.", 'hide-my-wp' ),
				/* translators: 1: Opening <a> tag to Author settings URL, 2: Plugin menu name, 3: Closing </a> tag. */
				'solution'   => wp_kses_post( sprintf( __( 'Switch on %1$s%2$s > Change Paths > Hide User Enumeration%3$s', 'hide-my-wp' ), '<a href="' . esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_permalinks&tab=author' ) ) . '" >', esc_html( HMWP_Classes_Tools::getOption( 'hmwp_plugin_menu' ) ), '</a>' ) ),
				'javascript' => "jQuery(this).hmwp_fixSettings('hmwp_hide_author_enumeration',1);",
			),
			'checkBlogDescription'  => array(
				'name'     => esc_html__( "Default WordPress Tagline", 'hide-my-wp' ),
				'value'    => false,
				'valid'    => false,
				'warning'  => false,
				'message'  => __( "Your site is still using the default WordPress tagline \"Just another WordPress site\". This is a well-known WordPress fingerprint that makes it easy for bots and scanners to confirm your CMS. It also appears unprofessional to visitors.", 'hide-my-wp' ),
				/* translators: 1: Opening <strong> tag and Settings label, 2: General label, 3: Tagline label with closing </strong> tag. */
				'solution' => wp_kses_post( sprintf( __( 'Update your tagline in %1$s > %2$s > %3$s', 'hide-my-wp' ), '<strong>' . esc_html__( 'Settings' ), esc_html__( 'General' ), esc_html__( 'Tagline' ) . '</strong>' ) ), //phpcs:ignore
			),

		);
	}

	/**
	 * Process the security check
	 */
	public function doSecurityCheck() {

		if ( ! $tasks_ignored = get_option( HMWP_SECURITY_CHECK_IGNORE ) ) {
			$tasks_ignored = array();
		}

		$tasks = $this->getTasks();
		foreach ( $tasks as $function => $task ) {
			if ( ! in_array( $function, $tasks_ignored ) ) {
				if ( $result = @call_user_func( array( $this, $function ) ) ) {
					$this->report[ $function ] = $result;
				}
			}
		}


		update_option( HMWP_SECURITY_CHECK, $this->report );
		update_option( HMWP_SECURITY_CHECK_TIME, array( 'timestamp' => current_time( 'timestamp', 1 ) ) );
	}

	/**
	 * Reset the Security Check
	 *
	 * @return void
	 * @throws Exception
	 */
	public function resetSecurityCheck() {

		// Force the recheck security notification
		delete_option( HMWP_SECURITY_CHECK_TIME );

		// Schedule cron once
		HMWP_Classes_ObjController::getClass( 'HMWP_Controllers_Cron' )->registerOnce();

	}


	/**
	 * Build a single, readable Frontend Check result row.
	 *
	 * Renders a status badge, a short label and the URL on its own line so long
	 * URLs wrap cleanly instead of breaking the centered layout.
	 *
	 * @param string $label Short description of the issue (may be empty).
	 * @param string $url   The tested URL.
	 * @param string $badge Short status text shown in the badge (e.g. "404").
	 *
	 * @return string
	 */
	private function frontendError( $label, $url, $badge ) {
		// Show a clean URL without the internal preview parameter
		$display = remove_query_arg( 'hmwp_preview', $url );

		return '<div style="display:flex;align-items:flex-start;gap:10px;text-align:left;margin:6px 0;padding:8px 12px;background:#fff;border-radius:6px;border-left:4px solid #FFA500;box-shadow:0 1px 2px rgba(0,0,0,0.06);">'
			. '<span style="flex:0 0 auto;display:inline-block;min-width:40px;text-align:center;padding:2px 6px;font-size:11px;font-weight:700;line-height:1.4;color:#fff;background:#1d2327;border-radius:4px;">' . esc_html( $badge ) . '</span>'
			. '<span style="flex:1 1 auto;min-width:0;">'
			. ( $label !== '' ? '<span style="display:block;font-weight:600;font-size:12px;color:#1d2327;margin-bottom:2px;">' . esc_html( $label ) . '</span>' : '' )
			. '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener" style="display:block;font-family:Menlo,Consolas,monospace;font-size:12px;line-height:1.5;word-break:break-all;color:#2271b1;text-decoration:none;">' . esc_html( $display ) . '</a>'
			. '</span>'
			. '</div>';
	}


	/**
	 * Everything that needs the user's attention, worst first, in one list.
	 *
	 * The Security Check screen shows a table of every task, passed and failed
	 * together. That is the evidence, not the answer. This builds the answer: only
	 * what is still wrong, ranked so the first row is the one to do first.
	 *
	 * Two sources are merged here. Failing website checks come first, because a
	 * site that is not serving its own paths is broken for visitors right now.
	 * Failed security tasks follow, split by whether they are counted as a real
	 * risk or as hardening.
	 *
	 * @return array
	 */
	public function getActionItems() {

		$items     = array();
		$risktasks = $this->getRiskTasks();

		// The website is not serving its own paths, so visitors see a broken site.
		// Guarded because Ghost Doctor is a separate model; the row appears on its
		// own once that model is present rather than needing this method changed.
		if ( file_exists( _HMWP_MODEL_DIR_ . 'Ghostdoctor.php' ) ) {

			/** @var HMWP_Models_Ghostdoctor $gdmodel */
			$gdmodel  = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Ghostdoctor' );
			$gdreport = $gdmodel->getReport();

			if ( ! empty( $gdreport['time'] ) && empty( $gdreport['success'] ) ) {
				$items[] = array(
					'id'       => 'paths',
					'severity' => 'critical',
					'source'   => 'paths',
					'title'    => sprintf(
					/* translators: 1: Number of checks that are failing. */
						esc_html( _n( '%1$s website check is failing', '%1$s website checks are failing', (int) $gdreport['remaining'], 'hide-my-wp' ) ),
						(int) $gdreport['remaining']
					),
					'why'      => esc_html__( 'Parts of your website are not loading correctly. Ghost Doctor can repair most of these for you.', 'hide-my-wp' ),
					// Relative on purpose. admin_url() returns the renamed admin path,
					// and this row only exists because the renamed paths are not being
					// served, so that link would 404. The relative form resolves against
					// whichever admin URL the browser is already on, which by definition
					// works.
					'link'     => HMWP_Classes_Tools::getSettingsUrl( 'hmwp_permalinks', true ),
				);
			}
		}

		// Failed security tasks. The ones counted in the score come first.
		if ( ! empty( $this->report ) ) {
			foreach ( $this->report as $function => $row ) {

				if ( ! isset( $row['name'] ) || ! empty( $row['valid'] ) ) {
					continue;
				}

				$items[] = array(
					'id'         => 'task:' . $function,
					'severity'   => ( in_array( $function, $risktasks ) ? 'important' : 'suggested' ),
					'source'     => 'task',
					'title'      => wp_strip_all_tags( $row['name'] ),
					'why'        => ( isset( $row['solution'] ) ? wp_kses_post( $row['solution'] ) : '' ),
					'javascript' => ( isset( $row['javascript'] ) ? $row['javascript'] : '' ),
					'anchor'     => 'hmwp_tasks_widget',
				);
			}
		}

		// Replace the built in wording with the explanation written for this site,
		// where there is one. The built in string stays as the fallback, so a row
		// is never blank when the account server cannot be reached.
		$items = $this->mergeAiExplanations( $items );

		// Worst first
		$order = array( 'critical' => 0, 'important' => 1, 'suggested' => 2 );
		usort( $items, function ( $a, $b ) use ( $order ) {
			if ( $order[ $a['severity'] ] === $order[ $b['severity'] ] ) {
				return 0;
			}

			return ( $order[ $a['severity'] ] < $order[ $b['severity'] ] ) ? - 1 : 1;
		} );

		return $items;
	}

	/**
	 * Signature of a set of findings.
	 *
	 * The summary describes the whole website at once, so it is only valid while
	 * the website has the same set of problems. Hashing the sorted ids gives a
	 * value that changes the moment a finding appears or is fixed, which is what
	 * decides whether the stored summary may still be shown.
	 *
	 * @param array $items The findings.
	 *
	 * @return string
	 */
	public function getFindingsSignature( $items ) {

		$ids = array();

		foreach ( (array) $items as $item ) {
			if ( isset( $item['id'] ) ) {
				$ids[] = $item['id'];
			}
		}

		sort( $ids );

		return md5( implode( ',', $ids ) );
	}

	/**
	 * Overlay the stored AI explanations onto the findings.
	 *
	 * Matching is by id, so an explanation only shows next to the finding it was
	 * written for. A finding that has been fixed since simply stops appearing, and
	 * a new one that appeared after the explanation was requested keeps the built
	 * in wording until the next request.
	 *
	 * The severity the model returned is used as well. It judges urgency against
	 * this site's configuration, which the static rules cannot do.
	 *
	 * @param array $items The findings.
	 *
	 * @return array
	 */
	protected function mergeAiExplanations( $items ) {

		$stored = get_option( HMWP_AI_EXPLAIN );

		if ( ! is_array( $stored ) || empty( $stored['findings'] ) ) {
			return $items;
		}

		$levels = array( 'critical', 'important', 'suggested' );

		foreach ( $items as &$item ) {

			if ( ! isset( $item['id'] ) || ! isset( $stored['findings'][ $item['id'] ] ) ) {
				continue;
			}

			$ai = $stored['findings'][ $item['id'] ];

			if ( ! empty( $ai['headline'] ) ) {
				$item['title'] = $ai['headline'];
			}

			// Kept apart on purpose. The action is the one line shown in the list,
			// the explanation is what opens underneath when someone wants the
			// reasoning. Merging them was what turned every row into a paragraph.
			if ( ! empty( $ai['explanation'] ) ) {
				// Hold on to the built in advice. It carries the links to the
				// settings screens and the guides, which the model's prose does
				// not, so both belong in the panel that opens.
				$item['detail'] = $item['why'];
				$item['why']    = $ai['explanation'];
			}

			if ( ! empty( $ai['action'] ) ) {
				$item['action_text'] = $ai['action'];
			}

			// Only accept a level we know about
			if ( ! empty( $ai['severity'] ) && in_array( $ai['severity'], $levels ) ) {
				$item['severity'] = $ai['severity'];
			}

			$item['explained'] = true;
		}
		unset( $item );

		return $items;
	}


	/**
	 * How many findings sit at each severity.
	 *
	 * @param array $items The findings.
	 *
	 * @return array
	 */
	public function countBySeverity( $items ) {

		$counts = array( 'critical' => 0, 'important' => 0, 'suggested' => 0 );

		foreach ( (array) $items as $item ) {
			if ( isset( $item['severity'], $counts[ $item['severity'] ] ) ) {
				$counts[ $item['severity'] ] ++;
			}
		}

		return $counts;
	}


	/**
	 * Probe the website paths and assets to see if the config rules are active.
	 *
	 * Read-only on purpose: it performs the HTTP calls and returns what it found,
	 * it never saves an option. That makes it safe to call repeatedly, which is what
	 * an automated check-fix-recheck flow needs.
	 *
	 * @return array {
	 *     @type bool  $success       True when every probe passed.
	 *     @type array $errors        Failed probes. Each: role, label, url, badge.
	 *     @type array $checks        Every probe performed. Each: role, url, code, ok.
	 *     @type array $file_mappings Files recorded as loading through WordPress.
	 *     @type bool  $home_rendered True when the homepage returned a complete HTML document.
	 * }
	 */
	public function runFrontendProbe() {

		$errors     = $checks = $targets = array();
		$home_body  = '';
		$filesystem = HMWP_Classes_Tools::initFilesystem();
		$disable_name = HMWP_Classes_Tools::getOption( 'hmwp_disable_name' );

		//set hmwp_preview and not load the broken paths with WordPress rules
		$custom_logo_id = get_theme_mod( 'custom_logo' );
		if ( (int) $custom_logo_id > 0 ) {
			if ( $logo = wp_get_attachment_image_src( $custom_logo_id, 'full' ) ) {
				$image = $logo[0];

				if ( $filesystem->exists( str_replace( home_url( '/' ), ABSPATH, $image ) ) ) {
					$url       = $image . '?hmwp_preview=' . $disable_name;
					$url       = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rewrite' )->find_replace_url( $url );
					$targets[] = array( 'role' => 'logo', 'url' => $url );
				}
			}
		}

		if ( empty( $targets ) ) {

			$image = _HMWP_ASSETS_URL_ . 'img/logo.svg';
			if ( $filesystem->exists( str_replace( home_url( '/' ), ABSPATH, $image ) ) ) {
				$url       = _HMWP_ASSETS_URL_ . 'img/logo.svg?hmwp_preview=' . $disable_name;
				$url       = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rewrite' )->find_replace_url( $url );
				$targets[] = array( 'role' => 'logo', 'url' => $url );
			}

		}

		$url       = home_url( '/' ) . '?hmwp_preview=' . $disable_name;
		$url       = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rewrite' )->find_replace_url( $url );
		$targets[] = array( 'role' => 'home', 'url' => $url );

		if ( HMWP_Classes_Tools::getOption( 'hmwp_hideajax_admin' ) ) {
			$url = home_url( HMWP_Classes_Tools::getOption( 'hmwp_admin-ajax_url' ) ) . '?hmwp_preview=' . $disable_name;
		} else {
			$url = admin_url( HMWP_Classes_Tools::getOption( 'hmwp_admin-ajax_url' ) ) . '?hmwp_preview=' . $disable_name;
		}
		$url       = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rewrite' )->find_replace_url( $url );
		$targets[] = array( 'role' => 'ajax', 'url' => $url );


		// Disable REST API Access turns /wp-json away for visitors who are not
		// logged in, and this probe is a loopback call with cookies off, so it is
		// exactly the visitor that setting exists to refuse. A 404 here is the
		// setting working, not the site being broken, so the REST target expects a
		// block once the matching option is on.
		//
		// The preview token is deliberately not used. It does not lift the URL
		// blocking, and models/compatibility/Others.php empties $_COOKIE when it is
		// present, which would only make the request more anonymous than it already
		// is. The target is still probed either way, so a renamed wp-json that has
		// no working rewrite is still reported.
		if ( ! HMWP_Classes_Tools::isPHPPermalink() ) {
			// The renamed REST path only resolves with the trailing slash
			$url         = home_url() . '/' . trailingslashit( HMWP_Classes_Tools::getOption( 'hmwp_wp-json' ) );
			$rest_closed = (bool) HMWP_Classes_Tools::getOption( 'hmwp_disable_rest_api' );
		} else {
			$url         = home_url() . '/index.php?rest_route=/';
			$rest_closed = (bool) HMWP_Classes_Tools::getOption( 'hmwp_disable_rest_api_param' );
		}
		$targets[] = array( 'role' => 'rest', 'url' => $url, 'block_is_ok' => $rest_closed );

		// Text Mapping renames classes/IDs/JS variables in the output and can break a
		// theme that uses a renamed label as a style hook. When an active mapping exists,
		// also validate that the homepage actually renders, not just that it returns 200.
		$text_mapping     = json_decode( HMWP_Classes_Tools::getOption( 'hmwp_text_mapping' ), true );
		$has_text_mapping = ! empty( $text_mapping['from'] );

		foreach ( $targets as $target ) {

			$role = $target['role'];
			$url  = $target['url'];

			if ( is_ssl() ) {
				$url = str_replace( 'http://', 'https://', $url );
			}

			$response = HMWP_Classes_Tools::hmwp_localcall( $url, array(
				'redirection' => 1,
				'cookies'     => false
			) );

			$code = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );
			$failed = ( ! is_wp_error( $response ) && in_array( $code, array( 404, 302, 301 ) ) );

			// With REST closed to visitors, both answers are correct and which one
			// this probe gets depends on the host. The call is a loopback, and WP
			// Ghost whitelists the site's own address, so on many servers it comes
			// back 200 because the probe was never treated as a visitor. On hosts
			// where the loopback arrives from a public address or through a proxy
			// it is treated as a visitor and correctly refused. Neither means the
			// site is broken, so only a real error is counted as a failure.
			if ( ! empty( $target['block_is_ok'] ) && in_array( $code, array( 401, 403, 404 ), true ) ) {
				$failed = false;
			}

			$checks[] = array( 'role' => $role, 'url' => $url, 'code' => $code, 'ok' => ! $failed );

			if ( $failed ) {
				$errors[] = array(
					'role'  => $role,
					'label' => wp_remote_retrieve_response_message( $response ),
					'url'   => $url,
					'badge' => $code
				);
			} elseif ( $role === 'home' && ! is_wp_error( $response ) && $code === 200 ) {
				// Keep the homepage HTML to validate the render and to probe theme assets below
				$home_body = wp_remote_retrieve_body( $response );

				// Text Mapping renames classes/IDs and can break a theme that styles a renamed label.
				// Broken render heuristic: empty body or truncated HTML (no closing tag)
				if ( $has_text_mapping && ( $home_body === '' || stripos( $home_body, '</html>' ) === false ) ) {
					$errors[] = array(
						'role'  => 'render',
						'label' => esc_html__( 'The page did not render correctly. A renamed Text Mapping label may be used by your theme.', 'hide-my-wp' ),
						'url'   => $url,
						'badge' => esc_html__( 'Layout', 'hide-my-wp' )
					);
				}
			}
		}

		//Test new admin path. Send all cookies to admin path
		if ( HMWP_Classes_Tools::getDefault( 'hmwp_admin_url' ) <> HMWP_Classes_Tools::getOption( 'hmwp_admin_url' ) ) {

			$url = admin_url( 'admin.php' );
			$url = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rewrite' )->find_replace_url( $url );

			if ( is_ssl() ) {
				$url = str_replace( 'http://', 'https://', $url );
			}

			$response = HMWP_Classes_Tools::hmwp_localcall( $url, array(
				'redirection' => 1,
				'cookies'     => $_COOKIE
			) );

			$code   = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );
			$failed = ( ! is_wp_error( $response ) && in_array( $code, array( 404, 302, 301 ) ) );

			$checks[] = array( 'role' => 'admin', 'url' => $url, 'code' => $code, 'ok' => ! $failed );

			if ( $failed ) {
				$errors[] = array(
					'role'  => 'admin',
					'label' => wp_remote_retrieve_response_message( $response ),
					'url'   => $url,
					'badge' => $code
				);
			}
		}

		// Probe a sample of the theme's CSS/JS assets referenced by the homepage. This catches a
		// broken layout where the page returns 200 but its stylesheets/scripts don't load because
		// the renamed wp-content/wp-includes paths are not served by the config rules.
		if ( $home_body !== '' && preg_match_all( '/<(?:link[^>]+href|script[^>]+src)=["\']([^"\']+\.(?:css|js)(?:\?[^"\']*)?)["\']/i', $home_body, $matches ) ) {

			$host   = wp_parse_url( home_url(), PHP_URL_HOST );
			$assets = array();

			foreach ( $matches[1] as $asset ) {
				// Normalize protocol-relative URLs
				if ( strpos( $asset, '//' ) === 0 ) {
					$asset = ( is_ssl() ? 'https:' : 'http:' ) . $asset;
				}

				// Only test same-host static assets and skip duplicates
				if ( wp_parse_url( $asset, PHP_URL_HOST ) === $host && ! in_array( $asset, $assets ) ) {
					$assets[] = $asset;
				}

				// Keep the check fast: test up to 4 assets
				if ( count( $assets ) >= 4 ) {
					break;
				}
			}

			// When the text mapping in CSS and JS files is on, the server rules send the
			// CSS and JS to WordPress on purpose, so those files are expected to be
			// served by the plugin and must not be probed with the fallback disabled
			$dynamicfiles = ( ( defined( 'HMW_DYNAMIC_FILES' ) && HMW_DYNAMIC_FILES ) ||
			                  ( HMWP_Classes_Tools::getOption( 'hmwp_mapping_text_show' ) && HMWP_Classes_Tools::getOption( 'hmwp_mapping_file' ) ) );

			foreach ( $assets as $asset ) {

				$extension = strtolower( (string) pathinfo( (string) wp_parse_url( $asset, PHP_URL_PATH ), PATHINFO_EXTENSION ) );

				// Bypass the WordPress fallback handler (showFile) so only the server config
				// rules serve the file. Otherwise a broken rewrite is masked by a 200 response.
				$test = add_query_arg( 'hmwp_preview', $disable_name, $asset );

				if ( is_ssl() ) {
					$test = str_replace( 'http://', 'https://', $test );
				}

				$response = HMWP_Classes_Tools::hmwp_localcall( $test, array(
					'redirection' => 0,
					'cookies'     => false
				) );

				$code   = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );
				$failed = ( ! is_wp_error( $response ) && in_array( $code, array( 404, 302, 301 ) ) );

				// The CSS and JS are sent to WordPress on purpose when the text mapping in
				// files is on, so ask for the file the way a visitor does before calling it
				// broken. An asset that answers to a normal request is not broken.
				if ( $failed && $dynamicfiles && in_array( $extension, array( 'js', 'css', 'scss' ), true ) ) {

					$plain = ( is_ssl() ? str_replace( 'http://', 'https://', $asset ) : $asset );

					$response = HMWP_Classes_Tools::hmwp_localcall( $plain, array(
						'redirection' => 0,
						'cookies'     => false
					) );

					$code   = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );
					$failed = ( ! is_wp_error( $response ) && in_array( $code, array( 404, 302, 301 ) ) );
				}

				$checks[] = array( 'role' => 'asset', 'url' => $asset, 'code' => $code, 'ok' => ! $failed );

				if ( $failed ) {
					$errors[] = array(
						'role'  => 'asset',
						'label' => esc_html__( 'Theme asset not loading', 'hide-my-wp' ),
						'url'   => $asset,
						'badge' => $code
					);
				}
			}
		}

		// Runtime fallback state: when assets were auto-detected loading through WordPress instead of
		// the config rules, the plugin temporarily disabled path hiding (prevent_slow_loading).
		$file_mappings = (array) HMWP_Classes_Tools::getOption( 'file_mappings' );

		// If the paths/assets failed and the safe-fallback already recorded offending files,
		// list them as context so the user sees exactly which files load through WordPress.
		if ( ! empty( $errors ) && ! empty( $file_mappings ) ) {
			foreach ( $file_mappings as $mapping ) {
				$errors[] = array(
					'role'  => 'bypass',
					'label' => esc_html__( 'Loaded through WordPress instead of the config rules', 'hide-my-wp' ),
					'url'   => $mapping,
					'badge' => esc_html__( 'Bypass', 'hide-my-wp' )
				);
			}
		}

		return array(
			'success'       => empty( $errors ),
			'errors'        => $errors,
			'checks'        => $checks,
			'file_mappings' => $file_mappings,
			'home_rendered' => ( $home_body !== '' && stripos( $home_body, '</html>' ) !== false )
		);
	}


	/**
	 * Run the actions on submit
	 *
	 * @throws Exception
	 */
	public function action() {
		parent::action();

		// Check if the current user has the 'hmwp_manage_settings' capability
		if ( ! HMWP_Classes_Tools::userCan( HMWP_CAPABILITY ) ) {
			return;
		}

		switch ( HMWP_Classes_Tools::getValue( 'action' ) ) {
			case 'hmwp_securitycheck':

				$this->doSecurityCheck();

				if ( HMWP_Classes_Tools::isAjax() ) {
					wp_send_json_success( esc_html__( 'Done!', 'hide-my-wp' ) );
				}

				break;

			case 'hmwp_frontendcheck':

				// Probe the paths and assets through the shared method. The same code
				// now answers the Frontend Test and Ghost Doctor, so the two can no
				// longer disagree about whether the site is serving correctly.
				$probe         = $this->runFrontendProbe();
				$file_mappings = $probe['file_mappings'];

				// runFrontendProbe returns structured rows; this screen renders HTML.
				$error = array();
				foreach ( $probe['errors'] as $row ) {
					$error[] = $this->frontendError( $row['label'], $row['url'], $row['badge'] );
				}

				if ( HMWP_Classes_Tools::isAjax() ) {
					if ( empty( $error ) ) {
						$message   = array();

						// Recovery: paths and assets load correctly now, so lift the temporary
						// safe-fallback (prevent_slow_loading) by clearing the recorded mappings.
						if ( ! empty( $file_mappings ) ) {
							HMWP_Classes_Tools::saveOptions( 'file_mappings', array() );
							$message[] = '<div style="margin-bottom:6px;">' . esc_html__( 'The website assets are now loading correctly through the config rules. The temporary safe mode has been lifted.', 'hide-my-wp' ) . '</div>';
						}

						$message[] = '<div style="font-weight:600;margin-bottom:6px;">' . esc_html__( 'Great! The new paths are loading correctly.', 'hide-my-wp' ) . '</div>';
						if ( HMWP_Classes_Tools::getOption( 'prevent_slow_loading' ) ) {
							// The action and the nonce live in the #hmwp_fixsettings_form carrier
							// rendered by view/SecurityCheck.php, which hmwp_fixSettings() reads.
							// Repeating the form here would duplicate that id in the page.
							$message[] = '<div class="col-sm-12 p-0 my-2 switch switch-xxs" style="font-size: 0.9rem;">
                                            <input type="checkbox" id="prevent_slow_loading" name="prevent_slow_loading" onChange="jQuery(this).hmwp_fixSettings(\'prevent_slow_loading\',0);" class="switch" ' . ( HMWP_Classes_Tools::getOption( 'prevent_slow_loading' ) ? 'checked="checked"' : '' ) . ' value="1"/>
											<label for="prevent_slow_loading">' . /* translators: 1: Feature label "Prevent Broken Website Layout". */ sprintf( esc_html__( 'You can now turn off "%1$s" option.', 'hide-my-wp' ), esc_html__( 'Prevent Broken Website Layout', 'hide-my-wp' ) ) . '</label>
										 </div>';
						}
						if ( HMWP_Classes_Tools::isCachePlugin() && ! HMWP_Classes_Tools::getOption( 'hmwp_change_in_cache' ) ) {
							$message[] = '<div class="col-sm-12 p-0 my-2 switch switch-xxs" style="font-size: 0.9rem;">
                                            <input type="checkbox" id="hmwp_change_in_cache" name="hmwp_change_in_cache" onChange="jQuery(this).hmwp_fixSettings(\'hmwp_change_in_cache\',1);" class="switch" ' . ( HMWP_Classes_Tools::getOption( 'hmwp_change_in_cache' ) ? 'checked="checked"' : '' ) . ' value="1"/>
                                            <label for="hmwp_change_in_cache">' . /* translators: 1: Feature label "Change Paths in Cached Files". */ sprintf( esc_html__( 'You can now turn on "%1$s" option.', 'hide-my-wp' ), esc_html__( 'Change Paths in Cached Files', 'hide-my-wp' ) ) . '</label>
                                         </div>';
						}

						wp_send_json_success( join( '', $message ) );
					} else {
						$html  = '<div style="font-weight:600;margin-bottom:10px;">' . esc_html__( 'Error! The new paths are not loading correctly. Clear all cache and try again.', 'hide-my-wp' ) . '</div>';
						$html .= '<div style="text-align:left;">' . join( '', $error ) . '</div>';

						if ( HMWP_Classes_Tools::isNginx() ) {
							$html .= '<div style="margin-top:12px;font-weight:700;"><a href="' . esc_url( HMWP_Classes_Tools::getOption( 'hmwp_plugin_website' ) . '/kb/setup-wp-ghost-on-nginx-server/' ) . '" target="_blank" rel="noopener">' . esc_html__( "Don't forget to reload the Nginx service.", 'hide-my-wp' ) . '</a></div>';
						}

						wp_send_json_error( $html );
					}
				}

				break;

			case 'hmwp_fixsettings':

				if ( HMWP_Classes_Tools::getIsset( 'name' ) && HMWP_Classes_Tools::getIsset( 'value' ) ) {

					$message = '';

					//Initialize WordPress Filesystem
					$wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

					$name  = HMWP_Classes_Tools::getValue( 'name', false, true );
					$value = HMWP_Classes_Tools::getValue( 'value', false, true );
					$options = HMWP_Classes_Tools::getOptions();

					if ( strpos( $name, ',' ) !== false && strpos( $value, ',' ) !== false ) {
						$names = explode( ',', $name );
						$values = explode( ',', $value );
					} else {
						$names = array( $name );
						$values = array( $value );
					}

					foreach ( $names as $index => $name ) {
						if ( isset($values[$index] ) && in_array( $name, array_keys( $options ) ) ) {
							HMWP_Classes_Tools::saveOptions( $name, $values[$index] );
							//call it in case of rule change
							HMWP_Classes_ObjController::getClass( 'HMWP_Models_Settings' )->saveRules();

							if ( HMWP_Classes_Tools::isIIS() && HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rules' )->isConfigWritable() ) {
								//Flush the changes for IIS server
								HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rewrite' )->flushChanges();
							}

							//Hide the common WP Files that migth be visible to detectors
							if ( $name == 'hmwp_hide_commonfiles' ) {
								$wp_filesystem->delete( HMWP_Classes_Tools::getRootPath() . 'readme.html' );
								$wp_filesystem->delete( HMWP_Classes_Tools::getRootPath() . 'license.txt' );
								$wp_filesystem->delete( HMWP_Classes_Tools::getRootPath() . 'wp-config-sample.php' );
							}

							$message = esc_html__( 'Saved! You can run the test again.', 'hide-my-wp' );
							if ( HMWP_Classes_Tools::isNginx() || HMWP_Classes_Tools::isCloudPanel() ) {
								$message .= '<br />' . esc_html__( "Don't forget to reload the Nginx service.", 'hide-my-wp' ) . ' ' . '<strong><a href="' . esc_url( HMWP_Classes_Tools::getOption( 'hmwp_plugin_website' ) . '/kb/setup-wp-ghost-on-nginx-server/' ) . '" target="_blank" style="color: red">' . esc_html__( "Learn How", 'hide-my-wp' ) . '</a></strong>';
							}

						}
					}

					if ( HMWP_Classes_Tools::isAjax() ) {
						wp_send_json_success( $message );
					}

					break;
				}

				if ( HMWP_Classes_Tools::isAjax() ) {
					wp_send_json_error( esc_html__( 'Could not fix it. You need to change it manually.', 'hide-my-wp' ) );
				}

				break;
			case 'hmwp_fixconfig':

				$name  = HMWP_Classes_Tools::getValue( 'name' );
				$value = HMWP_Classes_Tools::getValue( 'value', null );

				if ( ! in_array( $name, array(
						'WP_DEBUG',
						'SCRIPT_DEBUG',
						'DISALLOW_FILE_EDIT'
					) ) || ! in_array( $value, array( 'true', 'false' ) ) ) {

					if ( HMWP_Classes_Tools::isAjax() ) {
						wp_send_json_error( esc_html__( 'Could not fix it. You need to change it manually.', 'hide-my-wp' ) );
					}
					break;
				}

				if ( $name && isset( $value ) ) {
					if ( $config_file = HMWP_Classes_Tools::getConfigFile() ) {

						/** @var HMWP_Models_Rules $rulesModel */
						$rulesModel = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rules' );

						$wp_filesystem = HMWP_Classes_ObjController::initFilesystem();
						if ( ! $rulesModel->isConfigWritable( $config_file ) ) {
							$current_permission = $wp_filesystem->getchmod( $config_file );
							$wp_filesystem->chmod( $config_file, 0644 );
						}

						if ( $rulesModel->isConfigWritable( $config_file ) ) {
							$find    = "define\s?\(\s?'$name'";
							$replace = "define('$name',$value);" . PHP_EOL;
							if ( $rulesModel->findReplace( $find, $replace, $config_file ) ) {

								if ( isset( $current_permission ) ) {
									$wp_filesystem->chmod( $config_file, octdec( $current_permission ) );
								}

								if ( HMWP_Classes_Tools::isAjax() ) {
									wp_send_json_success( esc_html__( 'Saved! You can run the test again.', 'hide-my-wp' ) );
								}
								break;
							}
						}

					}
				}
				//refresh the security scan
				$this->doSecurityCheck();

				if ( HMWP_Classes_Tools::isAjax() ) {
					wp_send_json_error( esc_html__( 'Could not fix it. You need to change it manually.', 'hide-my-wp' ) );
				}
				break;
			case 'hmwp_fixprefix':

				/** @var HMWP_Models_Prefix $prefixModel */
				$prefixModel = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Prefix' );

				//to change or undo the database prefix (true = change, false = undo)
				if ( HMWP_Classes_Tools::getValue( 'value' ) == 'true' ) {
					//Generate random database prefix
					$prefixModel->setPrefix( $prefixModel->generateValidateNewPrefix() );
				}

				//Force the recheck security notification
				delete_option( HMWP_SECURITY_CHECK );
				remove_all_actions( 'shutdown' );

				//run the process to change the prefix
				if ( $prefixModel->changePrefix() ) {

					$new_prefix = $prefixModel->getPrefix(); // or however you store it

					global $wpdb;
					$GLOBALS['table_prefix'] = $new_prefix;
					$wpdb->set_prefix( $new_prefix, true );

					// Flush after rebinding
					flush_rewrite_rules( true );

					//empty the cache
					HMWP_Classes_Tools::emptyCache();

					//Flush the rules in WordPress
					flush_rewrite_rules();

					//wait for config refresh
					sleep( 10 );

					if ( HMWP_Classes_Tools::isAjax() ) {
						wp_send_json_success( esc_html__( 'Saved! Reload the page.', 'hide-my-wp' ) );
					}

					break;

				}

				if ( HMWP_Classes_Tools::isAjax() ) {
					wp_send_json_error( esc_html__( 'Could not fix it. You need to change it manually.', 'hide-my-wp' ) );
				}

				break;
			case 'hmwp_fixpermissions':
				$value = HMWP_Classes_Tools::getValue( 'value' );

				/** @var HMWP_Models_Permissions $permissionModel */
				$permissionModel = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Permissions' );

				//run the process to change the prefix
				if ( $permissionModel->changePermissions( $value ) ) {

					//refresh the security scan
					$this->doSecurityCheck();

					if ( HMWP_Classes_Tools::isAjax() ) {
						wp_send_json_success( esc_html__( 'Saved! You can run the test again.', 'hide-my-wp' ) );
					}
					break;
				}

				if ( HMWP_Classes_Tools::isAjax() ) {
					wp_send_json_error( esc_html__( 'Could not fix it. You need to change it manually.', 'hide-my-wp' ) );
				}
				break;
			case 'hmwp_fixsalts':

				/** @var HMWP_Models_Salts $saltsModel */ $saltsModel = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Salts' );

				//run the process to change the prefix
				if ( $saltsModel->validateSalts() ) {
					if ( $saltsModel->generateSalts() ) {
						update_option( HMWP_SALT_CHANGED, array( 'timestamp' => current_time( 'timestamp', 1 ) ) );

						//Force the recheck security notification
						delete_option( HMWP_SECURITY_CHECK );

						if ( HMWP_Classes_Tools::isAjax() ) {
							wp_send_json_success( esc_html__( 'Saved! You can run the test again.', 'hide-my-wp' ) . '<script>location.href = "' . site_url() . '/' . HMWP_Classes_Tools::getOption( 'hmwp_login_url' ) . '?redirect_to=' . urlencode( site_url( 'wp-admin/admin.php?page=hmwp_securitycheck' ) ) . '"</script>' );
						}
						break;
					}
				}

				if ( HMWP_Classes_Tools::isAjax() ) {
					wp_send_json_error( esc_html__( 'Could not fix it. You need to change it manually.', 'hide-my-wp' ) );
				}
				break;
			case 'hmwp_fixadmin':
				global $wpdb;
				$username = HMWP_Classes_Tools::getValue( 'name' );

				if ( ! validate_username( $username ) ) {
					if ( HMWP_Classes_Tools::isAjax() ) {
						wp_send_json_error( esc_html__( 'Invalid username.', 'hide-my-wp' ) );
					}
					break;
				}

				if ( username_exists( $username ) ) {
					if ( HMWP_Classes_Tools::isAjax() ) {
						wp_send_json_error( esc_html__( 'A user already exists with that username.', 'hide-my-wp' ) );
					}
					break;
				}

				$admin = false;
				if ( username_exists( 'admin' ) ) {
					$admin = 'admin';
				} elseif ( username_exists( 'administrator' ) ) {
					$admin = 'administrator';
				}

				if ( $admin ) {
					// Query main user table
					$wpdb->query( $wpdb->prepare( "UPDATE `{$wpdb->users}` SET user_login = %s WHERE user_login = %s", $username, $admin ) ); //phpcs:ignore

					// Process sitemeta if we're in a multi-site situation
					if ( HMWP_Classes_Tools::isMultisites() ) {
						$old_admins = $wpdb->get_var( "SELECT meta_value FROM `" . $wpdb->sitemeta . "` WHERE meta_key = 'site_admins'" ); //phpcs:ignore
						$new_admins = str_replace( strlen( $admin ) . ':"' . $admin . '"', strlen( $username ) . ':"' . $username . '"', $old_admins );
						$wpdb->query( $wpdb->prepare( "UPDATE `{$wpdb->sitemeta}` SET meta_value = %s WHERE meta_key = 'site_admins'", $new_admins ) ); //phpcs:ignore
					}
				}

				// Force the recheck security notification
				HMWP_Classes_ObjController::getClass( 'HMWP_Controllers_SecurityCheck' )->resetSecurityCheck();

				if ( HMWP_Classes_Tools::isAjax() ) {
					wp_send_json_success( esc_html__( 'Saved! You can run the test again.', 'hide-my-wp' ) . '<script>location.href = "' . site_url() . '/' . HMWP_Classes_Tools::getOption( 'hmwp_login_url' ) . '?redirect_to=' . urlencode( site_url( 'wp-admin/admin.php?page=hmwp_securitycheck' ) ) . '"</script>' );
				}

				break;
			case 'hmwp_fixupgrade':

				if ( ! function_exists( 'get_plugins' ) ) {
					require_once ABSPATH . 'wp-admin/includes/plugin.php';
				}

				$all_plugins = get_plugins();

				include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

				foreach ( $all_plugins as $plugin_slug => $value ) {
					$upgrader = new \Plugin_Upgrader( new \WP_Ajax_Upgrader_Skin() );
					$upgrader->upgrade( $plugin_slug );
				}

				if ( HMWP_Classes_Tools::isAjax() ) {
					wp_send_json_success( esc_html__( 'Saved! You can run the test again.', 'hide-my-wp' ) );
				}

				break;

			case 'hmwp_securityexclude':

				$name = HMWP_Classes_Tools::getValue( 'name' );
				if ( $name ) {
					if ( ! $tasks_ignored = get_option( HMWP_SECURITY_CHECK_IGNORE ) ) {
						$tasks_ignored = array();
					}

					$tasks_ignored[] = $name;
					$tasks_ignored   = array_unique( $tasks_ignored );
					update_option( HMWP_SECURITY_CHECK_IGNORE, $tasks_ignored );
				}

				if ( HMWP_Classes_Tools::isAjax() ) {
					wp_send_json_success( esc_html__( 'Saved! This task will be ignored on future tests.', 'hide-my-wp' ) );
				}

				break;
			case 'hmwp_resetexclude':

				update_option( HMWP_SECURITY_CHECK_IGNORE, array() );

				if ( HMWP_Classes_Tools::isAjax() ) {
					wp_send_json_success( esc_html__( 'Saved! You can run the test again.', 'hide-my-wp' ) );
				}

				break;
		}


	}

	/**
	 * Check PHP version
	 *
	 * @return array
	 */
	public function checkPHP() {
		$phpversion = phpversion();

		if ( $phpversion <> '' && strpos( $phpversion, '-' ) !== false ) {
			$phpversion = substr( $phpversion, 0, strpos( $phpversion, '-' ) );
		}

		return array(
			'value' => $phpversion,
			'valid' => ( version_compare( $phpversion, '8.3', '>=' ) ),
		);
	}

	/**
	 * Check if mysql is up-to-date
	 *
	 * @return array
	 */
	public function checkMysql() {
		global $wpdb;

		$mysql_version = $wpdb->db_version();

		return array(
			'value' => $mysql_version,
			'valid' => ( version_compare( $mysql_version, '8.0', '>' ) ),
		);

	}

	/**
	 * Check is WP_DEBUG is true
	 *
	 * @return array|bool
	 */
	public function checkWPDebug() {
		if ( defined( 'WP_DEBUG' ) ) {
			if ( defined( 'WP_DEBUG_DISPLAY' ) && ! WP_DEBUG_DISPLAY ) {
				return array(
					'value' => esc_html__( 'No', 'hide-my-wp' ),
					'valid' => true
				);
			} else {
				return array(
					'value' => ( WP_DEBUG ? esc_html__( 'Yes', 'hide-my-wp' ) : esc_html__( 'No', 'hide-my-wp' ) ),
					'valid' => ! WP_DEBUG,
				);
			}

		}

		return false;
	}

	/**
	 * Check if DB debugging is enabled
	 *
	 * @return array
	 */
	static function checkDbDebug() {
		global $wpdb;
		$show_errors = ( $wpdb->show_errors && ! HMWP_Classes_Tools::getOption( 'hmwp_disable_debug' ) );

		return array(
			'value' => ( $show_errors ? esc_html__( 'Yes', 'hide-my-wp' ) : esc_html__( 'No', 'hide-my-wp' ) ),
			'valid' => ! $show_errors,
		);

	}

	/**
	 * Check if global WP JS debugging is enabled
	 *
	 * @return array|bool
	 */
	static function checkScriptDebug() {
		if ( defined( 'SCRIPT_DEBUG' ) ) {
			return array(
				'value' => ( SCRIPT_DEBUG ? esc_html__( 'Yes', 'hide-my-wp' ) : esc_html__( 'No', 'hide-my-wp' ) ),
				'valid' => ! SCRIPT_DEBUG,
			);
		}

		return false;
	}

	/**
	 * Check if the backend is SSL or not
	 *
	 * @return array
	 */
	public function checkSSL() {

		$is_ssl = is_ssl()  || ( strpos( site_url(), 'https') !== false ) || ( defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN );

		return array(
			'value' => ( $is_ssl ? esc_html__( 'Yes', 'hide-my-wp' ) : esc_html__( 'No', 'hide-my-wp' ) ),
			'valid' => ( $is_ssl ),
		);
	}

	/**
	 * Check Admin User declared
	 *
	 * @return array
	 */
	public function checkAdminUsers() {
		if ( ! $admin = username_exists( 'admin' ) ) {
			$admin = username_exists( 'administrator' );
		}

		return array(
			'value' => ( ! empty( $admin ) ? esc_html__( 'Yes', 'hide-my-wp' ) : esc_html__( 'No', 'hide-my-wp' ) ),
			'valid' => ( empty( $admin ) ),
		);
	}

	/**
	 * Check WordPress version
	 *
	 * @return array|bool
	 */
	public function checkWP() {
		global $wp_version;
		if ( isset( $wp_version ) ) {

			$url      = 'https://api.wordpress.org/core/version-check/1.7/';
			$response = HMWP_Classes_Tools::hmwp_localcall( $url, array( 'timeout' => 10 ) );

			// Bail out on request failure
			if ( is_wp_error( $response ) ) {
				return false;
			}

			// Some wrappers may return a string body directly; normalize to array if needed
			if ( ! is_array( $response ) ) {
				return false;
			}

			$body = $response['body'] ?? '';
			if ( $body === '' ) {
				return false;
			}

			$obj = json_decode( $body );
			if ( ! $obj || empty( $obj->offers[0]->version ) ) {
				return false;
			}

			$wp_last_version = $obj->offers[0]->version;

			return array(
				'value'   => $wp_version,
				'valid'   => version_compare( $wp_version, $wp_last_version, '>=' ),
				'version' => $wp_last_version,
			);
		}

		return false;
	}

	/**
	 * Check if plugins are up-to-date
	 *
	 * @return array
	 */
	public function checkPluginsUpdates() {
		//Get the current update info
		$current = get_site_transient( 'update_plugins' );

		if ( ! is_object( $current ) ) {

			$current = new stdClass;

			set_site_transient( 'update_plugins', $current );

			// run the internal plugin update check
			wp_update_plugins();

			$current = get_site_transient( 'update_plugins' );
		}

		if ( isset( $current->response ) && is_array( $current->response ) ) {
			$plugin_update_cnt = count( $current->response );
		} else {
			$plugin_update_cnt = 0;
		}

		$plugins = array();
		foreach ( $current->response as $tmp ) {
			if ( isset( $tmp->slug ) ) {
				$plugins[] = $tmp->slug;
			}
		}

		return array(
			/* translators: 1: Number of outdated plugins, 2: List of plugin names wrapped in <span> with line breaks. */
			'value' => ( $plugin_update_cnt > 0 ? wp_kses_post( sprintf( __( '%1$s plugin(s) are outdated: %2$s', 'hide-my-wp' ), (int) $plugin_update_cnt, '<br /><span style="font-weight: normal; color: #dc3545!important">' . wp_kses_post( join( '<br />', $plugins ) ) . '</span>' ) ) : esc_html__( 'All plugins are up to date', 'hide-my-wp' ) ),
			'valid' => ( ! $plugin_update_cnt ),
		);

	}

	/**
	 * Check if themes are up-to-date
	 *
	 * @return array
	 */
	public function checkThemesUpdates() {
		$current          = get_site_transient( 'update_themes' );
		$themes           = array();
		$theme_update_cnt = 0;

		if ( ! is_object( $current ) ) {
			$current = new stdClass;
		}

		set_site_transient( 'update_themes', $current );
		wp_update_themes();

		$current = get_site_transient( 'update_themes' );

		if ( isset( $current->response ) && is_array( $current->response ) ) {
			$theme_update_cnt = count( $current->response );
		}

		foreach ( $current->response as $theme_name => $tmp ) {
			$themes[] = $theme_name;
		}

		return array(
			/* translators: 1: Number of outdated themes, 2: List of theme names wrapped in <span> with line breaks. */
			'value' => ( $theme_update_cnt > 0 ? wp_kses_post( sprintf( __( '%1$s theme(s) are outdated: %2$s', 'hide-my-wp' ), (int) $theme_update_cnt, '<br /><span style="font-weight: normal; color: #dc3545!important">' . wp_kses_post( join( '<br />', $themes ) ) . '</span>' ) ) : esc_html__( 'Themes are up to date', 'hide-my-wp' ) ),
			'valid' => ( ! $theme_update_cnt ),
		);

	}

	/**
	 * Check the old plugins from WordPress directory
	 *
	 * @return array
	 */
	public function checkOldPlugins() {
		global $hmwp_plugin_details;

		$hmwp_plugin_details = array();
		$bad                 = array();
		$active_plugins      = get_option( 'active_plugins', array() );

		foreach ( $active_plugins as $plugin_path ) {
			$plugin = explode( '/', $plugin_path );
			$plugin = @$plugin[0];
			if ( empty( $plugin ) || empty( $plugin_path ) ) {
				continue;
			}

			$response = HMWP_Classes_Tools::hmwp_localcall( 'https://api.wordpress.org/plugins/info/1.1/?action=plugin_information&request%5Bslug%5D=' . $plugin, array( 'timeout' => 5 ) );

			if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) == 200 && wp_remote_retrieve_body( $response ) ) {
				$details = wp_remote_retrieve_body( $response );
				$details = json_decode( $details, true );
				if ( empty( $details ) ) {
					continue;
				}
				$hmwp_plugin_details[ $plugin_path ] = $details;
				$updated                             = strtotime( $details['last_updated'] );
				if ( $updated + 365 * DAY_IN_SECONDS < time() ) {
					$bad[ $plugin_path ] = true;
				}
			}
		} // foreach active plugin

		if ( ! empty( $bad ) ) {
			$plugins = get_plugins();
			foreach ( $bad as $plugin_path => $tmp ) {
				if ( $plugins[ $plugin_path ]['Name'] <> '' ) {
					$bad[ $plugin_path ] = $plugins[ $plugin_path ]['Name'];
				}
			}
		}

		return array(
			/* translators: 1: Number of outdated plugins, 2: List of plugin names wrapped in <span> with line breaks. */
			'value' => ( count( $bad ) > 0 ? wp_kses_post( sprintf( __( '%1$s plugin(s) have NOT been updated by their developers in the past 12 months: %2$s', 'hide-my-wp' ), (int) count( $bad ), '<br /><span style="font-weight: normal; color: #dc3545!important">' . wp_kses_post( join( '<br />', $bad ) ) . '</span>' ) ) : esc_html__( 'All plugins have been updated by their developers in the past 12 months', 'hide-my-wp' ) ),
			'valid' => empty( $bad ),
		);

	}

	/**
	 * Check incompatible plugins
	 *
	 * @return array
	 */
	public function checkIncompatiblePlugins() {
		global $hmwp_plugin_details, $wp_version;

		$bad = array();

		if ( empty( $hmwp_plugin_details ) ) {
			$this->checkOldPlugins();
		}

		foreach ( $hmwp_plugin_details as $plugin_path => $plugin ) {
			if ( version_compare( $wp_version, $plugin['tested'], '>' ) ) {
				$bad[ $plugin_path ] = $plugin;
			}
		} // foreach active plugins we have details on

		if ( ! empty( $bad ) ) {
			$plugins = get_plugins();
			foreach ( $bad as $plugin_path => $tmp ) {
				$bad[ $plugin_path ] = $plugins[ $plugin_path ]['Name'];
			}
		}

		return array(
			'value' => ( empty( $bad ) ? esc_html__( 'All plugins are compatible', 'hide-my-wp' ) : implode( '<br />', $bad ) ),
			'valid' => empty( $bad ),
		);

	}

	/**
	 * Check if version is displayed in source code
	 *
	 * @return array
	 */
	public function checkVersionDisplayed() {
		return array(
			'value' => ( HMWP_Classes_Tools::getOption( 'hmwp_hide_version' ) ? 'Removed' : 'Visible' ),
			'valid' => ( HMWP_Classes_Tools::getOption( 'hmwp_hide_version' ) ),
		);
	}

	/**
	 * Check if PHP is exposed
	 *
	 * @return array
	 */
	public function checkExposedPHP() {

		if ( ! isset( $this->html ) || $this->html == '' ) {
			$this->getSourceCode();
		}

		$check = false;
		if ( isset( $this->headers ) && ! empty( $this->headers ) ) {
			if ( isset( $this->headers['X-Powered-By'] ) && is_string( $this->headers['X-Powered-By'] ) && stripos( $this->headers['X-Powered-By'], 'PHP' ) !== false ) {
				$check = true;
			}
			if ( isset( $this->headers['server'] ) && is_string( $this->headers['server'] ) && stripos( $this->headers['server'], 'PHP' ) !== false ) {
				$check = true;
			}
		} else {
			$check = (bool) ini_get( 'expose_php' );
		}

		return array(
			'value' => ( $check ? esc_html__( 'Yes', 'hide-my-wp' ) : esc_html__( 'No', 'hide-my-wp' ) ),
			'valid' => ( ! $check ),
		);

	}

	/**
	 * Check Database Prefix
	 *
	 * @return array
	 */
	public function checkDBPrefix() {
		global $wpdb;

		if ( ( $wpdb->prefix === 'wp_' ) || ( $wpdb->prefix === 'wordpress_' ) || ( $wpdb->prefix === 'wp3_' ) ) {

			return array(
				'value' => $wpdb->prefix,
				'valid' => false,
			);

		} else {
			return array(
				'value'             => $wpdb->prefix,
				'valid'             => true,
			);
		}

	}

	/**
	 * Check Salt Keys
	 *
	 * @return array
	 */
	public function checkSaltKeys() {
		$bad_keys = array();

		$keys = array(
			'AUTH_KEY',
			'SECURE_AUTH_KEY',
			'LOGGED_IN_KEY',
			'NONCE_KEY',
			'AUTH_SALT',
			'SECURE_AUTH_SALT',
			'LOGGED_IN_SALT',
			'NONCE_SALT'
		);

		try {
			$constants = get_defined_constants();

			foreach ( $keys as $key ) {
				if ( ! in_array( $key, array_keys( $constants ) ) ) {
					$bad_keys[] = $key;
				} else {
					$constant = $constants[ $key ];
					if ( empty( $constant ) || trim( $constant ) == 'put your unique phrase here' || strlen( $constant ) < 50 ) {
						$bad_keys[] = $key;
					}
				}
			} // foreach

		} catch ( Exception $e ) {
		}

		return array(
			'value' => ( ! empty( $bad_keys ) ? implode( ', ', $bad_keys ) : esc_html__( 'Yes', 'hide-my-wp' ) ),
			'valid' => empty( $bad_keys ),
		);

	}

	/**
	 * Check if wp-config.php has the right chmod
	 *
	 * @return array|false
	 */
	public function checkSaltKeysAge() {
		$old  = 95;
		$diff = false;

		if ( $saltcheck_time = get_option( HMWP_SALT_CHANGED ) ) {
			if ( ( isset( $saltcheck_time['timestamp'] ) ) ) {
				$diff = ( time() - $saltcheck_time['timestamp'] );
			}
		} elseif ( $config_file = HMWP_Classes_Tools::getConfigFile() ) {
			$age = @filemtime( $config_file );

			if ( ! empty( $age ) ) {
				$diff = time() - $age;
			}
		}


		if ( $diff ) {
			return array(
				/* translators: 1: Number of days since last update. */
				'value'             => ( ( $diff > ( DAY_IN_SECONDS * $old ) ) ? sprintf( esc_html__( '%1$s days since last update', 'hide-my-wp' ), esc_html( $diff ) ) : esc_html__( 'Updated', 'hide-my-wp' ) ),
				'valid'             => ( $diff <= ( DAY_IN_SECONDS * $old ) ),
			);
		}

		return false;
	}

	/**
	 * Check Database Password
	 *
	 * @return array
	 */
	public function checkDbPassword() {
		$password = DB_PASSWORD;

		if ( empty( $password ) ) {
			return array(
				'value' => esc_html__( 'Empty', 'hide-my-wp' ),
				'valid' => false,
			);
		} elseif ( strlen( $password ) < 6 ) {
			return array(
				/* translators: 1: Number of characters. */
				'value' => sprintf( esc_html__( 'only %1$d chars', 'hide-my-wp' ), (int) strlen( $password ) ),
				'valid' => false,
			);
		} elseif ( sizeof( count_chars( $password, 1 ) ) < 5 ) {
			return array(
				'value' => esc_html__( 'too simple', 'hide-my-wp' ),
				'valid' => false,
			);
		} else {
			return array(
				'value' => esc_html__( 'Good', 'hide-my-wp' ),
				'valid' => true,
			);
		}
	}

	/**
	 * Check if display_errors is off
	 *
	 * @return array
	 */
	public function checkDisplayErrors() {
		$check = ini_get( 'display_errors' );

		return array(
			'value' => $check,
			'valid' => ! (bool) $check,
		);
	}

	/**
	 * Compare WP Blog Url with WP Site Url
	 *
	 * @return array
	 */
	public function checkBlogSiteURL() {
		$siteurl = home_url();
		$wpurl   = site_url();

		return array(
			'value' => ( ( $siteurl == $wpurl ) ? esc_html__( 'Yes', 'hide-my-wp' ) : esc_html__( 'No', 'hide-my-wp' ) ),
			'valid' => ( $siteurl <> $wpurl ),
		);

	}

	/**
	 * Check if wp-config.php has the right chmod
	 *
	 * @return array|bool
	 */
	public function checkConfigChmod() {

		//Initialize WordPress Filesystem
		$wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

		if ( $config_file = HMWP_Classes_Tools::getConfigFile() ) {
			if ( HMWP_Classes_Tools::isWindows() ) {

				return array(
					'value'    => ( ( $wp_filesystem->is_writable( $config_file ) ) ? esc_html__( 'Yes', 'hide-my-wp' ) : esc_html__( 'No', 'hide-my-wp' ) ),
					'valid'    => ( ! $wp_filesystem->is_writable( $config_file ) ),
					'solution' => sprintf( esc_html__( "Change the wp-config.php file permission to Read-Only using File Manager.", 'hide-my-wp' ), '<a href="https://wordpress.org/support/article/changing-file-permissions/" target="_blank">', '</a>', '<a href="https://wordpress.org/support/article/changing-file-permissions/" target="_blank">', '</a>' ),
				);
			} else {
				$chmod   = $wp_filesystem->getchmod( $config_file );
				$octmode = substr( sprintf( '%o', $chmod ), - 4 );

				return array(
					'value' => ( ( substr( $octmode, - 1 ) != 0 ) ? esc_html__( 'Yes', 'hide-my-wp' ) : esc_html__( 'No', 'hide-my-wp' ) ), //phpcs:ignore
					'valid' => ( substr( $octmode, - 1 ) == 0 ),
				);
			}
		}

		return array(
			'value' => esc_html__( 'No', 'hide-my-wp' ),
			'valid' => true,
		);
	}

	/**
	 * Check wp-config.php file
	 *
	 * @return array
	 */
	public function checkConfig() {
		$url      = home_url( 'wp-config.php?hmwp_preview=' . HMWP_Classes_Tools::getOption( 'hmwp_disable_name' ) );
		$response = wp_remote_head( $url, array( 'user-agent' => _HMWP_NAMESPACE_,'timeout' => 5, 'cookies' => false ) );

		$visible = false;
		if ( ! is_wp_error( $response ) ) {
			if ( wp_remote_retrieve_response_code( $response ) == 200 ) {
				$visible = true;
			}
		}

		$url      = home_url( 'wp-config-sample.php?hmwp_preview=' . HMWP_Classes_Tools::getOption( 'hmwp_disable_name' ) );
		$response = wp_remote_head( $url, array( 'user-agent' => _HMWP_NAMESPACE_, 'timeout' => 5, 'cookies' => false ) );

		if ( ! is_wp_error( $response ) ) {
			if ( wp_remote_retrieve_response_code( $response ) == 200 ) {
				$visible = true;
			}
		}

		//if the settings are already activated
		if ( HMWP_Classes_Tools::getOption( 'hmwp_hide_commonfiles' ) ) {
			return array( 'value' => esc_html__( 'No', 'hide-my-wp' ), 'valid' => true );
		}

		return array(
			'value' => ( $visible ? esc_html__( 'Yes', 'hide-my-wp' ) : esc_html__( 'No', 'hide-my-wp' ) ),
			'valid' => ( ! $visible ),
		);
	}

	/**
	 * Check readme.html file
	 *
	 * @return array
	 */
	public function checkReadme() {
		$url      = home_url( 'readme.html?hmwp_preview=' . HMWP_Classes_Tools::getOption( 'hmwp_disable_name' ) );
		$response = wp_remote_head( $url, array( 'user-agent' => _HMWP_NAMESPACE_, 'timeout' => 5, 'cookies' => false ) );

		$visible = false;
		if ( ! is_wp_error( $response ) ) {

			if ( wp_remote_retrieve_response_code( $response ) == 200 ) {
				$visible = true;
			}
		}

		//In case it's litespeed, the file is hidden
		if ( HMWP_Classes_Tools::isLitespeed() ) {
			return array( 'value' => esc_html__( 'No', 'hide-my-wp' ), 'valid' => true );

		}

		//if the settings are already activated
		if ( HMWP_Classes_Tools::getOption( 'hmwp_hide_commonfiles' ) ) {
			$files = HMWP_Classes_Tools::getOption( 'hmwp_hide_commonfiles_files' );
			if ( ! empty( $files ) && in_array( 'readme.html', $files ) ) {
				return array( 'value' => esc_html__( 'No', 'hide-my-wp' ), 'valid' => true );
			}
		}

		return array(
			'value' => ( $visible ? esc_html__( 'Yes', 'hide-my-wp' ) : esc_html__( 'No', 'hide-my-wp' ) ),
			'valid' => ( ! $visible ),
		);
	}


	/**
	 * Does WP install.php file exist?
	 *
	 * @return array
	 */
	public function checkInstall() {
		$url      = site_url() . '/wp-admin/install.php?hmwp_preview=' . HMWP_Classes_Tools::getOption( 'hmwp_disable_name' );
		$response = wp_remote_head( $url, array( 'user-agent' => _HMWP_NAMESPACE_, 'timeout' => 10, 'cookies' => false ) );
		$visible  = false;

		if ( ! is_wp_error( $response ) ) {
			if ( wp_remote_retrieve_response_code( $response ) == 200 ) {
				$visible = true;
			}
		}

		//if the settings are already activated
		if ( HMWP_Classes_Tools::getOption( 'hmwp_hide_commonfiles' ) ) {
			$files = HMWP_Classes_Tools::getOption( 'hmwp_hide_commonfiles_files' );
			if ( ! empty( $files ) && in_array( 'install.php', $files ) ) {
				return array( 'value' => esc_html__( 'No', 'hide-my-wp' ), 'valid' => true );
			}
		}

		return array(
			'value' => ( $visible ? esc_html__( 'Yes', 'hide-my-wp' ) : esc_html__( 'No', 'hide-my-wp' ) ),
			'valid' => ( ! $visible ),
		);
	}

	/**
	 * Check if the 7G or 8G firewall is activated
	 *
	 * @return array
	 */
	public function checkFirewall() {
		$check = HMWP_Classes_Tools::getOption( 'hmwp_sqlinjection' );

		if ( $check ){
			$check = in_array( (int) HMWP_Classes_Tools::getOption( 'hmwp_sqlinjection_level' ), array( 3, 4 ), true );
		}

		return array(
			'value' => ( $check ? esc_html__( 'Yes', 'hide-my-wp' ) : esc_html__( 'No', 'hide-my-wp' ) ),
			'valid' => ( $check ),
		);
	}

	/**
	 * Check if the 7G or 8G firewall is activated
	 *
	 * @return array
	 */
	public function checkFirewallAutomation() {
		$check = HMWP_Classes_Tools::getOption( 'hmwp_threats_auto' );

		return array(
			'value' => ( $check ? esc_html__( 'Yes', 'hide-my-wp' ) : esc_html__( 'No', 'hide-my-wp' ) ),
			'valid' => ( $check ),
		);
	}

	/**
	 * Check if register_globals is off
	 *
	 * @return array
	 */
	public function checkRegisterGlobals() {
		$check = (bool) ini_get( 'register' . '_globals' );

		return array(
			'value' => ( $check ? esc_html__( 'Yes', 'hide-my-wp' ) : esc_html__( 'No', 'hide-my-wp' ) ),
			'valid' => ( ! $check ),
		);

	}

	/**
	 * Check if safe mode is off
	 *
	 * @return array
	 */
	public function checkPHPSafe() {
		$check = (bool) ini_get( 'safe' . '_mode' );

		return array(
			'value' => ( $check ? esc_html__( 'Yes', 'hide-my-wp' ) : esc_html__( 'No', 'hide-my-wp' ) ),
			'valid' => ( ! $check ),
		);

	}

	/**
	 * Check if allow_url_include is off
	 *
	 * @return array
	 */
	public function checkAllowUrlInclude() {
		$check = (bool) ini_get( 'allow_url_include' );

		return array(
			'value' => ( $check ? esc_html__( 'Yes', 'hide-my-wp' ) : esc_html__( 'No', 'hide-my-wp' ) ),
			'valid' => ( ! $check ),
		);
	}

	/**
	 * Is theme/plugin editor disabled?
	 *
	 * @return array
	 */
	public function checkAdminEditor() {
		if ( defined( 'DISALLOW_FILE_EDIT' ) ) {
			return array(
				'value'           => ( DISALLOW_FILE_EDIT ? esc_html__( 'Yes', 'hide-my-wp' ) : esc_html__( 'No', 'hide-my-wp' ) ),
				'valid'           => DISALLOW_FILE_EDIT,
				'javascript_undo' => "jQuery(this).hmwp_fixConfig('DISALLOW_FILE_EDIT',false);",

			);
		} else {
			return array(
				'value' => esc_html__( 'Yes', 'hide-my-wp' ),
				'valid' => false,
			);
		}
	}


	/**
	 * Check if Upload Folder is browsable
	 *
	 * @return array|false
	 */
	public function checkUploadsBrowsable() {

		//if the settings are already activated
		if ( HMWP_Classes_Tools::getOption( 'hmwp_disable_browsing' ) ) {
			return array( 'value' => esc_html__( 'No', 'hide-my-wp' ), 'valid' => true );
		}

		$upload_dir = wp_upload_dir();

		if ( ! isset( $upload_dir['baseurl'] ) || $upload_dir['baseurl'] == '' ) {
			return false;
		}

		$args = array(
			'method'      => 'GET',
			'timeout'     => 5,
			'sslverify'   => false,
			'httpversion' => 1.0,
			'blocking'    => true,
			'headers'     => array(),
			'body'        => null,
			'cookies'     => array()
		);

		$response = HMWP_Classes_Tools::hmwp_localcall( rtrim( $upload_dir['baseurl'], '/' ) . '/?nocache=' . wp_rand(), $args );

		if ( is_wp_error( $response ) ) {
			$return = array(
				'value' => esc_html__( 'No', 'hide-my-wp' ),
				'valid' => true,
			);
		} elseif ( wp_remote_retrieve_response_code( $response ) == 200 && stripos( $response['body'], 'index' ) !== false ) {
			$return = array(
				'value' => esc_html__( 'Yes', 'hide-my-wp' ),
				'valid' => false,
			);
		} else {
			$return = array(
				'value' => esc_html__( 'No', 'hide-my-wp' ),
				'valid' => true,
			);
		}

		if ( ! HMWP_Classes_Tools::isApache() && ! HMWP_Classes_Tools::isNginx() && ! HMWP_Classes_Tools::isLitespeed() ) {
			$return['javascript'] = '';
		}

		return $return;
	}

	/**
	 * Check if Wondows Live Writer is not disabled
	 *
	 * @return array
	 */
	public function checkWLW() {
		$check = ( ! HMWP_Classes_Tools::getOption( 'hmwp_disable_manifest' ) );

		return array(
			'value' => ( $check ? esc_html__( 'Yes', 'hide-my-wp' ) : esc_html__( 'No', 'hide-my-wp' ) ),
			'valid' => ( ! $check ),
		);

	}

	/**
	 * Check if XML PRC
	 *
	 * @return array
	 */
	public function checkXmlrpc() {

		$visible = false;

		if ( ! HMWP_Classes_Tools::getOption( 'hmwp_disable_xmlrpc' ) ) {
			$url      = site_url() . '/xmlrpc.php?hmwp_preview=' . HMWP_Classes_Tools::getOption( 'hmwp_disable_name' );
			$response = wp_remote_head( $url, array( 'user-agent' => _HMWP_NAMESPACE_, 'timeout' => 5, 'cookies' => false ) );

			if ( ! is_wp_error( $response ) ) {

				if ( wp_remote_retrieve_response_code( $response ) == 200 || wp_remote_retrieve_response_code( $response ) == 405 ) {
					$visible = true;
				}

			}
		}

		return array(
			'value' => ( $visible ? esc_html__( 'Yes', 'hide-my-wp' ) : esc_html__( 'No', 'hide-my-wp' ) ),
			'valid' => ( ! $visible ),
		);

	}

	/**
	 * Check if XML PRC
	 *
	 * @return array
	 */
	public function checkRDS() {
		$check = ( ! HMWP_Classes_Tools::getOption( 'hmwp_hide_rsd' ) );

		return array(
			'value' => ( $check ? esc_html__( 'Yes', 'hide-my-wp' ) : esc_html__( 'No', 'hide-my-wp' ) ),
			'valid' => ( ! $check ),
		);

	}

	/**
	 * Check if the WP MySQL user has too many permissions granted
	 *
	 * @return array
	 */
	static function checkMysqlPermissions() {
		global $wpdb;

		$grants = $wpdb->get_results( 'SHOW GRANTS', ARRAY_N ); //phpcs:ignore
		foreach ( $grants as $grant ) {
			if ( stripos( $grant[0], 'GRANT ALL PRIVILEGES' ) !== false ) {
				return array(
					'value' => esc_html__( 'Yes', 'hide-my-wp' ),
					'valid' => false,
				);
			}
		}

		return array(
			'value' => esc_html__( 'No', 'hide-my-wp' ),
			'valid' => true,
		);
	}

	/**
	 * Check if a user can be found by its ID
	 *
	 * @return array
	 */
	static function checkUsersById() {
		$users   = get_users( 'number=1' );
		$success = false;
		$url     = home_url() . '/?hmwp_preview=' . HMWP_Classes_Tools::getOption( 'hmwp_disable_name' ) . '&author=';

		foreach ( $users as $user ) {
			$response      = wp_remote_head( $url . $user->ID, array(
				'user-agent' => _HMWP_NAMESPACE_,
				'redirection' => 0,
				'timeout'     => 5,
				'cookies'     => false
			) );
			$response_code = wp_remote_retrieve_response_code( $response );

			if ( $response_code == 301 ) {
				$success = true;
			}
			break;
		} // foreach

		//If the option is on, the author is hidden
		if ( HMWP_Classes_Tools::getOption( 'hmwp_hide_authors' ) ) {
			$success = false;
		}

		return array(
			'value' => ( $success ? esc_html__( 'Yes', 'hide-my-wp' ) : esc_html__( 'No', 'hide-my-wp' ) ),
			'valid' => ( ! $success ),
		);
	}

	/**
	 * Check if a user can be enumerated through REST API
	 *
	 * @return array
	 */
	static function checkUsersEnumeration() {
		$success = false;
		$url     = home_url() . '/' . HMWP_Classes_Tools::getOption( 'hmwp_wp-json' ) . '/wp/v2/users/1?hmwp_preview=' . HMWP_Classes_Tools::getOption( 'hmwp_disable_name' );

		$response      = wp_remote_head( $url, array( 'user-agent' => _HMWP_NAMESPACE_, 'timeout' => 5, 'cookies' => false ) );
		$response_code = wp_remote_retrieve_response_code( $response );

		if ( $response_code == 200 ) {
			$success = true;
		}

		//If the option is on, the user enumeration is hidden
		if ( HMWP_Classes_Tools::getOption( 'hmwp_hide_author_enumeration' ) ) {
			$success = false;
		}

		return array(
			'value' => ( $success ? esc_html__( 'Yes', 'hide-my-wp' ) : esc_html__( 'No', 'hide-my-wp' ) ),
			'valid' => ( ! $success ),
		);
	}

	/**
	 * Check if XML PRC
	 *
	 * @return array
	 */
	public function checkOldPaths() {
		$visible  = false;
		$url      = site_url() . '/wp-content/?rnd=' . wp_rand();
		$response = wp_remote_head( $url, array( 'user-agent' => _HMWP_NAMESPACE_, 'timeout' => 5, 'cookies' => false ) );

		if ( ! is_wp_error( $response ) ) {

			if ( wp_remote_retrieve_response_code( $response ) == 200 ) {
				$visible = true;
			}

		}

		if ( HMWP_Classes_Tools::getDefault( 'hmwp_wp-content_url' ) <> HMWP_Classes_Tools::getOption( 'hmwp_wp-content_url' ) && HMWP_Classes_Tools::getOption( 'hmwp_hide_oldpaths' ) ) {
			$visible = false;
		}

		return array(
			'value' => ( $visible ? esc_html__( 'Yes', 'hide-my-wp' ) : esc_html__( 'No', 'hide-my-wp' ) ),
			'valid' => ( ! $visible ),
		);

	}

	/**
	 * Check the Old paths in source code
	 *
	 * @return array|bool
	 */
	public function checkCommonPaths() {
		$visible = false;

		if ( ! isset( $this->html ) || $this->html == '' ) {
			if ( ! $this->getSourceCode() ) {
				return false;
			}
		}

		//if the wp-content path is changed in HMWP
		if ( HMWP_Classes_Tools::getDefault( 'hmwp_wp-content_url' ) <> HMWP_Classes_Tools::getOption( 'hmwp_wp-content_url' ) ) {
			//if the new path is visible in the source code, the paths are changed
			if ( strpos( $this->html, site_url( '/' . HMWP_Classes_Tools::getOption( 'hmwp_wp-content_url' ) . '/' ) ) === false ) {
				//check if wp-content is visible in the source code
				$visible = ( strpos( $this->html, content_url() ) !== false );
			}
		} else {
			//check if wp-content is visible in the source code
			$visible = ( strpos( $this->html, content_url() ) !== false );
		}

		return array(
			'value' => ( $visible ? esc_html__( 'Yes', 'hide-my-wp' ) : esc_html__( 'No', 'hide-my-wp' ) ),
			'valid' => ( ! $visible ),
		);

	}

	/**
	 * Check the Login path in source code
	 *
	 * @return array|bool
	 */
	public function checkLoginPath() {
		if ( ! isset( $this->html ) || $this->html == '' ) {
			if ( ! $this->getSourceCode() ) {
				return false;
			}
		}

		if ( ! $found = strpos( $this->html, site_url( 'wp-login.php' ) ) ) {
			if ( ! HMWP_Classes_Tools::getOption( 'hmwp_bruteforce' ) ) {
				//If the custom login path is visible in the source code and Brute force is not activated
				$found = strpos( $this->html, site_url( '/' . HMWP_Classes_Tools::getOption( 'hmwp_login_url' ) . '/' ) );
			}
		}

		return array(
			'value' => ( $found ? esc_html__( 'Yes', 'hide-my-wp' ) : esc_html__( 'No', 'hide-my-wp' ) ),
			'valid' => ( ! $found ),
		);

	}

	/**
	 * Check the Admin path in source code
	 *
	 * @return array|bool
	 */
	public function checkAdminPath() {
		if ( ! isset( $this->html ) || $this->html == '' ) {
			if ( ! $this->getSourceCode() ) {
				return false;
			}
		}

		$found = strpos( $this->html, site_url( '/' . HMWP_Classes_Tools::getOption( 'hmwp_admin_url' ) . '/' ) );

		if ( HMWP_Classes_Tools::getDefault( 'hmwp_admin-ajax_url' ) == HMWP_Classes_Tools::getOption( 'hmwp_admin-ajax_url' ) ) {
			return array(
				'value'      => ( $found ? esc_html__( 'Yes', 'hide-my-wp' ) : esc_html__( 'No', 'hide-my-wp' ) ),
				'valid'      => ( ! $found ),
			);
		}

		return array(
			'value'      => ( $found ? esc_html__( 'Yes', 'hide-my-wp' ) : esc_html__( 'No', 'hide-my-wp' ) ),
			'valid'      => ( ! $found ),
			'javascript' => "jQuery(this).hmwp_fixSettings('hmwp_hideajax_admin',1);",
		);

	}

	/**
	 * Check if wp-admin is accessible for visitors
	 *
	 * @return array
	 */
	public function checkOldLogin() {
		$url      = home_url() . '/wp-login.php?hmwp_preview=' . HMWP_Classes_Tools::getOption( 'hmwp_disable_name' );
		$response = HMWP_Classes_Tools::hmwp_localcall( $url, array( 'redirection' => 0, 'cookies' => false ) );

		$visible = false;
		if ( ! is_wp_error( $response ) ) {

			if ( wp_remote_retrieve_response_code( $response ) == 200 ) {
				$visible = true;
			}
		}

		if ( HMWP_Classes_Tools::getDefault( 'hmwp_login_url' ) <> HMWP_Classes_Tools::getOption( 'hmwp_login_url' ) && HMWP_Classes_Tools::getOption( 'hmwp_hide_login' ) ) {
			$visible = false;
		}

		return array(
			'value' => ( $visible ? esc_html__( 'Yes', 'hide-my-wp' ) : esc_html__( 'No', 'hide-my-wp' ) ),
			'valid' => ( ! $visible ),
		);
	}

	/**
	 * Check if anyone can register easily
	 *
	 * @return array
	 */
	public function checkUserRegistration() {
		$check = ( get_option( 'users_can_register' ) );
		if ( $check ) {
			$check = ( HMWP_Classes_Tools::getOption( 'hmwp_register_url' ) == '' || ! HMWP_Classes_Tools::getOption( 'hmwp_bruteforce' ) || ! HMWP_Classes_Tools::getOption( 'hmwp_bruteforce_register' ) );
		}

		return array(
			'value' => ( $check ? esc_html__( 'Yes', 'hide-my-wp' ) : esc_html__( 'No', 'hide-my-wp' ) ),
			'valid' => ( ! $check ),
		);
	}

	/**
	 * Check if the default website description is shown
	 *
	 * @return array
	 */
	public function checkBlogDescription() {
		$check = ( get_option( 'blogdescription' ) == esc_html__( 'Just another WordPress site' ) );  //phpcs:ignore

		return array(
			'value' => ( $check ? esc_html__( 'Yes', 'hide-my-wp' ) : esc_html__( 'No', 'hide-my-wp' ) ),
			'valid' => ( ! $check ),
		);
	}

	/**
	 * Check if file and directory permissions are correctly set
	 *
	 * @return array
	 * @throws Exception
	 */
	public function checkFilePermissions() {

		/** @var HMWP_Models_Permissions $permissionModel */
		$permissionModel = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Permissions' );

		$invalid = $permissionModel->getInvalidPermissions();
		$values  = array();
		foreach ( $invalid as $row ) {
			$values[] = $row['display_path'] . ' (' . $row['display_permission'] . ')';
		}

		return array(
			/* translators: List of file paths wrapped in <span> with line breaks. */
			'value' => ( ! empty( $values ) ? wp_kses_post( sprintf( __( "%s don't have the correct permission.", 'hide-my-wp' ), '<span style="font-weight: normal; color: #dc3545!important">' . wp_kses_post( join( '<br />', $values ) ) . '</span><br />' ) ) : esc_html__( 'All files have the correct permissions.', 'hide-my-wp' ) ),
			'valid' => ( empty( $values ) ),
		);
	}

	/**
	 * Get the homepage source code
	 *
	 * @return string
	 */
	public function getSourceCode() {
		if ( ! isset( $this->html ) && ! isset( $this->htmlerror ) ) {
			// No preview token here on purpose. The three checks that read this
			// source ask whether the rendered page still leaks the default paths,
			// which is a question only the visitor's view can answer. The token
			// makes calledSafeUrl() true, and initHooks() then returns early with
			// hmwp_process_init disabled, so the page comes back deliberately
			// un-hidden and every one of those checks reported a leak on sites
			// that were hiding their paths perfectly well.
			$url      = home_url( '/' );
			$response = HMWP_Classes_Tools::hmwp_localcall( $url, array(
				'redirection' => 0,
				'timeout'     => 10,
				'cookies'     => false
			) );

			if ( ! is_wp_error( $response ) ) {

				if ( wp_remote_retrieve_response_code( $response ) == 200 ) {
					$this->html    = wp_remote_retrieve_body( $response );
					$this->headers = wp_remote_retrieve_headers( $response );
				} else {
					$this->htmlerror = true;
					$this->html      = false;
					$this->headers   = false;
				}
			} else {
				$this->htmlerror = true;
				$this->html      = false;
				$this->headers   = false;
			}
		}

		return $this->html;
	}
}
